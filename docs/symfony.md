# Symfony commands and troubleshooting

This document lists common Symfony commands used in this project and troubleshooting tips.

## Common commands (host)

```bash
# Clear cache
php bin/console cache:clear

# Create a new migration
php bin/console make:migration

# Run migrations
php bin/console doctrine:migrations:migrate

# Create a new controller
php bin/console make:controller

# Create a new entity
php bin/console make:entity

# Validate database schema
php bin/console doctrine:schema:validate

# Show migration status
php bin/console doctrine:migrations:status

# Run tests
php bin/phpunit
```

## Common commands (Docker)

```bash
# Clear cache
docker compose exec web php bin/console cache:clear

# Create a new migration
docker compose exec web php bin/console make:migration

# Run migrations
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction

# Validate database schema
docker compose exec web php bin/console doctrine:schema:validate

# Run tests
docker compose exec web php bin/phpunit
```

## Troubleshooting (Symfony)

- Cache issues
  - Clear cache: `php bin/console cache:clear`
  - If using prod mode, add: `--env=prod --no-debug`

- .env configuration
  - Ensure correct `APP_ENV`, `DATABASE_URL`, and `APP_SECRET`.
  - Use `.env.local` for local overrides.

- Rate limiter configuration
  - See: `config/packages/rate_limiter.yaml`

- Asset build issues
  - Reinstall node modules: `yarn install` (or `npm ci`)
  - Rebuild: `yarn encore dev` or `yarn build`

For Docker-specific or database-specific issues, see:
- Docker: `docs/docker.md`
- Database: `docs/database.md`
