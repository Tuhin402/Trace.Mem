# Database Backups

TraceMem automates database backups to prevent data loss.

## Scheduled Backups

Every day at **02:00 server time**, the Laravel Scheduler triggers the `backup:database` command.
This process:
1. Creates a full, compressed `pg_dump` of the production PostgreSQL database.
2. Moves the archive to secure, redundant storage.
3. Automatically prunes old backups to maintain the defined retention policy.

## Restoring Backups

In the event of a catastrophic failure, administrators can download the compressed backup file and restore it using standard PostgreSQL tooling (`pg_restore`).
