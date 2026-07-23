# API Key Security

Because TraceMem acts as the memory core for your AI applications, protecting API keys is paramount.

## Hashing

API keys are **never stored in plaintext**.
- When an API key is generated, the plaintext is shown to the user **exactly once**.
- The backend immediately hashes the key using SHA-256 before storing it in the database.
- The `ApiKeyAuthMiddleware` takes incoming requests, hashes the provided Bearer token, and performs a timing-safe lookup against the database.

## Key Rotation

Workspace Owners can rotate API keys at any time from the dashboard.
- Rotating a key immediately invalidates the previous key.
- A transactional email is sent to the Workspace Owner confirming the rotation event to prevent unnoticed account compromise.
