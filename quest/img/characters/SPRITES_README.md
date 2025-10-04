# Character Sprites Structure

This directory contains the layered sprite system for character customization and evolution across different ages.

## Directory Structure

```
characters/
├── stone/              # Stone Age (Levels 1-9)
├── bronze/             # Bronze Age (Levels 10-19)
├── iron/               # Iron Age (Levels 20-29)
├── medieval/           # Medieval Age (Levels 30-39)
├── renaissance/        # Renaissance (Levels 40-49)
├── industrial/         # Industrial Age (Levels 50-59)
├── modern/             # Modern Age (Levels 60-74)
├── digital/            # Digital Age (Levels 75-99)
└── space/              # Space Age (Levels 100+)
```

Each age directory contains:
- `clothing/` - Body armor, outfits, costumes
- `weapons/` - Weapons and tools
- `accessories/` - Shields, banners, devices
- `headgear/` - Helmets, hats, crowns
- `effects/` - Badges, scars, aging effects, technology markers

## Sprite Guidelines

### Format
- **SVG preferred** for scalability
- **PNG fallback** at 512x512px resolution
- Transparent backgrounds
- Layer-optimized for compositing

### Naming Convention
```
{age}_{type}_{itemkey}.svg
Example: stone_weapon_club.svg
```

### Layer Order (Z-Index)
1. Base character (0)
2. Clothing/Body (10)
3. Accessories (15)
4. Weapons (20)
5. Headgear (30)
6. Effects/Badges (40)

### Color Palette per Age

**Stone Age**: Earth tones, browns, grays
**Bronze Age**: Bronze, copper, tan
**Iron Age**: Dark grays, silvers
**Medieval**: Rich reds, blues, gold
**Renaissance**: Vibrant colors, ornate details
**Industrial**: Dark colors, brass accents
**Modern**: Muted professional colors
**Digital**: Neon blues, cyans, purples
**Space**: Metallic silvers, deep space blues

## Creating New Sprites

1. Design sprite at 512x512px canvas
2. Ensure transparent background
3. Align center point for proper layering
4. Export as SVG (preferred) or PNG
5. Place in appropriate age/type folder
6. Update sprite manifest if using automated loading

## Effects System

### Scars
- Located in `{age}/effects/scars/`
- Applied based on task completion milestones
- Examples: battle_scar_01.svg, wound_mark.svg

### Badges
- Located in `{age}/effects/badges/`
- Awarded for achievements
- Examples: champion_badge.svg, streak_master.svg

### Aging Effects
- Located in `{age}/effects/aging/`
- Progressive changes every 10 levels
- Examples: gray_hair.svg, wisdom_marks.svg

### Technology Markers
- Located in `{age}/effects/technology/`
- Age-specific tech indicators
- Examples: hologram_display.svg, jetpack_exhaust.svg

## Placeholder Sprites

Until custom sprites are created, the system uses:
- Age emoji icons as base sprites
- Type emoji icons for equipment
- Fallback rendering with CSS

## Contributing Sprites

To contribute character sprites:
1. Follow the guidelines above
2. Ensure sprites work across all ages
3. Test layering and compositing
4. Submit sprite with item definition
