# Rate Limits

TraceMem enforces rate limiting to protect the infrastructure and ensure fair usage across all accounts.

## Tenant Inheritance

Rate limits are assigned globally at the API Key (Workspace) level, but DDoS mitigation and heavy usage limits **inherit from the Tenant's overarching subscription limits**. 

If one workspace under a company account floods the API, it may exhaust the tenant's overall quota for that billing cycle.

## Headers

The API returns standard rate limit headers on every response:
- `X-RateLimit-Limit`: The maximum number of requests you're permitted to make per minute.
- `X-RateLimit-Remaining`: The number of requests remaining in the current minute window.
- `Retry-After`: If you exceed the rate limit, this header indicates how many seconds to wait before retrying.

If you exceed your rate limit, TraceMem will return a `429 Too Many Requests` status code.
