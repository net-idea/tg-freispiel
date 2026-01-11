# Docker Development Guide

This guide covers installing Docker and running the tg-freispiel project with Docker Compose. Use Docker if you want a reproducible environment without installing PHP/Node locally.

## Prerequisites

### Install Docker

#### macOS

Install Docker Desktop for Mac (recommended):

```bash
brew install --cask docker
```

Or download manually: https://www.docker.com/products/docker-desktop

#### Ubuntu/Debian

```bash
# Install Docker Engine
sudo apt-get update
sudo apt-get install -y apt-transport-https ca-certificates curl software-properties-common
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
sudo apt-get update
sudo apt-get install -y docker-ce docker-ce-cli containerd.io

# Use Docker without sudo
sudo usermod -aG docker $USER
newgrp docker

# Install Docker Compose plugin
sudo apt-get install -y docker-compose-plugin
```

## Quick Start

### Using the helper script (recommended)

The easiest way to start the development environment:

```bash
./docker-start.sh
```

This script will:

1. Start all necessary containers (PHP, Nginx, Node, MariaDB, Adminer, PHPMyAdmin)
2. Wait for the database to be ready
3. Install Composer dependencies
4. Install Yarn dependencies
5. Clear and warm up Symfony cache
6. Prompt to run migrations (if any exist)
7. Start Webpack Encore in watch mode

Press `Ctrl+C` to stop all containers.

### Manual Docker Compose commands

If you prefer manual control:

#### Basic web application (no database)

```bash
docker compose -p tg-freispiel -f docker-compose.yaml up -d --build
```

This starts:

- PHP-FPM container
- Nginx web server (port 8000)
- Node container with Webpack Encore (port 8080)

#### With MariaDB database

```bash
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.mariadb.yml up -d --build
```

Adds:

- MariaDB server (internal network only)
- Automatic database backups

#### With MariaDB and admin tools

```bash
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.mariadb.yml -f docker-compose.adminer.yml -f docker-compose.phpmyadmin.yml up -d --build
```

Adds:

- Adminer (port 8091)
- PHPMyAdmin (port 8092)

#### Expose MariaDB port (for external connections)

```bash
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.mariadb.yml -f docker-compose.mariadb.dev.yml up -d --build
```

This exposes MariaDB on port 3306 for external tools like TablePlus, DBeaver, etc.

## Optional Services

### Redis (caching/sessions)

```bash
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.redis.yml up -d
```

Update your `.env.local`:

```env
# For sessions
SESSION_HANDLER_ID=redis://redis:6379

# For cache
CACHE_DSN=redis://redis:6379

# For lock
LOCK_DSN=redis://redis:6379
```

### Memcached (caching)

```bash
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.memcache.yml up -d
```

Update your `.env.local`:

```env
CACHE_DSN=memcached://memcached:11211
```

### PostgreSQL (alternative to MariaDB)

```bash
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.postgresql.yml up -d
```

Update your `.env.local`:

```env
DATABASE_URL="postgresql://tg-freispiel:nopassword@postgres:5432/tg-freispiel?serverVersion=16&charset=utf8"
```

## Docker Services Overview

### Core Services

| Service | Description                 | Port | Access                |
| ------- | --------------------------- | ---- | --------------------- |
| php     | PHP 8.3 FPM                 | -    | Internal only         |
| nginx   | Nginx web server            | 8000 | http://127.0.0.1:8000 |
| node    | Node.js 20 (Webpack Encore) | 8080 | http://127.0.0.1:8080 |

### Database Services

| Service            | Description         | Port   | Access                                     |
| ------------------ | ------------------- | ------ | ------------------------------------------ |
| database (MariaDB) | MariaDB database    | 3306\* | Internal (or exposed with mariadb.dev.yml) |
| postgres           | PostgreSQL database | 5432\* | Internal                                   |

\*Only exposed to host with `.dev.yml` variants

### Database Admin Tools

| Service    | Description          | Port | Access                |
| ---------- | -------------------- | ---- | --------------------- |
| adminer    | Lightweight DB admin | 8091 | http://127.0.0.1:8091 |
| phpmyadmin | MySQL/MariaDB admin  | 8092 | http://127.0.0.1:8092 |

### Caching Services

| Service   | Description          | Port  | Access        |
| --------- | -------------------- | ----- | ------------- |
| redis     | Redis cache/sessions | 6379  | Internal only |
| memcached | Memcached cache      | 11211 | Internal only |

## Environment Configuration

Create or edit `.env.local` to configure:

```env
# Application
APP_ENV=dev
APP_SECRET=your-secret-here

# Docker
APP_NAME=tg-freispiel
NGINX_PORT=8000
NODE_PORT=8080
ADMINER_PORT=8091
PHPMYADMIN_PORT=8092

# Database
DB_HOST=database
DB_PORT=3306
DB_ROOT_PASSWORD=nopassword
DB_NAME=tg-freispiel
DB_USER=tg-freispiel
DB_PASSWORD=nopassword

# Symfony Database URL (when using MariaDB)
DATABASE_URL="mysql://tg-freispiel:nopassword@database:3306/tg-freispiel?serverVersion=10.11.2-MariaDB&charset=utf8mb4"

# Or use SQLite (default)
DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_dev.db"
```

## Common Docker Commands

### Container management

```bash
# Check running containers
docker compose -p tg-freispiel ps

# View logs
docker compose -p tg-freispiel logs -f

# View logs for specific service
docker compose -p tg-freispiel logs -f php
docker compose -p tg-freispiel logs -f nginx
docker compose -p tg-freispiel logs -f database

# Stop all containers
docker compose -p tg-freispiel down

# Stop and remove volumes (WARNING: deletes database data)
docker compose -p tg-freispiel down -v

# Restart a specific service
docker compose -p tg-freispiel restart php
```

### Execute commands in containers

```bash
# PHP container
docker compose -p tg-freispiel exec php bash
docker compose -p tg-freispiel exec php php bin/console cache:clear
docker compose -p tg-freispiel exec php composer install

# Node container
docker compose -p tg-freispiel run --rm node yarn install
docker compose -p tg-freispiel run --rm node yarn build

# Database container
docker compose -p tg-freispiel exec database mysql -u tg-freispiel -pnopassword tg-freispiel
docker compose -p tg-freispiel exec database mysqldump -u tg-freispiel -pnopassword tg-freispiel > backup.sql
```

### Build and rebuild

```bash
# Rebuild PHP container (after Dockerfile changes)
docker compose -p tg-freispiel build php

# Force rebuild all containers
docker compose -p tg-freispiel up -d --build --force-recreate

# Pull latest images
docker compose -p tg-freispiel pull
```

## Helper Scripts

### docker-start.sh

Interactive startup script that handles the complete initialization:

- Starts core services + MariaDB + admin tools
- Waits for database readiness
- Installs dependencies
- Clears cache
- Prompts for migrations
- Starts asset watcher

```bash
./docker-start.sh
```

### docker-stop.sh

Stops all running Docker containers (not project-specific):

```bash
./docker-stop.sh
```

**WARNING**: This stops ALL containers on your system, not just this project!

### docker-delete.sh

**DANGER**: Nuclear option - stops all containers, removes all images, networks, and volumes:

```bash
./docker-delete.sh
```

**USE WITH EXTREME CAUTION**: This will delete ALL Docker data on your system!

## Troubleshooting

### Port already in use

If ports 8000, 8080, 8091, or 8092 are already in use, change them in `.env.local`:

```env
NGINX_PORT=9000
NODE_PORT=9080
ADMINER_PORT=9091
PHPMYADMIN_PORT=9092
```

### Database connection refused

Wait for database to be ready:

```bash
# Check database status
docker compose -p tg-freispiel exec database mysqladmin ping -h"database" --silent

# View database logs
docker compose -p tg-freispiel logs database
```

### Permission issues with volumes

On Linux, you may need to fix file permissions:

```bash
# From host
sudo chown -R $USER:$USER var/

# Or run as root in container
docker compose -p tg-freispiel exec -u root php chown -R www-data:www-data /var/www/html/var
```

### Clear all Docker cache

If you encounter strange build issues:

```bash
# Stop containers
docker compose -p tg-freispiel down

# Clean build cache
docker builder prune -a

# Rebuild from scratch
docker compose -p tg-freispiel up -d --build --force-recreate
```

### Node modules issues

If Yarn has issues inside the container:

```bash
# Remove node_modules and reinstall
docker compose -p tg-freispiel run --rm node rm -rf node_modules
docker compose -p tg-freispiel run --rm node yarn install
```

### Database fixtures not loading

Fixtures are auto-loaded on first database creation. To reload:

```bash
# Stop and remove database volume
docker compose -p tg-freispiel down -v

# Place SQL files in mariadb/fixtures/
mkdir -p mariadb/fixtures
cp your-dump.sql mariadb/fixtures/

# Restart - fixtures will auto-load
docker compose -p tg-freispiel -f docker-compose.yaml -f docker-compose.mariadb.yml up -d
```

## Production Deployment

For production, DO NOT use these Docker Compose files as-is. Consider:

1. Use production-grade images (Alpine-based, security-hardened)
2. Use Docker secrets for sensitive data
3. Use proper orchestration (Kubernetes, Docker Swarm)
4. Implement proper backup strategies
5. Use separate networks for services
6. Enable HTTPS with proper certificates
7. Set resource limits
8. Use health checks
9. Implement log aggregation
10. Regular security updates

See the main `readme.md` and `deploy.sh` for production deployment strategies.

## Access URLs Summary

When running the full stack:

- **Application**: http://127.0.0.1:8000
- **Webpack Dev Server**: http://127.0.0.1:8080
- **Adminer**: http://127.0.0.1:8091
- **PHPMyAdmin**: http://127.0.0.1:8092

Database connection (from external tools when using `mariadb.dev.yml`):

- **Host**: localhost
- **Port**: 3306
- **User**: tg-freispiel
- **Password**: nopassword
- **Database**: tg-freispiel

````

## Database migrations (Docker)

```bash
# Create a new migration (if needed)
docker compose exec web php bin/console make:migration

# Run migrations
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
````

## Useful Docker commands

```bash
# Stop containers
docker compose down

# View logs
docker compose logs -f

# Shell into the web container
docker compose exec web bash

# Rebuild images from scratch
docker compose build --no-cache
```

## Troubleshooting (Docker)

- Port 8000 already in use
  - Change the port mapping in `compose.yaml` (e.g. "8080:8000") or stop the conflicting service.

- Container does not start
  - Check logs: `docker compose logs web` and `docker compose logs mariadb`
  - Rebuild containers: `docker compose down && docker compose build --no-cache && docker compose up -d`

- Permissions problems on Linux
  - Adjust ownership: `sudo chown -R $USER:$USER .`
  - Ensure cache directory is writable: `chmod -R 777 var/`

- Database connection errors
  - See Database troubleshooting: `docs/database.md`
