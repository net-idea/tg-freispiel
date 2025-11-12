#!/bin/bash

set -euo pipefail

# Deployment script for Seepferdchen-Garde (Symfony + Webpack Encore):
# - Installs Node and PHP deps if missing
# - Builds front-end assets (production)
# - Installs PHP dependencies
# - Runs database migrations (if SKIP_MIGRATIONS != true)
# - Clears & warms Symfony cache (prod)
#
# Usage:
#   ./deploy.sh                    # Normal deployment with migrations
#   SKIP_MIGRATIONS=true ./deploy.sh   # Skip database migrations

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

# Ensure Composer/Symfony auto-scripts run in production context
export APP_ENV=prod
export APP_DEBUG=0
export SYMFONY_ENV=prod

echo "Starting deployment for Seepferdchen-Garde..."
echo "Environment: APP_ENV=$APP_ENV"
echo ""

# Helper to run commands and print helpful errors
run() {
  echo "--> $*"
  if ! "$@"; then
    echo "ERROR: Command failed: $*" >&2
    exit 1
  fi
}

# Find composer binary: prefer global composer, fallback to composer.phar
COMPOSER_BIN=""
if command -v composer >/dev/null 2>&1; then
  COMPOSER_BIN="composer"
elif [ -f "$ROOT_DIR/composer.phar" ]; then
  COMPOSER_BIN="php $ROOT_DIR/composer.phar"
fi

# Find node package manager and build tool
USE_YARN=false
USE_NPM=false
if command -v yarn >/dev/null 2>&1; then
  USE_YARN=true
elif command -v npm >/dev/null 2>&1; then
  USE_NPM=true
fi

# Find PHP binary
PHP_BIN=""
if command -v php >/dev/null 2>&1; then
  PHP_BIN="php"
fi

# -----------------------------
# 1st: install Node dependencies (if available)
# Run Node install early so composer auto-scripts that call yarn will succeed
# -----------------------------
if [ "$USE_YARN" = true ]; then
  echo "[1/5] Installing Node.js dependencies (yarn)..."
  # Try more strict installs first
  if yarn install --immutable --frozen-lockfile 2>/dev/null; then
    echo "✓ Node.js dependencies installed (immutable)"
  elif yarn install --frozen-lockfile 2>/dev/null; then
    echo "✓ Node.js dependencies installed (frozen-lockfile)"
  else
    echo "Attempting yarn install (may run lifecycle scripts)..."
    if yarn install; then
      echo "✓ Node.js dependencies installed"
    else
      echo "yarn install failed; trying to install while skipping lifecycle scripts (--ignore-scripts)"
      if yarn install --ignore-scripts; then
        echo "✓ Node.js dependencies installed (scripts skipped)"
      else
        echo "ERROR: yarn install failed even with --ignore-scripts" >&2
        exit 1
      fi
    fi
  fi

elif [ "$USE_NPM" = true ]; then
  echo "[1/5] Installing Node.js dependencies (npm)..."
  if npm ci 2>/dev/null; then
    echo "✓ Node.js dependencies installed (ci)"
  else
    echo "Attempting npm install (may run lifecycle scripts)..."
    if npm install; then
      echo "✓ Node.js dependencies installed"
    else
      echo "npm install failed; trying to install while skipping lifecycle scripts (--ignore-scripts)"
      if npm install --ignore-scripts; then
        echo "✓ Node.js dependencies installed (scripts skipped)"
      else
        echo "ERROR: npm install failed even with --ignore-scripts" >&2
        exit 1
      fi
    fi
  fi
else
  echo "⚠ Neither yarn nor npm found. Skipping Node.js install." >&2
fi

echo ""

# 2nd: build production assets
if [ "$USE_YARN" = true ]; then
  echo "[2/5] Building production assets with Webpack Encore (yarn)..."
  run yarn build
  echo "✓ Production assets built"
elif [ "$USE_NPM" = true ]; then
  echo "[2/5] Building production assets with Webpack Encore (npm)..."
  run npm run build
  echo "✓ Production assets built"
else
  echo "✗ Neither yarn nor npm found. Cannot build assets." >&2
  exit 1
fi

echo ""

# 3rd: install Composer dependencies (if composer exists)
# Composer is executed after Node so any composer post-install hooks that call yarn/npm succeed
if [ -n "$COMPOSER_BIN" ]; then
  echo "[3/5] Installing Composer dependencies (no-dev, prod env)..."
  # Allow skipping composer auto-scripts by setting SKIP_COMPOSER_AUTOSCRIPTS=true in env
  if [ "${SKIP_COMPOSER_AUTOSCRIPTS:-false}" = "true" ]; then
    run $COMPOSER_BIN install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts
  else
    run $COMPOSER_BIN install --no-dev --prefer-dist --optimize-autoloader --no-interaction
  fi
  echo "✓ Composer dependencies installed"
else
  echo "⚠ Composer not found. Skipping Composer install." >&2
fi

echo ""

# 4th: optionally run database migrations (respect SKIP_MIGRATIONS)
if [ "${SKIP_MIGRATIONS:-false}" = "true" ]; then
  echo "[4/5] SKIPPING database migrations (SKIP_MIGRATIONS=true)"
else
  if [ -n "$PHP_BIN" ]; then
    echo "[4/5] Running database migrations..."
    # run migrations in prod environment
    run $PHP_BIN bin/console doctrine:migrations:migrate --no-interaction --env=prod
    echo "✓ Database migrations completed"
  else
    echo "⚠ PHP not found. Skipping database migrations." >&2
  fi
fi

echo ""

# 5th: clear & warm Symfony cache
if [ -n "$PHP_BIN" ]; then
  echo "[5/5] Clearing & warming Symfony cache (prod)..."
  # Clear without warmup first to avoid autoload issues, then warmup
  run $PHP_BIN bin/console cache:clear --env=prod --no-debug --no-warmup
  run $PHP_BIN bin/console cache:warmup --env=prod
  echo "✓ Symfony cache cleared and warmed"
else
  echo "✗ PHP not found. Cannot manage Symfony cache." >&2
  exit 1
fi

echo ""
echo "═══════════════════════════════════════════════════════"
echo "✓ Deployment completed successfully!"
echo "═══════════════════════════════════════════════════════"
