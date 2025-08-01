#!/bin/bash

# Backup script for news reorganization
# Run this before executing the news reorganization command

echo "Creating database backup before news reorganization..."

# Create backup with timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="backup-before-news-reorganize-${TIMESTAMP}.sql.gz"

# Export database using DDEV
ddev export-db --file="$BACKUP_FILE"

if [ $? -eq 0 ]; then
    echo "✅ Database backup created successfully: $BACKUP_FILE"
    echo ""
    echo "You can now run the news reorganization command:"
    echo "  ddev craft newsorganizer/reorganize --dry-run    # Preview changes"
    echo "  ddev craft newsorganizer/reorganize              # Execute changes"
    echo ""
    echo "If you need to restore the backup later:"
    echo "  ddev import-db --file=\"$BACKUP_FILE\""
else
    echo "❌ Failed to create database backup"
    exit 1
fi
