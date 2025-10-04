#!/bin/bash
# Age System Fix Script
# Run this script to fix the age progression system

echo "🔧 Nextcloud Quest - Age System Fix Script"
echo "=========================================="
echo ""

# Step 1: Run the migration
echo "📋 Step 1: Running migration Version1014Date20250930130000..."
sudo -u www-data php occ migrations:execute quest Version1014Date20250930130000

if [ $? -eq 0 ]; then
    echo "✅ Migration executed successfully"
else
    echo "❌ Migration failed"
    exit 1
fi

echo ""

# Step 2: Check the ages table
echo "📋 Step 2: Checking ages in database..."
sudo -u www-data php occ db:execute-query "SELECT age_key, age_name, min_level, max_level FROM oc_ncquest_character_ages ORDER BY min_level"

echo ""

# Step 3: Instructions for next steps
echo "📋 Step 3: Next Steps"
echo "--------------------"
echo "1. Check the debug endpoint to see current age data:"
echo "   curl -u username:password https://your-nextcloud/apps/quest/api/character/debug-age"
echo ""
echo "2. Force age recalculation (if needed):"
echo "   curl -X POST -u username:password https://your-nextcloud/apps/quest/api/character/recalculate-age"
echo ""
echo "3. Visit your Nextcloud Quest character page to see the updated age"
echo ""
echo "4. If still showing Stone Age after recalculation, there may be old progression records."
echo "   Check: SELECT * FROM oc_quest_char_progress WHERE user_id='your-username';"
echo ""
echo "Age definitions:"
echo "  Stone Age:       Level 1-9"
echo "  Bronze Age:      Level 10-19  ← You should be here at level 11"
echo "  Iron Age:        Level 20-29"
echo "  Medieval Age:    Level 30-39"
echo "  Renaissance:     Level 40-49"
echo "  Industrial Age:  Level 50-59"
echo "  Modern Age:      Level 60-74"
echo "  Digital Age:     Level 75-99"
echo "  Space Age:       Level 100+"
echo ""
echo "✅ Fix script completed!"
