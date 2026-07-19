#!/bin/bash

# Database backup and restore script for Docker Compose
# Usage:
#   ./database-backup.sh [--engine mariadb|postgres] [--output-dir DIR]
#   ./database-backup.sh --restore FILE [--engine mariadb|postgres]

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Get environment variables
if [ -f "$PROJECT_DIR/.env" ]; then
  set -o allexport
  # shellcheck disable=SC1091
  source "$PROJECT_DIR/.env"
  set +o allexport
fi

# Dynamic container naming based on APP_NAME from .env
APP_NAME="${APP_NAME:-Theatergruppe Freispiel}"
ENGINE="${DB:-mariadb}" # mariadb or postgres
OUTPUT_DIR="mariadb/backup"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)
MODE="backup" # backup or restore
RESTORE_FILE=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --engine)
      ENGINE="$2"; shift 2 ;;
    --output-dir)
      OUTPUT_DIR="$2"; shift 2 ;;
    --restore)
      MODE="restore"
      RESTORE_FILE="$2"; shift 2 ;;
    -h|--help)
      echo "Usage:"
      echo "  Backup:  $0 [--engine mariadb|postgres] [--output-dir DIR]"
      echo "  Restore: $0 --restore FILE [--engine mariadb|postgres]"
      exit 0 ;;
    *)
      echo "Unknown option: $1" >&2; exit 1 ;;
  esac
done

if [ "$ENGINE" = "postgres" ]; then
  DB_SERVICE="postgres"
else
  DB_SERVICE="mariadb"
fi

# Check if DB service is running
if ! "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- ps "$DB_SERVICE" --format json 2>/dev/null | grep -q '"State":"running"'; then
  echo "❌ Database service '$DB_SERVICE' is not running. Start it with: ./docker.sh up --dev" >&2
  exit 1
fi

if [ "$MODE" = "backup" ]; then
  mkdir -p "$OUTPUT_DIR"
  FILE="${OUTPUT_DIR}/${APP_NAME}-${ENGINE}-${TIMESTAMP}.sql"

  if [ "$ENGINE" = "postgres" ]; then
    echo "Creating Postgres backup to $FILE..."
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" pg_dump -U "${DB_USER:-Theatergruppe Freispiel}" "${DB_NAME:-Theatergruppe Freispiel}" > "$FILE"
  else
    echo "Creating MariaDB backup to $FILE..."
    # Use mariadb-dump (newer MariaDB) or fall back to mysqldump
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" sh -c "mariadb-dump -u'${DB_USER:-Theatergruppe Freispiel}' -p'${DB_PASSWORD:-nopassword}' '${DB_NAME:-Theatergruppe Freispiel}'" > "$FILE"
  fi

  echo "✅ Backup written: $FILE ($(du -h "$FILE" | cut -f1))"

else
  # Restore mode
  if [ -z "$RESTORE_FILE" ]; then
    echo "❌ No restore file specified. Use: $0 --restore FILE" >&2
    exit 1
  fi

  if [ ! -f "$RESTORE_FILE" ]; then
    echo "❌ Restore file not found: $RESTORE_FILE" >&2
    exit 1
  fi

  echo "⚠️  WARNING: This will overwrite the current database content!"
  echo "Database: ${DB_NAME:-Theatergruppe Freispiel}"
  echo "Engine:   $ENGINE"
  echo "File:     $RESTORE_FILE"
  read -rp "Continue? [y/N] " confirm

  if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "Restore aborted."
    exit 0
  fi

  if [ "$ENGINE" = "postgres" ]; then
    echo "Restoring Postgres backup from $RESTORE_FILE..."
    # Drop and recreate database, then restore
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" psql -U "${DB_USER:-Theatergruppe Freispiel}" -c "DROP DATABASE IF EXISTS ${DB_NAME:-Theatergruppe Freispiel};" || true
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" psql -U "${DB_USER:-Theatergruppe Freispiel}" -c "CREATE DATABASE ${DB_NAME:-Theatergruppe Freispiel};"
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" psql -U "${DB_USER:-Theatergruppe Freispiel}" "${DB_NAME:-Theatergruppe Freispiel}" < "$RESTORE_FILE"
  else
    echo "Restoring MariaDB backup from $RESTORE_FILE..."
    # Copy file into container and restore from there
    RESTORE_BASENAME=$(basename "$RESTORE_FILE")
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- cp "$RESTORE_FILE" "$DB_SERVICE:/tmp/$RESTORE_BASENAME"
    # Disable SSL for local restore and use batch mode with force
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" sh -c "MYSQL_PWD='${DB_PASSWORD:-nopassword}' mariadb --skip-ssl --batch --force --user='${DB_USER:-Theatergruppe Freispiel}' '${DB_NAME:-Theatergruppe Freispiel}' </tmp/$RESTORE_BASENAME"
    "$PROJECT_DIR/docker.sh" raw --dev --db "$ENGINE" -- exec -T "$DB_SERVICE" rm "/tmp/$RESTORE_BASENAME"
  fi

  echo "✅ Database restored from: $RESTORE_FILE"
fi
