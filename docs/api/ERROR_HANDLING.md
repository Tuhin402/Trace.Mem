# Error Handling

TraceMem uses standard HTTP response codes to indicate the success or failure of an API request.

## HTTP Status Codes

| Code | Status | Description |
|------|--------|-------------|
| `200` | OK | The request was successful. |
| `201` | Created | The memory or resource was successfully created. |
| `400` | Bad Request | The request payload was invalid or missing required fields. |
| `401` | Unauthorized | Missing, invalid, or revoked API key. |
| `403` | Forbidden | API key is valid, but the workspace lacks permissions or the subscription is inactive. |
| `404` | Not Found | The requested resource (e.g., a specific memory ID) does not exist. |
| `422` | Unprocessable Entity | Validation failed on the provided JSON payload. |
| `429` | Too Many Requests | Rate limit exceeded. |
| `500` | Server Error | An internal error occurred on TraceMem's servers. |

## Error Responses

When an error occurs, the API returns a JSON object detailing the issue:

```json
{
  "error": {
    "code": "invalid_api_key",
    "message": "The provided API key is invalid or has been revoked."
  }
}
```
