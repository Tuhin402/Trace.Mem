# TraceMem

TraceMem is a drop-in semantic memory layer designed for AI applications and Large Language Models. It provides long-term, persistent context to AI agents by extracting, storing, and seamlessly recalling structured knowledge across user sessions. 

Instead of relying on fragile keyword matching or manually passing raw chat transcripts into an LLM context window, TraceMem acts as an intelligent intermediary. It isolates tenants cryptographically, resolves contradicting memories automatically, and assembles highly relevant context blocks right before you make your LLM API call.

## Why TraceMem?

Most AI memory implementations face three major challenges: context window limits, irrelevant retrieval, and token waste. TraceMem solves this by introducing a robust semantic infrastructure:

* **Semantic Extraction:** Raw interactions are parsed intelligently to extract core facts, user preferences, and actionable intents. 
* **Optimized Recall:** Vector-based memory retrieval ensures that only the most relevant memories are fetched for any given prompt, bypassing simple keyword searches.
* **Context Assembly:** Memories are bundled into compact, prompt-ready blocks, eliminating token bloat and keeping your AI focused.
* **Strict Tenant Isolation:** Cryptographic boundaries ensure complete data separation between users and tenants.
* **Automatic Conflict Resolution:** If a user preference changes over time, TraceMem identifies the contradiction and updates its context safely.

## Tech Stack

This project features a fully integrated backend engine and a premium developer dashboard:

* **Backend Engine:** Laravel 11, PHP 8.2+
* **Frontend Dashboard:** React 18, Inertia.js, TypeScript
* **Database & Storage:** PostgreSQL / MySQL / SQLite, Pinecone (or similar vector stores)
* **Billing & Subscriptions:** Stripe Integration
* **Styling:** Custom Vanilla CSS tailored for a premium, GitHub-inspired dark aesthetic

## Getting Started

### Prerequisites

Ensure your environment meets the following requirements:
* PHP 8.2 or higher
* Composer
* Node.js (v18+) and npm/yarn
* A relational database (MySQL, PostgreSQL, or SQLite)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-username/context-memory-layer.git
   cd context-memory-layer
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Install frontend dependencies:**
   ```bash
   npm install
   ```

4. **Environment Setup:**
   Copy the example environment file and generate a secure application key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Update your `.env` file with your database credentials, Stripe API keys, and LLM configuration settings.

5. **Database Migration:**
   Run the migrations to set up your tables:
   ```bash
   php artisan migrate
   ```

6. **Start the Development Servers:**
   Boot up both the Laravel backend and the Vite frontend server:
   ```bash
   php artisan serve
   npm run dev
   ```

## API Reference

TraceMem provides a clean REST API. All endpoints (except the health check) require authentication via a Bearer token. Generate an API Key from your TraceMem Dashboard.

**Base URL:** `https://your-domain.com/api/v1`

### Authentication
Include your API key in the request headers:
```http
Authorization: Bearer tmk_live_your_api_key_here
```

### Endpoints

* `GET /health`
  Check the operational status of the TraceMem API.

* `POST /remember`
  Ingest and store a new interaction. TraceMem will process the input, extract semantic meaning, and store it against the specific user/tenant.
  *Payload:* `user_id`, `content`, `category` (optional)

* `POST /recall`
  Retrieve raw memories that are semantically related to the query you are about to send to your LLM.
  *Payload:* `user_id`, `query`

* `POST /context/assemble`
  Fetch a pre-formatted, optimized text block containing all relevant memories. You can directly inject this string into your LLM prompt.
  *Payload:* `user_id`, `query`

## Environment Modes

TraceMem supports dual-key environments to protect your production data:

* **Test Keys:** Strictly sandboxed. Test keys can only be used from `localhost` or API clients like Postman. Test keys enforce a `semantic_only` mode, which prevents real AI pipeline processing to save costs during development.
* **Live Keys:** Production-ready keys that require secure HTTPS connections. You can whitelist specific origins via the dashboard to prevent unauthorized usage.

## License

This project is proprietary software. All rights reserved.
