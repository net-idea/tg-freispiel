#!/bin/bash

# Get environment variables
if [ -f .env ]; then
  set -o allexport
  source .env
  set +o allexport
fi

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-tg-freispiel}"
PROJECT_NAME="$APP_NAME"
DB="${DB:-mariadb}" # mariadb or postgres

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
  -f docker-compose.phpmyadmin.yml
)

trap 'echo -e "${YELLOW}Stopping containers...${NC}"; docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" down; exit 0' INT TERM

echo -e "${GREEN}Starting Docker development environment...${NC}"

# Start services
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" up -d --build --force-recreate

# Wait for database to be healthy
echo -e "${YELLOW}Waiting for database to be ready...${NC}"
DB_WAIT_MAX=120
DB_WAIT=0
if [ "$DB" = "postgres" ]; then
  TARGET_SERVICE=postgres
  until docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" ps "$TARGET_SERVICE" --format json 2>/dev/null | grep -q '"State":"running"'; do
    echo -n "."
    sleep 1
    DB_WAIT=$((DB_WAIT+1))
    if [ $DB_WAIT -ge $DB_WAIT_MAX ]; then
      echo ""; echo -e "${RED}Database did not become ready within ${DB_WAIT_MAX}s.${NC}"; exit 1
    fi
  done
else
  TARGET_SERVICE=mariadb
  until [ "$(docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" ps "$TARGET_SERVICE" --format json 2>/dev/null | grep -o '"Health":"[^\"]*"' | cut -d'"' -f4)" = "healthy" ] || \
        [ "$(docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" ps "$TARGET_SERVICE" --format json 2>/dev/null | grep -o '"State":"[^\"]*"' | cut -d'"' -f4)" = "running" ]; do
      echo -n "."
      sleep 1
      DB_WAIT=$((DB_WAIT+1))
      if [ $DB_WAIT -ge $DB_WAIT_MAX ]; then
        echo ""; echo -e "${RED}Database did not become ready within ${DB_WAIT_MAX}s.${NC}"; exit 1
      fi
  done
fi

echo ""
echo -e "${GREEN}Database is ready!${NC}"

# Run Composer install (if not done)
echo -e "${YELLOW}Running Composer install...${NC}"
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php composer install --prefer-dist --no-progress

# Run Yarn install
echo -e "${YELLOW}Running Yarn install...${NC}"
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" run --rm node yarn install

# Clear & warmup cache
echo -e "${YELLOW}Clearing cache...${NC}"
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php php bin/console cache:clear --no-warmup
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php php bin/console cache:warmup

# Migrations
if [ -d "migrations" ] && [ "$(ls -A migrations/*.php 2>/dev/null | wc -l)" -gt 0 ]; then
    echo
    echo -e "${YELLOW}Migrations found. Run them? (y/N)${NC}"
    read -r response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}Running migrations...${NC}"
        docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" exec php php bin/console doctrine:migrations:migrate --no-interaction
    fi
fi

# Start Yarn watch in node service
echo -e "${GREEN}Starting Yarn Encore watch...${NC}"
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" up -d node

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
echo -e "${YELLOW} → phpMyAdmin: $pma_url${NC}"
echo
echo -e "${YELLOW}Press Ctrl+C to stop.${NC}"

# Keep script alive
tail -f /dev/null
