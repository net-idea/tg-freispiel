#!/bin/bash

set -euo pipefail

# Stop the TG Freispiel compose stack (project-scoped).
# If you want to stop *all* containers on your machine, use `docker stop $(docker ps -q)` manually.

# Load .env if present
if [ -f .env ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source .env
  set +o allexport
fi

PROJECT_NAME="${APP_NAME:-tg-freispiel}"

echo "Stopping docker compose stack '$PROJECT_NAME'..."
docker compose -p "$PROJECT_NAME" down
