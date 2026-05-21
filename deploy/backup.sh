#!/bin/bash
# STOCK365 — Backup diario (DB + uploads)
# Cron: 0 2 * * * bash /var/www/stock365/deploy/backup.sh

APP_DIR="/var/www/stock365"
BACKUP_DIR="/var/backups/stock365"
DATE=$(date +%Y%m%d_%H%M%S)
KEEP_DAYS=30

DB_HOST="127.0.0.1"
DB_NAME="stock365"
DB_USER="stock365"
DB_PASS="Stock365Secure2026!"

mkdir -p "$BACKUP_DIR/db"
mkdir -p "$BACKUP_DIR/uploads"

# Backup DB
mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" \
    --single-transaction --quick --lock-tables=false \
    "$DB_NAME" | gzip > "$BACKUP_DIR/db/stock365_${DATE}.sql.gz"

# Backup uploads (facturas y archivos)
if [ -d "$APP_DIR/storage/app/public" ]; then
    tar -czf "$BACKUP_DIR/uploads/uploads_${DATE}.tar.gz" \
        -C "$APP_DIR/storage/app" public/ 2>/dev/null || true
fi

# Limpiar backups viejos
find "$BACKUP_DIR" -name "*.gz" -mtime +$KEEP_DAYS -delete

echo "[$(date)] Backup completado: stock365_${DATE}.sql.gz"
