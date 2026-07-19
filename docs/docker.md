# Docker Guide

This project uses a single Docker CLI wrapper: `./docker.sh`.
It orchestrates all `docker compose` calls and keeps compose file resolution in one place.

## Quick start

```bash
# start production-like stack (detached)
./docker.sh up

# start development stack (loads *.dev.yml)
./docker.sh up --dev

# attached mode
./docker.sh up --dev --attach
```

## Core commands

```bash
./docker.sh build
./docker.sh up [--attach] [--dev]
./docker.sh down [--dev]
./docker.sh restart [SERVICE]
./docker.sh pull
./docker.sh update
./docker.sh destroy [--rmi-local]
./docker.sh ps
./docker.sh logs [SERVICE]
./docker.sh exec SERVICE
./docker.sh test
./docker.sh config
```

## Optional services via profiles

Enable optional services per command:

```bash
./docker.sh up --dev --adminer --phpmyadmin
./docker.sh up --redis
./docker.sh up --memcache
./docker.sh up --profile redis
```

Available profiles:

- `adminer`
- `phpmyadmin`
- `redis`
- `memcache`

## Port block schema (15000)

Host port mappings use env vars with 15000-block defaults directly in compose files:

- `APP_PORT=15080` (nginx)
- `NODE_PORT=15081` (encore dev server)
- `MAILER_WEB_PORT=15082` (mailpit UI)
- `ADMINER_PORT=15083`
- `PHPMYADMIN_PORT=15084`
- `DB_HOST_PORT=15085` (MariaDB host mapping)
- `POSTGRES_HOST_PORT=15086` (PostgreSQL host mapping)
- `MAILER_SMTP_PORT=15087` (mailpit SMTP)

`DB_PORT` remains the internal DB service port (MariaDB default `3308`).

## Compose file strategy

- `docker-compose*.yml` / `docker-compose*.yaml`: base/prod-safe config
- `docker-compose*.dev.yml` / `docker-compose.dev.yaml`: local overrides only

Resolution order:

1. `docker-compose.yaml`
2. `docker-compose.dev.yaml` (only with `--dev`)
3. DB base (`docker-compose.mariadb.yml` or `docker-compose.postgresql.yml`)
4. DB dev override (only with `--dev`)
5. Optional compose fragments (profile-gated services)

## Compatibility wrappers

Legacy scripts are wrappers to preserve existing entry points:

- `docker-start.sh` → `docker.sh up --dev`
- `docker-stop.sh` → `docker.sh down`
- `docker-delete.sh` → `docker.sh destroy`
- `docker-list.sh` → `docker.sh config`
- `docker-test.sh` → `docker.sh test`
- `develop.sh` provides local mode (no Docker) and Docker mode (`--docker`)
