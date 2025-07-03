#!/bin/bash

# Script to rename directories ending with " copy" to end with "-2"
# Author: AI Assistant
# Usage: ./rename_copy_dirs.sh

echo "Starting directory renaming process..."
echo "Looking for directories ending with ' copy'..."

# Counter for renamed directories
count=0

# Find all directories ending with " copy" and process them
while IFS= read -r -d '' dir; do
    if [[ -d "$dir" ]]; then
        # Get the directory name without the " copy" suffix
        base_name="${dir% copy}"
        new_name="${base_name}-2"
        
        echo "Renaming: '$dir' -> '$new_name'"
        
        # Check if the target directory already exists
        if [[ -d "$new_name" ]]; then
            echo "  WARNING: Target directory '$new_name' already exists. Skipping..."
            continue
        fi
        
        # Rename the directory
        if mv "$dir" "$new_name"; then
            echo "  SUCCESS: Renamed successfully"
            ((count++))
        else
            echo "  ERROR: Failed to rename '$dir'"
        fi
    fi
done < <(find . -type d -name "* copy" -print0)

echo ""
echo "Renaming process completed!"
echo "Total directories renamed: $count"

# Show the results
echo ""
echo "Directories that now end with '-2':"
find . -type d -name "*-2" | sort 