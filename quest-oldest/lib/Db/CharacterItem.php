<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Character Item Entity
 * 
 * @method string getItemKey()
 * @method void setItemKey(string $itemKey)
 * @method string getItemName()
 * @method void setItemName(string $itemName)
 * @method string getItemType()
 * @method void setItemType(string $itemType)
 * @method string getAgeKey()
 * @method void setAgeKey(string $ageKey)
 * @method string|null getItemDescription()
 * @method void setItemDescription(?string $itemDescription)
 * @method int|null getUnlockLevel()
 * @method void setUnlockLevel(?int $unlockLevel)
 * @method string|null getUnlockAchievement()
 * @method void setUnlockAchievement(?string $unlockAchievement)
 * @method string getItemRarity()
 * @method void setItemRarity(string $itemRarity)
 * @method string|null getSpritePath()
 * @method void setSpritePath(?string $spritePath)
 * @method int getSpriteLayer()
 * @method void setSpriteLayer(int $spriteLayer)
 * @method bool getIsDefault()
 * @method void setIsDefault(bool $isDefault)
 * @method bool getIsActive()
 * @method void setIsActive(bool $isActive)
 * @method \DateTime getCreatedAt()
 * @method void setCreatedAt(\DateTime $createdAt)
 */
class CharacterItem extends Entity implements JsonSerializable {
    
    /** @var string */
    protected $itemKey;
    
    /** @var string */
    protected $itemName;
    
    /** @var string */
    protected $itemType;
    
    /** @var string */
    protected $ageKey;
    
    /** @var string|null */
    protected $itemDescription;
    
    /** @var int|null */
    protected $unlockLevel;
    
    /** @var string|null */
    protected $unlockAchievement;
    
    /** @var string */
    protected $itemRarity;
    
    /** @var string|null */
    protected $spritePath;
    
    /** @var int */
    protected $spriteLayer;
    
    /** @var bool */
    protected $isDefault;
    
    /** @var bool */
    protected $isActive;
    
    /** @var \DateTime */
    protected $createdAt;

    // Item types
    public const TYPE_CLOTHING = 'clothing';
    public const TYPE_WEAPON = 'weapon';
    public const TYPE_ACCESSORY = 'accessory';
    public const TYPE_HEADGEAR = 'headgear';

    // Item rarities
    public const RARITY_COMMON = 'common';
    public const RARITY_RARE = 'rare';
    public const RARITY_EPIC = 'epic';
    public const RARITY_LEGENDARY = 'legendary';

    public function __construct() {
        $this->addType('unlockLevel', 'integer');
        $this->addType('spriteLayer', 'integer');
        $this->addType('isDefault', 'boolean');
        $this->addType('isActive', 'boolean');
        $this->addType('createdAt', 'datetime');
    }

    /**
     * Get all valid item types
     *
     * @return array
     */
    public static function getValidTypes(): array {
        return [
            self::TYPE_CLOTHING,
            self::TYPE_WEAPON,
            self::TYPE_ACCESSORY,
            self::TYPE_HEADGEAR
        ];
    }

    /**
     * Get all valid rarities
     *
     * @return array
     */
    public static function getValidRarities(): array {
        return [
            self::RARITY_COMMON,
            self::RARITY_RARE,
            self::RARITY_EPIC,
            self::RARITY_LEGENDARY
        ];
    }

    /**
     * Get rarity color
     *
     * @return string
     */
    public function getRarityColor(): string {
        $colors = [
            self::RARITY_COMMON => '#9e9e9e',     // Gray
            self::RARITY_RARE => '#2196f3',       // Blue
            self::RARITY_EPIC => '#9c27b0',       // Purple
            self::RARITY_LEGENDARY => '#ff5722'   // Orange
        ];

        return $colors[$this->itemRarity] ?? $colors[self::RARITY_COMMON];
    }

    /**
     * Get type icon
     *
     * @return string
     */
    public function getTypeIcon(): string {
        $icons = [
            self::TYPE_CLOTHING => '👕',
            self::TYPE_WEAPON => '⚔️',
            self::TYPE_ACCESSORY => '📿',
            self::TYPE_HEADGEAR => '👑'
        ];

        return $icons[$this->itemType] ?? '📦';
    }

    /**
     * Check if item can be unlocked at a specific level
     *
     * @param int $level
     * @return bool
     */
    public function canUnlockAtLevel(int $level): bool {
        if ($this->unlockLevel === null) {
            return true; // No level requirement
        }

        return $level >= $this->unlockLevel;
    }

    /**
     * Check if item requires an achievement to unlock
     *
     * @return bool
     */
    public function requiresAchievement(): bool {
        return $this->unlockAchievement !== null && !empty($this->unlockAchievement);
    }

    /**
     * Get unlock requirements as a string
     *
     * @return string
     */
    public function getUnlockRequirements(): string {
        $requirements = [];

        if ($this->unlockLevel !== null) {
            $requirements[] = "Level {$this->unlockLevel}";
        }

        if ($this->requiresAchievement()) {
            $requirements[] = "Achievement: {$this->unlockAchievement}";
        }

        if (empty($requirements)) {
            return "No requirements";
        }

        return implode(', ', $requirements);
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'item_key' => $this->itemKey,
            'item_name' => $this->itemName,
            'item_type' => $this->itemType,
            'age_key' => $this->ageKey,
            'item_description' => $this->itemDescription,
            'unlock_level' => $this->unlockLevel,
            'unlock_achievement' => $this->unlockAchievement,
            'item_rarity' => $this->itemRarity,
            'sprite_path' => $this->spritePath,
            'sprite_layer' => $this->spriteLayer,
            'is_default' => $this->isDefault,
            'is_active' => $this->isActive,
            'rarity_color' => $this->getRarityColor(),
            'type_icon' => $this->getTypeIcon(),
            'unlock_requirements' => $this->getUnlockRequirements(),
            'requires_achievement' => $this->requiresAchievement(),
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s')
        ];
    }
}