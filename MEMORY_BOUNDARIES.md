# TraceMem Memory Boundaries and Architecture

TraceMem acts as the semantic memory layer for external AI applications. Because it handles sensitive, long-term context for downstream users, strict isolation and clear identity boundaries are non-negotiable. 

This document defines the core primitives, isolation rules, and architectural responsibilities that govern how TraceMem stores, retrieves, and protects data.

---

## Design Philosophy

TraceMem is intentionally opinionated.

It provides:
- durable memory
- deterministic isolation
- semantic retrieval

while deliberately avoiding application-specific concerns such as authentication, authorization, business logic, CRM modeling, user management, or workflow automation.

The application owns users. TraceMem owns memory.

---

## 1. Core Primitives

### What is a Tenant?
A **Tenant** represents the TraceMem account holder (either an Individual or a Company). 
- **Billing Entity:** The Tenant is the entity that holds a Razorpay subscription. Billing is *always* tenant-level, never workspace-level.
- **Scope Identifier:** Every tenant is assigned a cryptographic UUID (`tenant_scope_id`) upon creation. 
- **Database Mapping:** Corresponds to the `User` model in the TraceMem database.

### What is a Workspace?
A **Workspace** is the strict organizational boundary for data isolation. 
- **Topology:** Individual tenants get exactly one locked, default workspace. Company tenants can provision multiple workspaces (1..N).
- **Scope:** Workspaces scope **API Keys**, **Memories**, and **Members**. 
- **Immutability:** Once a memory or API key is assigned to a `workspace_id`, it is **immutable**. It cannot be transferred to another workspace.
- **Database Mapping:** Corresponds to the `Team` model in the TraceMem database.

### What is a User ID?
A **User ID** is an opaque string (e.g., `user-123`, `patient_882`) provided by the *API Client* in their payload.
- **End-User Mapping:** It represents the end-user or entity interacting with the client's AI application.
- **Memory Scoping:** TraceMem groups memories by this `user_id`. When `/recall` or `/remember` is called, the operations are isolated to the specific `user_id` passed in the request.

#### Identity Stability
TraceMem assumes that `user_id` is stable over time. 

If the client changes the identifier for the same human (for example, `user_123` → `user_456`), TraceMem will treat them as two entirely different identities unless the client explicitly migrates or aliases them.

### What is a Memory?
A **Memory** is an atomic block of semantically extracted context.
- **Strict Tuple:** Every memory is scoped by the tuple: `(tenant_scope_id, workspace_id, user_id)`.
- **Lifecycle:** It is extracted from raw text, scored, decayed over time, and automatically retrieved when semantically relevant to a future query.

### What is an Identity?
In TraceMem, **Identity** is a trusted proxy. TraceMem does not authenticate the downstream end-user (no passwords, no sessions, no OAuth for end-users). 
Instead, TraceMem relies entirely on the API Client to securely authenticate their own users and pass a consistent `user_id`. Identity inside TraceMem is effectively the combination of `API Key (Workspace) + user_id`.

#### Human Identity vs TraceMem Identity
TraceMem never attempts to infer whether two memories belong to the same real-world person.

For example, given:
- `Rahul`
- `Rahul Sharma`
- `rahul@company.com`
- `EMP-44`

TraceMem never guesses these are identical. Only the client can decide. TraceMem exclusively trusts the supplied `user_id`.

---

## 2. Responsibilities and Guarantees

### What is Client Responsibility?
The developer or company integrating TraceMem (the Tenant) is responsible for:
1. **Authentication:** Authenticating their own end-users securely.
2. **Identity Mapping:** Consistently passing the correct `user_id` for a given user across sessions. If a user logs out and logs back in, the client must ensure the same `user_id` is passed.
3. **Key Security:** Protecting their Live API keys and not exposing them in client-side code (browsers/mobile apps).
4. **Data Sanitization:** (Optional) While TraceMem processes what is sent, clients should handle regulatory anonymization (e.g., stripping SSNs before hitting TraceMem) if their compliance framework requires it *prior* to ingestion.

### What is TraceMem Responsibility?
TraceMem, as the infrastructure provider, guarantees:
1. **Cryptographic Isolation:** Ensuring that an API Key resolves strictly to its assigned `workspace_id` and `tenant_scope_id`.
2. **Immutability & Deduplication:** Safely storing, deduplicating, and persisting memories without cross-contamination.
3. **Semantic Operations:** Providing high-accuracy retrieval and context assembly based on multi-factor scoring (temporal, semantic, decay).
4. **Tenant Billing:** Charging the tenant based on aggregated usage across all their workspaces.

### TraceMem is Stateless
TraceMem does not maintain application sessions. It stores semantic memories only.

### Client Metadata
Client metadata is optional and opaque. TraceMem may store metadata but never interprets its business meaning. 

For example, if a client attaches:
```json
{
  "country": "IN",
  "subscription": "pro",
  "crm": "hubspot"
}
```
TraceMem stores it but does not attempt to understand or route logic based on it.

### Memory Ownership
TraceMem never determines the legal ownership of customer data. The **Tenant** owns the workspace. Any contractual data ownership between the Tenant and their downstream customers is outside TraceMem's responsibility. If an agency provisions a workspace for their client and that client later leaves, the agency (as the Tenant) legally owns that workspace's data in the eyes of TraceMem.

---

## 3. Trust Boundary

TraceMem treats input with a strict trust boundary. TraceMem trusts **only**:
- Authenticated API key
- Workspace
- Tenant
- User ID

Everything else in a request is treated as **untrusted input**.

Because of this, the core ingestion pipeline flows strictly as:

`remember()` → **Validate** → **Normalize** → **Score** → **Store**

---

## 4. Isolation Rules

TraceMem operates on a set of non-negotiable architectural guardrails.

### Workspace Isolation Guarantee
During retrieval, TraceMem **never**:
- searches another workspace
- ranks another workspace
- embeds another workspace
- scores another workspace

Other workspaces never even participate in the retrieval pipeline.

### General Guardrails
| Rule | Description |
|------|-------------|
| **Key-to-Workspace Immutability** | An API key is permanently bound to a single `workspace_id`. It cannot query or mutate data outside that workspace. |
| **Memory Immutability** | A memory's `workspace_id` is immutable. If an agency wants to move a client's memory to a different workspace, they must re-ingest it. |
| **Strict Scoping** | Even if `user-123` exists in Workspace A and Workspace B (under the same Tenant), their memories are physically and logically partitioned. TraceMem treats them as entirely distinct entities. |
| **Fail-Open Billing, Fail-Closed Security** | If the billing cache fails, TraceMem fails *open* to prevent downtime for paying users. If an API key signature or environment check fails, TraceMem fails *closed* (401/403). |
| **Sandbox Restrictions** | Test keys (`cmtest_`) are strictly hardcoded to only allow requests from `localhost` and `Postman`. Production leakage is impossible at the middleware layer. |

---

## 5. Compatibility (Stable API)

Once a memory has been stored, future TraceMem releases will preserve:
- retrieval semantics
- identity boundaries
- workspace boundaries

unless a documented migration path exists.

---

## 6. Enterprise Examples

How different industries map their architecture to TraceMem's primitives:

### CRM Example
- **Tenant:** A B2B SaaS CRM company.
- **Workspaces:** One workspace per *Customer Account* using the CRM.
- **User ID:** The individual Sales Rep or the End-Client being communicated with.
- **Result:** Memories are strictly isolated per CRM account. If an employee leaves one company and joins another using the same CRM, their context does not bleed over.

### Healthcare Example
- **Tenant:** A Hospital Network.
- **Workspaces:** One workspace per *Hospital Branch* or *Clinic*.
- **User ID:** The Patient ID (`pat_55921`).
- **Result:** HIPAA-compliant logical isolation. Doctors querying the AI for patient history only retrieve memories extracted during past visits for that specific patient, safely contained within that clinic's workspace.

### Agency Example
- **Tenant:** A Marketing/Dev Agency building chatbots.
- **Workspaces:** One workspace per *Client* the agency serves.
- **User ID:** The visitors chatting with the specific client's bot.
- **Result:** The agency pays one master Razorpay bill. Each client's data, memories, and API keys are cleanly segmented. If a client churns, the agency just archives their workspace.

### Website Chatbot Example
- **Tenant:** An e-commerce brand.
- **Workspaces:** A single "Production" workspace and a single "Staging" workspace.
- **User ID:** A browser session UUID or logged-in Customer ID.
- **Result:** The bot remembers what a user was looking at last week ("I remember you were asking about the red sneakers..."), increasing conversion rates.

### Internal AI Example
- **Tenant:** A medium-sized tech company.
- **Workspaces:** Departmental isolation: `Engineering`, `HR`, `Sales`.
- **User ID:** The employee's Okta/Google Workspace ID.
- **Result:** An engineer asking the internal AI about code receives context from the Engineering workspace. The HR workspace remains completely separate, ensuring salary or policy queries don't mix with engineering docs.

---

## 7. Known Edge Cases

1. **User Identity Merging:** 
   If an anonymous user (`session_uuid`) signs up and becomes a logged-in user (`user_id`), TraceMem currently sees these as two separate identities. The API Client must manage this transition (e.g., by querying the old session ID's memories and re-ingesting them under the new User ID, or maintaining a mapping on their backend).
2. **Cross-Workspace Rate Limiting:** 
   While rate limits are assigned per API Key (Workspace level), global DDoS mitigation and heavy usage limits inherit from the Tenant's overarching subscription limits. 
3. **Orphaned Memories:** 
   If a client deletes a user on their end, TraceMem does not automatically know. The client must explicitly trigger a deletion for that `user_id` in TraceMem to clear the context, or rely on long-term decay archiving.

---

## 8. Future Memory Namespace

As TraceMem evolves, the memory boundary layer may introduce:

- **Global/Shared Workspaces:** The ability for an API Client to designate a `user_id = 'global'` for facts that apply to *all* users in a workspace (e.g., "The company refund policy is 30 days"). The Context Assembler would fetch both User-specific + Global memories.
- **Session ID Clustering:** Adding an optional `session_id` payload to group memories not just by User, but by distinct conversational threads, allowing finer-grained context assembly.
- **Administrative Operations:** Enterprise plans may later support *export*, *import*, and *migration* without changing memory ownership guarantees.