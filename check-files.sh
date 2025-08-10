#!/bin/bash

# Check if all required files exist for Nextcloud Quest

echo "Checking Nextcloud Quest files..."

# Base path - update this to your actual Nextcloud apps path
BASE_PATH="/path/to/nextcloud/apps/nextcloudquest"

# Files that should exist
FILES=(
    # Templates
    "templates/quests.php"
    "templates/achievements.php"
    "templates/progress.php"
    "templates/settings.php"
    
    # CSS
    "css/quests-page.css"
    "css/achievements-page.css"
    "css/progress-page.css"
    "css/settings-page.css"
    
    # JavaScript
    "js/quests-page.js"
    "js/achievements-page.js"
    "js/progress-page.js"
    "js/settings-page.js"
    
    # Controllers
    "lib/Controller/ProgressAnalyticsController.php"
    "lib/Controller/SettingsController.php"
    
    # Migrations
    "lib/Migration/Version1004Date20250805140000.php"
)

# Check each file
MISSING=0
for FILE in "${FILES[@]}"; do
    if [ ! -f "$BASE_PATH/$FILE" ]; then
        echo "❌ MISSING: $FILE"
        MISSING=$((MISSING + 1))
    else
        echo "✅ Found: $FILE"
    fi
done

echo ""
echo "Total files checked: ${#FILES[@]}"
echo "Missing files: $MISSING"

if [ $MISSING -gt 0 ]; then
    echo ""
    echo "⚠️  Some files are missing! These were created by the agents but may not have been copied to your server."
    echo "You'll need to copy these files from your development directory."
fi