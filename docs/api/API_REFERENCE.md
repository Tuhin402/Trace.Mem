# API Reference

**Base URL:** `https://api.tracemem.one/v1`

## Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/health` | API + Redis queue heartbeat status |
| `POST` | `/remember` | Ingest a message — extract, process, store memories |
| `POST` | `/recall` | Retrieve semantically relevant raw memories |
| `POST` | `/context/assemble` | Fetch a prompt-ready memory context block |
| `POST` | `/chat` | Chat with memory-augmented context (AI-First mode) |
| `POST` | `/debug/memory-decision` | Inspect memory extraction decision (debug mode) |

## Required Parameters

Every memory endpoint strictly requires a `user_id`.

**`user_id` (Client-Supplied):** A stable identifier representing your downstream end-user (e.g., `customer_8472`, `patient_991`, `session_abcd1234`). Changing the `user_id` creates a completely separate memory graph. TraceMem never merges identities automatically.

## Common Request Example

```curl
curl -X POST "https://api.tracemem.one/v1/remember" \
  -H "Authorization: Bearer cmlive_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": "customer_8472",
    "content": "I have a meeting with Q2 investors on Monday at 3pm.",
    "category": "schedule"
  }'
```
