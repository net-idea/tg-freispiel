#!/bin/bash

# Get environment variables
if [ -f .env ]; then
  set -o allexport
  source .env
  set +o allexport
fi

set -euo pipefail

# Options
DETACH=0
BUILD=0
usage() {
  echo "Usage: ./docker-start.sh [options]"
  echo "  -d, --detach   Start containers and exit; containers keep running in the background"
  echo "      --build    Rebuild images before starting (slower, more output)"
  echo "  -h, --help     Show this help"
}
while [ $# -gt 0 ]; do
  case "$1" in
    -d|--detach) DETACH=1 ;;
    --build) BUILD=1 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown option: $1"; usage; exit 1 ;;
  esac
  shift
done

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-tg-freispiel}"
PROJECT_NAME="$APP_NAME"
DB="${DB:-mariadb}" # mariadb/mysql or postgres

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

COMPOSE_FILES=(
  -f docker-compose.yaml
  -f docker-compose.dev.yaml
)

if [ "$DB" = "postgres" ]; then
  COMPOSE_FILES+=(
    -f docker-compose.postgresql.yml
    -f docker-compose.postgresql.dev.yml
  )
else
  COMPOSE_FILES+=(
    -f docker-compose.mariadb.yml
    -f docker-compose.mariadb.dev.yml
  )
fi

COMPOSE_FILES+=(
  -f docker-compose.adminer.yml
)

if [ "$DB" != "postgres" ]; then
  COMPOSE_FILES+=(
    -f docker-compose.phpmyadmin.yml
  )
fi

trap 'echo -e "${YELLOW}Stopping containers...${NC}"; docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" down; exit 0' INT TERM

echo -e "${GREEN}Starting Docker development environment...${NC}"

# Start services
DB_WAIT_MAX=120
if [ "$DB" = "postgres" ]; then
  DB_SERVICE=postgres
  DB_PORT_INTERNAL=5432
  DB_DSN="postgresql://${DB_USER:-tg-freispiel}:${DB_PASSWORD:-nopassword}@postgres:${DB_PORT_INTERNAL}/${DB_NAME:-tg-freispiel}?serverVersion=17&charset=utf8"
else
  DB_SERVICE=mariadb
  DB_PORT_INTERNAL="${DB_PORT:-3308}"
  DB_DSN="mysql://${DB_USER:-tg-freispiel}:${DB_PASSWORD:-nopassword}@mariadb:${DB_PORT_INTERNAL}/${DB_NAME:-tg-freispiel}?serverVersion=11.8.0-MariaDB&charset=utf8mb4"
fi

UP_ARGS=(up -d --wait --wait-timeout "$DB_WAIT_MAX" --quiet-pull)
if [ "$BUILD" = "1" ]; then
  UP_ARGS+=(--build)
fi
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" "${UP_ARGS[@]}"

echo -e "${GREEN}Database is ready!${NC}"

# Run Composer install (if not done)
echo -e "${YELLOW}Running Composer install...${NC}"
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php composer install --prefer-dist --no-progress

# Clear & warmup cache
echo -e "${YELLOW}Clearing cache...${NC}"
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php php bin/console cache:clear --no-warmup
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php php bin/console cache:warmup

# Migrations
if [ -t 0 ] && [ -d "migrations" ] && [ "$(ls -A migrations/*.php 2>/dev/null | wc -l)" -gt 0 ]; then
    echo
    echo -e "${YELLOW}Migrations found. Run them? (y/N)${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Running migrations...${NC}"
        docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec -e DATABASE_URL="$DB_DSN" php php bin/console doctrine:migrations:migrate --no-interaction
    fi
fi

# Endpoint summary from env defaults
APP_PORT="${APP_PORT:-8000}"
NODE_PORT="${NODE_PORT:-8080}"
ADMINER_PORT="${ADMINER_PORT:-8091}"
PHPMYADMIN_PORT="${PHPMYADMIN_PORT:-8092}"
MAILER_WEB_PORT="${MAILER_WEB_PORT:-8025}"

app_url="http://127.0.0.1:${APP_PORT:-8000}"
assets_url="http://127.0.0.1:${NODE_PORT:-8080}"
mail_url="http://127.0.0.1:${MAILER_WEB_PORT:-8025}"
adminer_url="http://127.0.0.1:${ADMINER_PORT:-8091}"
pma_url="http://127.0.0.1:${PHPMYADMIN_PORT:-8092}"

echo
echo -e "${GREEN}Development environment is running and provides following endpoints:${NC}"
echo -e "${YELLOW} → App:        $app_url${NC}"
echo -e "${YELLOW} → Assets:     $assets_url (via Yarn)${NC}"
echo -e "${YELLOW} → DB service: $DB_SERVICE (internal)${NC}"
echo -e "${YELLOW} → Mailpit:    $mail_url${NC}"
echo -e "${YELLOW} → Adminer:    $adminer_url${NC}"
if [ "$DB" != "postgres" ]; then
  echo -e "${YELLOW} → phpMyAdmin: $pma_url${NC}"
fi
echo

if [ "$DETACH" = "1" ]; then
  echo -e "${YELLOW}Running detached. Stop with: docker compose -p $PROJECT_NAME down${NC}"
  exit 0
fi

echo -e "${YELLOW}Press Ctrl+C to stop.${NC}"

# Keep script alive
tail -f /dev/null
