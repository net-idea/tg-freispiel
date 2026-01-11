#!/bin/bash

# Usage: ./yarn.sh encore dev --watch

set -euo pipefail

# Get environment variables
if [ -f .env ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source .env
  set +o allexport
fi

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-tg-freispiel}"
PROJECT_NAME="$APP_NAME"
SERVICE_NAME="node"

# Ensure Docker and the target container are running
if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose v2 is required. Please start Docker and the dev stack." >&2
  exit 1
fi

if ! docker compose -p "$PROJECT_NAME" ps "$SERVICE_NAME" --format json 2>/dev/null | grep -q '"State":"running"'; then
  echo "Service '$SERVICE_NAME' is not running. Start it with: ./docker-start.sh" >&2
  exit 1
fi

# Always run in Docker via service name to honor project flag
echo "🐳 Running Yarn command in Docker ($SERVICE_NAME service)..."
docker compose -p "$PROJECT_NAME" exec "$SERVICE_NAME" yarn "$@"
