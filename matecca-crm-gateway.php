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
