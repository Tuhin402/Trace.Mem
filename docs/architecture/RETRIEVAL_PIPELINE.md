# Retrieval Pipeline

The Retrieval Pipeline is responsible for fetching, ranking, and assembling memories when you query the `/recall`, `/context/assemble`, or `/chat` endpoints.

## Pipeline Architecture

```text
User Query (/recall or /chat)
    │
    ▼
Context Assembly Service
    Segments query → extracts temporal intent → candidate memories
    │
    ▼
Multi-Factor Scoring (per candidate)
    Semantic Similarity + Temporal Match + Importance
    + Decay Score + Subject Relevance + Confidence
    │
    ▼
Top-K Selection → deduplicate → apply token budget
    │
    ▼
Context Packager → prompt-ready block
    │
    ▼
Reinforcement on Recall
    access_count++ · last_accessed_at updated · decay_score adjusted
```

## Multi-Factor Scoring

TraceMem doesn't just rely on basic vector similarity. It scores each candidate memory using a weighted algorithm:
1. **Semantic Similarity:** How close the memory's embedding is to the user's query.
2. **Temporal Match:** Boosts memories that match time-based intents (e.g., "what did I say yesterday?").
3. **Importance:** Facts and Rules have a higher base importance than minor Preferences.
4. **Decay Score:** Older memories that haven't been accessed recently decay slowly over time to prevent stale context.
5. **Subject Relevance:** Matches the entity or topic being discussed.
6. **Confidence:** The original ingestion confidence score.

## Reinforcement on Recall

Every time a memory is successfully retrieved and used in a context block, TraceMem reinforces it:
- Increments the `access_count`.
- Updates `last_accessed_at`.
- Adjusts the `decay_score` to keep frequently used memories near the top of the retrieval stack.
