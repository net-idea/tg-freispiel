#!/bin/bash

# Colors for output
GREEN=$'\033[0;32m'
YELLOW=$'\033[1;33m'
RED=$'\033[0;31m'
BLUE=$'\033[0;34m'
NC=$'\033[0m' # No Color

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Load env vars if present
if [ -f "$PROJECT_DIR/.env" ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source "$PROJECT_DIR/.env"
  set +o allexport
fi

PROJECT_NAME="${APP_NAME:-tg-freispiel}"
DB="${DB:-mariadb}"

# Resolve compose args + service list via docker-list.sh (single source of truth)
if [ ! -x "$PROJECT_DIR/docker-list.sh" ]; then
  echo "Missing $PROJECT_DIR/docker-list.sh. Please ensure it exists and is executable." >&2
  exit 1
fi

COMPOSE_ARGS_RAW="$($PROJECT_DIR/docker-list.sh --compose-args)"

# shellcheck disable=SC2206
COMPOSE_ARGS=( $COMPOSE_ARGS_RAW )

# shellcheck disable=SC2034
SERVICES=()
while IFS= read -r svc; do
  SERVICES+=("$svc")
done < <($PROJECT_DIR/docker-list.sh --services)

# Identify DB service name
if [ "$DB" = "postgres" ]; then
  DB_SERVICE="postgres"
else
  DB_SERVICE="mariadb"
fi

require_docker() {
  if ! command -v docker >/dev/null 2>&1 || ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is required. Please start Docker." >&2
    exit 1
  fi
}

get_container_id() {
  local svc="$1"
  docker compose "${COMPOSE_ARGS[@]}" ps -q "$svc" 2>/dev/null || true
}

get_state() {
  local cid="$1"
  docker inspect -f '{{.State.Status}}' "$cid" 2>/dev/null || true
}

get_health() {
  local cid="$1"
  docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{end}}' "$cid" 2>/dev/null || true
}

retry() {
  local max_seconds="$1"; shift
  local i

  for ((i=0; i<max_seconds; i++)); do
    if "$@"; then
      return 0
    fi
    sleep 1
  done

  return 1
}

check_mariadb_ready() {
  # Validate that we can run a trivial query as root.
  # Use service hostname inside compose network.
  local pw="${DB_ROOT_PASSWORD:-nopassword}"
  docker compose "${COMPOSE_ARGS[@]}" exec -T "$DB_SERVICE" sh -lc "(command -v mariadb >/dev/null 2>&1 && mariadb -uroot -p\"$pw\" -h\"$DB_SERVICE\" -e 'SELECT 1' >/dev/null) || (command -v mysql >/dev/null 2>&1 && mysql -uroot -p\"$pw\" -h\"$DB_SERVICE\" -e 'SELECT 1' >/dev/null)"
}

check_postgres_ready() {
  local user="${POSTGRES_USER:-tg-freispiel}"
  docker compose "${COMPOSE_ARGS[@]}" exec -T "$DB_SERVICE" pg_isready -U "$user" >/dev/null 2>&1
}

require_docker

if ! docker compose "${COMPOSE_ARGS[@]}" ps --format json >/dev/null 2>&1; then
  echo "Stack '$PROJECT_NAME' not found or docker compose failed." >&2
  exit 1
fi

echo
echo " TG Freispiel stack health check"
echo " Project : $PROJECT_NAME"
echo " DB      : $DB ($DB_SERVICE)"
echo " Compose : ${COMPOSE_ARGS[*]}"
echo

echo "Services:"
status=0
ok_count=0
bad_count=0

db_check_time_start=$(date +%s)

for svc in "${SERVICES[@]}"; do
  cid="$(get_container_id "$svc")"
  if [ -z "$cid" ]; then
    icon="❌"
    color="$RED"
    status_msg="missing container"
    ((bad_count++))
    status=1
  else
    state="$(get_state "$cid")"
    health="$(get_health "$cid")"

    if [ -z "$health" ]; then
      health="n/a"
      health_defined=false
    else
      health_defined=true
    fi

    if [ "$state" != "running" ]; then
      icon="⚠️"
      color="$RED"
      status_msg="state=$state, health=$health"
      ((bad_count++))
      status=1
    elif $health_defined && [ "$health" != "healthy" ]; then
      icon="⚠️"
      color="$YELLOW"
      status_msg="state=$state, health=$health"
      ((bad_count++))
      status=1
    else
      icon="✅"
      color="$GREEN"
      status_msg="state=$state, health=$health"
      ((ok_count++))
    fi

    # DB readiness check
    if [ "$svc" = "$DB_SERVICE" ]; then
      if [ "$DB" = "postgres" ]; then
        if ! retry 30 check_postgres_ready; then
          icon="⚠️"
          color="$RED"
          status_msg="$status_msg (DB readiness failed)"
          ((bad_count++))
          status=1
        fi
      else
        if ! retry 30 check_mariadb_ready; then
          icon="⚠️"
          color="$RED"
          status_msg="$status_msg (DB readiness failed)"
          ((bad_count++))
          status=1
        fi
      fi
      db_check_time_end=$(date +%s)
    fi
  fi

  printf ' → %s %-12s %s\n' "$icon" "$svc" "$color$status_msg$NC"
done

db_check_time_end=$(date +%s)
db_check_duration=$((db_check_time_end - db_check_time_start))

echo
echo "Summary: OK=$ok_count, FAIL=$bad_count"
echo "DB readiness time: ${db_check_duration}s"
echo

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

echo -e "${GREEN}Development environment is running and provides following endpoints:${NC}"
echo -e "${YELLOW} → App:        $app_url${NC}"
echo -e "${YELLOW} → Assets:     $assets_url (via Yarn)${NC}"
echo -e "${YELLOW} → DB service: $DB_SERVICE (internal)${NC}"
echo -e "${YELLOW} → Mailpit:    $mail_url${NC}"
echo -e "${YELLOW} → Adminer:    $adminer_url${NC}"
echo -e "${YELLOW} → phpMyAdmin: $pma_url${NC}"
echo

if [ $status -eq 0 ]; then
  echo "✅ All services are healthy."
else
  echo "❌ Some services are not healthy."
fi

exit $status
