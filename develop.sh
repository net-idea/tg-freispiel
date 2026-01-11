#!/bin/bash

### Development environment helper
# This script helps starting a development environment in two modes:
# 1) Local mode (default): uses host PHP/Node/Composer/Yarn. It:
#    - starts MariaDB via Docker compose (dev DB compose files)
#    - installs Composer and Yarn dependencies on host
#    - clears and warms Symfony cache
#    - optionally runs Doctrine migrations
#    - starts Webpack Encore in watch mode and the PHP built-in server
# 2) Docker Compose mode: brings up the full dev stack using Docker Compose
#    (PHP, Nginx, Node watcher, MariaDB, Mailpit).
#
# Usage: ./develop.sh [-d]
# -d: Run Docker Compose in detached mode (background)
#
# Press Ctrl+C to stop local watchers or the attached Docker stack.

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Parse arguments
DETACHED=false
while [[ $# -gt 0 ]]; do
  case "$1" in
    -d|--detach)
      DETACHED=true; shift ;;
    *)
      shift ;; # Ignore other args
  esac
done

PROJECT_DIR=$(pwd)
PHP_BIN=$(which php 2>/dev/null || echo "")
COMPOSER_BIN=$(which composer 2>/dev/null || echo "")
SYMFONY_BIN=$(which symfony 2>/dev/null || echo "")
NODE_BIN=$(which node 2>/dev/null || echo "")
YARN_BIN=$(which yarn 2>/dev/null || echo "")
DOCKER_BIN=$(which docker 2>/dev/null || echo "")

# Checks for Docker availability
require_docker() {
    if [ -z "$DOCKER_BIN" ]; then
        echo -e "${RED}Docker is not installed or not in PATH.${NC}" >&2
        exit 1
    fi
    if ! docker compose version >/dev/null 2>&1; then
        echo -e "${RED}Docker Compose v2 is required (docker compose).${NC}" >&2
        exit 1
    fi
}

# Source docker-list.sh for compose args/services
load_compose_args() {
    if [ ! -x "$PROJECT_DIR/docker-list.sh" ]; then
        echo -e "${RED}Missing $PROJECT_DIR/docker-list.sh${NC}" >&2
        exit 1
    fi

    COMPOSE_ARGS_RAW="$($PROJECT_DIR/docker-list.sh --compose-args)"
    # shellcheck disable=SC2206
    COMPOSE_ARGS=( $COMPOSE_ARGS_RAW )

    # determine DB service name (for logs/info)
    if [ "${DB:-mariadb}" = "postgres" ]; then
        DB_SERVICE=postgres
    else
        DB_SERVICE=mariadb
    fi
}

# Ask for migrations if files exist
# $1: command to execute php (e.g., "$PHP_BIN" or "docker compose ... exec ...")
ask_and_run_migrations() {
    local exec_cmd="$1"

    # Check for migration files
    if [ -d "migrations" ] && [ -n "$(find migrations -maxdepth 1 -type f -name '*.php' -print -quit 2>/dev/null)" ]; then
        echo
        echo -e "${YELLOW}Migrations found. Run them now? (y/N)${NC}"
        read -r response
        if [[ "$response" =~ ^[Yy]$ ]]; then
            echo -e "${YELLOW}Running migrations...${NC}"
            # shellcheck disable=2086
            $exec_cmd bin/console doctrine:migrations:migrate --no-interaction
        else
            echo -e "${GREEN}Migrations skipped.${NC}"
        fi
    else
        echo -e "${GREEN}No migrations found.${NC}"
    fi
}

# Print generic service list with defaults from .env if possible
print_service_info() {
    if [ -f .env ]; then
        set -o allexport
        # shellcheck disable=SC1091
        source .env
        set +o allexport
    fi

    local app_url="http://127.0.0.1:${APP_PORT:-8000}"
    local assets_url="http://127.0.0.1:${NODE_PORT:-8080}"

    echo -e "${GREEN}Development environment is running and provides following endpoints:${NC}"
    echo "${YELLOW} → App:        $app_url${NC}"
    echo "${YELLOW} → Assets:     $assets_url (via Yarn)${NC}"
}

run_docker_stack() {
    require_docker
    load_compose_args

    echo -e "${YELLOW}Starting Docker dev stack (detached init)...${NC}"

    # 1. Bring up stack in detached mode first to ensure services correspond
    #    and to allow running migrations interactively.
    docker compose "${COMPOSE_ARGS[@]}" up -d --build --remove-orphans

    echo -e "${GREEN}Docker containers are up.${NC}"

    # 2. Migration prompt
    #    We use docker executable directly to ensure interactive input works if needed,
    #    though 'migrate --no-interaction' is passed.
    #    The 'ask_and_run_migrations' function handles the prompt logic.
    ask_and_run_migrations "docker compose ${COMPOSE_ARGS[*]} exec php php"

    # 3. Print Services using docker-test.sh for detailed health/endpoint info
    if [ -x "./docker-test.sh" ]; then
        ./docker-test.sh || true
    else
        print_service_info
    fi

    # 4. Handle Attached vs Detached
    if [ "$DETACHED" = true ]; then
        echo -e "${GREEN}Running in detached mode. Use './docker-stop.sh' to stop.${NC}"
        exit 0
    else
        echo
        echo -e "${YELLOW}Attaching to logs...${NC}"
        echo -e "${YELLOW}Press Ctrl+C to stop the stack and clean up.${NC}"
        echo

        # We define a trap to catch Ctrl+C (SIGINT)
        # When caught, we explicitly stop the containers to mimic 'docker compose up' behavior
        trap 'echo -e "\n${YELLOW}Stopping stack...${NC}"; docker compose "${COMPOSE_ARGS[@]}" stop; exit 0' INT TERM

        # Follow logs. This blocks until Ctrl+C.
        docker compose "${COMPOSE_ARGS[@]}" logs -f
    fi
}

run_local_stack() {
    echo -e "${GREEN}Starting local Symfony + Webpack Encore development environment...${NC}"
    echo -e "${YELLOW}Checking dependencies...${NC}"

    if [ -z "$PHP_BIN" ]; then
        echo -e "${RED}PHP is not installed or not in PATH.${NC}" >&2
        exit 1
    fi

    if [ -z "$SYMFONY_BIN" ]; then
        echo -e "${RED}Symfony CLI is not installed. Install with: curl -sS https://get.symfony.com/cli/installer | bash${NC}" >&2
        exit 1
    fi

    if [ -z "$COMPOSER_BIN" ]; then
        echo -e "${RED}Composer is not installed.${NC}" >&2
        exit 1
    fi

    if [ -z "$NODE_BIN" ]; then
        echo -e "${RED}Node.js is not installed.${NC}" >&2
        exit 1
    fi

    if [ -z "$YARN_BIN" ]; then
        echo -e "${RED}Yarn is not installed. Install with: npm install -g yarn${NC}" >&2
        exit 1
    fi

    require_docker
    load_compose_args

    echo -e "${GREEN}All dependencies are available.${NC}"

    echo -e "${YELLOW}Starting database (Docker)...${NC}"
    docker compose "${COMPOSE_ARGS[@]}" up -d "$DB_SERVICE"

    echo -e "${YELLOW}Running Composer install...${NC}"
    $COMPOSER_BIN install --working-dir="$PROJECT_DIR" --prefer-dist --no-progress

    echo -e "${YELLOW}Running Yarn install...${NC}"
    $YARN_BIN install --cwd "$PROJECT_DIR"

    echo -e "${YELLOW}Clearing and warming up cache...${NC}"
    $PHP_BIN bin/console cache:clear --no-warmup
    $PHP_BIN bin/console cache:warmup

    # Ask for migrations (local PHP)
    ask_and_run_migrations "$PHP_BIN"

    trap 'echo -e "${YELLOW}Stopping local development environment...${NC}"; kill $YARN_PID $PHP_PID 2>/dev/null; exit 0' INT TERM

    echo
    echo -e "${GREEN}Starting development servers...${NC}"
    echo
    print_service_info
    echo
    echo -e "${YELLOW}Press Ctrl+C to stop.${NC}"
    echo

    $YARN_BIN encore dev-server --port "${NODE_PORT:-8080}" --host 127.0.0.1 --hot &
    YARN_PID=$!

    sleep 2

    $PHP_BIN -S "127.0.0.1:${APP_PORT:-8000}" -t public &
    PHP_PID=$!

    wait
}

echo -e "${YELLOW}Select environment:${NC}"
echo "1) Local (host tools) + MariaDB via Docker"
echo "2) Docker Compose (full dev stack)"
read -rp "Choice [1/2]: " ENV_CHOICE

case "$ENV_CHOICE" in
    2) run_docker_stack ;;
    *) run_local_stack ;;
esac
