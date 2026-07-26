# Tenant Architecture

TraceMem uses a UUID-based canonical boundary for multi-tenancy.

## Canonical UUID (`tenant_scope_id`)
The single source of truth for the organization boundary across the platform is the UUID, historically known as `tenant_scope_id`.
All relationships to the tenant (Users, API Keys, Memories) use this UUID to determine ownership and boundaries.

In the `tenants` database table, this UUID is stored directly in the `id` column.

This architectural decision guarantees:
1. Perfect backward compatibility with the legacy `tenant_scope_id` system.
2. Zero schema modifications to massive production tables like `users` and `memories`.
3. High performance via string indexing without the need for integer foreign key lookups.

## Backfill Processes
Because the explicit `tenants` table was introduced *after* the platform was in production, a backfill process was established to safely create explicit `Tenant` records matching the existing `tenant_scope_id`s found across the platform.

The `BackfillTenantsCommand` operates via:
1. **Union Discovery**: Queries all unique UUIDs across `users`, `api_keys`, and `memories`.
2. **Deduplication**: Resolves the unique set of UUIDs to prevent collisions.
3. **Deterministic Slugging**: If `tenant_scope_id = '9f31...'` and the company is `Acme`, the slug is safely generated as `acme-9f31` to completely avoid duplicate slug exceptions.
4. **Idempotency**: Utilizes `Tenant::firstOrCreate()` to safely insert or ignore records.
5. **Configurable Chunks**: Executes in configurable `--chunk=500` batches to manage RAM consumption.

## Rollback & Recovery
If an issue occurs during or after the backfill process:

### Migration Rollback
Because existing tables (`users`, `teams`, `api_keys`) were completely untouched by the new schema, you can safely drop the `tenants` table without impacting any customer-facing code.
```bash
php artisan migrate:rollback --step=1
```
All existing authentication, API token resolution, and memory scoping will continue to use the `tenant_scope_id` stored on their respective tables.

### Backfill Recovery
If the backfill script crashes halfway through due to an unexpected production anomaly (e.g. database timeout):
1. Resolve the underlying timeout/connection issue.
2. Re-run the command: `php artisan trace:backfill-tenants`.
3. The command will instantly skip all successfully backfilled tenants and automatically resume on the missing ones without creating duplicates.