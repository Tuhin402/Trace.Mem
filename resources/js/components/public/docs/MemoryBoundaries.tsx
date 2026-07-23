import React from 'react';

export default function MemoryBoundaries() {
    return (
        <div className="docs-markdown-content">
            <h1 className="docs-section-h2" style={{ marginBottom: 16 }}>TraceMem Memory Boundaries and Architecture</h1>
            
            <p className="docs-section-lead" style={{ marginBottom: 24 }}>
                TraceMem acts as the semantic memory layer for external AI applications. Because it handles sensitive, long-term context for downstream users, strict isolation and clear identity boundaries are non-negotiable. 
            </p>
            <p className="docs-section-lead" style={{ marginBottom: 40 }}>
                This document defines the core primitives, isolation rules, and architectural responsibilities that govern how TraceMem stores, retrieves, and protects data.
            </p>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>Design Philosophy</h2>
            
            <p style={{ marginBottom: 16 }}>TraceMem is intentionally opinionated.</p>
            
            <p style={{ marginBottom: 8 }}>It provides:</p>
            <ul className="docs-list" style={{ marginBottom: 16 }}>
                <li>durable memory</li>
                <li>deterministic isolation</li>
                <li>semantic retrieval</li>
            </ul>
            
            <p style={{ marginBottom: 24 }}>
                while deliberately avoiding application-specific concerns such as authentication, authorization, business logic, CRM modeling, user management, or workflow automation.
            </p>
            
            <div className="docs-callout docs-callout-info">
                The application owns users. TraceMem owns memory.
            </div>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>1. Core Primitives</h2>

            <h3 className="docs-section-h3">What is a Tenant?</h3>
            <p style={{ marginBottom: 16 }}>A <strong>Tenant</strong> represents the TraceMem account holder (either an Individual or a Company).</p>
            <ul className="docs-list">
                <li><strong>Billing Entity:</strong> The Tenant is the entity that holds a Razorpay subscription. Billing is <em>always</em> tenant-level, never workspace-level.</li>
                <li><strong>Scope Identifier:</strong> Every tenant is assigned a cryptographic UUID (<code>tenant_scope_id</code>) upon creation.</li>
                <li><strong>Database Mapping:</strong> Corresponds to the <code>User</code> model in the TraceMem database.</li>
            </ul>

            <h3 className="docs-section-h3">What is a Workspace?</h3>
            <p style={{ marginBottom: 16 }}>A <strong>Workspace</strong> is the strict organizational boundary for data isolation.</p>
            <ul className="docs-list">
                <li><strong>Topology:</strong> Individual tenants get exactly one locked, default workspace. Company tenants can provision multiple workspaces (1..N).</li>
                <li><strong>Scope:</strong> Workspaces scope <strong>API Keys</strong>, <strong>Memories</strong>, and <strong>Members</strong>.</li>
                <li><strong>Immutability:</strong> Once a memory or API key is assigned to a <code>workspace_id</code>, it is <strong>immutable</strong>. It cannot be transferred to another workspace.</li>
                <li><strong>Database Mapping:</strong> Corresponds to the <code>Team</code> model in the TraceMem database.</li>
            </ul>

            <h3 className="docs-section-h3">What is a User ID?</h3>
            <p style={{ marginBottom: 16 }}>A <strong>User ID</strong> is an opaque string (e.g., <code>user-123</code>, <code>patient_882</code>) provided by the <em>API Client</em> in their payload.</p>
            <ul className="docs-list">
                <li><strong>End-User Mapping:</strong> It represents the end-user or entity interacting with the client's AI application.</li>
                <li><strong>Memory Scoping:</strong> TraceMem groups memories by this <code>user_id</code>. When <code>/recall</code> or <code>/remember</code> is called, the operations are isolated to the specific <code>user_id</code> passed in the request.</li>
            </ul>

            <h4 className="docs-section-h4">Identity Stability</h4>
            <p style={{ marginBottom: 16 }}>TraceMem assumes that <code>user_id</code> is stable over time.</p>
            <p style={{ marginBottom: 32 }}>
                If the client changes the identifier for the same human (for example, <code>user_123</code> → <code>user_456</code>), TraceMem will treat them as two entirely different identities unless the client explicitly migrates or aliases them.
            </p>

            <h3 className="docs-section-h3">What is a Memory?</h3>
            <p style={{ marginBottom: 16 }}>A <strong>Memory</strong> is an atomic block of semantically extracted context.</p>
            <ul className="docs-list">
                <li><strong>Strict Tuple:</strong> Every memory is scoped by the tuple: <code>(tenant_scope_id, workspace_id, user_id)</code>.</li>
                <li><strong>Lifecycle:</strong> It is extracted from raw text, scored, decayed over time, and automatically retrieved when semantically relevant to a future query.</li>
            </ul>

            <h3 className="docs-section-h3">What is an Identity?</h3>
            <p style={{ marginBottom: 16 }}>
                In TraceMem, <strong>Identity</strong> is a trusted proxy. TraceMem does not authenticate the downstream end-user (no passwords, no sessions, no OAuth for end-users). 
                Instead, TraceMem relies entirely on the API Client to securely authenticate their own users and pass a consistent <code>user_id</code>. Identity inside TraceMem is effectively the combination of <code>API Key (Workspace) + user_id</code>.
            </p>

            <h4 className="docs-section-h4">Human Identity vs TraceMem Identity</h4>
            <p style={{ marginBottom: 16 }}>TraceMem never attempts to infer whether two memories belong to the same real-world person.</p>
            <p style={{ marginBottom: 8 }}>For example, given:</p>
            <ul className="docs-list" style={{ marginBottom: 16 }}>
                <li><code>Rahul</code></li>
                <li><code>Rahul Sharma</code></li>
                <li><code>rahul@company.com</code></li>
                <li><code>EMP-44</code></li>
            </ul>
            <p style={{ marginBottom: 32 }}>
                TraceMem never guesses these are identical. Only the client can decide. TraceMem exclusively trusts the supplied <code>user_id</code>.
            </p>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>2. Responsibilities and Guarantees</h2>

            <h3 className="docs-section-h3">What is Client Responsibility?</h3>
            <p style={{ marginBottom: 16 }}>The developer or company integrating TraceMem (the Tenant) is responsible for:</p>
            <ol className="docs-list docs-list-numbered">
                <li><strong>Authentication:</strong> Authenticating their own end-users securely.</li>
                <li><strong>Identity Mapping:</strong> Consistently passing the correct <code>user_id</code> for a given user across sessions. If a user logs out and logs back in, the client must ensure the same <code>user_id</code> is passed.</li>
                <li><strong>Key Security:</strong> Protecting their Live API keys and not exposing them in client-side code (browsers/mobile apps).</li>
                <li><strong>Data Sanitization:</strong> (Optional) While TraceMem processes what is sent, clients should handle regulatory anonymization (e.g., stripping SSNs before hitting TraceMem) if their compliance framework requires it <em>prior</em> to ingestion.</li>
            </ol>

            <h3 className="docs-section-h3">What is TraceMem Responsibility?</h3>
            <p style={{ marginBottom: 16 }}>TraceMem, as the infrastructure provider, guarantees:</p>
            <ol className="docs-list docs-list-numbered">
                <li><strong>Cryptographic Isolation:</strong> Ensuring that an API Key resolves strictly to its assigned <code>workspace_id</code> and <code>tenant_scope_id</code>.</li>
                <li><strong>Immutability & Deduplication:</strong> Safely storing, deduplicating, and persisting memories without cross-contamination.</li>
                <li><strong>Semantic Operations:</strong> Providing high-accuracy retrieval and context assembly based on multi-factor scoring (temporal, semantic, decay).</li>
                <li><strong>Tenant Billing:</strong> Charging the tenant based on aggregated usage across all their workspaces.</li>
            </ol>

            <h3 className="docs-section-h3">TraceMem is Stateless</h3>
            <p style={{ marginBottom: 32 }}>TraceMem does not maintain application sessions. It stores semantic memories only.</p>

            <h3 className="docs-section-h3">Client Metadata</h3>
            <p style={{ marginBottom: 16 }}>Client metadata is optional and opaque. TraceMem may store metadata but never interprets its business meaning.</p>
            <p style={{ marginBottom: 16 }}>For example, if a client attaches:</p>
            <pre className="docs-code-block">
                <code>
{`{
  "country": "IN",
  "subscription": "pro",
  "crm": "hubspot"
}`}
                </code>
            </pre>
            <p style={{ marginBottom: 32 }}>TraceMem stores it but does not attempt to understand or route logic based on it.</p>

            <h3 className="docs-section-h3">Memory Ownership</h3>
            <p style={{ marginBottom: 32 }}>
                TraceMem never determines the legal ownership of customer data. The <strong>Tenant</strong> owns the workspace. Any contractual data ownership between the Tenant and their downstream customers is outside TraceMem's responsibility. If an agency provisions a workspace for their client and that client later leaves, the agency (as the Tenant) legally owns that workspace's data in the eyes of TraceMem.
            </p>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>3. Trust Boundary</h2>

            <p style={{ marginBottom: 16 }}>TraceMem treats input with a strict trust boundary. TraceMem trusts <strong>only</strong>:</p>
            <ul className="docs-list">
                <li>Authenticated API key</li>
                <li>Workspace</li>
                <li>Tenant</li>
                <li>User ID</li>
            </ul>
            <p style={{ marginBottom: 16 }}>Everything else in a request is treated as <strong>untrusted input</strong>.</p>
            <p style={{ marginBottom: 16 }}>Because of this, the core ingestion pipeline flows strictly as:</p>
            <div className="docs-callout" style={{ fontFamily: 'var(--font-mono)' }}>
                remember() → Validate → Normalize → Score → Store
            </div>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>4. Isolation Rules</h2>

            <p style={{ marginBottom: 32 }}>TraceMem operates on a set of non-negotiable architectural guardrails.</p>

            <h3 className="docs-section-h3">Workspace Isolation Guarantee</h3>
            <p style={{ marginBottom: 16 }}>During retrieval, TraceMem <strong>never</strong>:</p>
            <ul className="docs-list" style={{ marginBottom: 16 }}>
                <li>searches another workspace</li>
                <li>ranks another workspace</li>
                <li>embeds another workspace</li>
                <li>scores another workspace</li>
            </ul>
            <p style={{ marginBottom: 32 }}>Other workspaces never even participate in the retrieval pipeline.</p>

            <h3 className="docs-section-h3">General Guardrails</h3>
            <div className="docs-table-wrapper" style={{ marginBottom: 32 }}>
                <table className="docs-table">
                    <thead>
                        <tr>
                            <th>Rule</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Key-to-Workspace Immutability</strong></td>
                            <td>An API key is permanently bound to a single <code>workspace_id</code>. It cannot query or mutate data outside that workspace.</td>
                        </tr>
                        <tr>
                            <td><strong>Memory Immutability</strong></td>
                            <td>A memory's <code>workspace_id</code> is immutable. If an agency wants to move a client's memory to a different workspace, they must re-ingest it.</td>
                        </tr>
                        <tr>
                            <td><strong>Strict Scoping</strong></td>
                            <td>Even if <code>user-123</code> exists in Workspace A and Workspace B (under the same Tenant), their memories are physically and logically partitioned. TraceMem treats them as entirely distinct entities.</td>
                        </tr>
                        <tr>
                            <td><strong>Fail-Open Billing, Fail-Closed Security</strong></td>
                            <td>If the billing cache fails, TraceMem fails <em>open</em> to prevent downtime for paying users. If an API key signature or environment check fails, TraceMem fails <em>closed</em> (401/403).</td>
                        </tr>
                        <tr>
                            <td><strong>Sandbox Restrictions</strong></td>
                            <td>Test keys (<code>cmtest_</code>) are strictly hardcoded to only allow requests from <code>localhost</code> and <code>Postman</code>. Production leakage is impossible at the middleware layer.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>5. Compatibility (Stable API)</h2>

            <p style={{ marginBottom: 16 }}>Once a memory has been stored, future TraceMem releases will preserve:</p>
            <ul className="docs-list" style={{ marginBottom: 16 }}>
                <li>retrieval semantics</li>
                <li>identity boundaries</li>
                <li>workspace boundaries</li>
            </ul>
            <p style={{ marginBottom: 32 }}>unless a documented migration path exists.</p>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>6. Enterprise Examples</h2>
            
            <p style={{ marginBottom: 24 }}>How different industries map their architecture to TraceMem's primitives:</p>

            <h3 className="docs-section-h3">CRM Example</h3>
            <ul className="docs-list" style={{ marginBottom: 24 }}>
                <li><strong>Tenant:</strong> A B2B SaaS CRM company.</li>
                <li><strong>Workspaces:</strong> One workspace per <em>Customer Account</em> using the CRM.</li>
                <li><strong>User ID:</strong> The individual Sales Rep or the End-Client being communicated with.</li>
                <li><strong>Result:</strong> Memories are strictly isolated per CRM account. If an employee leaves one company and joins another using the same CRM, their context does not bleed over.</li>
            </ul>

            <h3 className="docs-section-h3">Healthcare Example</h3>
            <ul className="docs-list" style={{ marginBottom: 24 }}>
                <li><strong>Tenant:</strong> A Hospital Network.</li>
                <li><strong>Workspaces:</strong> One workspace per <em>Hospital Branch</em> or <em>Clinic</em>.</li>
                <li><strong>User ID:</strong> The Patient ID (<code>pat_55921</code>).</li>
                <li><strong>Result:</strong> HIPAA-compliant logical isolation. Doctors querying the AI for patient history only retrieve memories extracted during past visits for that specific patient, safely contained within that clinic's workspace.</li>
            </ul>

            <h3 className="docs-section-h3">Agency Example</h3>
            <ul className="docs-list" style={{ marginBottom: 24 }}>
                <li><strong>Tenant:</strong> A Marketing/Dev Agency building chatbots.</li>
                <li><strong>Workspaces:</strong> One workspace per <em>Client</em> the agency serves.</li>
                <li><strong>User ID:</strong> The visitors chatting with the specific client's bot.</li>
                <li><strong>Result:</strong> The agency pays one master Razorpay bill. Each client's data, memories, and API keys are cleanly segmented. If a client churns, the agency just archives their workspace.</li>
            </ul>

            <h3 className="docs-section-h3">Website Chatbot Example</h3>
            <ul className="docs-list" style={{ marginBottom: 24 }}>
                <li><strong>Tenant:</strong> An e-commerce brand.</li>
                <li><strong>Workspaces:</strong> A single "Production" workspace and a single "Staging" workspace.</li>
                <li><strong>User ID:</strong> A browser session UUID or logged-in Customer ID.</li>
                <li><strong>Result:</strong> The bot remembers what a user was looking at last week ("I remember you were asking about the red sneakers..."), increasing conversion rates.</li>
            </ul>

            <h3 className="docs-section-h3">Internal AI Example</h3>
            <ul className="docs-list" style={{ marginBottom: 32 }}>
                <li><strong>Tenant:</strong> A medium-sized tech company.</li>
                <li><strong>Workspaces:</strong> Departmental isolation: <code>Engineering</code>, <code>HR</code>, <code>Sales</code>.</li>
                <li><strong>User ID:</strong> The employee's Okta/Google Workspace ID.</li>
                <li><strong>Result:</strong> An engineer asking the internal AI about code receives context from the Engineering workspace. The HR workspace remains completely separate, ensuring salary or policy queries don't mix with engineering docs.</li>
            </ul>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>7. Known Edge Cases</h2>

            <ol className="docs-list docs-list-numbered" style={{ marginBottom: 32 }}>
                <li>
                    <strong>User Identity Merging:</strong><br />
                    If an anonymous user (<code>session_uuid</code>) signs up and becomes a logged-in user (<code>user_id</code>), TraceMem currently sees these as two separate identities. The API Client must manage this transition (e.g., by querying the old session ID's memories and re-ingesting them under the new User ID, or maintaining a mapping on their backend).
                </li>
                <li style={{ marginTop: 16 }}>
                    <strong>Cross-Workspace Rate Limiting:</strong><br />
                    While rate limits are assigned per API Key (Workspace level), global DDoS mitigation and heavy usage limits inherit from the Tenant's overarching subscription limits. 
                </li>
                <li style={{ marginTop: 16 }}>
                    <strong>Orphaned Memories:</strong><br />
                    If a client deletes a user on their end, TraceMem does not automatically know. The client must explicitly trigger a deletion for that <code>user_id</code> in TraceMem to clear the context, or rely on long-term decay archiving.
                </li>
            </ol>

            <hr className="docs-hr" />

            <h2 className="docs-section-h2" style={{ fontSize: '24px', marginTop: 40, marginBottom: 16 }}>8. Future Memory Namespace</h2>

            <p style={{ marginBottom: 16 }}>As TraceMem evolves, the memory boundary layer may introduce:</p>
            <ul className="docs-list">
                <li><strong>Global/Shared Workspaces:</strong> The ability for an API Client to designate a <code>user_id = 'global'</code> for facts that apply to <em>all</em> users in a workspace (e.g., "The company refund policy is 30 days"). The Context Assembler would fetch both User-specific + Global memories.</li>
                <li><strong>Session ID Clustering:</strong> Adding an optional <code>session_id</code> payload to group memories not just by User, but by distinct conversational threads, allowing finer-grained context assembly.</li>
                <li><strong>Administrative Operations:</strong> Enterprise plans may later support <em>export</em>, <em>import</em>, and <em>migration</em> without changing memory ownership guarantees.</li>
            </ul>
        </div>
    );
}
