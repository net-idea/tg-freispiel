# Database Configuration and Troubleshooting

## DATABASE_URL examples

- SQLite (default per environment):
  - `DATABASE_URL="sqlite:///%kernel.project_dir%/var/data_%kernel.environment%.db"`
- MariaDB/MySQL:
  - `DATABASE_URL="mysql://user:pass@127.0.0.1:3306/db?serverVersion=10.11.2-MariaDB&charset=utf8mb4"`
- PostgreSQL:
  - `DATABASE_URL="postgresql://user:pass@127.0.0.1:5432/db?serverVersion=16&charset=utf8"`

## Migrations and schema

```bash
# Create the database (if not exists)
php bin/console doctrine:database:create

# Apply migrations
php bin/console doctrine:migrations:migrate
```

## Troubleshooting (Database)

### Connection errors

1. Ensure the DB service is running
   - Local: verify your MariaDB/MySQL/PostgreSQL server is up
   - Docker: `docker compose ps`
2. Check credentials and host/port in `.env.local`
3. Verify `DATABASE_URL` matches your server version and charset
4. If ports were changed, reflect them in the URL

### Resetting local database (danger)

```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

For Symfony usage and local dev workflow, see `docs/symfony.md`. For running via containers, see `docs/docker.md`.
# Docker Setup and Development

This guide covers installing Docker, running the project with Docker Compose, and troubleshooting common issues.

## Requirements

- Docker Desktop (macOS) or Docker Engine + Docker Compose (Ubuntu/Linux)
- Docker Compose v2.0 or higher

## Install Docker

### macOS

1. Download Docker Desktop for Mac: https://www.docker.com/products/docker-desktop
2. Install and start Docker Desktop
3. Verify installation:

```bash
docker --version
docker compose version
```

### Ubuntu

```bash
# Update package index
sudo apt-get update

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add your user to docker group
sudo usermod -aG docker $USER

# Install Docker Compose plugin
sudo apt-get install docker-compose-plugin

# Verify installation
docker --version
docker compose version
```

## Clone the Repository

```bash
git clone https://github.com/net-idea/tg-freispiel.git
cd tg-freispiel
```

## Configure Environment

Copy the environment file and adjust values if needed (e.g., database, mail):

```bash
cp .env.dist .env.local
```

For available keys and examples, see the comments in `.env.dist`.

## Run with Docker Compose

```bash
# Build and start containers (in background)
docker compose up -d

# Install PHP dependencies (if not already installed in the image)
docker compose exec web composer install

# Run database migrations (non-interactive)
docker compose exec web php bin/console doctrine:migrations:migrate --no-interaction
```

## Access the Application

- Website: http://localhost:8000
- Database (host machine): localhost:3306
  - User: `app`
  - Password: `!ChangeMe!`
  - Database: `app`

## Stop the Application

```bash
docker compose down

# Remove volumes as well (WARNING: deletes persistent data)
docker compose down -v
```

## Troubleshooting (Docker)

### Port already in use (web 8000 or DB 3306)

Update port mappings in `compose.override.yaml`:

- Web: use `"8080:8000"` instead of `"8000:8000"`
- DB: use `"3307:3306"` instead of `"3306:3306"`

Then restart:

```bash
docker compose down && docker compose up -d
```

### Check container status

```bash
docker compose ps
docker compose logs -f web
```

### Database container not reachable

- Ensure containers are running: `docker compose ps`
- Confirm your app `.env.local` uses the correct `DATABASE_URL`
- If you changed ports, reflect them in your connection string

For additional Symfony commands and local development workflow, see `docs/symfony.md`.
