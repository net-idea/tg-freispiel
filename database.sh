#!/bin/bash

# Get environment variables
if [ -f .env ]; then
  set -o allexport
  source .env
  set +o allexport
fi

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-unisurf}"
PROJECT_NAME="$APP_NAME"
ENGINE="${DB:-mariadb}"
COMPOSE_FILES=()

while [[ $# -gt 0 ]]; do
  case "$1" in
    --engine)
      ENGINE="$2"; shift 2 ;;
    -h|--help)
      echo "Usage: $0 [--engine mariadb|postgres]"; exit 0 ;;
    *)
      echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

if [ "$ENGINE" = "postgres" ]; then
  COMPOSE_FILES+=(
    -f docker-compose.postgresql.yml
    -f docker-compose.postgresql.dev.yml
  )
  DB_SERVICE=postgres
else
  COMPOSE_FILES+=(
    -f docker-compose.mariadb.yml
    -f docker-compose.mariadb.dev.yml
  )
  DB_SERVICE=mariadb
fi

echo "Starting DB stack ($ENGINE)..."
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" up -d --build --force-recreate

# quick state info
docker compose -p "$PROJECT_NAME" "${COMPOSE_FILES[@]}" ps "$DB_SERVICE"
