# Queues

TraceMem uses Redis to manage robust, priority-driven background queues. We divide workload across four strict priorities to ensure critical paths (like billing and emails) are never blocked by heavy batch processing (like memory decay).

## Priority Levels

| Queue | Handled Jobs | Priority |
|-------|--------------|----------|
| `high` | `ProcessRazorpayWebhookJob` | 5 retries, exponential backoff. Reserved for critical billing webhooks. |
| `emails` | `SendEmailJob`, `ProcessResendWebhookJob` | 3–5 retries. Isolates external HTTP requests to Resend. |
| `default` | `AggregateUsageStatsJob` | Standard Laravel defaults. |
| `low` | `ReinforceMemoriesJob`, `DecayMemoryJob`, `DecayMemoriesSweepJob` | Runs memory decay and reinforcement without blocking active API traffic. |

## Supervisor Configuration

In production, Laravel's queue workers are managed by Supervisor:

```ini
[program:tracemem-worker]
command=php /var/www/tracemem/artisan queue:work redis --queue=high,emails,default,low --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
```

## Managing Failed Jobs

```bash
php artisan queue:failed
php artisan queue:retry {id}
php artisan queue:retry all
php artisan queue:prune-failed --hours=168
```
