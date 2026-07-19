#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Get environment variables
if [ -f "$SCRIPT_DIR/.env" ]; then
  set -o allexport
  source "$SCRIPT_DIR/.env"
  set +o allexport
fi

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-Theatergruppe Freispiel}"
ENGINE="${DB:-mariadb}"

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
  DB_SERVICE=postgres
else
  DB_SERVICE=mariadb
fi

echo "Starting DB stack ($ENGINE)..."
"$SCRIPT_DIR/docker.sh" raw --dev --db "$ENGINE" -- up -d --build --force-recreate "$DB_SERVICE"

# quick state info
"$SCRIPT_DIR/docker.sh" raw --dev --db "$ENGINE" -- ps "$DB_SERVICE"
