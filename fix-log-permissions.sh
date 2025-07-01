#!/bin/bash

# Script to fix log file permissions
# This ensures all log files in storage/logs have proper permissions

LOGS_DIR="storage/logs"
WEB_USER="modobomMDB"
WEB_GROUP="modobomMDB"

echo "Fixing log file permissions..."

# Set directory permissions
chmod 777 "$LOGS_DIR"

# Set ownership for all files in logs directory
chown -R "$WEB_USER:$WEB_GROUP" "$LOGS_DIR"

# Set proper permissions for all log files
find "$LOGS_DIR" -name "*.log" -exec chmod 777 {} \;

echo "Log permissions fixed successfully!"

# Display current permissions
echo "Current log directory permissions:"
ls -la "$LOGS_DIR" 