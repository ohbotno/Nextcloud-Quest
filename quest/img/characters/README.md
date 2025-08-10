# Character Assets Directory

This directory contains character sprites and assets for the Nextcloud Quest character system.

## Directory Structure

```
img/characters/
├── ages/               # Age-specific backgrounds and themes
│   ├── stone/          # Stone Age assets
│   ├── bronze/         # Bronze Age assets
│   ├── iron/           # Iron Age assets
│   ├── classical/      # Classical Age assets
│   ├── medieval/       # Medieval Age assets
│   ├── renaissance/    # Renaissance assets
│   ├── industrial/     # Industrial Age assets
│   ├── modern/         # Modern Age assets
│   ├── digital/        # Digital Age assets
│   └── space/          # Space Age assets
├── base/               # Base character sprites
│   ├── avatar-128.png  # 128x128 dashboard avatar
│   └── avatar-256.png  # 256x256 detailed view
├── clothing/           # Clothing layer sprites
├── weapons/            # Weapon sprites
├── accessories/        # Accessory sprites
├── headgear/           # Headgear sprites
└── composite/          # Pre-generated composite sprites
```

## Asset Specifications

### Character Avatars
- **Dashboard Size**: 128x128 pixels
- **Detail Size**: 256x256 pixels
- **Format**: PNG with transparency
- **Background**: Transparent

### Equipment Sprites
- **Size**: 256x256 pixels (scaled down as needed)
- **Format**: PNG with transparency
- **Layers**: Separate PNGs for each equipment piece
- **Naming Convention**: `{age}_{type}_{item_key}.png`

### Color Themes
Each age has its own color palette defined in the database:
- Stone Age: #8b7355 (Brown)
- Bronze Age: #cd7f32 (Bronze)
- Iron Age: #71797e (Gray)
- Classical Age: #d4af37 (Gold)
- Medieval Age: #4b0082 (Purple)
- Renaissance Age: #800020 (Burgundy)
- Industrial Age: #2f4f4f (Dark Slate)
- Modern Age: #1e90ff (Blue)
- Digital Age: #00ff41 (Green)
- Space Age: #9370db (Purple)

## Implementation Notes

- Character sprites are layered in this order (bottom to top):
  1. Base character (layer 0)
  2. Clothing (layer 1)
  3. Accessories (layer 2)
  4. Weapons (layer 3)
  5. Headgear (layer 4)

- All sprites should be optimized for web delivery
- Fallback emojis are provided when sprites are not available
- The system gracefully handles missing assets with default representations

## Asset Generation

For production use, consider:
1. Creating sprite sheets for better performance
2. Implementing lazy loading for character assets
3. Using WebP format with PNG fallbacks
4. Implementing client-side sprite composition