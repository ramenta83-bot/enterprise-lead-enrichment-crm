# Enterprise Lead Enrichment & Decoupled CRM Engine

A production-grade, secure microservice architecture decoupling a frontend registration capture system from an internal CRM data management layer, emulating enterprise platforms like Salesforce and Slate.

## 🚀 Key Engineering Showcases

* **Decoupled Gateway Abstraction:** Public browsers interact via an IIFE architecture proxying payloads through a private WordPress plugin bridge.
* **Hardened Transport Layer Security:** SSL/TLS encryption over Port 443 via a custom Nginx reverse proxy; raw socket probes are blocked by Azure NSGs.
* **Bearer Token Authorization:** Outbound requests authenticate via unique cryptographic headers (`X-Matecca-API-Key`), rejecting unauthenticated probes with an HTTP 401.
* **Secure Request Ingestion:** Uses raw JSON request bodies instead of legacy URL query string variables to prevent plain-text logging.
* **Resilient Private Relational Storage:** Internal MySQL engine bound strictly to the private loopback (`127.0.0.1`).

## 🛠 Tech Stack Mappings

* **Frontend Canvas:** WordPress Core, Vanilla JavaScript (IIFE), Custom PHP Hooks Layer
* **Transport & Security Boundary Proxy:** Linux Nginx Reverse Proxy, OpenSSL (Port 443)
* **Compute Backend Processing:** Python 3, Flask REST API (Port 8000)
* **Data Relational Storage Tier:** Firewalled MySQL Database Service (127.0.0.1)
* **Presentation Dashboard:** Apache2 HTTP Server (Port 80), PHP Data Objects (PDO)

## 📋 Architectural Diagram

```text
[ Local WP Frontend ] ──> [ Plugin Layer ] ──(HTTPS / Bearer Token)──> [ Nginx (Port 443) ] ──> [ Flask (Port 8000) ] ──> [ MySQL / Apache Dashboard (Port 80) ]
```
