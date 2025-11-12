# Local Development (Symfony)

This document covers local development without Docker, common Symfony commands, helper scripts, and troubleshooting.

## Requirements

- PHP 8.2 or higher
- Composer
- MariaDB 11.5 or MySQL 8.0 (or SQLite)
- PHP extensions: `pdo_mysql`, `mbstring`, `xml`, `zip`, `intl`
- Node.js + Yarn or npm (for building assets)
- Symfony CLI (optional, recommended)

## Setup and Run (Local)

```bash
# Install PHP dependencies
composer install

# Create local environment config
cp .env .env.local

# Edit .env.local and configure your database
# Example for MariaDB/MySQL:
# DATABASE_URL="mysql://username:password@127.0.0.1:3306/database_name?serverVersion=mariadb-11.5.0&charset=utf8mb4"

# Create database and run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Start the local web server (choose one)
php -S localhost:8000 -t public
# or (recommended)
symfony server:start
```

## Common Symfony and Project Commands

```bash
# Clear cache (current env)
php bin/console cache:clear

# Warm up cache (prod)
php bin/console cache:warmup --env=prod

# Generate a new APP_SECRET
php bin/console regenerate-app-secret

# Doctrine
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Run tests
php bin/phpunit

# Security checks
composer audit
composer update
```

## Helper Scripts

### develop.sh

A convenience script that prepares and starts the dev environment:

- Installs Node dependencies if `node_modules/` is missing (uses Yarn)
- Installs PHP dependencies if `vendor/` is missing (Composer)
- Clears Symfony cache
- Builds assets with Webpack Encore (watch mode)
- Starts Webpack and the Symfony local server in the background

Usage:

```bash
./develop.sh
```

Notes:
- Requires Yarn and (optionally) Symfony CLI on your PATH
- Webpack dev server: http://localhost:8080
- Symfony server: http://localhost:8000
- Press Ctrl+C to stop both processes

### deploy.sh

Production build/deploy helper. It:

1. Installs Node deps (yarn or npm)
2. Builds production assets (Encore)
3. Installs Composer deps (no-dev)
4. Runs database migrations (unless `SKIP_MIGRATIONS=true`)
5. Clears and warms Symfony prod cache

Usage:

```bash
# Normal deployment
./deploy.sh

# Skip DB migrations
SKIP_MIGRATIONS=true ./deploy.sh

# Skip Composer auto-scripts during install
SKIP_COMPOSER_AUTOSCRIPTS=true ./deploy.sh
```

Environment defaults:

```bash
APP_ENV=prod
APP_DEBUG=0
```

## Troubleshooting (Symfony)

### Permission issues on Linux/Ubuntu

```bash
sudo chown -R $USER:$USER var/
chmod -R 775 var/
```

### Symfony server port already in use

Start on a different port:

```bash
symfony server:start --port=8001
```

### Spam getting through the contact form

The honeypot should stop most bots. If needed, consider:
- Add additional JS-based validation
- Rate limit form submissions
- Add time-based validation (reject too-quick submissions)

For Docker-related topics, see `docs/docker.md`. For database-specific topics, see `docs/database.md`.
