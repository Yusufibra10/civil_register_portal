#!/bin/bash
# =====================================================================
# Database backup — Civil Registry Portal
#
# Dumps the full database (schema + data) to a timestamped, gzipped
# file under database/backups/, then deletes backups older than
# RETENTION_DAYS. Intended to run via cron on the production server;
# safe to run manually too.
#
# Usage:
#   ./database/backup.sh
#
# Configure DB_USER/DB_PASS/DB_NAME below to match config/database.local.php,
# or export them as environment variables before calling this script so the
# password never has to be written into the script itself.
# =====================================================================
set -euo pipefail

DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-civil_registry_portal}"
DB_USER="${DB_USER:-civil_registry_app}"
DB_PASS="${DB_PASS:-}"
RETENTION_DAYS="${RETENTION_DAYS:-30}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$SCRIPT_DIR/backups"
mkdir -p "$BACKUP_DIR"

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
OUTFILE="$BACKUP_DIR/civil_registry_portal_${TIMESTAMP}.sql.gz"

MYSQL_PWD="$DB_PASS" mysqldump \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    --single-transaction \
    --routines \
    --triggers \
    --default-character-set=utf8mb4 \
    "$DB_NAME" | gzip > "$OUTFILE"

echo "Backup written to $OUTFILE"

# Prune backups older than RETENTION_DAYS.
find "$BACKUP_DIR" -name '*.sql.gz' -mtime "+${RETENTION_DAYS}" -print -delete
