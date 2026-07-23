# Security Model

TraceMem is designed with a defense-in-depth security model to ensure that memory data is never leaked across tenants or workspaces.

## Layers of Defense

1. **Authentication:** All API interactions must provide a valid `X-API-Key` (Bearer token).
2. **Cryptographic Identity Resolution:** The `ApiKeyAuthMiddleware` takes the hashed API key and securely resolves it into a specific `tenant_scope_id` and `workspace_id`. This happens completely server-side and cannot be spoofed by a client payload.
3. **Immutability:** Once a memory is created, its `workspace_id` is permanently locked. A memory can never be reassigned to a different workspace.
4. **Data Isolation:** All database queries within the memory lifecycle are strictly bound by `WHERE workspace_id = ?`. Cross-tenant data bleeds are structurally impossible.

## Encryption
- **In Transit:** All API requests require TLS 1.3.
- **At Rest:** Database volumes are encrypted using standard AES-256 cloud provider encryption. API Keys are hashed before storage.
