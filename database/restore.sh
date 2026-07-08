#!/bin/bash
# =====================================================================
# Database restore — Civil Registry Portal
#
# Restores a .sql.gz backup produced by database/backup.sh. This
# REPLACES the destination database's contents — it does not merge.
#
# Usage:
#   ./database/restore.sh database/backups/civil_registry_portal_20260707_070000.sql.gz
# =====================================================================
set -euo pipefail

if [ "$#" -ne 1 ]; then
    echo "Usage: $0 <path-to-backup.sql.gz>"
    exit 1
fi

BACKUP_FILE="$1"
DB_HOST="${DB_HOST:-localhost}"
DB_NAME="${DB_NAME:-civil_registry_portal}"
DB_USER="${DB_USER:-civil_registry_app}"
DB_PASS="${DB_PASS:-}"

if [ ! -f "$BACKUP_FILE" ]; then
    echo "Backup file not found: $BACKUP_FILE"
    exit 1
fi

echo "This will OVERWRITE all data currently in database '$DB_NAME' on host '$DB_HOST'."
read -p "Type the database name to confirm: " CONFIRM
if [ "$CONFIRM" != "$DB_NAME" ]; then
    echo "Confirmation did not match. Aborted — nothing was changed."
    exit 1
fi

gunzip -c "$BACKUP_FILE" | MYSQL_PWD="$DB_PASS" mysql \
    --host="$DB_HOST" \
    --user="$DB_USER" \
    "$DB_NAME"

echo "Restore complete from $BACKUP_FILE"
