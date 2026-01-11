#!/bin/bash

# Usage: ./docker-php bin/console app:list:contacts

# Get environment variables
if [ -f .env ]; then
  set -o allexport
  source .env
  set +o allexport
fi

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-tg-freispiel}"
PROJECT_NAME="$APP_NAME"
SERVICE_NAME="php"
CONTAINER_NAME="${APP_NAME}_php"

# Ensure Docker and the target container are running
if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose v2 is required. Please start Docker and the dev stack." >&2
  exit 1
fi

# Prefer compose service status with project flag; fall back to name match
if ! docker compose -p "$PROJECT_NAME" ps "$SERVICE_NAME" --format json 2>/dev/null | grep -q '"State":"running"'; then
  if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER_NAME"; then
    echo "Container $CONTAINER_NAME is not running. Start it with: docker compose -p ${PROJECT_NAME} up -d" >&2
    exit 1
  fi
fi

# Always run in Docker via service name to honor project flag
echo "🐳 Running Symfony command in Docker..."
docker compose -p "$PROJECT_NAME" exec "$SERVICE_NAME" php "$@"
