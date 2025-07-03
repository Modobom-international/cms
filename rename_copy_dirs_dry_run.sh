#!/bin/bash

# Dry-run script to preview directory renaming from " copy" to "-2"
# Author: AI Assistant
# Usage: ./rename_copy_dirs_dry_run.sh

echo "DRY RUN: Preview of directory renaming process..."
echo "Looking for directories ending with ' copy'..."
echo ""

# Counter for directories that would be renamed
count=0

# Find all directories ending with " copy" and show what would happen
while IFS= read -r -d '' dir; do
    if [[ -d "$dir" ]]; then
        # Get the directory name without the " copy" suffix
        base_name="${dir% copy}"
        new_name="${base_name}-2"
        
        echo "WOULD RENAME: '$dir' -> '$new_name'"
        
        # Check if the target directory already exists
        if [[ -d "$new_name" ]]; then
            echo "  WARNING: Target directory '$new_name' already exists. Would skip..."
        else
            echo "  OK: Rename would proceed"
            ((count++))
        fi
        echo ""
    fi
done < <(find . -type d -name "* copy" -print0)

echo "========================================"
echo "DRY RUN SUMMARY:"
echo "Total directories that would be renamed: $count"
echo ""
echo "To actually perform the renaming, run: ./rename_copy_dirs.sh" 