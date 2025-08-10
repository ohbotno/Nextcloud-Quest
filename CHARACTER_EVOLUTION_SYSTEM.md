# Nextcloud Quest - Character Evolution System Design

## Overview

This document outlines a comprehensive character evolution system that progresses users through historical ages based on their level progression in Nextcloud Quest. The system adds visual representation, customization options, and enhanced motivation for continued task completion.

## 1. Age Progression System

### Historical Ages and Level Requirements

| Age | Level Range | XP Range* | Era Description |
|-----|-------------|-----------|-----------------|
| **Stone Age** | 1-5 | 0-600 | Primitive tools, basic survival |
| **Bronze Age** | 6-10 | 600-2,200 | Metal working, early civilization |
| **Iron Age** | 11-20 | 2,200-13,000 | Advanced tools, warrior culture |
| **Classical Age** | 21-30 | 13,000-43,000 | Greek/Roman civilization peak |
| **Medieval Age** | 31-45 | 43,000-150,000 | Knights, castles, feudalism |
| **Renaissance** | 46-60 | 150,000-410,000 | Art, science, exploration |
| **Industrial Age** | 61-75 | 410,000-900,000 | Steam power, mass production |
| **Modern Age** | 76-90 | 900,000-1,800,000 | Electricity, automobiles, flight |
| **Digital Age** | 91-100 | 1,800,000-3,200,000 | Computers, internet, mobile |
| **Space Age** | 100+ | 3,200,000+ | Space exploration, future tech |

*XP calculations based on existing formula: 100 * 1.5^(level-1)

### Age Transition Mechanics

- **Automatic Progression**: Characters automatically enter new ages upon reaching level thresholds
- **Transition Celebration**: Special animations and notifications when entering new ages  
- **Retroactive Access**: Players can customize their character with items from all unlocked ages
- **Age Themes**: UI elements subtly reflect the current age (color schemes, backgrounds)

## 2. Character Customization Items

### Stone Age (Levels 1-5)
**Theme**: Primitive survival, natural materials

**Clothing**:
- Animal hide tunic (Level 1)
- Bone necklace (Level 2) 
- Fur boots (Level 3)
- Leaf crown (Level 4)
- Tribal face paint (Level 5)

**Weapons/Tools**:
- Stone axe (Level 1)
- Wooden spear (Level 3)
- Bone knife (Level 5)

**Accessories**:
- Shell bracelet (Level 2)
- Feather headpiece (Level 4)

### Bronze Age (Levels 6-10)
**Theme**: Early metallurgy, simple civilization

**Clothing**:
- Bronze-studded leather armor (Level 6)
- Woven cloth tunic (Level 7)
- Copper arm bands (Level 8)
- Ceremonial robe (Level 9)
- Chieftain's cloak (Level 10)

**Weapons/Tools**:
- Bronze sword (Level 6)
- Bronze shield (Level 8)
- Ceremonial mace (Level 10)

**Accessories**:
- Bronze torque (Level 7)
- Trading beads (Level 9)

### Iron Age (Levels 11-20)
**Theme**: Advanced metallurgy, warrior culture

**Clothing**:
- Iron chain mail (Level 11)
- Warrior's tunic (Level 13)
- Iron helm (Level 15)
- Battle cloak (Level 17)
- Warlord's armor set (Level 20)

**Weapons/Tools**:
- Iron sword (Level 11)
- War hammer (Level 14)
- Battle axe (Level 16)
- Iron spear (Level 18)
- Legendary iron blade (Level 20)

**Accessories**:
- Iron torque (Level 12)
- Victory wreath (Level 19)

### Classical Age (Levels 21-30)
**Theme**: Greek/Roman civilization, philosophy, architecture

**Clothing**:
- Toga (Level 21)
- Greek chiton (Level 23)
- Roman centurion armor (Level 25)
- Philosopher's robe (Level 27)
- Emperor's regalia (Level 30)

**Weapons/Tools**:
- Gladius (Level 21)
- Greek hoplon shield (Level 24)
- Trident (Level 26)
- Laurel crown (Level 28)
- Golden scepter (Level 30)

**Accessories**:
- Olive branch (Level 22)
- Scroll of wisdom (Level 29)

### Medieval Age (Levels 31-45)
**Theme**: Knights, castles, feudalism, chivalry

**Clothing**:
- Chainmail hauberk (Level 31)
- Knight's surcoat (Level 33)
- Plate armor (Level 35)
- Noble's doublet (Level 37)
- Royal robes (Level 40)
- Crusader armor (Level 43)
- King's crown (Level 45)

**Weapons/Tools**:
- Longsword (Level 31)
- Knight's shield (Level 34)
- War lance (Level 36)
- Crossbow (Level 38)
- Excalibur replica (Level 45)

**Accessories**:
- Heraldic banner (Level 32)
- Holy grail (Level 44)

### Renaissance (Levels 46-60)
**Theme**: Art, science, exploration, cultural rebirth

**Clothing**:
- Renaissance doublet (Level 46)
- Artist's smock (Level 48)
- Explorer's coat (Level 50)
- Nobleman's cape (Level 52)
- Royal Renaissance gown (Level 55)
- Master artisan outfit (Level 58)
- Lorenzo de' Medici costume (Level 60)

**Weapons/Tools**:
- Rapier (Level 46)
- Telescope (Level 49)
- Compass (Level 51)
- Artist's palette (Level 53)
- Leonardo's flying machine model (Level 60)

**Accessories**:
- Feathered hat (Level 47)
- Renaissance jewelry (Level 54)
- Patron's ring (Level 57)

### Industrial Age (Levels 61-75)
**Theme**: Steam power, industrialization, progress

**Clothing**:
- Factory worker outfit (Level 61)
- Victorian gentleman suit (Level 63)
- Engineer's uniform (Level 65)
- Industrialist's top hat (Level 67)
- Steam punk goggles (Level 70)
- Railroad conductor uniform (Level 72)
- Captain of industry attire (Level 75)

**Weapons/Tools**:
- Steam wrench (Level 61)
- Pocket watch (Level 64)
- Steam engine model (Level 66)
- Industrial blueprint (Level 68)
- Locomotive miniature (Level 75)

**Accessories**:
- Brass buttons (Level 62)
- Steam gauge badge (Level 69)
- Golden railway spike (Level 74)

### Modern Age (Levels 76-90)
**Theme**: 20th century innovation, electricity, automotive

**Clothing**:
- Business suit (Level 76)
- Aviator jacket (Level 78)
- Lab coat (Level 80)
- Military uniform (Level 82)
- 1950s formal wear (Level 85)
- Space race jumpsuit (Level 88)
- Moon landing suit (Level 90)

**Weapons/Tools**:
- Radio (Level 76)
- Airplane model (Level 79)
- Light bulb (Level 81)
- Atomic symbol (Level 83)
- Television (Level 86)
- Rocket model (Level 90)

**Accessories**:
- Pilot wings (Level 77)
- Nobel Prize medal (Level 84)
- Astronaut helmet (Level 89)

### Digital Age (Levels 91-100)
**Theme**: Computer revolution, internet, mobile technology

**Clothing**:
- Tech startup hoodie (Level 91)
- Programmer's t-shirt (Level 93)
- Silicon Valley casual (Level 95)
- Tech CEO outfit (Level 97)
- Cyberpunk ensemble (Level 100)

**Weapons/Tools**:
- Personal computer (Level 91)
- Mobile phone (Level 94)
- VR headset (Level 96)
- Holographic interface (Level 98)
- Quantum computer (Level 100)

**Accessories**:
- Binary code tattoo (Level 92)
- Smart watch (Level 99)

### Space Age (Levels 100+)
**Theme**: Future technology, space exploration, sci-fi

**Clothing**:
- Space explorer suit (Level 101)
- Alien diplomat robes (Level 105)
- Interstellar captain uniform (Level 110)
- Cosmic entity cloak (Level 120)
- Time traveler's outfit (Level 150)

**Weapons/Tools**:
- Plasma sword (Level 101)
- Starship model (Level 108)
- Alien artifact (Level 115)
- Time manipulation device (Level 125)
- Universal translator (Level 140)

## 3. Visual Asset Requirements

### Character Base Sprites
- **Base Character Models**: 4 body types (2 masculine, 2 feminine) in neutral poses
- **Age Variations**: Each base model adapted for different historical contexts
- **Pose Variations**: Standing, celebration, working poses for different contexts

### Layered Asset System
All customization items designed as separate layers for dynamic combination:

**Layer Order** (bottom to top):
1. Base character body
2. Underwear/base clothing
3. Main clothing/armor
4. Accessories (jewelry, etc.)
5. Weapons/tools (held items)
6. Headgear/helmets
7. Special effects (glow, particles)

### Technical Specifications
- **Format**: PNG with transparency
- **Resolution**: 128x128px for dashboard avatars, 256x256px for detailed views
- **Style**: Consistent pixel art or illustrated style matching Nextcloud's design language
- **Color Palette**: Complementary to existing Nextcloud themes

### Animation Requirements
- **Idle Animation**: Subtle breathing/standing animation (2-3 frames)
- **Transition Effects**: Age progression celebration (particle effects)
- **Achievement Unlock**: Item glow/sparkle when unlocked
- **Level Up**: Character celebration animation

## 4. Unlock Mechanics

### Level-Based Unlocks
- **Primary Progression**: Each level unlocks 1-2 items from the current age
- **Milestone Rewards**: Special items at major level milestones (multiples of 5)
- **Age Transition Bonus**: Complete outfit set unlocked when entering new age

### Achievement-Based Unlocks
Integrate with existing achievement system for special items:

**Achievement Categories**:
- **Streak Achievements**: Unlock rare accessories for maintaining long streaks
- **Task Volume**: Special weapons/tools for completing many tasks
- **Priority Focus**: Unique items for consistently completing high-priority tasks
- **Speed Achievements**: Rare items for rapid task completion
- **Dedication**: Legendary items for long-term consistent usage

**Special Achievement Items**:
- **Master Craftsman Tools**: For completing 1000 tasks (any age)
- **Time Lord's Watch**: For maintaining 365-day streak
- **Multitasker's Crown**: For completing 10 tasks in one day
- **Perfectionist's Medallion**: For 100% task completion rate over 30 days
- **Ancient Relic**: For reaching max level in any age

### Seasonal/Event Unlocks
- **Holiday Items**: Special seasonal decorations and accessories
- **Anniversary Items**: Exclusive items for app usage anniversaries
- **Community Events**: Rare items for participating in global challenges

## 5. Database Schema Extensions

### New Tables

#### `nc_quest_characters`
```sql
CREATE TABLE nc_quest_characters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id VARCHAR(64) NOT NULL,
    current_age VARCHAR(32) NOT NULL DEFAULT 'stone_age',
    base_character_id INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES oc_users(uid)
);
```

#### `nc_quest_character_items`
```sql
CREATE TABLE nc_quest_character_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id VARCHAR(64) NOT NULL,
    item_id VARCHAR(64) NOT NULL,
    unlocked_at DATETIME NOT NULL,
    unlock_method VARCHAR(32) NOT NULL, -- 'level', 'achievement', 'event'
    unlock_reference VARCHAR(64), -- level number, achievement key, event id
    FOREIGN KEY (user_id) REFERENCES oc_users(uid),
    UNIQUE(user_id, item_id)
);
```

#### `nc_quest_character_customization`
```sql
CREATE TABLE nc_quest_character_customization (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id VARCHAR(64) NOT NULL,
    slot_type VARCHAR(32) NOT NULL, -- 'clothing', 'weapon', 'accessory', 'headgear'
    equipped_item_id VARCHAR(64),
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES oc_users(uid),
    UNIQUE(user_id, slot_type)
);
```

#### `nc_quest_item_catalog`
```sql
CREATE TABLE nc_quest_item_catalog (
    item_id VARCHAR(64) PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    description TEXT,
    age VARCHAR(32) NOT NULL,
    category VARCHAR(32) NOT NULL, -- 'clothing', 'weapon', 'accessory', 'headgear'
    unlock_level INTEGER,
    unlock_achievement VARCHAR(64),
    rarity VARCHAR(16) NOT NULL DEFAULT 'common', -- 'common', 'rare', 'epic', 'legendary'
    sprite_path VARCHAR(256) NOT NULL,
    created_at DATETIME NOT NULL
);
```

### Existing Table Modifications

#### Update `nc_quest_quests` table:
```sql
ALTER TABLE nc_quest_quests ADD COLUMN character_age VARCHAR(32) DEFAULT 'stone_age';
```

## 6. Service Layer Implementation

### New Service Classes

#### `CharacterService.php`
**Primary Functions**:
- `getCurrentCharacter($userId)`: Get user's current character configuration
- `updateCharacterCustomization($userId, $customization)`: Update equipped items
- `getAvailableItems($userId)`: Get all unlocked items for user
- `unlockItem($userId, $itemId, $method, $reference)`: Unlock new item
- `getCharacterForAge($userId, $age)`: Get character appearance for specific age
- `calculateAgeProgression($level)`: Determine current age from level

#### `CharacterUnlockService.php`
**Primary Functions**:
- `checkLevelUnlocks($userId, $oldLevel, $newLevel)`: Process level-based unlocks
- `checkAchievementUnlocks($userId, $achievementKey)`: Process achievement unlocks
- `getUpcomingUnlocks($userId)`: Show preview of next unlockable items
- `getUnlockHistory($userId)`: Get chronological unlock history

#### `CharacterAssetService.php`
**Primary Functions**:
- `generateCharacterSprite($userId)`: Create composite character image
- `getItemSprite($itemId)`: Get individual item sprite
- `validateItemCombination($items)`: Check for item conflicts
- `getCachedCharacterSprite($userId)`: Efficient sprite caching

### Integration with Existing Services

#### `XPService.php` Modifications:
- Add character unlock checks in `awardXP()` method
- Update level progression to trigger character age transitions
- Add character-related data to XP award responses

#### `LevelService.php` Modifications:
- Include character progression in level info
- Add character milestones to level milestone data
- Update upcoming levels to show character unlocks

## 7. UI Integration Points

### Dashboard Integration

#### Main Character Display
- **Location**: Left sidebar, below quick actions
- **Size**: 120x120px avatar with current equipment
- **Interactions**: Click to open character customization panel
- **Context**: Shows current age and next unlock preview

#### Character Customization Panel
- **Trigger**: Click on main character avatar
- **Layout**: Modal dialog with character preview and item slots
- **Features**:
  - Live preview of changes
  - Tabbed interface by item category
  - Filter by age/rarity
  - Item lore and unlock information

#### Age Progression Indicator
- **Location**: Top of dashboard, integrated with level indicator
- **Display**: Age name with progress bar to next age
- **Animation**: Special effects during age transitions

### Component Updates

#### `QuestDashboard.vue`
```vue
<!-- Add to sidebar after quick actions -->
<div class="sidebar-section character-section">
    <h3>{{ t('nextcloudquest', 'Your Character') }}</h3>
    <CharacterAvatar 
        :user-id="user.uid"
        :clickable="true"
        @click="showCharacterCustomization = true"
    />
    <div class="character-age">
        <strong>{{ currentAge.name }}</strong>
        <small>{{ t('nextcloudquest', 'Level {level}', { level: stats.level }) }}</small>
    </div>
</div>

<!-- Character customization modal -->
<CharacterCustomization
    v-if="showCharacterCustomization"
    @close="showCharacterCustomization = false"
/>
```

#### New Components

##### `CharacterAvatar.vue`
**Purpose**: Display user's character with current customization
**Props**: `userId`, `size`, `clickable`, `showAge`
**Features**: 
- Layered sprite rendering
- Loading states
- Hover effects for interactive avatars
- Age-appropriate background themes

##### `CharacterCustomization.vue`
**Purpose**: Full character customization interface
**Features**:
- Equipment slots (clothing, weapon, accessory, headgear)
- Item browser with filtering and search
- Preview with real-time updates
- Save/cancel functionality
- Item information tooltips

##### `AgeProgressIndicator.vue`
**Purpose**: Show current age and progress to next age
**Features**:
- Visual age timeline
- Progress bar with XP information
- Next age preview
- Achievement milestones

##### `ItemUnlockNotification.vue`
**Purpose**: Celebrate new item unlocks
**Features**:
- Animated item reveal
- Unlock method explanation
- Quick equip option
- Item lore/description

### Theme Integration

#### Game Theme Enhancements
- **Character Backgrounds**: Themed environments matching current age
- **Item Rarity Colors**: Distinct color coding for item rarity
- **Animation Effects**: Particle effects for unlocks and age transitions
- **Sound Effects**: Optional audio feedback for interactions

#### Professional Theme Adaptations
- **Simplified Sprites**: More abstract, icon-like character representations
- **Minimal Animations**: Subtle, professional appearance changes
- **Muted Colors**: Consistent with professional color palette
- **Business Context**: Frame character progression as "professional development"

## 8. Implementation Timeline

### Phase 1: Foundation (2-3 weeks)
- Database schema implementation
- Character service layer development
- Basic character avatar component
- Item catalog population

### Phase 2: Core Features (3-4 weeks)
- Character customization interface
- Level-based unlock system
- Age progression mechanics
- Integration with existing XP/level system

### Phase 3: Visual Assets (4-6 weeks)
- Character base sprite creation
- Age-specific item sprite creation
- Animation system implementation
- Asset optimization and caching

### Phase 4: Advanced Features (2-3 weeks)
- Achievement-based unlocks
- Character themes and backgrounds
- Advanced customization options
- Performance optimization

### Phase 5: Polish & Testing (2-3 weeks)
- Cross-browser compatibility
- Mobile responsiveness
- User testing and feedback
- Documentation and tutorials

## 9. Future Enhancement Opportunities

### Social Features
- **Character Gallery**: View other users' characters (privacy-controlled)
- **Style Competitions**: Community voting on character designs
- **Guild Systems**: Team-based character themes and unlocks

### Advanced Customization
- **Color Variations**: Recolor existing items with achievement unlocks
- **Accessory Stacking**: Multiple accessories in same slot
- **Pose Selection**: Different character poses and expressions
- **Environment Backgrounds**: Age-appropriate scene backgrounds

### Gamification Extensions
- **Character Stats**: Attributes that affect gameplay bonuses
- **Equipment Bonuses**: Items that provide XP multipliers or special effects
- **Collection Challenges**: Complete outfit sets for bonus rewards
- **Trading System**: Exchange duplicate items with other users

### Technical Enhancements
- **3D Character Models**: Upgrade from 2D sprites to 3D avatars
- **Mobile App Integration**: Sync character across web and mobile
- **AR Features**: View character in augmented reality
- **API Extensions**: Allow third-party apps to display user characters

## 10. Success Metrics

### Engagement Metrics
- **Character Customization Usage**: % of users who modify their character
- **Daily Avatar Views**: How often users view character progression
- **Age Progression Rate**: Time spent in each age
- **Item Collection Rate**: % of available items unlocked by users

### Retention Metrics
- **Unlock-Driven Task Completion**: Tasks completed to reach unlock milestones
- **Return Rate After Age Transition**: User activity after entering new ages
- **Long-term Engagement**: Character system impact on 30/60/90-day retention

### User Satisfaction
- **Customization Diversity**: Variety in user character choices
- **Progression Satisfaction**: User feedback on unlock pacing
- **Visual Appeal**: User ratings of character designs and animations

This character evolution system provides a compelling visual progression that transforms the abstract concept of productivity levels into tangible, collectible rewards that users can see and customize. By tying character advancement to both level progression and achievement unlocks, it creates multiple pathways for engagement while maintaining the core focus on task completion that drives Nextcloud Quest's primary value proposition.