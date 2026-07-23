# Scheduler

TraceMem utilises Laravel's Task Scheduler to run automated background tasks reliably via a single cron entry on the production server.

## Cron Entry

```bash
* * * * * cd /var/www/tracemem && php artisan schedule:run >> /dev/null 2>&1
```

## Scheduled Tasks

| Time | Task | Purpose |
|------|------|---------|
| 02:00 daily | `memory:archive-stale` | Archives low-confidence memories that haven't been accessed. |
| 02:00 daily | `backup:database` | Executes a compressed PostgreSQL database backup and handles auto-pruning. |
| 03:00 daily | `DecayMemoriesSweepJob` | Applies the memory decay scoring algorithm across all workspaces. |
| 09:00 daily | `email:api-key-expiry-reminders` | Sends 7-day advance API key expiry emails to owners. |
| 09:00 daily | `email:plan-expiry-reminders` | Sends 7-day advance plan expiry emails to owners. |
| Every minute | Queue heartbeat | Updates a Redis key (TTL 120s) to monitor worker health on `/api/v1/health`. |
