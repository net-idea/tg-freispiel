#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"

PROJECT_NAME_DEFAULT="tg-freispiel"
DB_ENGINE_DEFAULT="mariadb"
USE_DEV=0
ATTACH_MODE=0
UP_BUILD=0
DESTROY_RMI_LOCAL=0
LOG_FOLLOW=1
DB_ENGINE=""

COMPOSE_FILES=()
COMPOSE_ARGS=()
COMPOSE_PROFILES=()

COMMAND="${1:-help}"
shift || true

load_env() {
  if [ -f "$PROJECT_DIR/.env" ]; then
    set -o allexport
    # shellcheck disable=SC1091
    source "$PROJECT_DIR/.env"
    set +o allexport
  fi
}

print_help() {
  cat <<'EOF'
Usage:
  ./docker.sh <command> [options] [args]

Commands:
  build                 Build images (without starting containers)
  up                    Start stack (default: detached)
  down                  Stop and remove containers
  restart [SERVICE...]  Restart all or selected services
  pull                  Pull images
  update                Pull + build + up
  destroy               Down with volumes (+ optional --rmi local)
  ps                    Show container status
  status                Alias for ps
  logs [SERVICE]        Follow logs (-f)
  exec SERVICE [CMD...] Open shell in service (bash -> sh fallback)
  test                  Run basic stack health checks
  config                Show resolved docker compose config
  raw ...               Run raw docker compose subcommand (internal helper)
  help                  Show this help

Common options:
  --dev                 Include *.dev.yml compose overrides
  --db mariadb|postgres Select DB stack (default from DB env)
  --profile NAME        Enable compose profile (repeatable)
  --redis               Shortcut for --profile redis
  --memcache            Shortcut for --profile memcache
  --adminer             Shortcut for --profile adminer
  --phpmyadmin          Shortcut for --profile phpmyadmin

Command options:
  up:
    -a, --attach        Run attached (foreground)
    -d, --detach        Run detached (default)
    --build             Build before start

  logs:
    --no-follow         Disable log following

  destroy:
    --rmi-local         Add --rmi local
EOF
}

require_docker() {
  if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is not installed." >&2
    exit 1
  fi

  if ! docker compose version >/dev/null 2>&1; then
    echo "Docker Compose v2 is required (docker compose)." >&2
    exit 1
  fi
}

add_profile() {
  local profile="$1"
  local existing

  for existing in "${COMPOSE_PROFILES[@]}"; do
    if [ "$existing" = "$profile" ]; then
      return 0
    fi
  done

  COMPOSE_PROFILES+=("$profile")
}

parse_args() {
  local positional=()
  DB_ENGINE="${DB:-$DB_ENGINE_DEFAULT}"

  while [ $# -gt 0 ]; do
    case "$1" in
      --dev)
        USE_DEV=1
        ;;
      --db)
        shift
        if [ $# -eq 0 ]; then
          echo "Missing value for --db (mariadb|postgres)." >&2
          exit 2
        fi
        DB_ENGINE="$1"
        ;;
      --profile)
        shift
        if [ $# -eq 0 ]; then
          echo "Missing value for --profile." >&2
          exit 2
        fi
        add_profile "$1"
        ;;
      --redis)
        add_profile "redis"
        ;;
      --memcache)
        add_profile "memcache"
        ;;
      --adminer)
        add_profile "adminer"
        ;;
      --phpmyadmin)
        add_profile "phpmyadmin"
        ;;
      -a|--attach)
        ATTACH_MODE=1
        ;;
      -d|--detach)
        ATTACH_MODE=0
        ;;
      --build)
        UP_BUILD=1
        ;;
      --rmi-local)
        DESTROY_RMI_LOCAL=1
        ;;
      --no-follow)
        LOG_FOLLOW=0
        ;;
      -h|--help)
        print_help
        exit 0
        ;;
      --)
        shift
        while [ $# -gt 0 ]; do
          positional+=("$1")
          shift
        done
        break
        ;;
      *)
        positional+=("$1")
        ;;
    esac
    shift || true
  done

  if [ "$DB_ENGINE" = "mysql" ]; then
    DB_ENGINE="mariadb"
  fi

  case "$DB_ENGINE" in
    mariadb|postgres)
      ;;
    *)
      echo "Invalid DB engine '$DB_ENGINE'. Use mariadb or postgres." >&2
      exit 2
      ;;
  esac

  if [ "$USE_DEV" -eq 1 ]; then
    add_profile "adminer"
    if [ "$DB_ENGINE" = "mariadb" ]; then
      add_profile "phpmyadmin"
    fi
  fi

  set -- "${positional[@]}"
  POSITIONAL_ARGS=("$@")
}

build_compose_args() {
  PROJECT_NAME="${APP_NAME:-$PROJECT_NAME_DEFAULT}"

  COMPOSE_FILES=(
    "docker-compose.yaml"
  )

  if [ "$USE_DEV" -eq 1 ]; then
    COMPOSE_FILES+=("docker-compose.dev.yaml")
  fi

  if [ "$DB_ENGINE" = "postgres" ]; then
    COMPOSE_FILES+=("docker-compose.postgresql.yml")
    if [ "$USE_DEV" -eq 1 ]; then
      COMPOSE_FILES+=("docker-compose.postgresql.dev.yml")
    fi
  else
    COMPOSE_FILES+=("docker-compose.mariadb.yml")
    if [ "$USE_DEV" -eq 1 ]; then
      COMPOSE_FILES+=("docker-compose.mariadb.dev.yml")
    fi
  fi

  COMPOSE_FILES+=(
    "docker-compose.adminer.yml"
    "docker-compose.redis.yml"
    "docker-compose.memcache.yml"
  )

  if [ "$DB_ENGINE" = "mariadb" ]; then
    COMPOSE_FILES+=("docker-compose.phpmyadmin.yml")
  fi

  COMPOSE_ARGS=(
    -p "$PROJECT_NAME"
  )

  local file
  for file in "${COMPOSE_FILES[@]}"; do
    COMPOSE_ARGS+=(-f "$file")
  done

  local profile
  for profile in "${COMPOSE_PROFILES[@]}"; do
    COMPOSE_ARGS+=(--profile "$profile")
  done
}

compose() {
  docker compose "${COMPOSE_ARGS[@]}" "$@"
}

cmd_build() {
  compose build
}

cmd_up() {
  local args=(up --remove-orphans)

  if [ "$ATTACH_MODE" -eq 0 ]; then
    args+=(-d)
  fi

  if [ "$UP_BUILD" -eq 1 ]; then
    args+=(--build)
  fi

  compose "${args[@]}"
}

cmd_down() {
  compose down --remove-orphans
}

cmd_restart() {
  if [ "${#POSITIONAL_ARGS[@]}" -eq 0 ]; then
    compose restart
  else
    compose restart "${POSITIONAL_ARGS[@]}"
  fi
}

cmd_pull() {
  compose pull
}

cmd_update() {
  compose pull
  compose build
  cmd_up
}

cmd_destroy() {
  local args=(down --volumes --remove-orphans)

  if [ "$DESTROY_RMI_LOCAL" -eq 1 ]; then
    args+=(--rmi local)
  fi

  compose "${args[@]}"
}

cmd_ps() {
  compose ps
}

cmd_logs() {
  local args=(logs)

  if [ "$LOG_FOLLOW" -eq 1 ]; then
    args+=(-f)
  fi

  if [ "${#POSITIONAL_ARGS[@]}" -gt 0 ]; then
    args+=("${POSITIONAL_ARGS[0]}")
  fi

  compose "${args[@]}"
}

cmd_exec() {
  if [ "${#POSITIONAL_ARGS[@]}" -lt 1 ]; then
    echo "Usage: ./docker.sh exec SERVICE [CMD...]" >&2
    exit 2
  fi

  local service="${POSITIONAL_ARGS[0]}"

  if [ "${#POSITIONAL_ARGS[@]}" -gt 1 ]; then
    compose exec "$service" "${POSITIONAL_ARGS[@]:1}"
    return
  fi

  compose exec "$service" sh -lc 'command -v bash >/dev/null 2>&1 && exec bash || exec sh'
}

cmd_test() {
  local services=()
  local service
  local cid
  local state
  local health
  local status=0
  local ok_count=0
  local fail_count=0

  while IFS= read -r service; do
    [ -n "$service" ] && services+=("$service")
  done < <(compose ps --services)

  if [ "${#services[@]}" -eq 0 ]; then
    echo "No running services found for project '$PROJECT_NAME'."
    exit 1
  fi

  echo "Project: $PROJECT_NAME"
  echo "DB: $DB_ENGINE"
  echo

  for service in "${services[@]}"; do
    cid="$(compose ps -q "$service")"

    if [ -z "$cid" ]; then
      printf '❌ %-16s missing\n' "$service"
      fail_count=$((fail_count + 1))
      status=1
      continue
    fi

    state="$(docker inspect -f '{{.State.Status}}' "$cid" 2>/dev/null || true)"
    health="$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}n/a{{end}}' "$cid" 2>/dev/null || true)"

    if [ "$state" = "running" ] && { [ "$health" = "healthy" ] || [ "$health" = "n/a" ]; }; then
      printf '✅ %-16s state=%s health=%s\n' "$service" "$state" "$health"
      ok_count=$((ok_count + 1))
    else
      printf '⚠️  %-16s state=%s health=%s\n' "$service" "${state:-unknown}" "${health:-unknown}"
      fail_count=$((fail_count + 1))
      status=1
    fi
  done

  echo
  echo "Summary: OK=$ok_count FAIL=$fail_count"
  exit "$status"
}

cmd_config() {
  compose config
}

cmd_raw() {
  if [ "${#POSITIONAL_ARGS[@]}" -eq 0 ]; then
    echo "Usage: ./docker.sh raw -- <docker compose args...>" >&2
    exit 2
  fi

  compose "${POSITIONAL_ARGS[@]}"
}

main() {
  load_env

  if [ "$COMMAND" = "help" ] || [ "$COMMAND" = "-h" ] || [ "$COMMAND" = "--help" ]; then
    print_help
    exit 0
  fi

  parse_args "$@"
  build_compose_args
  require_docker

  case "$COMMAND" in
    build)
      cmd_build
      ;;
    up)
      cmd_up
      ;;
    down)
      cmd_down
      ;;
    restart)
      cmd_restart
      ;;
    pull)
      cmd_pull
      ;;
    update)
      cmd_update
      ;;
    destroy)
      cmd_destroy
      ;;
    ps|status)
      cmd_ps
      ;;
    logs)
      cmd_logs
      ;;
    exec)
      cmd_exec
      ;;
    test)
      cmd_test
      ;;
    config)
      cmd_config
      ;;
    raw)
      cmd_raw
      ;;
    *)
      echo "Unknown command: $COMMAND" >&2
      echo "Run './docker.sh help' for usage." >&2
      exit 2
      ;;
  esac
}

main "$@"
