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

* **Backend Engine:** Laravel 13, PHP 8.3+
* **Frontend Dashboard:** React 18, Inertia.js, TypeScript
* **Authentication:** Laravel Fortify (email verification, password reset, 2FA-ready)
* **Database & Storage:** PostgreSQL / MySQL / SQLite, with vector store integration
* **Billing & Subscriptions:** Razorpay (INR, subscription model) — HMAC-SHA256 webhook verification
* **Transactional Email:** Resend via `resend/resend-laravel` — Svix-signed webhook callbacks
* **Queue & Cache:** Redis — multi-priority queue (`high`, `emails`, `default`, `low`)
* **Scheduling:** Laravel Scheduler — daily backups, memory decay, expiry reminders
* **Styling:** Custom Vanilla CSS tailored for a premium, GitHub-inspired dark aesthetic

## Getting Started

### Prerequisites

Ensure your environment meets the following requirements:

* PHP 8.3 or higher
* Composer
* Node.js (v18+) and npm
* A relational database (MySQL, PostgreSQL, or SQLite for local dev)
* Redis (required for queue and cache in production; optional for local dev)

### Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Tuhin402/context-memory-layer.git
   cd context-memory-layer
   ```

2. **Run the automated setup script:**
   ```bash
   composer run setup
   ```
   This installs PHP and Node dependencies, copies `.env.example` → `.env`, generates the app key, runs migrations, and builds frontend assets.

   *Or run each step manually:*
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate
   npm install
   npm run build
   ```

3. **Environment Setup:**
   Update your `.env` file with your credentials. Key variables for local development:

   ```env
   DB_CONNECTION=sqlite          # zero-setup for local dev
   QUEUE_CONNECTION=redis        # or 'database' / 'sync' without Redis
   CACHE_STORE=redis             # or 'file' without Redis
   MAIL_MAILER=log               # emails appear in storage/logs, not sent
   RAZORPAY_KEY_ID=rzp_test_...
   RAZORPAY_KEY_SECRET=...
   OPENAI_API_KEY=sk-proj-...
   ```

4. **Start the Development Servers:**
   A single command boots the Laravel server, queue worker, scheduler, and Vite HMR simultaneously:
   ```bash
   composer run dev
   ```

---

## API Reference

TraceMem provides a clean REST API. All endpoints (except the health check) require authentication via a Bearer token. Generate an API Key from your TraceMem Dashboard.

**Base URL:** `https://api.tracemem.one/v1`

### Authentication

Include your API key in the request headers:
```http
Authorization: Bearer tmk_live_your_api_key_here
```

### Endpoints

* `GET /health`
  Check the operational status of the TraceMem API. Also reports Redis queue heartbeat status.

* `POST /remember`
  Ingest and store a new interaction. TraceMem will process the input, extract semantic meaning, and store it against the specific user/tenant.
  *Payload:* `user_id`, `content`, `category` (optional)

* `POST /recall`
  Retrieve raw memories that are semantically related to the query you are about to send to your LLM.
  *Payload:* `user_id`, `query`

* `POST /context/assemble`
  Fetch a pre-formatted, optimized text block containing all relevant memories. You can directly inject this string into your LLM prompt.
  *Payload:* `user_id`, `query`

---

## Environment Modes (API Keys)

TraceMem enforces a strict two-key environment to protect your production data:

* **Test Keys (`tmk_test_`):** Strictly sandboxed. Test keys can only be used from `localhost`, `127.0.0.1`, or Postman. They are rejected from production environments and real browser origins.
* **Live Keys (`tmk_live_`):** Production-ready keys that require HTTPS connections. You can whitelist specific origins via the dashboard to prevent unauthorized usage. Plain HTTP requests are rejected.

API keys are stored **hashed** in the database — plaintext is shown once at creation time and never retrievable again. Key rotation immediately revokes the previous key.

---

## Billing — Razorpay Integration

TraceMem uses **Razorpay** for subscriptions (INR currency, recurring billing).

### Checkout Flow

1. User selects a plan → `POST /billing/checkout`
2. Backend lazily creates a Razorpay Plan (cached in `subscription_plans.razorpay_plan_ids`) and a Razorpay Subscription
3. A pending `BillingTransaction` is recorded
4. Frontend receives the `subscription_id` and initialises the Razorpay JS modal
5. On modal success → `POST /billing/verify-payment`
6. Server verifies `HMAC-SHA256(payment_id|subscription_id, key_secret)` — **never trusts frontend success alone**
7. Subscription is activated in an atomic DB transaction

### Webhook Flow

Razorpay posts events to `POST /razorpay/webhook`:
- Signature is verified via `HMAC-SHA256(rawBody, webhook_secret)` + `hash_equals()`
- The controller returns `200` immediately and dispatches `ProcessRazorpayWebhookJob` to the `high` queue
- The job handles idempotency via a `UNIQUE` constraint on `razorpay_webhook_events.event_id`
- Razorpay retries up to 15 times over 24 hours on non-2xx — never put business logic in the controller

**Supported webhook events:**
`subscription.activated`, `subscription.charged`, `subscription.completed`, `subscription.cancelled`, `subscription.paused`, `subscription.resumed`, `payment.captured`, `payment.failed`, `refund.processed`, `order.paid`

---

## Transactional Email — Resend Integration

TraceMem uses **Resend** for all transactional email via `resend/resend-laravel`. Every email is dispatched as a `SendEmailJob` to the dedicated `emails` queue, ensuring email delivery is never starved by analytics or memory jobs.

### Architecture

```
Business event (billing, auth, API key)
    → SendEmailJob::dispatch() [emails queue]
        → ResendEmailService::send()
            → EmailLog (status: queued → sent / failed)
            → Resend API via Laravel Mail::to()->send()
```

All 14 transactional email templates are defined in the `EmailTemplate` enum:

| Category | Templates |
|----------|-----------|
| **Auth** | Verification, Password Reset, Password Changed, Email Changed |
| **Billing** | Subscription Purchased, Subscription Renewed, Subscription Cancelled, Payment Received, Payment Failed, Refund Processed, Plan Expiry Reminder |
| **API Keys** | API Key Created, API Key Rotated, API Key Expiry Reminder |

### Resend Webhook Flow

Resend posts events to `POST /resend/webhook`:
- Signature is verified using the Svix scheme: `HMAC-SHA256(svix-id.svix-timestamp.rawBody, secret)`
- The controller dispatches `ProcessResendWebhookJob` to the `emails` queue and returns `200` immediately
- Idempotency is enforced via `UNIQUE` constraint on `resend_webhook_events.event_id` (= svix-id)
- `email.opened` / `email.clicked` events update **analytics only** — they never trigger account actions
- Full delivery lifecycle is tracked in `email_logs`: `queued → sent → delivered → bounced / complained`

### Email Log Traceability

Every email carries custom headers for debugging:
- `X-TraceMem-Request-Id` — correlates the email back to the originating HTTP request
- `X-TraceMem-Log-Id` — links directly to the `email_logs` row
- `X-TraceMem-Template` — identifies which template was used

---

## Security

### Security Headers

All responses include hardened HTTP headers applied by the `SecurityHeaders` middleware:

| Header | Value |
|--------|-------|
| `Content-Security-Policy` | Whitelists Razorpay JS/iframe domains; `unsafe-inline` for Inertia hydration |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS-only, never applied locally) |
| `X-Frame-Options` | `SAMEORIGIN` |
| `X-Content-Type-Options` | `nosniff` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | camera, mic, geolocation, payment, USB, interest-cohort all disabled |
| `Cross-Origin-Resource-Policy` | `same-origin` |
| `Cross-Origin-Opener-Policy` | `same-origin-allow-popups` |
| `Cross-Origin-Embedder-Policy` | `unsafe-none` (preserves Razorpay modal iframe compatibility) |

### Webhook Security

Both Razorpay and Resend webhooks are CSRF-exempt and instead validated with their own HMAC-SHA256 signature schemes:

- **Razorpay:** `HMAC-SHA256(rawBody, RAZORPAY_WEBHOOK_SECRET)` — signature in `X-Razorpay-Signature` header
- **Resend (Svix):** `HMAC-SHA256(svix-id.svix-timestamp.rawBody, whsec_secret)` — signature in `svix-signature` header

Both use `hash_equals()` for timing-safe comparison. Both return `400` on invalid signatures (not `401`) so providers know the delivery was genuinely rejected.

### Password & Auth

- Minimum 12 characters, mixed case, digits, symbols (enforced in all environments)
- `BCRYPT_ROUNDS=12` for production-strength hashing
- `Password::uncompromised()` check in production (blocks passwords found in data breaches)
- `DB::prohibitDestructiveCommands()` enabled in production (prevents accidental DROP/TRUNCATE)

---

## Production Operations

### Queue Worker

TraceMem uses four priority queues. The worker must process them in order:

```bash
# Local dev (handled automatically by composer run dev)
php artisan queue:listen --tries=1

# Production — Supervisor config
[program:tracemem-worker]
command=php /var/www/tracemem/artisan queue:work redis --queue=high,emails,default,low --sleep=3 --tries=3 --max-time=3600
directory=/var/www/tracemem
autostart=true
autorestart=true
user=www-data
numprocs=2
stderr_logfile=/var/log/tracemem-worker.err.log
stdout_logfile=/var/log/tracemem-worker.out.log
```

| Queue | Contents | Retry Strategy |
|-------|----------|----------------|
| `high` | `ProcessRazorpayWebhookJob` — billing-critical | 5 tries, backoff: 10s→30s→60s→120s |
| `emails` | `SendEmailJob`, `ProcessResendWebhookJob` | 3 tries (email), 5 tries (webhook), exponential backoff |
| `default` | `AggregateUsageStatsJob`, notifications | Laravel defaults |
| `low` | `ReinforceMemoriesJob`, `DecayMemoryJob`, `DecayMemoriesSweepJob`, cleanup | Laravel defaults |

### Failed Job Operations

```bash
php artisan queue:failed                     # list all failed jobs
php artisan queue:retry {id}                 # retry one job by ID
php artisan queue:retry all                  # retry all failed jobs
php artisan queue:prune-failed --hours=168   # delete failures older than 7 days
```

### Scheduled Tasks

The Laravel Scheduler runs via a single crontab entry and manages:

| Time | Task | Purpose |
|------|------|---------|
| 02:00 daily | `memory:archive-stale` | Archives low-confidence memories (withoutOverlapping) |
| 02:00 daily | `backup:database` | Compressed DB backup, auto-pruning (withoutOverlapping) |
| 03:00 daily | `DecayMemoriesSweepJob` | Memory decay scoring — `chunkById(200)`, never full scan |
| 09:00 daily | `email:api-key-expiry-reminders` | Sends 7-day expiry reminders for API keys |
| 09:00 daily | `email:plan-expiry-reminders` | Sends 7-day expiry reminders for subscription plans |
| Every minute | Queue heartbeat | Writes `queue:heartbeat` Redis key (TTL 120s) for `/api/v1/health` |

### Cache Version Bump (Schema Migration)

When analytics schema or cache key structure changes, bump the global version.
All stale cached data becomes immediately unreachable — no key scans required:

```bash
php artisan tinker
>>> app(\App\Services\Cache\TraceMemCache::class)->bumpVersion();
```

This is an O(1) operation. Old keys expire naturally via their TTLs.

### Redis Memory Management

Add to your Redis configuration (`/etc/redis/redis.conf`):

```conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

> **⚠️ Production Warning:** `allkeys-lru` allows Redis to evict keys under memory pressure.
> If the same Redis instance serves both cache **and** queues, eviction can delete queued jobs.
> For production, use a **dedicated Redis instance for queues** (no eviction policy) and a
> separate instance for cache (`allkeys-lru`). Configure with `REDIS_QUEUE_*` and
> `REDIS_CACHE_*` env vars in `config/database.php`.

### Future Upgrade Path — Laravel Horizon

When queue monitoring and operational visibility become a priority:

```bash
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

Horizon provides:
- Real-time queue throughput dashboard
- Per-queue metrics and wait time tracking
- Failed job visibility with one-click retry
- Worker health monitoring
- Tag-based job searching and filtering
- Supervisor-compatible process management

This is not required for the current implementation but is the recommended long-term operations upgrade for Redis-backed queues.

---

## License

This project is proprietary software. All rights reserved.
