#!/bin/bash

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Load .env if present
if [ -f "$PROJECT_DIR/.env" ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source "$PROJECT_DIR/.env"
  set +o allexport
fi

PROJECT_NAME="${APP_NAME:-tg-freispiel}"

if [ ! -x "$PROJECT_DIR/docker-list.sh" ]; then
  echo "Missing $PROJECT_DIR/docker-list.sh. Please ensure it exists and is executable." >&2
  exit 1
fi

COMPOSE_ARGS_RAW="$($PROJECT_DIR/docker-list.sh --compose-args)"
# shellcheck disable=SC2206
COMPOSE_ARGS=( $COMPOSE_ARGS_RAW )

read -r -p "Delete Docker stack for project '$PROJECT_NAME' (containers, networks, volumes)? [y/N] " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
  echo "Aborted."
  exit 0
fi

echo "Stopping and removing project stack '$PROJECT_NAME'..."
docker compose "${COMPOSE_ARGS[@]}" down -v

echo "Removing mariadb and postgresql data and logs..."
rm -rf mariadb/data mariadb/log
mkdir -p mariadb/data mariadb/log
:> mariadb/data/.gitignore
:> mariadb/log/.gitignore

rm -rf postgresql/data postgresql/log
mkdir -p postgresql/data postgresql/log
:> postgresql/data/.gitignore
:> postgresql/log/.gitignore

echo "Done."
