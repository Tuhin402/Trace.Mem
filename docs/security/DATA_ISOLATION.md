# Data Isolation

TraceMem is built on a multi-tenant architecture where strict data isolation is structurally enforced.

## The Isolation Boundary

Every single memory operation relies on a three-tier isolation tuple:
`[tenant_scope_id, workspace_id, user_id]`

1. **`tenant_scope_id`**: Automatically resolved via the authenticated API Key. It represents the overarching Razorpay billing account and prevents cross-tenant leakage.
2. **`workspace_id`**: Automatically resolved via the API Key. It represents the strict data boundary for a given set of users and memories (e.g., a specific hospital branch).
3. **`user_id`**: Client-supplied. It represents the downstream end-user whose memories are being processed.

## Architectural Guarantees

- **No Shared Context:** Under no circumstances will a `/recall` or `/chat` request retrieve a memory belonging to a different `workspace_id`.
- **Immutable Bindings:** Once a memory is ingested and bound to a `workspace_id`, that binding cannot be mutated.
- **Server-Side Trust:** The client cannot spoof the `tenant_scope_id` or `workspace_id`. They are resolved strictly server-side by checking the hashed Bearer token against the database.
