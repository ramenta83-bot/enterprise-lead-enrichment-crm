<?php
/**
 * Plugin Name: Matecca CRM Enterprise Gateway Bridge
 * Description: Hardened CRM-style server-side listener and script encapsulator utilizing an isolated static external stylesheet.
 * Version: 2.2.0
 * Author: Matecca Industries
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct malicious access attempts
}

// 1. Enqueue and Encapsulate Frontend Assets via WordPress Core
add_action('wp_enqueue_scripts', 'matecca_enqueue_secure_crm_assets');
function matecca_enqueue_secure_crm_assets() {
    
    // FETCH EXTERNAL STYLESHEET: Dynamically routes style.css from the plugin directory path
    wp_enqueue_style(
        'matecca-crm-external-styles', 
        plugins_url('style.css', __FILE__), 
        array(), 
        '2.2.0'
    );

    // Register a clean handle for our custom script process execution loop
    wp_register_script('matecca-crm-listener', false, array(), '2.2.0', true);
    wp_enqueue_script('matecca-crm-listener');

    wp_localize_script('matecca-crm-listener', 'matecca_crm_config', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

    // Hardened, Self-Executing Decoupled Script Namespace (IIFE Architecture Pattern)
    $secure_javascript_closure = "
    (function (window, document) {
        'use strict';

        function initCRMFormListener() {
            const captureForm = document.querySelector('#native-lead-form');
            const responseAlert = document.querySelector('#form-response-message');
            const btn = captureForm ? captureForm.querySelector('.crm-submit-btn') : null;

            if (!captureForm || !btn) return;

            // Gradient Hover Tracking Vector
            btn.addEventListener('mousemove', (e) => {
                const rect = btn.getBoundingClientRect();
                btn.style.setProperty('--mouse-x', (e.clientX - rect.left) + 'px');
                btn.style.setProperty('--mouse-y', (e.clientY - rect.top) + 'px');
            });

            // Spatial Click Ripple Wave
            btn.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                ripple.classList.add('crm-btn-ripple');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = (e.clientX - rect.left - (size / 2)) + 'px';
                ripple.style.top = (e.clientY - rect.top - (size / 2)) + 'px';
                
                const oldRipple = this.querySelector('.crm-btn-ripple');
                if (oldRipple) oldRipple.remove();
                this.appendChild(ripple);
            });

             // Form Submit Interceptor Loop
            captureForm.addEventListener('submit', function (event) {
                event.preventDefault();
                btn.disabled = true;
                btn.classList.add('processing');
                btn.innerText = 'Routing Data...';

                const payloadEnvelope = new FormData();
                const isUploadMode = document.getElementById('upload-doc')?.checked || document.querySelector('input[type=\"radio\"]:checked')?.value === 'upload';

                if (isUploadMode) {
                    // --- ROUTE A: BULK CSV UPLOAD ---
                    const fileInput = document.querySelector('#crm-csv-uploader');
                    
                    if (!fileInput || fileInput.files.length === 0) {
                        btn.disabled = false;
                        btn.classList.remove('processing');
                        btn.innerText = 'Submit to Cloud Engine';
                        if (responseAlert) {
                            responseAlert.style.color = '#dc2626';
                            responseAlert.innerText = 'Error: Please select a valid CSV file before uploading.';
                        }
                        return;
                    }

                    payloadEnvelope.append('action', 'matecca_forward_bulk_csv');
                    payloadEnvelope.append('leads_file', fileInput.files.item(0));
                    
                } else {
                    // --- ROUTE B: SINGLE MANUAL ENTRY ---
                    const nameVal = document.querySelector('#lead-name')?.value.trim();
                    const emailVal = document.querySelector('#lead-email')?.value.trim();

                    if (!nameVal || !emailVal) {
                        btn.disabled = false;
                        btn.classList.remove('processing');
                        btn.innerText = 'Submit to Cloud Engine';
                        if (responseAlert) {
                            responseAlert.style.color = '#dc2626';
                            responseAlert.innerText = 'Required parameters missing validation';
                        }
                        return;
                    }

                    payloadEnvelope.append('action', 'matecca_forward_lead');
                    payloadEnvelope.append('lead_name', nameVal);
                    payloadEnvelope.append('lead_email', emailVal);
                }

                const endpointGateway = typeof matecca_crm_config !== 'undefined' ? matecca_crm_config.ajax_url : '/wp-admin/admin-ajax.php';
                
                if (responseAlert) {
                    responseAlert.style.color = '#d97706';
                    responseAlert.innerText = 'Synchronizing with secure CRM gateway...';
                }

                fetch(endpointGateway, {
                    method: 'POST',
                    body: payloadEnvelope
                })
                .then(response => response.json().then(json => {
                    if (!response.ok) {
                        throw new Error(json.data && json.data.message ? json.data.message : 'Local gateway infrastructure fault');
                    }
                    return json;
                }))
                .then(result => {
                    if (result.success) {
                        if (responseAlert) {
                            responseAlert.style.color = '#16a34a';
                            responseAlert.innerText = '? ' + (result.data.message || 'Operation successful');
                        }
                        captureForm.reset();
                    } else {
                        throw new Error(result.data.message || 'Pipeline parsing collision');
                    }
                })
                .catch(error => {
                    if (responseAlert) {
                        responseAlert.style.color = '#dc2626';
                        responseAlert.innerText = error.message;
                    }
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.classList.remove('processing');
                    btn.innerText = 'Submit to Cloud Engine';
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCRMFormListener);
        } else {
            initCRMFormListener();
        }
    })(window, document);
    ";


    wp_add_inline_script('matecca-crm-listener', $secure_javascript_closure);
}

// 2. Private Server-to-Server Gateway Routing Hook
add_action('wp_ajax_matecca_forward_lead', 'matecca_send_lead_to_azure');
add_action('wp_ajax_nopriv_matecca_forward_lead', 'matecca_send_lead_to_azure');

function matecca_send_lead_to_azure() {
    $name  = isset($_POST['lead_name']) ? sanitize_text_field($_POST['lead_name']) : '';
    $email = isset($_POST['lead_email']) ? sanitize_email($_POST['lead_email']) : '';

    if (empty($name) || empty($email)) {
        wp_send_json_error(array('message' => 'Required parameters missing validation'), 400);
    }

    $secure_azure_url = 'https://172.208.96.146/api/v1/leads';
    $is_local_env = (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || defined('WP_DEBUG') && WP_DEBUG);
    
    $json_payload = json_encode(array(
        'name'  => $name,
        'email' => $email
    ));

    $response = wp_remote_post($secure_azure_url, array(
        'timeout'   => 15,
        'sslverify' => !$is_local_env, 
        'headers'   => array(
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'X-Matecca-API-Key' => defined('MATECCA_CRM_API_KEY') ? MATECCA_CRM_API_KEY : '' 
        ),
        'body'      => $json_payload
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Azure VM Unreachable: ' . $response->get_error_message()), 500);
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body_content  = wp_remote_retrieve_body($response);
    $data_payload  = json_decode($body_content, true);

    if ($response_code !== 200) {
        $error_msg = isset($data_payload['message']) ? $data_payload['message'] : 'Backend pipeline fault';
        wp_send_json_error(array('message' => "Cloud Error [HTTP $response_code]: " . $error_msg), $response_code);
    }

    wp_send_json_success($data_payload);
}
=========
<?php
/**
 * Plugin Name: Matecca CRM Enterprise Gateway Bridge
 * Description: Hardened CRM-style server-side listener and script encapsulator for Azure REST API routing.
 * Version: 1.0.0
 * Author: Matecca Industries
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct malicious access attempts
}

// 1. Enqueue and Encapsulate Frontend Scripts via WordPress Core
add_action('wp_enqueue_scripts', 'matecca_enqueue_secure_crm_listener');
function matecca_enqueue_secure_crm_listener() {
    // Register a clean handle for our custom script process execution loop
    wp_register_script('matecca-crm-listener', false, array(), '1.0.0', true);
    wp_enqueue_script('matecca-crm-listener');

    // Secure Data Bridge: Pass local AJAX endpoint properties without exposing raw variables
    wp_localize_script('matecca-crm-listener', 'matecca_crm_config', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

    // Hardened, Self-Executing Decoupled Script Namespace (IIFE Architecture Pattern)
    $secure_javascript_closure = "
    (function (window, document) {
        'use strict';

        function initCRMFormListener() {
            const captureForm = document.querySelector('#native-lead-form');
            const responseAlert = document.querySelector('#form-response-message');

            if (!captureForm) return;

            captureForm.addEventListener('submit', function (event) {
                // Intercept default browser action to prevent full-page reloads
                event.preventDefault(); 

                if (responseAlert) {
                    responseAlert.style.color = '#d97706'; // Amber processing status
                    responseAlert.innerText = 'Synchronizing with secure CRM gateway...';
                }

                // Bundle data parameters safely into a standard transmission envelope
                const payloadEnvelope = new FormData();
                payloadEnvelope.append('action', 'matecca_forward_lead'); // Hooks to our secure PHP action
                payloadEnvelope.append('lead_name', document.querySelector('#lead-name').value);
                payloadEnvelope.append('lead_email', document.querySelector('#lead-email').value);

                // Dynamically fetch localized routing configurations passed safely from the server layer
                const endpointGateway = typeof matecca_crm_config !== 'undefined' ? matecca_crm_config.ajax_url : '/wp-admin/admin-ajax.php';

                // Query the local server gateway instead of exposing Azure parameters to the public DOM
                fetch(endpointGateway, {
                    method: 'POST',
                    body: payloadEnvelope
                })
                .then(response => {
                    if (!response.ok) throw new Error('Local gateway infrastructure fault');
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        console.log('CRM Synchronization Success:', result.data);
                        if (responseAlert) {
                            responseAlert.style.color = '#16a34a'; // Production green success code
                            responseAlert.innerText = 'Success! Lead processed with score: ' + (result.data.score || 'Enriched');
                        }
                        captureForm.reset(); // Safely clear form properties for next submission
                    } else {
                        throw new Error(result.data.message || 'Pipeline data parsing conflict');
                    }
                })
                .catch(error => {
                    console.error('CRM Pipeline Execution Fault:', error);
                    if (responseAlert) {
                        responseAlert.style.color = '#dc2626'; // Error red
                        responseAlert.innerText = 'Error syncing with system gateway. Check browser console.';
                    }
                });
            });
        }

        // Validate execution safety thresholds based on active DOM ready states
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCRMFormListener);
        } else {
            initCRMFormListener();
        }
    })(window, document);
    ";

    wp_add_inline_script('matecca-crm-listener', $secure_javascript_closure);
}

// 2. Private Server-to-Server Gateway Routing Hook
add_action('wp_ajax_matecca_forward_lead', 'matecca_send_lead_to_azure');
add_action('wp_ajax_nopriv_matecca_forward_lead', 'matecca_send_lead_to_azure');

function matecca_send_lead_to_azure() {
    // Sanitize parameters strictly on the internal PHP layer before transmitting over the web
    $name  = isset($_POST['lead_name']) ? sanitize_text_field($_POST['lead_name']) : '';
    $email = isset($_POST['lead_email']) ? sanitize_email($_POST['lead_email']) : '';

    if (empty($name) || empty($email)) {
        wp_send_json_error(array('message' => 'Required parameters missing validation'), 400);
    }

  // Secure Target: Absolute production Nginx HTTPS proxy route mapping path
    $secure_azure_url = 'https://172.208.96.146/api/v1/leads';
    
    $json_payload = json_encode(array(
        'name'  => $name,
        'email' => $email
    ));

    // Execute isolated Server-to-Server transaction over HTTPS with custom auth headers
    $response = wp_remote_post($secure_azure_url, array(
        'timeout'   => 15,
        'sslverify' => false, // Set to true once Let's Encrypt SSL domain is locked in
        'headers'   => array(
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'X-Matecca-API-Key' => 'MateccaSecretLiveToken2026_Secure' // Hidden bearer token core
        ),
        'body'      => $json_payload
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API Cloud Target Connection Fault'), 500);
    }

    $body_content = wp_remote_retrieve_body($response);
    $data_payload = json_decode($body_content, true);

    wp_send_json_success($data_payload);
}
>>>>>>>>> Temporary merge branch 2
=========
<?php
/**
 * Plugin Name: Matecca CRM Enterprise Gateway Bridge
 * Description: Hardened CRM-style server-side listener and script encapsulator for Azure REST API routing.
 * Version: 1.0.0
 * Author: Matecca Industries
 */

if (!defined('ABSPATH')) {
    exit; // Prevent direct malicious access attempts
}

// 1. Enqueue and Encapsulate Frontend Scripts via WordPress Core
add_action('wp_enqueue_scripts', 'matecca_enqueue_secure_crm_listener');
function matecca_enqueue_secure_crm_listener() {
    // Register a clean handle for our custom script process execution loop
    wp_register_script('matecca-crm-listener', false, array(), '1.0.0', true);
    wp_enqueue_script('matecca-crm-listener');

    // Secure Data Bridge: Pass local AJAX endpoint properties without exposing raw variables
    wp_localize_script('matecca-crm-listener', 'matecca_crm_config', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));

    // Hardened, Self-Executing Decoupled Script Namespace (IIFE Architecture Pattern)
    $secure_javascript_closure = "
    (function (window, document) {
        'use strict';

        function initCRMFormListener() {
            const captureForm = document.querySelector('#native-lead-form');
            const responseAlert = document.querySelector('#form-response-message');

            if (!captureForm) return;

            captureForm.addEventListener('submit', function (event) {
                // Intercept default browser action to prevent full-page reloads
                event.preventDefault(); 

                if (responseAlert) {
                    responseAlert.style.color = '#d97706'; // Amber processing status
                    responseAlert.innerText = 'Synchronizing with secure CRM gateway...';
                }

                // Bundle data parameters safely into a standard transmission envelope
                const payloadEnvelope = new FormData();
                payloadEnvelope.append('action', 'matecca_forward_lead'); // Hooks to our secure PHP action
                payloadEnvelope.append('lead_name', document.querySelector('#lead-name').value);
                payloadEnvelope.append('lead_email', document.querySelector('#lead-email').value);

                // Dynamically fetch localized routing configurations passed safely from the server layer
                const endpointGateway = typeof matecca_crm_config !== 'undefined' ? matecca_crm_config.ajax_url : '/wp-admin/admin-ajax.php';

                // Query the local server gateway instead of exposing Azure parameters to the public DOM
                fetch(endpointGateway, {
                    method: 'POST',
                    body: payloadEnvelope
                })
                .then(response => {
                    if (!response.ok) throw new Error('Local gateway infrastructure fault');
                    return response.json();
                })
                .then(result => {
                    if (result.success) {
                        console.log('CRM Synchronization Success:', result.data);
                        if (responseAlert) {
                            responseAlert.style.color = '#16a34a'; // Production green success code
                            responseAlert.innerText = 'Success! Lead processed with score: ' + (result.data.score || 'Enriched');
                        }
                        captureForm.reset(); // Safely clear form properties for next submission
                    } else {
                        throw new Error(result.data.message || 'Pipeline data parsing conflict');
                    }
                })
                .catch(error => {
                    console.error('CRM Pipeline Execution Fault:', error);
                    if (responseAlert) {
                        responseAlert.style.color = '#dc2626'; // Error red
                        responseAlert.innerText = 'Error syncing with system gateway. Check browser console.';
                    }
                });
            });
        }

        // Validate execution safety thresholds based on active DOM ready states
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initCRMFormListener);
        } else {
            initCRMFormListener();
        }
    })(window, document);
    ";

    wp_add_inline_script('matecca-crm-listener', $secure_javascript_closure);
}

// 2. Private Server-to-Server Gateway Routing Hook
add_action('wp_ajax_matecca_forward_lead', 'matecca_send_lead_to_azure');
add_action('wp_ajax_nopriv_matecca_forward_lead', 'matecca_send_lead_to_azure');

function matecca_send_lead_to_azure() {
    // Sanitize parameters strictly on the internal PHP layer before transmitting over the web
    $name  = isset($_POST['lead_name']) ? sanitize_text_field($_POST['lead_name']) : '';
    $email = isset($_POST['lead_email']) ? sanitize_email($_POST['lead_email']) : '';

    if (empty($name) || empty($email)) {
        wp_send_json_error(array('message' => 'Required parameters missing validation'), 400);
    }

  // Secure Target: Absolute production Nginx HTTPS proxy route mapping path
    $secure_azure_url = 'https://172.208.96.146/api/v1/leads';
    
    $json_payload = json_encode(array(
        'name'  => $name,
        'email' => $email
    ));

    // Execute isolated Server-to-Server transaction over HTTPS with custom auth headers
    $response = wp_remote_post($secure_azure_url, array(
        'timeout'   => 15,
        'sslverify' => false, // Set to true once Let's Encrypt SSL domain is locked in
        'headers'   => array(
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'X-Matecca-API-Key' => 'MateccaSecretLiveToken2026_Secure' // Hidden bearer token core
        ),
        'body'      => $json_payload
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API Cloud Target Connection Fault'), 500);
    }

    $body_content = wp_remote_retrieve_body($response);
    $data_payload = json_decode($body_content, true);

    wp_send_json_success($data_payload);
}
>>>>>>>>> Temporary merge branch 2
// 1. New Bulk CSV Server-Side Proxy Routing Action Links
add_action('wp_ajax_matecca_forward_bulk_csv', 'matecca_send_bulk_csv_to_azure');
add_action('wp_ajax_nopriv_matecca_forward_bulk_csv', 'matecca_send_bulk_csv_to_azure');

// 2. The PHP Forwarding Function
function matecca_send_bulk_csv_to_azure() {
    if (!isset($_FILES['leads_file']) || empty($_FILES['leads_file']['name'])) {
        wp_send_json_error(array('message' => 'Required CSV file payload missing validation'), 400);
    }

    $uploaded_file = $_FILES['leads_file'];

    // Enforce MIME validation criteria to block shell injection vectors
    $file_mime = mime_content_type($uploaded_file['tmp_name']);
    $allowed_mimes = array('text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/octet-stream');
    if (!in_array($file_mime, $allowed_mimes)) {
        wp_send_json_error(array('message' => 'Security Threat Blocked: Invalid file format schema.'), 400);
    }

    $secure_azure_url = 'https://172.208.96.146/api/v1/leads/bulk-upload';
    $is_local_env = (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1')) || defined('WP_DEBUG') && WP_DEBUG);

    // --- ?? MANUALLY ASSEMBLE MULTIPART BOUNDARY PAYLOAD ---
    $boundary = wp_generate_password(24);
    $payload = '';

    // Open file block envelope boundary
    $payload .= '--' . $boundary . "\r\n";
    $payload .= 'Content-Disposition: form-data; name="leads_file"; filename="' . basename($uploaded_file['name']) . '"' . "\r\n";
    $payload .= 'Content-Type: text/csv' . "\r\n\r\n";
    $payload .= file_get_contents($uploaded_file['tmp_name']);
    $payload .= "\r\n";
    
    // Close multi-part boundary string container bounds
    $payload .= '--' . $boundary . '--' . "\r\n";

    $response = wp_remote_post($secure_azure_url, array(
        'timeout'   => 45,
        'sslverify' => !$is_local_env, 
        'headers'   => array(
            'Content-Type'      => 'multipart/form-data; boundary=' . $boundary,
            'X-Matecca-API-Key' => defined('MATECCA_CRM_API_KEY') ? MATECCA_CRM_API_KEY : ''
        ),
        'body'      => $payload // Passes the raw manually constructed string stream envelope
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Azure VM Unreachable: ' . $response->get_error_message()), 500);
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $body_content  = wp_remote_retrieve_body($response);
    $data_payload  = json_decode($body_content, true);

    if ($response_code !== 200) {
        $error_msg = isset($data_payload['message']) ? $data_payload['message'] : 'Backend bulk pipeline fault';
        wp_send_json_error(array('message' => "Cloud Error [HTTP $response_code]: " . $error_msg), $response_code);
    }

    wp_send_json_success($data_payload);
}

