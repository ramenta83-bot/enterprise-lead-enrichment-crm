Matecca Industries — Lead Enrichment & Custom CRM Engine
Operational Runbook, API Data Schema & Database Dictionary
This document serves as the technical single-source-of-truth for the data contracts, validation rules, payload shapes, and transactional schemas governing the Matecca Lead Enrichment pipeline.

**1. REST API Request/Response Data Contract**

The Flask REST API strictly enforces an application/json payload constraint. URL parameters are systematically rejected to protect data integrity and prevent leakage in server access logs.
Inbound Ingestion Payload Shape (POST /api/v1/leads)
JSON Property Key	Variable Primitive	Input Classification	Validation Matrix / Constraints	Operational Purpose
name	String	Explicit Data Field	Maximum 100 characters; alphanumeric text strings. Cannot be null.	Captures the full name of the registering prospect.
email	String	Explicit Data Field	Maximum 100 characters; must comply with RFC 5322 regex standards.	Used for system unique indexing, parsing, and domain categorization.
Sample Inbound Request Body JSON Payload:
json
{
  "name": "Alex Mercer",
  "email": "alex@microsoft.com"
}
Use code with caution.
Outbound Operational Status Responses
A. Ingestion Success Structure (HTTP 200 OK)
Returned when a lead passes authentication, validation, domain-parsing automation, and is successfully committed to the relational schema.
json
{
  "status": "success",
  "score": 85
}
Use code with caution.
B. Authorization Boundary Rejection (HTTP 401 Unauthorized)
Returned instantly if the client request headers fail to include or match the system security key.
json
{
  "status": "error",
  "message": "Unauthorized API Access Blocked"
}
Use code with caution.
C. Malformed Payload Validation Rejection (HTTP 400 Bad Request)
Returned if incoming JSON parameters are corrupt, misaligned, or missing a mandatory key.
json
{
  "status": "error",
  "message": "Required validation values missing"
}
Use code with caution.

**2. Production Database Schema & Field Matrix**

• Database Catalog Context: saas_crm
• Target Management Engine: MySQL Server (Restricted to loopback socket 127.0.0.1)
• Primary Relational Table Node: crm_leads
Relational Table Field Dictionary
Column Name	SQL Type	Indexing Attribute	Default Metric	Functional Engineering Purpose
id	INT	PRIMARY KEY	AUTO_INCREMENT	System unique identifier generated for every individual record insertion.
name	VARCHAR(100)	Standard Property	None	Stores the sanitized registration text input payload property.
email	VARCHAR(100)	Standard Property	None	Stores the sanitized registration email identifier property.
company	VARCHAR(100)	Derived Property	None	The computed corporate entity name extracted from the email domain string via the Python string splitter algorithm.
industry	VARCHAR(100)	Derived Property	None	Categorical industry value mapped automatically based on enterprise classification routing boundaries.
lead_score	INT	Derived Property	None	Numeric grading value calculated by the backend evaluation module rules.
created_at	TIMESTAMP	Temporal Metric	CURRENT_TIMESTAMP	System stamp registering the exact transaction runtime date and time.

**3. Automation Processing Engine Workflow Logic**

When an authenticated payload crosses the gateway perimeter, the Python microservice pipeline executes these exact transformation rules:
[ Inbound Email Variable Passed ]
               │
        (String Split on '@')
               │
               ▼
   [ Domain Signature Isolated ]
               │
       ┌───────┴────────────────────────────────────────┐
       ▼ (Matches Personal Email Domains)                ▼ (Matches Non-Consumer Domains)
["gmail.com", "yahoo.com", "hotmail.com"]       [ e.g., "microsoft.com", "apple.com" ]
       │                                                │
       ├─────────────────────────────────┐              ├────────────────────────────────
       ▼                                 ▼              ▼                                ▼
[ industry = "Unknown (Personal Email)" ] [ score = 10 ] [ industry = "B2B Tech Sector" ] [ score = 85 ]

**1. Brand Extraction Rule**
The pipeline splits the email variable string at the @ locator token to isolate the domain. It then clips the top domain suffix loop loopback block and invokes a .capitalize() constructor algorithm method to generate clean presentation records:
• Example Input Input Property: test@acmelabs.com ➔ Extracted Field Map Value: Acmelabs

**2. Algorithmic Lead Value Assignment Matrix**  
• Scenario Alpha (Low-Value Category): If the parsed domain matches consumer addresses (gmail.com, yahoo.com, or hotmail.com), it signals a non-enterprise client profile. The data processor overrides corporate lookups, logs "Unknown (Personal Email)", and records a core priority score of 10.
• Scenario Beta (High-Value Category): If the domain falls outside consumer lists, the system assumes a corporate business lead profile. The engine categorizes the operational target as the "B2B Tech Sector" and elevates the lead profile rating to an immediate priority score of 85.
