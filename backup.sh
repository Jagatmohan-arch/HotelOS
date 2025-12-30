#!/bin/bash
# ============================================
# HotelOS - Automated Backup Script
# Run via cron: 0 2 * * * /path/to/backup.sh
# ============================================

# Configuration
DB_NAME="uplfveim_hotelos"
DB_USER="your_db_user"
DB_PASS="your_db_password"
BACKUP_DIR="/home/uplfveim/backups"
DATE=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

# Create backup directory if not exists
mkdir -p "$BACKUP_DIR"

# Database backup
echo "Starting database backup..."
mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" | gzip > "$BACKUP_DIR/db_${DATE}.sql.gz"

# Files backup (uploads, etc)
echo "Starting files backup..."
tar -czf "$BACKUP_DIR/files_${DATE}.tar.gz" \
    /home/uplfveim/hotelos.needkit.in/public/uploads \
    /home/uplfveim/hotelos.needkit.in/logs \
    /home/uplfveim/hotelos.needkit.in/.env

# Cleanup old backups (older than RETENTION_DAYS)
echo "Cleaning up old backups..."
find "$BACKUP_DIR" -name "db_*.sql.gz" -mtime +$RETENTION_DAYS -delete
find "$BACKUP_DIR" -name "files_*.tar.gz" -mtime +$RETENTION_DAYS -delete

# Log completion
echo "Backup completed: $DATE" >> "$BACKUP_DIR/backup.log"

# Optional: Upload to cloud storage (S3, Google Drive, etc)
# aws s3 cp "$BACKUP_DIR/db_${DATE}.sql.gz" s3://your-bucket/hotelos-backups/

echo "✅ Backup successful!"
