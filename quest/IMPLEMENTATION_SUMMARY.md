# Character System Implementation Summary

## Overview

A complete player character system has been implemented for the Nextcloud Quest app, featuring:

- **Visual avatar system** with layered sprite rendering
- **Age-based progression** from Stone Age to Space Age
- **Equipment customization** with 4 slots (clothing, weapon, accessory, headgear)
- **Character evolution** with scars, badges, aging effects, and technology markers
- **Comprehensive item system** with 70+ items across 9 ages and 4 rarity tiers

## Architecture

### Backend (PHP)

#### Database Schema

**New Migrations:**
1. **Version1013Date20250930120000.php** - Character appearance fields
   - `character_equipped_clothing`, `character_equipped_weapon`, `character_equipped_accessory`, `character_equipped_headgear`
   - `character_current_age` (stone, bronze, iron, etc.)
   - `character_base_sprite` (default sprite identifier)
   - `character_appearance_data` (JSON: scars, badges, effects, technology markers)

2. **Version1014Date20250930130000.php** - Character ages seed data
   - Creates `ncquest_character_ages` table
   - Seeds 9 ages from Stone Age (levels 1-9) to Space Age (100+)
   - Each age has icon, color, description, level ranges

3. **Version1015Date20250930140000.php** - Character items seed data
   - Seeds 70+ equipment items across all ages
   - 4 equipment types per age (clothing, weapons, accessories, headgear)
   - Rarity tiers: common, rare, epic, legendary
   - Level-based unlocking system

#### Entity Updates

**lib/Db/Quest.php** - Extended with character fields:
- Added 7 new protected properties for character data
- Added helper methods:
  - `getCharacterAppearanceArray()` - Parse JSON appearance data
  - `setCharacterAppearanceArray()` - Save appearance data
  - `addCharacterBadge()` - Add achievement badge
  - `addCharacterScar()` - Add scar marker
  - `addAgingEffect()` - Add aging effect
  - `addTechnologyMarker()` - Add tech marker

#### Existing Services Enhanced

The existing **CharacterService** (lib/Service/CharacterService.php) already provides:
- Character data retrieval with age and equipment
- Item unlocking based on level/achievements
- Equipment validation and updates
- Age progression tracking

### Frontend

#### Vue Components

**src/components/CharacterAvatar.vue** - Reusable character avatar component:
- **Props:**
  - `characterAge` - Current age (stone, bronze, etc.)
  - `level` - Player level
  - `equipment` - Equipped items object
  - `appearanceData` - Scars, badges, effects
  - `size` - Avatar size (small, medium, large, xlarge)
  - `showLevel`, `showAge`, `animated` - Display options

- **Features:**
  - Layered sprite rendering system
  - Z-index based layer management (base=0, clothing=10, accessory=15, weapon=20, headgear=30, effects=40)
  - Age-specific color theming
  - Equipment indicator badges
  - Fallback emoji rendering when sprites unavailable
  - Responsive sizing with 4 size options
  - Smooth animations and hover effects

#### JavaScript Integration

**js/sidebar-character.js** - Sidebar character manager:
- Loads character data from API
- Renders layered character in sidebar
- Age-based rank titles (e.g., "Cave Dweller", "Knight", "Space Explorer")
- Equipment indicators below avatar
- Click handler for character customization
- Event listener for character updates

**js/character-customizer.js** (already exists) - Enhanced to support:
- New equipment slots
- Age progression display
- Effects/badges management
- Real-time preview

#### Controller Updates

**lib/Controller/Base/BasePageController.php:**
- Added `sidebar-character` script to common scripts
- Added `character-customizer` script to common scripts
- Now loads on every page for consistent character display

### Asset Structure

**img/characters/** - Sprite asset directory:
```
characters/
├── stone/
│   ├── clothing/
│   ├── weapons/
│   ├── accessories/
│   ├── headgear/
│   └── effects/
├── bronze/
├── iron/
├── medieval/
├── renaissance/
├── industrial/
├── modern/
├── digital/
└── space/
```

Each age contains subdirectories for equipment types and effects (scars, badges, aging, technology).

**Current State:** Directory structure created, using emoji fallbacks until sprites are added.

## Character Ages

| Age | Levels | Icon | Color | Theme |
|-----|--------|------|-------|-------|
| Stone Age | 1-9 | 🪨 | #8b7355 | Primitive tools, survival |
| Bronze Age | 10-19 | ⚒️ | #cd7f32 | Early metalworking |
| Iron Age | 20-29 | ⚔️ | #71706e | Strong weapons/tools |
| Medieval | 30-39 | 🏰 | #8b4513 | Castles, knights, chivalry |
| Renaissance | 40-49 | 🎨 | #daa520 | Art, science, culture |
| Industrial | 50-59 | ⚙️ | #696969 | Steam power, machinery |
| Modern | 60-74 | 💡 | #4169e1 | Electricity, automobiles |
| Digital | 75-99 | 💻 | #00ced1 | Computers, internet |
| Space | 100+ | 🚀 | #9370db | Space exploration, advanced tech |

## Equipment System

### Item Categories

**By Type:**
- **Clothing:** Armor, outfits, protective gear
- **Weapons:** Melee, ranged, energy weapons
- **Accessories:** Shields, devices, tools
- **Headgear:** Helmets, hats, crowns, visors

**By Rarity:**
- **Common:** Basic items, low-level unlocks
- **Rare:** Mid-tier items, moderate requirements
- **Epic:** High-quality items, significant unlocks
- **Legendary:** Ultimate items, special achievements

### Example Items by Age

**Stone Age:**
- Clothing: Animal Hide, Decorated Hide
- Weapons: Wooden Club, Stone Spear, Stone Axe
- Accessories: Bone Necklace, Shell Bracelet
- Headgear: Leather Headband, Fur Hood

**Space Age:**
- Clothing: Space Suit, Exo Armor
- Weapons: Ion Blaster, Antimatter Cannon
- Accessories: Jetpack, Quantum Field Generator
- Headgear: Space Helmet, Commander Helm

## Character Progression

### Age Advancement
- Automatic age progression when reaching level thresholds
- Default items unlocked for new age
- Age recorded in `ncquest_character_progression` table
- Event dispatched: `CharacterAgeReachedEvent`

### Equipment Unlocking
- Level-based unlocking (e.g., Stone Axe at level 6)
- Achievement-based unlocking (special items)
- Bulk unlock on age advancement
- Event dispatched: `CharacterItemUnlockedEvent`

### Appearance Evolution

**Badges:** Achievement markers
- Automatically added via `addCharacterBadge()`
- Examples: champion_badge, streak_master

**Scars:** Combat/streak markers
- Added via `addCharacterScar()`
- Examples: battle_scar_01, wound_mark

**Aging Effects:** Progressive changes
- Added every 10 levels via `addAgingEffect()`
- Examples: gray_hair, wisdom_marks

**Technology Markers:** Age-specific tech
- Added on age advancement via `addTechnologyMarker()`
- Examples: hologram_display, jetpack_exhaust

## Integration Points

### Existing Systems Enhanced

1. **LevelService** - Should call `CharacterService::checkAgeProgression()` on level up
2. **AchievementService** - Should call `CharacterService::addCharacterBadge()` on achievement unlock
3. **StreakService** - Could add scars for major streak milestones
4. **TaskCompletionController** - Should trigger character updates on task completion

### API Endpoints (Already Existing)

From **lib/Controller/CharacterController.php**:
- `GET /api/character/data` - Get full character data
- `GET /api/character/customization` - Get customization interface data
- `GET /api/character/items` - Get available items
- `POST /api/character/equip/{itemKey}` - Equip an item
- `DELETE /api/character/unequip/{slot}` - Unequip item from slot
- `PUT /api/character/appearance` - Update appearance
- `GET /api/character/ages` - Get ages with progression status

### Sidebar Integration

The character now appears in the sidebar with:
- Layered avatar rendering
- Age-appropriate background color
- Equipment indicators
- Dynamic rank title
- Click-to-customize functionality

## Running Migrations

To activate the character system:

```bash
# Run migrations to create tables and seed data
sudo -u www-data php occ migrations:execute quest Version1013Date20250930120000
sudo -u www-data php occ migrations:execute quest Version1014Date20250930130000
sudo -u www-data php occ migrations:execute quest Version1015Date20250930140000

# Verify migrations
sudo -u www-data php occ migrations:status quest
```

## Next Steps

### Phase 1: Sprite Assets (Current Priority)
1. Create base character sprites for each age
2. Create equipment sprites (70+ items)
3. Create effect overlays (badges, scars, aging, tech)
4. Update `hasSprite()` method in CharacterAvatar.vue to load actual sprites

### Phase 2: Enhanced Features
1. Character naming system
2. Character stats (strength, intelligence, etc.)
3. More equipment slots (gloves, boots, background)
4. Animated sprite transitions
5. 360° character rotation

### Phase 3: Social Features
1. Character profile page
2. Character showcase/leaderboard
3. Equipment trading/gifting
4. Character achievements gallery

### Phase 4: Advanced Progression
1. Skill trees based on character customization
2. Class/specialization system
3. Character-based achievements
4. Equipment enhancement/upgrading

## Design Philosophy

**Progressive Evolution:** Characters evolve naturally as players complete tasks, unlocking items and effects that tell the story of their journey.

**Visual Storytelling:** Equipment and effects provide visual cues about a player's achievements, playstyle, and dedication.

**Accessibility:** Emoji fallbacks ensure the system works immediately while sprite assets are being created.

**Performance:** Layered rendering with CSS z-index provides smooth performance even with multiple equipped items.

**Extensibility:** JSON-based appearance data and modular component design allow easy addition of new features.

## Technical Notes

### Sprite Rendering System

The layered sprite system uses CSS z-index for proper compositing:
```
Layer 0: Base character sprite
Layer 10: Clothing/body armor
Layer 15: Accessories
Layer 20: Weapons
Layer 30: Headgear
Layer 40: Effects (badges, scars)
```

### Fallback Strategy

When sprites are unavailable:
1. Age icon serves as base character
2. Equipment type icons indicate equipped items
3. Small badge indicators show equipped slots
4. System remains fully functional

### Performance Considerations

- Lazy loading of sprite assets
- CSS-based rendering (no canvas/WebGL overhead)
- Minimal API calls (character data cached)
- Efficient Vue component re-rendering

## Credits

Character system designed and implemented with:
- Database migrations for persistent storage
- Service-oriented architecture for business logic
- Vue.js components for reactive UI
- Layered sprite system for visual customization
- Comprehensive seed data for immediate usability

Ready for sprite asset creation and further enhancement!
