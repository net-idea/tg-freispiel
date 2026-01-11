#!/bin/bash

# docker-list.sh
#
# Single source of truth for:
# - compose file selection (mariadb vs postgres)
# - expected service list
# - machine-friendly compose args output
#
# Usage:
#   ./docker-list.sh                 # human readable overview
#   ./docker-list.sh --services      # print services (one per line)
#   ./docker-list.sh --compose-args  # print args usable as: docker compose $(...) ps
#

set -euo pipefail

# Load .env if present
if [ -f .env ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source .env
  set +o allexport
fi

APP_NAME="${APP_NAME:-tg-freispiel}"
PROJECT_NAME="$APP_NAME"
DB="${DB:-mariadb}" # mariadb | postgres

compose_files=(
  docker-compose.yaml
  docker-compose.dev.yaml
)

if [ "$DB" = "postgres" ]; then
  compose_files+=(
    docker-compose.postgresql.yml
    docker-compose.postgresql.dev.yml
  )
  DB_SERVICE="postgres"
else
  compose_files+=(
    docker-compose.mariadb.yml
    docker-compose.mariadb.dev.yml
  )
  DB_SERVICE="mariadb"
fi

compose_files+=(
  docker-compose.adminer.yml
  docker-compose.phpmyadmin.yml
)

services=(php nginx node "$DB_SERVICE" adminer phpmyadmin mailer)

print_services() {
  for s in "${services[@]}"; do
    echo "$s"
  done
}

print_compose_args() {
  printf -- "-p %q" "$PROJECT_NAME"
  for f in "${compose_files[@]}"; do
    printf -- " -f %q" "$f"
  done
  echo ""
}

print_overview() {
  echo "Project:   $PROJECT_NAME"
  echo "DB engine: $DB"
  echo ""
  echo "Compose files:"
  for f in "${compose_files[@]}"; do
    echo "  - $f"
  done
  echo ""
  echo "Services:"
  for s in "${services[@]}"; do
    echo "  - $s"
  done
}

case "${1:-}" in
  --services)
    print_services
    ;;
  --compose-args)
    print_compose_args
    ;;
  "")
    print_overview
    ;;
  *)
    echo "Unknown option: $1" >&2
    echo "Usage: $0 [--services|--compose-args]" >&2
    exit 2
    ;;
esac
