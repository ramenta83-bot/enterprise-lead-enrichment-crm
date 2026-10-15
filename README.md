# Enterprise Lead Enrichment & Decoupled CRM Engine

A production-grade, secure microservice architecture decoupling a frontend registration capture system from an internal CRM data management layer, emulating enterprise platforms like Salesforce and Slate.

## 🚀 Key Engineering Showcases

*   **Decoupled Gateway Abstraction:** Public browsers interact via a secure JavaScript IIFE abstraction architecture, proxying payloads through a private WordPress plugin bridge to isolate backend endpoints.
*   **Dual-Mode Payload Ingestion:** Handles single lead parameter submissions via raw JSON envelopes alongside an optimized, manually constructed multipart boundary streaming layout for bulk CSV document uploads.
*   **Hardened Transport Layer Security:** SSL/TLS encryption over Port 443 via a custom Nginx reverse proxy profile configuration (`underscores_in_headers on;`); raw public socket probes are blocked by Azure NSGs.
*   **Bearer Token Authorization:** Outbound requests are signed dynamically using an abstracted constant token (`MATECCA_CRM_API_KEY`) and verified by a Python-Flask backend matching secrets loaded safely in system memory (`.env`).
*   **Automated Continuous Sync Loops:** Unified, real-time repository synchronization layout utilizing a local Windows PowerShell watcher alongside an Azure server-side `inotifywait` daemon loop to commit filesystem variations instantly.
*   **Resilient Private Relational Storage:** Internal MySQL relational engine bound strictly to the private loopback (`127.0.0.1`), utilizing optimized transaction batches (`cursor.executemany()`) to maximize insertion efficiency.

## 🛠 Tech Stack Mappings

*   **Frontend Canvas:** WordPress Core, Vanilla JavaScript (IIFE Layout), Standalone CSS3 `:has()` Parent State Selectors
*   **Transport & Security Boundary Proxy:** Linux Nginx Reverse Proxy, OpenSSL (Port 443), Let's Encrypt Transport Layer
*   **Compute Backend Processing:** Python 3, Flask REST API (Port 8000), Linux Kernel `inotify-tools` System Watcher
*   **Data Relational Storage Tier:** Firewalled MySQL Database Service (127.0.0.1:3306), Parameterized SQL Transaction Blocks
*   **Presentation Dashboard:** Apache2 HTTP Server (Port 80), PHP Data Objects (PDO Architecture Core)

## 📋 Architectural Diagram

```text
[ Local WP Frontend ] ──> [ Plugin Layer ] ──(Manually Bound Multipart Envelope)
                                    │
                                    ▼  (HTTPS / Bearer Token Header)
                            [ Nginx (Port 443) ]
                                    │
                                    ▼  (Private Loopback Socket: 8000)
                            [ Flask (Port 8000) ]
                                    │
                                    ▼  (executemany Batch Ingestion)
                         [ Firewalled MySQL Database ]
```

## 📦 Continuous Integration & Synchronization Workflows

The distributed architecture implements a bidirectional real-time file monitoring layout to ensure code consistency across environments:
1. **Local Windows Machine Workspace:** A background PowerShell file tracker (`git_auto_sync.ps1`) hooks into the local directory, automatically staging, committing, and pushing code variants to GitHub the millisecond a save event executes.
2. **Azure Cloud Compute Workspace:** A native shell file daemon (`vm_auto_sync.sh`) leverages the Linux kernel engine framework to track script writes (`close_write`) on `main.py`, executing silent token-authenticated pushes immediately.
3. **Automated Server Pull Mechanism:** A scheduled cron job utility query tasks every 5 minutes to sweep GitHub, pull down frontend plugin modifications automatically, and reload background daemons (`systemctl restart flask-crm.service`).
