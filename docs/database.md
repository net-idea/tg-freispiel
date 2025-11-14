# Database troubleshooting

This document covers common database-related issues and tips.

## MariaDB shell (Docker)

```bash
docker compose exec mariadb mysql -u huette9 -phuette9pass huette9
```

## Common issues

- Cannot connect to database
  - Ensure containers are running: `docker compose ps`
  - Check database logs: `docker compose logs mariadb`
  - Check health: `docker compose exec mariadb healthcheck.sh --connect`

- Schema validation fails
  - Run: `php bin/console doctrine:schema:validate`
  - Fix entity mappings and re-run migrations.

- Migration problems
  - Check status: `php bin/console doctrine:migrations:status`
  - If a migration failed, you may need to fix it and re-run with `--no-interaction`.
