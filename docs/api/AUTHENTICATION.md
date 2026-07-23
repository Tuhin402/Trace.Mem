# Authentication

All TraceMem API endpoints require authentication via an API Key passed as a Bearer token in the `Authorization` header.

## Using the Bearer Token

Include your API key in the `Authorization` header for every request:

```http
Authorization: Bearer cmlive_your_key_here
```

## Test vs Live Keys

TraceMem uses prefixes to distinguish between test and live environments:

- **Test Keys (`cmtest_`)**: Used for local development, testing, and CI pipelines. Test keys do not incur billing charges and should only be used with non-production data.
- **Live Keys (`cmlive_`)**: Used for production applications. Live keys require an active paid subscription and enforce strict security measures.

## Security Warning

- Never commit your API key to public repositories.
- Never expose your API key in client-side code (e.g., frontend React/Vue apps). Always proxy TraceMem requests through your own backend to protect your key.
