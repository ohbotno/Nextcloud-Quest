# Character System Quick Start Guide

## Installation

### 1. Run Database Migrations

Execute the three new migrations to set up character system tables and seed data:

```bash
# Migration 1: Add character fields to users table
sudo -u www-data php occ migrations:execute quest Version1013Date20250930120000

# Migration 2: Create and seed character ages (Stone Age → Space Age)
sudo -u www-data php occ migrations:execute quest Version1014Date20250930130000

# Migration 3: Seed 70+ character items across all ages
sudo -u www-data php occ migrations:execute quest Version1015Date20250930140000
```

### 2. Verify Installation

```bash
# Check migration status
sudo -u www-data php occ migrations:status quest

# Verify character ages table
sudo -u www-data php occ db:execute-query "SELECT age_key, age_name, min_level FROM oc_ncquest_character_ages"

# Verify character items table
sudo -u www-data php occ db:execute-query "SELECT COUNT(*) as total_items, item_type, item_rarity FROM oc_ncquest_character_items GROUP BY item_type, item_rarity"

# Check user character fields
sudo -u www-data php occ db:execute-query "DESCRIBE oc_ncquest_users" | grep character
```

### 3. Build Frontend Assets

```bash
# Build JavaScript and CSS
npm run build

# Or for development with watch mode
npm run dev
```

### 4. Clear Nextcloud Cache

```bash
# Clear all caches
sudo -u www-data php occ maintenance:repair

# Or clear specific caches
sudo -u www-data php occ files:cleanup
```

## Testing the Character System

### 1. Verify Sidebar Display

1. Navigate to the Quest app
2. Look at the left sidebar - you should see:
   - Character avatar with age-appropriate icon
   - Character name and rank
   - Level badge
   - Stats gauges (XP, Health, Streak)

### 2. Test Character Customization

1. Click on the character section in the sidebar
2. Character customization modal should open showing:
   - Current character preview
   - Age information
   - Equipment tabs (Clothing, Weapons, Accessories, Headgear)
   - Available items organized by unlock status

### 3. Test Equipment System

```bash
# Via API (test with curl or browser)
curl -X GET 'https://your-nextcloud.com/apps/quest/api/character/data' \
  -H 'requesttoken: YOUR_TOKEN'

# Expected response:
{
  "status": "success",
  "data": {
    "current_age": {
      "key": "stone",
      "name": "Stone Age",
      "icon": "🪨",
      "color": "#8b7355"
    },
    "equipped_items": {
      "clothing": null,
      "weapon": null,
      "accessory": null,
      "headgear": null
    },
    "customization_stats": {
      "unlocked_items": 0,
      "total_items": 70+,
      ...
    }
  }
}
```

### 4. Test Age Progression

To manually test age progression:

```bash
# Update user level to trigger age change
# Example: Set user to level 10 (should trigger Bronze Age)
sudo -u www-data php occ db:execute-query "UPDATE oc_ncquest_users SET level = 10 WHERE user_id = 'your_username'"

# Manually trigger age progression check (via CharacterService)
# This would normally happen automatically on level up
```

### 5. Test Item Unlocking

Items unlock automatically based on level. To verify:

```bash
# Check which items should be unlocked for level 1
sudo -u www-data php occ db:execute-query "SELECT item_key, item_name, unlock_level FROM oc_quest_char_items WHERE unlock_level <= 1 ORDER BY unlock_level"

# Check unlocked items for a user
sudo -u www-data php occ db:execute-query "SELECT * FROM oc_quest_char_unlocks WHERE user_id = 'your_username'"
```

## API Endpoints Reference

All character endpoints are prefixed with `/apps/quest/api/character/`:

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/data` | GET | Get full character data |
| `/customization` | GET | Get customization interface data |
| `/items` | GET | Get available items list |
| `/appearance` | PUT | Update character appearance |
| `/equip/{itemKey}` | POST | Equip a specific item |
| `/unequip/{slot}` | DELETE | Unequip item from slot |
| `/ages` | GET | Get ages with progression |
| `/stats` | GET | Get progression statistics |

## Common Issues & Solutions

### Issue: Character avatar not showing in sidebar

**Solution:**
1. Check browser console for JavaScript errors
2. Verify `sidebar-character.js` is loaded in network tab
3. Clear browser cache (Ctrl+Shift+Delete)
4. Ensure migrations ran successfully

### Issue: "Character service not available" error

**Solution:**
1. Verify CharacterService is registered in `lib/AppInfo/Application.php`
2. Check that all character tables exist in database
3. Review Nextcloud logs: `tail -f /var/log/nextcloud/nextcloud.log`

### Issue: Items not showing in customization

**Solution:**
1. Verify item seed migration ran: `SELECT COUNT(*) FROM oc_quest_char_items`
2. Check character age table: `SELECT * FROM oc_quest_char_ages`
3. Ensure user has proper character_current_age set

### Issue: Age not progressing on level up

**Solution:**
The age progression hook needs to be integrated:

```php
// In LevelService or TaskCompletionController, after level up:
$characterService = \OC::$server->get(\OCA\NextcloudQuest\Service\CharacterService::class);
$newAge = $characterService->checkAgeProgression($userId, $newLevel, $totalXp);

if ($newAge) {
    // Age progressed! Dispatch event or show notification
}
```

### Issue: Equipment not appearing on character

**Currently Expected:** Equipment is shown as small indicator badges below the avatar since sprite assets haven't been created yet. Once you add sprite files to `img/characters/{age}/{type}/`, they will render as layered sprites.

## Development Workflow

### Adding New Character Items

1. Add item definition to seed migration or insert directly:
```sql
INSERT INTO oc_quest_char_items (
    item_key, item_name, item_type, age_key,
    item_description, unlock_level, item_rarity,
    sprite_path, sprite_layer, is_active, created_at
) VALUES (
    'stone_super_club', 'Super Stone Club', 'weapon', 'stone',
    'An exceptionally large club', 3, 'rare',
    'characters/stone/weapons/super_club.svg', 20, 1, NOW()
);
```

2. Create corresponding sprite file: `img/characters/stone/weapons/super_club.svg`

3. Refresh customization interface to see new item

### Adding New Character Ages

1. Update age seed migration or insert directly:
```sql
INSERT INTO oc_quest_char_ages (
    age_key, age_name, min_level, max_level,
    age_description, age_color, age_icon, is_active, created_at
) VALUES (
    'quantum', 'Quantum Age', 200, NULL,
    'Beyond space and time', '#ff00ff', '⚛️', 1, NOW()
);
```

2. Add age config to `ageConfig` in:
   - `js/sidebar-character.js`
   - `src/components/CharacterAvatar.vue`

3. Create sprite directory: `mkdir -p img/characters/quantum/{clothing,weapons,accessories,headgear,effects}`

### Creating Sprite Assets

**SVG Template (512x512px):**
```svg
<?xml version="1.0" encoding="UTF-8"?>
<svg width="512" height="512" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
  <!-- Center point at 256,256 -->
  <!-- Your sprite artwork here -->
  <g id="character-layer">
    <!-- Equipment or character graphics -->
  </g>
</svg>
```

**Naming Convention:**
- Base sprites: `{age}_base_default.svg`
- Equipment: `{item_key}.svg` (e.g., `stone_club.svg`)
- Effects: `{effect_type}_{effect_key}.svg`

**Placement:**
- Base: `img/characters/{age}/base/`
- Equipment: `img/characters/{age}/{type}/`
- Effects: `img/characters/{age}/effects/{effect_type}/`

## Integration Checklist

To fully integrate character system with existing features:

- [ ] Hook age progression to level up in `LevelService`
- [ ] Add badge awarding to `AchievementService`
- [ ] Add scar awarding for streak milestones in `StreakService`
- [ ] Integrate character display in dashboard (QuestDashboard.vue)
- [ ] Add character preview to task completion dialog
- [ ] Create age progression celebration notification
- [ ] Add character stats to leaderboard
- [ ] Create character profile page

## Performance Monitoring

```bash
# Monitor database query performance
sudo -u www-data php occ db:execute-query "SHOW PROCESSLIST"

# Check character table sizes
sudo -u www-data php occ db:execute-query "
  SELECT
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
  FROM information_schema.tables
  WHERE table_schema = 'nextcloud'
    AND table_name LIKE '%character%'
  ORDER BY (data_length + index_length) DESC"

# Monitor API response times
tail -f /var/log/nginx/access.log | grep "/apps/quest/api/character"
```

## Next Steps

1. **Create Sprite Assets:** Start with Stone Age base character and essential equipment
2. **Test Age Progression:** Complete tasks to level up and watch character evolve
3. **Add Integration Hooks:** Connect character system to existing level/achievement systems
4. **Customize Appearance:** Try equipping different items and see visual changes
5. **Extend System:** Add new ages, items, or character features as needed

## Support

For issues or questions:
- Check Nextcloud logs: `tail -f data/nextcloud.log`
- Browser console for frontend errors
- Review `IMPLEMENTATION_SUMMARY.md` for architecture details
- Test API endpoints directly with curl/Postman

The character system is now ready to use! 🎮🚀
