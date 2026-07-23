# Ingestion Pipeline

The Ingestion Pipeline handles the processing of incoming messages to extract, deduplicate, and securely store semantic memories. It is triggered via the `/remember` or `/chat` endpoints.

## Pipeline Architecture

```text
User Message
    │
    ▼
[1] ApiKeyAuthMiddleware
    Validates key → resolves tenant_id, user_id, workspace_id, environment
    Sets request attribute: resolved_scope
    │
    ▼
[2] Ingestion Orchestrator
    ┌─────────────────┬──────────────────────────────┐
    │  AI-FIRST       │  SEMANTIC-ONLY               │
    │  AI Extraction  │  Semantic Segmentation       │
    │  Service        │  Service + Segmentation      │
    │  Code Detection │  Pipeline (Delimiter /       │
    │  (pre-filter)   │  Structure / List / MD)      │
    └─────────────────┴──────────────────────────────┘
    │
    ▼
[4] Parallel Processing
    ├── Normalization Service
    ├── Code Detection Service
    ├── Temporal Layer
    ├── Subject Detection Service
    └── Scoring & Metadata Enricher
    │
    ▼
[5] Conflict Detection → [6] Deduplication → [7] Final Memory Assembler
    │
    ▼
[8] Storage (Memory Model)
    workspace_id scoped · tenant_scope_id isolated
```

## Processing Modes

- **AI-First Extraction:** Used for conversational messages where intent and entities must be parsed dynamically using LLMs.
- **Semantic-Only Pipeline:** Used for highly structured text, markdown lists, or explicit delimiter-separated data where NLP overhead is unnecessary.

## Deduplication & Conflict Resolution

Before a memory is saved, TraceMem checks the user's existing memory graph for conflicts:
- **Exact Duplicates** are discarded to prevent bloat.
- **Direct Conflicts** (e.g., "I prefer Vue" vs previous "I prefer React") are resolved by overwriting the old preference or archiving it, ensuring the AI always has the most up-to-date state.
- **Merges** occur when a new memory expands upon an existing fact without contradicting it.
