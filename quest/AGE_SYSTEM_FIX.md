# Age System Fix Documentation

## Problem Identified

The character age system was not progressing correctly because of **three critical issues**:

### 1. Table Name Mismatch
- **CharacterAgeMapper** was looking for table: `ncquest_character_ages`
- **Migration Version1014** was creating table: `quest_char_ages`
- Result: Mapper couldn't find age data, defaulting to Stone Age

### 2. Conflicting Age Definitions
- **Old Migration (Version1003)** had:
  - Stone Age: Level 1+
  - Bronze Age: Level 6+
  - Iron Age: Level 11+
- **New Migration (Version1014)** has:
  - Stone Age: Level 1-9
  - Bronze Age: Level 10-19 ✅ (Level 11 should be Bronze)
  - Iron Age: Level 20-29

### 3. Case Sensitivity Issue
- Old data used capitalized age keys: `'Bronze'`, `'Iron'`, etc.
- New system uses lowercase keys: `'bronze'`, `'iron'`, etc.
- This caused progression tracking to fail

## What Was Fixed

### 1. Migration Table Name (lib/Migration/Version1014Date20250930130000.php)
- Changed all table references from `quest_char_ages` to `ncquest_character_ages`
- Now matches the mapper configuration

### 2. Data Cleanup
- Added code to delete old conflicting age entries with capitalized keys
- Ensures fresh start with correct lowercase age definitions

### 3. Correct Age Definitions
Age progression now works as follows:

| Age | Level Range | Icon | Description |
|-----|-------------|------|-------------|
| Stone Age | 1-9 | 🪨 | Dawn of civilization |
| **Bronze Age** | **10-19** | ⚒️ | **← Level 11 belongs here** |
| Iron Age | 20-29 | ⚔️ | Stronger metals |
| Medieval Age | 30-39 | 🏰 | Castles and knights |
| Renaissance | 40-49 | 🎨 | Art and culture |
| Industrial Age | 50-59 | ⚙️ | Steam power |
| Modern Age | 60-74 | 💡 | Electricity |
| Digital Age | 75-99 | 💻 | Computers |
| Space Age | 100+ | 🚀 | Beyond Earth |

## How to Apply the Fix

### Method 1: Automated Script (Recommended)

Run the provided fix script:

```bash
cd /path/to/nextcloud/apps/quest
./fix-age-system.sh
```

This will:
1. Run the corrected migration
2. Show the ages now in the database
3. Provide next steps for verification

### Method 2: Manual Steps

#### Step 1: Run the Migration
```bash
sudo -u www-data php occ migrations:execute quest Version1014Date20250930130000
```

#### Step 2: Verify Ages in Database
```bash
sudo -u www-data php occ db:execute-query "SELECT age_key, age_name, min_level, max_level FROM oc_ncquest_character_ages ORDER BY min_level"
```

You should see 9 ages with correct level ranges.

#### Step 3: Check Current Age Detection

Visit the debug endpoint (replace with your Nextcloud URL):
```bash
curl -u username:password https://your-nextcloud/apps/quest/api/character/debug-age
```

This shows:
- Your current level
- All ages in database
- The age detected for your level
- Your character's stored age field

#### Step 4: Force Age Recalculation (If Needed)

If you're still showing Stone Age after the migration:
```bash
curl -X POST -u username:password https://your-nextcloud/apps/quest/api/character/recalculate-age
```

This will:
- Get your current level
- Calculate the correct age
- Update your character's age
- Record the progression if it's a new age

## New Debug Tools Added

### 1. Age System Debug Endpoint
**URL:** `GET /apps/quest/api/character/debug-age`

Returns:
- Your current level
- All ages in the database
- The age that should apply to your level
- Your character's current stored age

### 2. Age Recalculation Endpoint
**URL:** `POST /apps/quest/api/character/recalculate-age`

Forces a recalculation of your character's age based on current level. Useful if:
- You were already past level 10 when the fix was applied
- The migration ran but your character age wasn't updated
- You want to manually trigger age progression

## Verification Steps

After applying the fix:

1. **Check Database Ages:**
   ```sql
   SELECT age_key, age_name, min_level, max_level
   FROM oc_ncquest_character_ages
   ORDER BY min_level;
   ```
   Should show 9 ages (stone through space)

2. **Check Your Character Age:**
   - Visit: `/apps/quest/character`
   - Should show "Bronze Age" for level 11
   - Should show correct progression to next age

3. **Test Age Progression:**
   - Complete tasks to level up
   - At level 20, should progress to Iron Age
   - At level 30, should progress to Medieval Age
   - And so on...

## How Age Progression Works

The system automatically checks age progression when you level up:

1. **Task Completed** → `XPService::awardXP()`
2. **Level Up Detected** → Calls `CharacterService::checkAgeProgression()`
3. **Age Calculation:**
   - Queries `CharacterAgeMapper::getAgeForLevel($newLevel)`
   - Finds age where `min_level <= $newLevel <= max_level`
4. **New Age Reached:**
   - Records in `quest_char_progress` table
   - Updates `character_current_age` field
   - Unlocks default items for that age
   - Dispatches `CharacterAgeReachedEvent`

## Files Modified

1. **lib/Migration/Version1014Date20250930130000.php**
   - Fixed table name from `quest_char_ages` to `ncquest_character_ages`
   - Added cleanup of old age entries
   - Already had correct lowercase age keys and level ranges

2. **lib/Controller/CharacterController.php**
   - Added `debugAgeSystem()` method
   - Added `recalculateAge()` method

3. **appinfo/routes.php**
   - Added routes for new debug and recalculation endpoints

4. **fix-age-system.sh** (new)
   - Automated fix script

## Troubleshooting

### Still Showing Stone Age After Fix

**Check if migration ran successfully:**
```bash
sudo -u www-data php occ migrations:status quest
```

**Check database for age data:**
```bash
sudo -u www-data php occ db:execute-query "SELECT COUNT(*) FROM oc_ncquest_character_ages"
```
Should return 9 (the 9 ages)

**Force recalculation:**
```bash
curl -X POST -u username:password https://your-nextcloud/apps/quest/api/character/recalculate-age
```

### Age Progression Not Recording

**Check progression table:**
```sql
SELECT * FROM oc_quest_char_progress WHERE user_id = 'your-username';
```

If you have an old 'bronze' entry from before the fix, the system won't record it again. The `checkAgeProgression()` method only records new ages you haven't reached before.

### Next Age Not Showing Correctly

The "Next Age" display should show:
- At level 11 (Bronze Age): "Next: Iron Age (Level 20)"
- At level 19 (still Bronze): "Next: Iron Age (Level 20)"
- At level 20 (Iron Age): "Next: Medieval Age (Level 30)"

If incorrect, the `CharacterAgeMapper::getNextAge()` method should be checked.

## Summary

The fix ensures:
- ✅ Correct table name matching between mapper and migration
- ✅ Proper age level ranges (Bronze Age = 10-19)
- ✅ Lowercase age keys for consistency
- ✅ Cleanup of old conflicting data
- ✅ Debug tools to verify and fix issues
- ✅ Manual recalculation if needed

**Your level 11 character should now correctly show as Bronze Age!**
