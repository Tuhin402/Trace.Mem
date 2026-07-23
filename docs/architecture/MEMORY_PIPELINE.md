# The Memory Pipeline

TraceMem employs a transparent, multi-stage pipeline between your users and your LLM. Every memory passes through a strict flow to ensure that only the most relevant, deduplicated, and accurate context reaches your prompt window.

## End-to-End Flow

1. **Ingest (Capture):** Raw conversation turns, messages, or structured records are submitted to the `/remember` or `/chat` endpoint via the REST API.
2. **Normalize:** Content is cleaned, language-normalized, and pre-processed to prepare for semantic extraction.
3. **Extract:** The AI pipeline parses intent, extracts memory candidates, and classifies them by type.
4. **Deduplicate:** New memories are checked against existing ones. Conflicts are resolved automatically, and duplicates are merged or discarded.
5. **Recall:** On `/recall`, the most semantically relevant memories are retrieved using vector similarity, ranked by recency and salience.
6. **Assemble:** Retrieved memories are assembled into a compact, prompt-ready context block. Inject it before your LLM call with zero extra work.

## Memory Types

TraceMem classifies every extracted memory into one of four semantic types. Each type is stored, retrieved, and assembled independently to give your AI precise, contextually correct recall.

### 1. Preference
User-stated or inferred likes, dislikes, communication styles, formatting choices, and behavioral tendencies. Extracted from conversational cues and stored persistently to personalise future responses.
*Examples:* "Prefers bullet points", "Dislikes formal tone", "Uses dark mode"

### 2. Fact
Stable, objective information about the user or their world, names, roles, projects, affiliations, and declared knowledge. Facts form the stable foundation of the memory graph.
*Examples:* "Name: Sarah", "Role: ML Engineer", "Uses Python"

### 3. Rule
Constraints, invariants, and non-negotiables. Rules govern how the AI should or must behave in specific situations; they override general defaults and are strictly respected.
*Examples:* "Never use jargon", "Always cite sources", "Respond in English"

### 4. Skill
Capabilities, competencies, and areas of expertise. Skills describe what the user can do, knows how to do, or wants to learn, helping the AI calibrate explanations and suggestions appropriately.
*Examples:* "Knows React", "Beginner at ML", "Proficient in SQL"
