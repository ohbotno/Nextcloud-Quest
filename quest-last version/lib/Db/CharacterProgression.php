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
 * Character Progression Entity
 * 
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getAgeKey()
 * @method void setAgeKey(string $ageKey)
 * @method \DateTime getReachedAt()
 * @method void setReachedAt(\DateTime $reachedAt)
 * @method int getReachedAtLevel()
 * @method void setReachedAtLevel(int $reachedAtLevel)
 * @method int getReachedWithXp()
 * @method void setReachedWithXp(int $reachedWithXp)
 */
class CharacterProgression extends Entity implements JsonSerializable {
    
    /** @var string */
    protected $userId;
    
    /** @var string */
    protected $ageKey;
    
    /** @var \DateTime */
    protected $reachedAt;
    
    /** @var int */
    protected $reachedAtLevel;
    
    /** @var int */
    protected $reachedWithXp;

    public function __construct() {
        $this->addType('reachedAt', 'datetime');
        $this->addType('reachedAtLevel', 'integer');
        $this->addType('reachedWithXp', 'integer');
    }

    /**
     * Check if progression is recent (within last 24 hours)
     *
     * @return bool
     */
    public function isRecent(): bool {
        $oneDayAgo = new \DateTime('-1 day');
        return $this->reachedAt > $oneDayAgo;
    }

    /**
     * Get time since progression in human-readable format
     *
     * @return string
     */
    public function getTimeSinceProgression(): string {
        $now = new \DateTime();
        $diff = $now->diff($this->reachedAt);

        if ($diff->days > 0) {
            return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'Just now';
        }
    }

    /**
     * Get progression milestone description
     *
     * @return string
     */
    public function getMilestoneDescription(): string {
        return "Reached {$this->ageKey} Age at level {$this->reachedAtLevel} with {$this->reachedWithXp} total XP";
    }

    public function jsonSerialize(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'age_key' => $this->ageKey,
            'reached_at' => $this->reachedAt?->format('Y-m-d H:i:s'),
            'reached_at_level' => $this->reachedAtLevel,
            'reached_with_xp' => $this->reachedWithXp,
            'is_recent' => $this->isRecent(),
            'time_since_progression' => $this->getTimeSinceProgression(),
            'milestone_description' => $this->getMilestoneDescription()
        ];
    }
}