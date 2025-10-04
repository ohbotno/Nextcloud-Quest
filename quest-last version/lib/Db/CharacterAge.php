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
 * Character Age Entity
 * 
 * @method string getAgeKey()
 * @method void setAgeKey(string $ageKey)
 * @method string getAgeName()
 * @method void setAgeName(string $ageName)
 * @method int getMinLevel()
 * @method void setMinLevel(int $minLevel)
 * @method int|null getMaxLevel()
 * @method void setMaxLevel(?int $maxLevel)
 * @method string|null getAgeDescription()
 * @method void setAgeDescription(?string $ageDescription)
 * @method string|null getAgeColor()
 * @method void setAgeColor(?string $ageColor)
 * @method string|null getAgeIcon()
 * @method void setAgeIcon(?string $ageIcon)
 * @method bool getIsActive()
 * @method void setIsActive(bool $isActive)
 * @method \DateTime getCreatedAt()
 * @method void setCreatedAt(\DateTime $createdAt)
 */
class CharacterAge extends Entity implements JsonSerializable {
    
    /** @var string */
    protected $ageKey;
    
    /** @var string */
    protected $ageName;
    
    /** @var int */
    protected $minLevel;
    
    /** @var int|null */
    protected $maxLevel;
    
    /** @var string|null */
    protected $ageDescription;
    
    /** @var string|null */
    protected $ageColor;
    
    /** @var string|null */
    protected $ageIcon;
    
    /** @var bool */
    protected $isActive;
    
    /** @var \DateTime */
    protected $createdAt;

    public function __construct() {
        $this->addType('minLevel', 'integer');
        $this->addType('maxLevel', 'integer');
        $this->addType('isActive', 'boolean');
        $this->addType('createdAt', 'datetime');
    }

    /**
     * Check if a level falls within this age
     *
     * @param int $level
     * @return bool
     */
    public function containsLevel(int $level): bool {
        if ($level < $this->minLevel) {
            return false;
        }
        
        if ($this->maxLevel !== null && $level > $this->maxLevel) {
            return false;
        }
        
        return true;
    }

    /**
     * Get the level range as a string
     *
     * @return string
     */
    public function getLevelRange(): string {
        if ($this->maxLevel === null) {
            return $this->minLevel . '+';
        }
        
        if ($this->minLevel === $this->maxLevel) {
            return (string)$this->minLevel;
        }
        
        return $this->minLevel . '-' . $this->maxLevel;
    }

    /**
     * Check if this is the final age (no max level)
     *
     * @return bool
     */
    public function isFinalAge(): bool {
        return $this->maxLevel === null;
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'age_key' => $this->ageKey,
            'age_name' => $this->ageName,
            'min_level' => $this->minLevel,
            'max_level' => $this->maxLevel,
            'age_description' => $this->ageDescription,
            'age_color' => $this->ageColor,
            'age_icon' => $this->ageIcon,
            'level_range' => $this->getLevelRange(),
            'is_final' => $this->isFinalAge(),
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s')
        ];
    }
}