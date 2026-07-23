# API Versioning

TraceMem APIs are versioned to ensure backward compatibility as the platform evolves.

## URL Structure

All API requests must include the API version in the base URL path:
`https://api.tracemem.one/v1/...`

## Deprecation Policy

We consider an API version stable. If we ever need to introduce breaking changes (such as modifying payload schemas or removing properties), we will release a new API version (e.g., `/v2`). 

- Non-breaking changes (like adding new optional parameters or new fields in a response) will be added to the current version.
- TraceMem will provide at least 6 months of notice before deprecating an older API version.
