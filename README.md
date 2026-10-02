# Enterprise Lead Enrichment & Decoupled CRM Engine

A production-grade, secure microservice architecture that decouples a frontend lead registration capture system from an internal CRM data management layer. 
Built to emulate the security boundaries, data pipeline abstractions, and authorization structures found in industry-leading platforms like Salesforce and Technolutions Slate.

## 🚀 Key Engineering Showcases
* **Decoupled Gateway Abstraction:** The public web browser never interacts with internal cloud IPs or ports. Instead, it fires to an isolated, self-executing client-side namespace closure (IIFE Architecture Pattern) that proxies payloads safely behind the scenes via a private WordPress Server Hook Plugin.
* **Hardened Transport Layer Security:** Network routing profiles pass entirely via a custom Nginx reverse proxy running SSL/TLS encryption over Port 443. Public internet traffic directed straight at backend database layers is strictly rejected at the firewall boundary by Azure Network Security Groups.
* **Bearer Token Authorization:** Outbound server-to-server transmissions authenticate via unique cryptographic headers (`X-Matecca-API-Key`). Malicious unauthenticated probes are dropped immediately at the network perimeter with an HTTP 401 response code.
* **Secure Request Ingestion:** Swapped legacy URL query string variables for raw JSON request bodies, preventing sensitive customer PII from getting logged in plain text across public web routing files.
* **Resilient Infrastructure Node Monitoring:** The backend automation microservice runs as an isolated, persistent system background runner daemon (`systemd`) capable of automated execution resets if cloud modules crash.

## 🛠️ Tech Stack Mappings
* **Frontend Canvas:** WordPress Core, Vanilla JavaScript (IIFE Closure Architecture), Custom PHP Hooks Layer (Standalone Plugin)
* **Transport & Security Boundary Proxy:** Linux Nginx Reverse Proxy Engine, OpenSSL
* **Compute Backend Processing:** Python 3, Flask REST API Framework
* **Data Relational Storage Tier:** Firewalled MySQL Database Service (Bound to internal 127.0.0.1 loopbacks)
* **Presentation Dashboard:** Apache2 HTTP Server (Isolated to Port 8080), PHP Data Objects (PDO)

## 📋 Architectural Diagram
