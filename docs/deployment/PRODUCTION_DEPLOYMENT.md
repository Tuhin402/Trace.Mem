# Production Deployment

Deploying TraceMem to production requires configuring several moving parts to ensure high availability, secure database connections, and background job processing.

## Pre-requisites

- **PHP 8.3+**
- **PostgreSQL** (Required for production; SQLite is only for local/tests)
- **Redis** (Required for queue management and caching)
- **Node.js v18+ & npm** (For building frontend assets)

## One-Time Deployment Command

If deploying the Workspace Architecture Phase A migrations for the first time, run the following commands sequentially:

```bash
php artisan migrate
php artisan workspace:backfill
```

> **CRITICAL WARNING:** Do not schedule `workspace:backfill`. It is a one-time data migration. New records created after this deployment automatically have `workspace_id` set at creation time. Running it again will cause errors.

## Standard Deployment Flow

For every subsequent deployment, run the following:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm install && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```
