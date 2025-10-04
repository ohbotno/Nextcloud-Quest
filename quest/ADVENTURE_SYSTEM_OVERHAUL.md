# Adventure System Overhaul - Grid-Based Map

## Overview

The adventure system has been completely overhauled from a Mario-style linear path to a **2D grid-based exploration map** with procedural generation, age-themed areas, and node-based encounters.

## Key Features

### 1. **7x7 Grid Map (49 Nodes)**
- Procedurally generated node graph with guaranteed path to boss
- Click-to-move navigation system
- Open exploration within unlocked areas
- Visual connections between nodes

### 2. **Age-Themed Areas (9 Themes)**
Each area matches the character progression ages:
- **Stone Age** (Levels 1-9): Primitive enemies, basic equipment
- **Bronze Age** (Levels 10-19): Early metalworking rewards
- **Iron Age** (Levels 20-29): Stronger equipment and enemies
- **Medieval Age** (Levels 30-39): Knights and castles
- **Renaissance** (Levels 40-49): Muskets and art
- **Industrial Age** (Levels 50-59): Steam-powered challenges
- **Modern Age** (Levels 60-74): Corporate warfare
- **Digital Age** (Levels 75-99): Cyber enemies
- **Space Age** (Levels 100+): Cosmic threats

### 3. **Node Types**
- **START**: Beginning of the adventure
- **COMBAT**: Fight age-themed enemies
- **SHOP**: Purchase equipment (integrates with character system)
- **TREASURE**: Find equipment and gold
- **EVENT**: Random events (gold, XP, health restoration)
- **BOSS**: Final challenge with major rewards

### 4. **Progression System**
- Complete boss to finish area
- Generate new area with next age theme
- Track area history (all completed areas)
- Cumulative stats: total nodes explored, bosses defeated, areas completed

## Architecture

### Backend (PHP)

#### Database Tables (Migration: Version1016Date20251004120000)

**`ncquest_adventure_areas`** - Area history
- `id`, `user_id`, `area_number`, `age_key`
- `nodes_explored`, `total_nodes`, `is_completed`
- Tracks all completed and current areas

**`ncquest_adventure_maps`** - Node data
- `id`, `user_id`, `area_id`, `node_id`
- `node_type`, `grid_x`, `grid_y`, `connections`
- `is_unlocked`, `is_completed`, `reward_data`

**`ncquest_adventure_progress`** - Player state
- `id`, `user_id`, `current_area_id`, `current_node_id`
- `total_areas_completed`, `total_nodes_explored`, `total_bosses_defeated`

#### Services

**`AdventureMapService`** ([lib/Service/AdventureMapService.php](lib/Service/AdventureMapService.php))
- Procedural map generation with pathfinding
- Guaranteed path from START to BOSS
- Branch path creation for exploration
- Node unlocking and completion logic
- Database persistence

**`AdventureThemeService`** ([lib/Service/AdventureThemeService.php](lib/Service/AdventureThemeService.php))
- Age theme configurations (enemies, rewards, colors)
- Random enemy generation per age
- Boss encounters themed to each age
- Treasure pool matching age equipment
- Event generation with age-specific themes

#### Controller

**`AdventureController`** ([lib/Controller/AdventureController.php](lib/Controller/AdventureController.php))

API Endpoints:
- `GET /api/adventure/map` - Get current map state
- `POST /api/adventure/generate` - Generate new area
- `POST /api/adventure/move` - Move to node
- `POST /api/adventure/encounter` - Get node encounter details
- `POST /api/adventure/complete-node` - Complete node and unlock connections
- `POST /api/adventure/complete-boss` - Defeat boss and finish area
- `GET /api/adventure/progress` - Get overall adventure stats

### Frontend (JavaScript)

**`adventure-grid-map.js`** ([js/adventure-grid-map.js](js/adventure-grid-map.js))
- HTML Canvas rendering (pixel art style)
- 2D grid layout with visual connections
- Click-to-move interaction
- Node type icons and colors
- Player position indicator
- Age theme color integration

**Features:**
- Node click handling with validation (locked, completed, distance checks)
- Real-time map updates after actions
- Encounter system integration (combat, treasure, events, shop)
- Visual feedback for unlocked/completed nodes

### Integration Points

#### Character System
- Equipment rewards match current age
- Shop nodes link to character equipment page
- Age progression determines area themes

#### Combat System
- Age-themed enemies with scaling stats
- Boss encounters with unique names and descriptions
- Combat rewards (XP, gold, items)

#### Equipment System
- Treasure nodes drop age-appropriate items
- Shop nodes allow equipment purchases
- Item unlocks tied to age progression

## Usage Flow

### First Time
1. Player visits Adventure page
2. No active area found → show "Generate New Area" prompt
3. System generates area based on player's current level/age
4. START node is unlocked, player begins exploration

### Normal Gameplay
1. Click unlocked adjacent node to move
2. Trigger node encounter:
   - **Combat**: Fight enemy (integrates with combat.js)
   - **Treasure**: Receive equipment + gold
   - **Event**: Random bonus (gold, XP, health)
   - **Shop**: Browse/purchase equipment
   - **Boss**: Final combat challenge
3. Complete node → unlocks connected nodes
4. Defeat boss → area complete → generate next age theme

### Progression
- Each area completion increments area number
- New area uses next age theme in sequence
- Area history tracked for all completed areas
- Stats accumulate across all areas

## Configuration

### Procedural Generation Settings
Located in `AdventureMapService`:
- Grid size: 7x7 (configurable via `GRID_SIZE`)
- Main path: Guaranteed START → BOSS traversal
- Branch paths: 15-20 additional connections for exploration
- Node distribution: 60% combat, 16% treasure, 16% events

### Age Theme Customization
Located in `AdventureThemeService::getAllThemes()`:
- Enemy stats scale with age
- Boss health/attack increase per age
- Treasure pools reference age equipment items
- Event themes match age setting (e.g., "ancient_forge" vs "space_station")

## Future Enhancements

### Planned Features
1. **Combat Integration**: Full combat UI for enemy encounters
2. **Loot System**: Equipment drops with rarity tiers
3. **Event Variety**: More event types (puzzles, choices, mini-games)
4. **Map Modifiers**: Special area effects (foggy, cursed, blessed)
5. **Achievements**: Area-specific achievement triggers
6. **Leaderboards**: Fastest area completions, most nodes explored

### Technical Improvements
1. **Animation**: Player movement animation between nodes
2. **Path Highlighting**: Show available moves on hover
3. **Mini-map**: Overview of entire grid
4. **Save States**: Manual save/load area progress
5. **Difficulty Scaling**: Adjustable enemy difficulty

## Migration & Deployment

### Database Migration
```bash
# Run migration
sudo -u www-data php occ migrations:execute quest Version1016Date20251004120000
```

### Service Registration
Services and controller are registered in `lib/AppInfo/Application.php`:
- `AdventureMapService` - registered line 237-243
- `AdventureThemeService` - registered line 245-247
- `AdventureController` - registered line 266-275

### Routes
Defined in `appinfo/routes.php` (lines 80-87):
- All new endpoints prefixed with `/api/adventure/`
- Legacy adventure world routes preserved for backward compatibility

## Testing

### Manual Testing Steps
1. Visit `/apps/quest/adventure`
2. Click "Generate New Area"
3. Verify map renders with 49 nodes
4. Click START node → should show "Your adventure begins"
5. Move to adjacent unlocked node
6. Complete combat/treasure/event nodes
7. Verify nodes unlock connected neighbors
8. Defeat boss → verify area completion
9. Generate new area → verify age theme changes

### Debug Endpoints
- Check map state: `GET /api/adventure/map`
- Check progress: `GET /api/adventure/progress`
- View theme colors: Inspect `data.theme` in map response

## Files Modified/Created

### Created Files
- `lib/Migration/Version1016Date20251004120000.php`
- `lib/Service/AdventureMapService.php`
- `lib/Service/AdventureThemeService.php`
- `lib/Controller/AdventureController.php`
- `js/adventure-grid-map.js`
- `ADVENTURE_SYSTEM_OVERHAUL.md`

### Modified Files
- `lib/AppInfo/Application.php` (service registration)
- `appinfo/routes.php` (API routes)
- `templates/adventure.php` (UI template + CSS + initialization script)

## Performance Considerations

- Map generation is O(n²) for 7x7 grid (acceptable)
- Canvas rendering optimized with single draw pass
- Database queries use indexed lookups (user_id, area_id)
- JSON connection data kept small (node IDs only)

## Security

- All endpoints require authentication (`@NoAdminRequired`)
- CSRF protection via `@NoCSRFRequired` for AJAX
- User isolation via `user_id` in all queries
- Node unlock validation prevents cheating
- Connection validation ensures only adjacent moves

---

**Status**: ✅ Core implementation complete
**Next Steps**: Combat integration, loot system, event variety
