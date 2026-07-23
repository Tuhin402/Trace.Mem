# API Key Lifecycle

TraceMem API keys are strictly scoped to a Workspace and enforce the environment mode (Live vs Test). The lifecycle of an API key is designed to prevent accidental production mutations while allowing seamless local development.

## Environments

| Key Type | Prefix | Behaviour |
|----------|--------|-----------|
| **Test** | `cmtest_` | Sandbox only. Accepted from localhost or Postman. Auto-created on workspace creation. |
| **Live** | `cmlive_` | Production. Requires HTTPS + active paid subscription. Created manually by the Workspace Owner. |

## Creation & Management

1. **Auto Test Key:** When a new workspace is created (whether for an Individual or Company), TraceMem automatically generates exactly one `cmtest_` API key. No live key is created automatically.
2. **Manual Live Key:** To process real user memory and charge against the Tenant's billing quota, the Workspace Owner must manually generate a `cmlive_` key from the dashboard after setting up a paid subscription.
3. **Immutability:** An API key's `workspace_id` is permanently locked at creation. It cannot be moved to a different workspace.
4. **Visibility:** API keys are hashed (SHA-256) in the database. The plaintext key is only shown **once** to the user at the moment of creation.

## Rotation & Revocation

If an API key is compromised, it can be rotated from the dashboard.
- Rotating a key **immediately revokes** the previous key.
- All subsequent requests using the old key will result in a `401 Unauthorized` response.
- TraceMem automatically sends a "Key Rotated" transactional email to the Tenant Owner whenever a live key is rotated.
