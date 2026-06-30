# TraceMem — Launch Checklist

> **Status:** Use this file as your go-live gate. Every item must be checked before production traffic is enabled.
> Tick each box (`[x]`) when verified.

---

## 🔴 Environment & Secrets

- [ ] Copy `.env.example` → `.env` on the production server. **Never commit `.env` to git.**
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false` — debug mode exposes stack traces to end users
- [ ] Set `APP_KEY` (run `php artisan key:generate` if blank)
- [ ] Set `APP_URL` to the canonical HTTPS domain (e.g., `https://tracemem.one`)
- [ ] Set `APP_DOMAIN=tracemem.one`, `APP_APP_DOMAIN=app.tracemem.one`, `APP_API_DOMAIN=api.tracemem.one`
- [ ] Replace Razorpay test keys with live keys (`rzp_live_...`):
  - `RAZORPAY_KEY_ID=rzp_live_...`
  - `RAZORPAY_KEY_SECRET=...`
  - `RAZORPAY_WEBHOOK_SECRET=...` (from Razorpay Dashboard → Webhooks → Secret)
  - `VITE_RAZORPAY_KEY_ID="${RAZORPAY_KEY_ID}"` (safe — publishable key only)
- [ ] Set real `RESEND_API_KEY=re_live_...` (from Resend dashboard)
- [ ] Set `RESEND_WEBHOOK_SECRET=whsec_...` (from Resend Dashboard → Webhooks → Signing Secret)
- [ ] Set `MAIL_MAILER=resend` (change from `log` used in local dev)
- [ ] Confirm `MAIL_FROM_ADDRESS=noreply@tracemem.one` and `MAIL_FROM_NAME="Trace.Mem"`
- [ ] Set real `OPENAI_API_KEY` (or `NVIDIA_KEY` if using NVIDIA NIM)
- [ ] Confirm **no secrets** (passwords, API keys, tokens) are present in:
  - Git commit history
  - Laravel log files (`storage/logs/`)
  - Server error pages (APP_DEBUG=false handles this)

---

## 🔴 Database

- [ ] Switch from SQLite to PostgreSQL or MySQL: set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Run seeders if needed: `php artisan db:seed --force`
- [ ] Verify no `DB::statement()` or raw string-concatenated queries exist (audit passed ✅)
- [ ] Enable SSL for the database connection if your provider requires it
- [ ] Confirm all migration tables exist, including:
  - `sessions`, `cache`, `jobs`, `failed_jobs`
  - `memories`, `api_keys`, `api_usage_logs`
  - `subscription_plans`, `subscription_plan_features`, `user_subscriptions`, `billing_transactions`
  - `razorpay_webhook_events` ✅ (idempotency table for Razorpay webhooks)
  - `email_logs` ✅ (tracks all sent/delivered/bounced transactional emails)
  - `resend_webhook_events` ✅ (idempotency table for Resend webhooks)
  - `stripe_webhook_events` (legacy — kept for schema completeness)

---

## 🔴 Session Security

- [ ] Set `SESSION_SECURE_COOKIE=true` — session cookie only sent over HTTPS
- [ ] Set `SESSION_HTTP_ONLY=true` — prevents JavaScript from reading the session cookie
- [ ] Set `SESSION_SAME_SITE=lax` — CSRF protection layer
- [ ] Set `SESSION_DRIVER=database` (already default — verify the `sessions` table was migrated)
- [ ] Set `SESSION_DOMAIN` to your production domain (e.g., `.tracemem.one`)

---

## 🔴 CSRF Protection

- [ ] All web routes use Laravel's web middleware group — CSRF is **automatic** ✅
- [ ] `/razorpay/webhook` is **exempted** from CSRF (uses Razorpay HMAC-SHA256 signature instead) ✅
- [ ] `/resend/webhook` is **exempted** from CSRF (uses Svix HMAC-SHA256 signature instead) ✅
- [ ] API routes (`/api/v1/*`) are **excluded** from CSRF — they use API-key bearer token auth ✅
- [ ] Verify Inertia's `X-XSRF-TOKEN` header is sent on all mutating requests (Inertia handles this automatically)

---

## 🔴 HTTPS & Security Headers

- [ ] TLS/SSL certificate installed and auto-renewing (Let's Encrypt / provider-managed)
- [ ] All HTTP traffic redirected to HTTPS at the web server level (Nginx/Apache config)
- [ ] Verify security headers are present in production responses (use [securityheaders.com](https://securityheaders.com)):
  - `Content-Security-Policy` ✅ (added via `SecurityHeaders` middleware — scoped to Razorpay domains)
  - `Strict-Transport-Security` ✅ (only applied over HTTPS — added via middleware)
  - `X-Frame-Options: SAMEORIGIN` ✅
  - `X-Content-Type-Options: nosniff` ✅
  - `Referrer-Policy: strict-origin-when-cross-origin` ✅
  - `Permissions-Policy` ✅ (camera, mic, geolocation, payment, usb, interest-cohort all disabled)
  - `Cross-Origin-Resource-Policy: same-origin` ✅
  - `Cross-Origin-Opener-Policy: same-origin-allow-popups` ✅
  - `Cross-Origin-Embedder-Policy: unsafe-none` ✅ (permissive for Razorpay modal iframe compat)
- [ ] Remove `Server:` and `X-Powered-By:` headers in Nginx/PHP-FPM config

**Nginx snippet to remove server headers:**
```nginx
server_tokens off;
fastcgi_hide_header X-Powered-By;
```

**CSP domains whitelisted for Razorpay (already wired in `SecurityHeaders` middleware):**
- `script-src`: `https://checkout.razorpay.com`
- `img-src`: `https://checkout.razorpay.com`
- `connect-src`: `https://api.razorpay.com`
- `frame-src`: `https://api.razorpay.com https://checkout.razorpay.com`

---

## 🔴 Password Security

- [ ] Password rules enforced in **all environments**: min 12 chars, uppercase, lowercase, digit, symbol ✅
- [ ] `BCRYPT_ROUNDS=12` set in `.env` ✅
- [ ] `Password::defaults()` in `AppServiceProvider` also adds `uncompromised()` check in production ✅
- [ ] `DB::prohibitDestructiveCommands()` enabled in production ✅ (prevents accidental `DROP`/`TRUNCATE` from Artisan)

---

## 🔴 Rate Limiting

- [ ] Login: 5 attempts/minute per email+IP ✅ (AuthController + Fortify RateLimiter)
- [ ] Register: 3 attempts/5 minutes per email+IP ✅ (AuthController)
- [ ] Password reset: handled by Fortify internally ✅
- [ ] Resend verification email: 5/10 minutes per user+IP ✅ (FortifyServiceProvider)
- [ ] Password change: `throttle:6,1` on the route ✅ (settings.php)
- [ ] API key rate limiting: per-key, per-endpoint, configurable window ✅ (ApiKeyAuthMiddleware)

---

## 🔴 API Key Security (Sandbox vs. Live)

- [ ] Sandbox (test) keys are **restricted to localhost + Postman only** — blocked in production environments ✅
- [ ] Live keys require **HTTPS** — plain HTTP requests are rejected ✅
- [ ] Live keys enforce an **allowed_origins** whitelist — requests from unlisted origins are rejected ✅
- [ ] `tmk_test_` prefix = sandbox key; `tmk_live_` prefix = live key — enforced by `ApiKeyService` ✅
- [ ] API keys are stored **hashed** in the database — plaintext is shown once and never stored ✅
- [ ] Key rotation issues a new key and immediately revokes the old one ✅

---

## 🔴 Import / Export Security

- [ ] Import: max 7 MB, max 500 memories, depth ≤ 5, UTF-8, no control chars, strict schema ✅
- [ ] Import: duplicate `content_hash` within same file rejected ✅
- [ ] Import resolve: only whitelisted fields updated (no mass-assignment) ✅
- [ ] Export: scoped to authenticated user AND their `tenant_scope_id` ✅

---

## 🔴 Redis & Queue

- [ ] Redis running and reachable (`php artisan tinker` → `Redis::ping()`)
- [ ] Set `CACHE_STORE=redis` and `QUEUE_CONNECTION=redis`
- [ ] Queue worker running via **Supervisor** (not just `queue:work` in a terminal):

```ini
# /etc/supervisor/conf.d/tracemem-worker.conf
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

> **⚠️ Important:** The queue order is `high,emails,default,low`. The `emails` queue must appear **before** `default` to ensure transactional email delivery is never starved by analytics jobs.

- [ ] After deploying: `supervisorctl reread && supervisorctl update && supervisorctl restart tracemem-worker:*`

**Queue priority reference:**

| Queue     | Jobs dispatched to it |
|-----------|----------------------|
| `high`    | `ProcessRazorpayWebhookJob` — billing-critical, never blocked |
| `emails`  | `SendEmailJob`, `ProcessResendWebhookJob` — all transactional email |
| `default` | `AggregateUsageStatsJob`, other notifications |
| `low`     | `ReinforceMemoriesJob`, `DecayMemoryJob`, `DecayMemoriesSweepJob`, cleanup |

---

## 🔴 Laravel Scheduler (Cron)

Add this **one line** to the server's crontab (`crontab -e` as the web user):

```cron
* * * * * cd /var/www/tracemem && php artisan schedule:run >> /dev/null 2>&1
```

**What the scheduler runs:**

| Time         | Command / Job                     | Purpose                                                  |
|--------------|-----------------------------------|----------------------------------------------------------|
| 02:00 daily  | `memory:archive-stale`            | Archives old, low-confidence memories                    |
| 02:00 daily  | `backup:database`                 | Creates compressed DB backup, prunes old ones            |
| 03:00 daily  | `DecayMemoriesSweepJob` (low)     | Memory decay scoring sweep (chunked, never full scan)    |
| 09:00 daily  | `email:api-key-expiry-reminders`  | Sends 7-day expiry reminder emails for API keys          |
| 09:00 daily  | `email:plan-expiry-reminders`     | Sends 7-day expiry reminder emails for active plans      |
| Every minute | Queue heartbeat                   | Writes Redis heartbeat key for `/api/v1/health`          |

- [ ] Cron entry added to server
- [ ] Run `php artisan schedule:list` to verify all tasks appear
- [ ] Run `php artisan schedule:run` manually once and check the Laravel log for output

---

## 🔴 Database Backup

- [ ] Set `BACKUP_DIR` to an absolute path with write permissions (e.g., `/var/backups/tracemem`)
- [ ] Set `BACKUP_RETENTION_DAYS=30` (or your desired retention)
- [ ] For **managed databases** (RDS, Supabase, Neon, PlanetScale): set `BACKUP_DRIVER=managed` — the command logs a reminder and exits cleanly, deferring to the provider's native backup system
- [ ] For **PostgreSQL**: ensure `pg_dump` is on the server's PATH: `which pg_dump`
- [ ] For **MySQL**: ensure `mysqldump` is on the server's PATH: `which mysqldump`
- [ ] Run the backup command manually and verify it succeeds:

```bash
php artisan backup:database
```

- [ ] Check Laravel log (`storage/logs/laravel.log`) confirms: `[backup:database] Backup completed successfully.`
- [ ] Verify the backup file exists in `BACKUP_DIR` and is non-zero size

### ⚠️ RESTORE TEST — Do This Before Launch

> **Many companies discover their backups never worked when they need them most.**
> Create a backup AND restore it before launch. This is not optional.

```bash
# PostgreSQL restore example:
gunzip -c /var/backups/tracemem/backup_YYYY_MM_DD_HH_II_SS.pgsql.gz \
  | psql -U tracemem_user tracemem_db

# MySQL restore example:
gunzip -c /var/backups/tracemem/backup_YYYY_MM_DD_HH_II_SS.sql.gz \
  | mysql -u tracemem_user -p tracemem_db

# SQLite restore example:
gunzip -c /var/backups/tracemem/backup_YYYY_MM_DD_HH_II_SS.sqlite.gz \
  > /tmp/restored.sqlite
```

- [ ] Restore one backup on staging or locally
- [ ] Spot-check a few records in the restored database to confirm data integrity
- [ ] Document the restore procedure in your team's runbook

---

## 🔴 Razorpay & Billing

- [ ] Switch from test mode to live mode in the Razorpay dashboard — use `rzp_live_` keys
- [ ] Register the production webhook URL in Razorpay dashboard: `https://tracemem.one/razorpay/webhook`
- [ ] Select all relevant events in the Razorpay webhook config:
  - `subscription.activated`
  - `subscription.charged`
  - `subscription.completed`
  - `subscription.cancelled`
  - `subscription.paused`
  - `subscription.resumed`
  - `payment.captured`
  - `payment.failed`
  - `refund.processed`
  - `order.paid` (optional)
- [ ] Copy the webhook signing secret to `RAZORPAY_WEBHOOK_SECRET` in `.env`
- [ ] Verify `razorpay.webhook` route is CSRF-exempt ✅ (excluded in `bootstrap/app.php` or `VerifyCsrfToken`)
- [ ] Verify webhook signature validation uses `hash_hmac('sha256', $rawBody, $secret)` + `hash_equals()` ✅
- [ ] Test a real (or test-mode) subscription end-to-end:
  1. Click a plan → Razorpay modal opens
  2. Complete payment → `verifyPayment` endpoint validates HMAC signature server-side
  3. `ProcessRazorpayWebhookJob` fires on the `high` queue, activates subscription
  4. Subscription confirmation email is dispatched via `SendEmailJob` on the `emails` queue
- [ ] Confirm `razorpay_webhook_events` table records idempotency entries (no duplicate processing)
- [ ] Verify `VITE_RAZORPAY_KEY_ID` is set — frontend reads this to initialise the Razorpay JS modal ✅

---

## 🔴 Resend & Transactional Email

- [ ] Verify `tracemem.one` domain in the Resend dashboard (add DNS records: SPF, DKIM, DMARC)
- [ ] Replace `RESEND_API_KEY` with your real `re_live_...` key
- [ ] Set `RESEND_WEBHOOK_SECRET=whsec_...` from Resend Dashboard → Webhooks → Signing Secret
- [ ] Set `MAIL_MAILER=resend` (was `log` in local dev)
- [ ] Register the production inbound webhook URL in Resend dashboard: `https://tracemem.one/resend/webhook`
- [ ] Select Resend webhook events to subscribe:
  - `email.sent`, `email.delivered`, `email.delivery_delayed`
  - `email.bounced`, `email.complained`
  - `email.opened`, `email.clicked` (analytics only — no business logic triggered)
- [ ] Verify `resend.webhook` route is CSRF-exempt ✅
- [ ] Verify webhook signature validation uses Svix HMAC-SHA256 (`svix-id.svix-timestamp.rawBody`) ✅
- [ ] Test all 14 transactional email templates fire correctly:
  - **Auth:** `verification`, `password_reset`, `password_changed`, `email_changed`
  - **Billing:** `subscription_purchased`, `subscription_renewed`, `subscription_cancelled`, `payment_received`, `payment_failed`, `refund_processed`, `plan_expiry_reminder`
  - **API:** `api_key_created`, `api_key_rotated`, `api_key_expiry_reminder`
- [ ] Confirm `email_logs` table records each email with `status = sent` after delivery
- [ ] Confirm `resend_webhook_events` table records idempotency entries (no duplicate processing)
- [ ] Confirm `email.opened` / `email.clicked` events update analytics only — never trigger account actions ✅

---

## 🔴 Laravel Production Hardening

Run on the server after **each deployment**:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
# or all at once:
php artisan optimize

php artisan storage:link
php artisan queue:restart   # gracefully restarts workers after deploy
```

- [ ] `config:cache` run — `.env` changes now require `php artisan config:clear` + re-cache
- [ ] `route:cache` run
- [ ] `view:cache` run
- [ ] `storage:link` run
- [ ] `queue:restart` run to pick up new code
- [ ] `storage/` and `bootstrap/cache/` are writable by the web user

---

## 🔴 Application Logs

- [ ] Set `LOG_CHANNEL=daily` and `LOG_LEVEL=warning` in production (reduce noise)
- [ ] Confirm log files do **NOT** contain passwords, API keys, or bearer tokens
- [ ] Set up log rotation or forwarding (Papertrail, Logtail, AWS CloudWatch)
- [ ] Monitor `[backup:database]` log entries to confirm nightly backups succeed
- [ ] Monitor `SendEmailJob: all retries exhausted` log entries — alert on this in production

---

## 🟡 Optional but Recommended

- [ ] Enable OPcache in PHP-FPM (`opcache.enable=1`, `opcache.validate_timestamps=0` in prod)
- [ ] Review CSP header on [csp-evaluator.withgoogle.com](https://csp-evaluator.withgoogle.com)
- [ ] Review headers on [securityheaders.com](https://securityheaders.com) — target grade A
- [ ] Set up uptime monitoring (BetterUptime, UptimeRobot) pointing at `/api/v1/health`
- [ ] Add `robots.txt` entries to block crawlers from `/dashboard`, `/api-keys`, `/billing`
- [ ] Enable Razorpay smart routing / fraud rules from the Razorpay dashboard
- [ ] Set up a separate Redis instance for queues (prevents cache eviction from killing queued jobs):
  - Queue Redis: no eviction policy (`REDIS_QUEUE_HOST`, `REDIS_QUEUE_PORT`)
  - Cache Redis: `allkeys-lru` eviction (`REDIS_CACHE_HOST`, `REDIS_CACHE_PORT`)
- [ ] Install Laravel Horizon for real-time queue monitoring (see README → Future Upgrade Path)

---

## ✅ Sign-Off

| Item                                    | Owner | Date | Notes |
|-----------------------------------------|-------|------|-------|
| All 🔴 items verified                   |       |      |       |
| Backup created and **restored**         |       |      |       |
| Razorpay live mode verified             |       |      |       |
| Razorpay webhook verified end-to-end    |       |      |       |
| Resend domain verified (SPF/DKIM/DMARC) |       |      |       |
| All 14 email templates tested           |       |      |       |
| SSL / HTTPS verified                    |       |      |       |
| Security headers verified (A grade)     |       |      |       |
| Cron + scheduler verified               |       |      |       |
| Queue worker via Supervisor (`high,emails,default,low`) | | | |
| Smoke-test: register → verify email → login → checkout → webhook → email → dashboard | | | |

---

*Generated for TraceMem — commit this file and update it with each major release.*
