#!/bin/bash

# Initialize database locally or via Docker Compose
# Usage: ./database-init.sh [--engine mariadb|postgres] [--local] [--reset]

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ -f "$PROJECT_DIR/.env" ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source "$PROJECT_DIR/.env"
  set +o allexport
fi

APP_NAME="${APP_NAME:-Theatergruppe Freispiel}"
ENGINE="${DB:-mariadb}" # mariadb or postgres
MODE="compose" # or local
RESET_DB=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --engine)
      ENGINE="$2"; shift 2 ;;
    --local)
      MODE="local"; shift ;;
    --reset)
      RESET_DB=true; shift ;;
    -h|--help)
      echo "Usage: $0 [--engine mariadb|postgres] [--local] [--reset]"; exit 0 ;;
    *)
      echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

# Load compose args and services from docker-list.sh (single source of truth)
if [ "$MODE" != "local" ]; then
  if [ ! -x "$PROJECT_DIR/docker-list.sh" ]; then
    echo "Missing $PROJECT_DIR/docker-list.sh. Please ensure it exists and is executable." >&2
    exit 1
  fi
  COMPOSE_ARGS_RAW="$($PROJECT_DIR/docker-list.sh --compose-args)"
  # shellcheck disable=SC2206
  COMPOSE_ARGS=( $COMPOSE_ARGS_RAW )
  if [ "$ENGINE" = "postgres" ]; then
    DB_SERVICE="postgres"
  else
    DB_SERVICE="mariadb"
  fi
fi

if $RESET_DB && [ "$MODE" = "local" ]; then
  echo "--reset is only supported in compose mode (it drops Docker volumes)." >&2
  exit 1
fi

run_php() {
  if [ "$MODE" = "local" ]; then
    php "$@"
  else
    docker compose "${COMPOSE_ARGS[@]}" exec php php "$@"
  fi
}

wait_for_db() {
  local tries=${1:-60}
  local delay=${2:-1}
  if [ "$ENGINE" = "postgres" ]; then
    local check_cmd=(docker compose "${COMPOSE_ARGS[@]}" exec -T "$DB_SERVICE" pg_isready -U "${POSTGRES_USER:-Theatergruppe Freispiel}")
  else
    local pw="${DB_ROOT_PASSWORD:-nopassword}"
    local check_cmd=(docker compose "${COMPOSE_ARGS[@]}" exec -T "$DB_SERVICE" sh -lc "(command -v mariadb >/dev/null 2>&1 && mariadb -uroot -p\"$pw\" -h\"$DB_SERVICE\" -e 'SELECT 1' >/dev/null) || (command -v mysql >/dev/null 2>&1 && mysql -uroot -p\"$pw\" -h\"$DB_SERVICE\" -e 'SELECT 1' >/dev/null)")
  fi
  for ((i=1; i<=tries; i++)); do
    if "${check_cmd[@]}" >/dev/null 2>&1; then
      return 0
    fi
    sleep "$delay"
  done
  return 1
}

if [ "$MODE" = "local" ]; then
  echo "Using local PHP/DB (DATABASE_URL-driven)."
else
  if $RESET_DB; then
    echo "Resetting database stack (compose down -v + removing local data dirs)..."
    docker compose "${COMPOSE_ARGS[@]}" down -v || true
    rm -rf "$PROJECT_DIR"/mariadb/data "$PROJECT_DIR"/mariadb/log "$PROJECT_DIR"/mariadb/backup
    rm -rf "$PROJECT_DIR"/postgresql/data "$PROJECT_DIR"/postgresql/log "$PROJECT_DIR"/postgresql/backup
  fi
  echo "Ensuring DB and PHP services are up ($ENGINE)..."
  # If php is missing, start full stack similar to docker-start
  if ! docker compose "${COMPOSE_ARGS[@]}" ps php --format json 2>/dev/null | grep -q '"State":"running"'; then
    docker compose "${COMPOSE_ARGS[@]}" up -d --build --force-recreate
  else
    docker compose "${COMPOSE_ARGS[@]}" up -d --build --force-recreate "$DB_SERVICE" php
  fi
  if ! wait_for_db 120 1; then
    echo "Database did not become ready in time." >&2
    exit 1
  fi
fi

echo "Running migrations..."
run_php bin/console doctrine:migrations:migrate --no-interaction

echo "✅ Database ready (engine=$ENGINE, mode=$MODE)."
