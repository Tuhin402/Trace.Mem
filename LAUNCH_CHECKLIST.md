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
- [ ] Replace all `_test_` Stripe keys with live keys (`pk_live_...`, `sk_live_...`)
- [ ] Generate and set `STRIPE_WEBHOOK_SECRET` via the Stripe dashboard (not the CLI listener)
- [ ] Set real `OPENAI_API_KEY` (or `NVIDIA_KEY` if using NVIDIA NIM)
- [ ] Set real SMTP credentials (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`)
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
- [ ] `/stripe/webhook` is **exempted** from CSRF (uses Stripe HMAC-SHA256 signature instead) ✅
- [ ] API routes (`/api/v1/*`) are **excluded** from CSRF — they use API-key bearer token auth ✅
- [ ] Verify Inertia's `X-XSRF-TOKEN` header is sent on all mutating requests (Inertia handles this automatically)

---

## 🔴 HTTPS & Security Headers

- [ ] TLS/SSL certificate installed and auto-renewing (Let's Encrypt / provider-managed)
- [ ] All HTTP traffic redirected to HTTPS at the web server level (Nginx/Apache config)
- [ ] Verify security headers are present in production responses (use [securityheaders.com](https://securityheaders.com)):
  - `Content-Security-Policy` ✅ (added via `SecurityHeaders` middleware)
  - `Strict-Transport-Security` ✅ (only applied over HTTPS — added via middleware)
  - `X-Frame-Options: SAMEORIGIN` ✅
  - `X-Content-Type-Options: nosniff` ✅
  - `Referrer-Policy: strict-origin-when-cross-origin` ✅
  - `Permissions-Policy` ✅
  - `Cross-Origin-Resource-Policy: same-origin` ✅
  - `Cross-Origin-Opener-Policy: same-origin-allow-popups` ✅
  - `Cross-Origin-Embedder-Policy: unsafe-none` ✅ (permissive for Stripe iframe compat)
- [ ] Remove `Server:` and `X-Powered-By:` headers in Nginx/PHP-FPM config

**Nginx snippet to remove server headers:**
```nginx
server_tokens off;
fastcgi_hide_header X-Powered-By;
```

---

## 🔴 Password Security

- [ ] Password rules enforced in **all environments**: min 12 chars, uppercase, lowercase, digit, symbol ✅
- [ ] `BCRYPT_ROUNDS=12` set in `.env` ✅
- [ ] `Password::defaults()` in `AppServiceProvider` also adds `uncompromised()` check in production ✅

---

## 🔴 Rate Limiting

- [ ] Login: 5 attempts/minute per email+IP ✅ (AuthController + Fortify RateLimiter)
- [ ] Register: 3 attempts/5 minutes per email+IP ✅ (AuthController)
- [ ] Password reset: handled by Fortify internally ✅
- [ ] Resend verification email: 5/10 minutes per user+IP ✅ (FortifyServiceProvider)
- [ ] Password change: `throttle:6,1` on the route ✅ (settings.php)
- [ ] API key rate limiting: per-key, per-endpoint, configurable window ✅ (ApiKeyAuthMiddleware)

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
command=php /var/www/tracemem/artisan queue:work redis --queue=high,default,low --sleep=3 --tries=3 --max-time=3600
directory=/var/www/tracemem
autostart=true
autorestart=true
user=www-data
numprocs=2
stderr_logfile=/var/log/tracemem-worker.err.log
stdout_logfile=/var/log/tracemem-worker.out.log
```

- [ ] After deploying: `supervisorctl reread && supervisorctl update && supervisorctl restart tracemem-worker:*`

---

## 🔴 Laravel Scheduler (Cron)

Add this **one line** to the server's crontab (`crontab -e` as the web user):

```cron
* * * * * cd /var/www/tracemem && php artisan schedule:run >> /dev/null 2>&1
```

**What the scheduler runs:**

| Time         | Command                  | Purpose                                        |
|--------------|--------------------------|------------------------------------------------|
| 02:00 daily  | `memory:archive-stale`   | Archives old, low-confidence memories          |
| 02:00 daily  | `backup:database`        | Creates compressed DB backup, prunes old ones  |
| 03:00 daily  | `DecayMemoriesSweepJob`  | Memory decay scoring sweep                     |
| Every minute | Queue heartbeat          | Writes Redis heartbeat key for `/api/v1/health`|

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

## 🔴 Stripe & Billing

- [ ] Switch from test mode to live mode in the Stripe dashboard
- [ ] Register the production webhook URL in Stripe: `https://tracemem.one/stripe/webhook`
- [ ] Select events: `checkout.session.completed`, `customer.subscription.*`, `invoice.*`
- [ ] Copy the signing secret to `STRIPE_WEBHOOK_SECRET`
- [ ] Test a real (or test-mode) subscription end-to-end
- [ ] Verify `stripe.webhook` route is CSRF-exempt and Stripe signature-validated ✅

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

---

## 🟡 Optional but Recommended

- [ ] Enable OPcache in PHP-FPM (`opcache.enable=1`, `opcache.validate_timestamps=0` in prod)
- [ ] Review CSP header on [csp-evaluator.withgoogle.com](https://csp-evaluator.withgoogle.com)
- [ ] Review headers on [securityheaders.com](https://securityheaders.com) — target grade A
- [ ] Set up uptime monitoring (BetterUptime, UptimeRobot) pointing at `/api/v1/health`
- [ ] Add `robots.txt` entries to block crawlers from `/dashboard`, `/api-keys`, `/billing`
- [ ] Enable Stripe Radar rules to reduce billing fraud
- [ ] Set up a separate Redis instance for queues (prevents cache eviction from killing queued jobs)

---

## ✅ Sign-Off

| Item                               | Owner | Date | Notes |
|------------------------------------|-------|------|-------|
| All 🔴 items verified              |       |      |       |
| Backup created and **restored**    |       |      |       |
| Stripe live mode verified          |       |      |       |
| SSL / HTTPS verified               |       |      |       |
| Security headers verified (A grade)|       |      |       |
| Cron + scheduler verified          |       |      |       |
| Queue worker via Supervisor        |       |      |       |
| Smoke-test: register → verify → login → dashboard → API call | | | |

---

*Generated for TraceMem — commit this file and update it with each major release.*
