#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$SCRIPT_DIR"

MODE=""
DETACHED=1

GREEN=$'\033[0;32m'
YELLOW=$'\033[1;33m'
RED=$'\033[0;31m'
BLUE=$'\033[0;34m'
NC=$'\033[0m'

usage() {
  cat <<'EOF'
Usage: ./develop.sh [--local|--docker] [--detach|--attach]

Modes:
  --local   Run local development stack without Docker (default)
  --docker  Run Docker development stack via ./docker.sh up --dev

Options:
  -d, --detach  Docker mode only: detached (default)
  -a, --attach  Docker mode only: attach logs in foreground
  -h, --help    Show this help
EOF
}

load_env() {
  if [ -f "$PROJECT_DIR/.env" ]; then
    set -o allexport
    # shellcheck disable=SC1091
    source "$PROJECT_DIR/.env"
    set +o allexport
  fi
}

choose_mode() {
  if [ -n "$MODE" ]; then
    return
  fi

  if [ -t 0 ]; then
    echo -e "${BLUE}Select development mode:${NC}"
    echo -e "${YELLOW}1) Local (without Docker)${NC}"
    echo -e "${YELLOW}2) Docker (via docker.sh up --dev)${NC}"
    read -r -p "Choice [1/2, default 1]: " choice

    case "${choice:-1}" in
      2) MODE="docker" ;;
      *) MODE="local" ;;
    esac
  else
    MODE="local"
  fi
}

has_migrations() {
  [ -d "$PROJECT_DIR/migrations" ] && \
    find "$PROJECT_DIR/migrations" -maxdepth 1 -type f -name '*.php' -print -quit | grep -q .
}

ask_and_run_migrations_local() {
  if ! has_migrations; then
    return
  fi

  if [ -t 0 ]; then
    read -r -p "Run Doctrine migrations now? [y/N] " response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
      echo -e "${GREEN}Migrations skipped.${NC}"
      return
    fi
  fi

  echo -e "${YELLOW}Running Doctrine migrations (local)...${NC}"
  php bin/console doctrine:migrations:migrate --no-interaction
}

ask_and_run_migrations_docker() {
  if ! has_migrations; then
    return
  fi

  if [ -t 0 ]; then
    read -r -p "Run Doctrine migrations in Docker now? [y/N] " response
    if [[ ! "$response" =~ ^[Yy]$ ]]; then
      echo -e "${GREEN}Migrations skipped.${NC}"
      return
    fi
  fi

  echo -e "${YELLOW}Running Doctrine migrations (docker)...${NC}"
  "$PROJECT_DIR/docker.sh" exec --dev php php bin/console doctrine:migrations:migrate --no-interaction
}

require_local_deps() {
  local missing=()
  local bin
  for bin in php composer node yarn; do
    if ! command -v "$bin" >/dev/null 2>&1; then
      missing+=("$bin")
    fi
  done

  if [ "${#missing[@]}" -gt 0 ]; then
    echo -e "${RED}Missing local dependencies: ${missing[*]}${NC}" >&2
    exit 1
  fi
}

print_local_overview() {
  local app_port="${APP_PORT:-15080}"
  local node_port="${NODE_PORT:-15081}"

  echo
  echo -e "${GREEN}Local development is running:${NC}"
  echo -e "${YELLOW} → App:       http://127.0.0.1:${app_port}${NC}"
  echo -e "${YELLOW} → Assets:    http://127.0.0.1:${node_port}${NC}"
  echo -e "${YELLOW} → Runtime:   local PHP + local Node${NC}"
  echo
}

print_docker_overview() {
  local db="${DB:-mariadb}"
  local db_service="mariadb"
  local app_port="${APP_PORT:-15080}"
  local node_port="${NODE_PORT:-15081}"
  local mailer_web_port="${MAILER_WEB_PORT:-15082}"
  local adminer_port="${ADMINER_PORT:-15083}"
  local phpmyadmin_port="${PHPMYADMIN_PORT:-15084}"

  if [ "$db" = "postgres" ]; then
    db_service="postgres"
  fi

  echo
  echo -e "${GREEN}Docker development is running:${NC}"
  echo -e "${YELLOW} → App:        http://127.0.0.1:${app_port}${NC}"
  echo -e "${YELLOW} → Assets:     http://127.0.0.1:${node_port}${NC}"
  echo -e "${YELLOW} → Mailpit:    http://127.0.0.1:${mailer_web_port}${NC}"
  echo -e "${YELLOW} → Adminer:    http://127.0.0.1:${adminer_port}${NC}"
  if [ "$db_service" = "mariadb" ]; then
    echo -e "${YELLOW} → phpMyAdmin: http://127.0.0.1:${phpmyadmin_port}${NC}"
  fi
  echo -e "${YELLOW} → DB service: ${db_service} (internal Docker network)${NC}"
  echo
  echo -e "${BLUE}Services:${NC}"
  "$PROJECT_DIR/docker.sh" ps --dev || true
  echo
}

run_local_mode() {
  local yarn_pid=""
  local php_pid=""

  require_local_deps

  echo -e "${GREEN}Starting local development environment...${NC}"
  echo -e "${YELLOW}Installing dependencies...${NC}"
  composer install --working-dir="$PROJECT_DIR" --prefer-dist --no-progress
  yarn install --cwd "$PROJECT_DIR"

  echo -e "${YELLOW}Preparing Symfony cache...${NC}"
  php bin/console cache:clear --no-warmup
  php bin/console cache:warmup
  ask_and_run_migrations_local

  cleanup_local() {
    [ -n "$yarn_pid" ] && kill "$yarn_pid" 2>/dev/null || true
    [ -n "$php_pid" ] && kill "$php_pid" 2>/dev/null || true
  }
  trap cleanup_local INT TERM EXIT

  print_local_overview
  echo -e "${YELLOW}Press Ctrl+C to stop.${NC}"

  yarn encore dev-server --port "${NODE_PORT:-15081}" --host 127.0.0.1 --hot &
  yarn_pid="$!"

  sleep 2

  php -S "127.0.0.1:${APP_PORT:-15080}" -t public &
  php_pid="$!"

  wait
}

run_docker_mode() {
  echo -e "${GREEN}Starting Docker development environment...${NC}"
  "$PROJECT_DIR/docker.sh" up --dev
  ask_and_run_migrations_docker
  print_docker_overview

  if [ "$DETACHED" -eq 1 ]; then
    echo -e "${GREEN}Docker dev stack is running detached.${NC}"
    echo -e "${YELLOW}Use ./docker.sh logs --dev (or ./develop.sh --docker --attach) to follow logs.${NC}"
    return
  fi

  echo -e "${YELLOW}Attaching Docker logs...${NC}"
  echo -e "${YELLOW}Press Ctrl+C to stop log tailing.${NC}"
  "$PROJECT_DIR/docker.sh" logs --dev
}

while [ $# -gt 0 ]; do
  case "$1" in
    --local)
      MODE="local"
      ;;
    --docker)
      MODE="docker"
      ;;
    -d|--detach)
      DETACHED=1
      ;;
    -a|--attach)
      DETACHED=0
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo -e "${RED}Unknown option: $1${NC}" >&2
      usage
      exit 2
      ;;
  esac
  shift
done

load_env
choose_mode

case "$MODE" in
  local)
    run_local_mode
    ;;
  docker)
    run_docker_mode
    ;;
  *)
    echo -e "${RED}Invalid mode: $MODE${NC}" >&2
    exit 2
    ;;
esac
