#!/usr/bin/env bash

# Usage: ./yarn.sh encore dev --watch

set -euo pipefail

# Get environment variables
if [ -f .env ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source .env
  set +o allexport
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_NAME="node"

# Ensure Docker and the target container are running
if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose v2 is required. Please start Docker and the dev stack." >&2
  exit 1
fi

if ! "$SCRIPT_DIR/docker.sh" raw --dev -- ps "$SERVICE_NAME" --format json 2>/dev/null | grep -q '"State":"running"'; then
  echo "Service '$SERVICE_NAME' is not running. Start it with: ./docker.sh up --dev" >&2
  exit 1
fi

# Always run in Docker via service name to honor project flag
echo "🐳 Running Yarn command in Docker ($SERVICE_NAME service)..."
"$SCRIPT_DIR/docker.sh" exec --dev "$SERVICE_NAME" yarn "$@"
