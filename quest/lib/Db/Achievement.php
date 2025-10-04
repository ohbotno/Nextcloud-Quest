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
 * @method int getId()
 * @method void setId(int $id)
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method string getAchievementKey()
 * @method void setAchievementKey(string $achievementKey)
 * @method string getUnlockedAt()
 * @method void setUnlockedAt(string $unlockedAt)
 * @method int getNotified()
 * @method void setNotified(int $notified)
 * @method int getAchievementPoints()
 * @method void setAchievementPoints(int $achievementPoints)
 * @method string getAchievementCategory()
 * @method void setAchievementCategory(string $achievementCategory)
 * @method int getProgressCurrent()
 * @method void setProgressCurrent(int $progressCurrent)
 * @method int getProgressTarget()
 * @method void setProgressTarget(int $progressTarget)
 */
class Achievement extends Entity implements JsonSerializable {
    protected string $userId = '';
    protected string $achievementKey = '';
    protected string $unlockedAt = '';
    protected int $notified = 0;
    protected int $achievementPoints = 0;
    protected ?string $achievementCategory = null;
    protected int $progressCurrent = 0;
    protected int $progressTarget = 0;
    
    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('userId', 'string');
        $this->addType('achievementKey', 'string');
        $this->addType('unlockedAt', 'string');
        $this->addType('notified', 'integer');
        $this->addType('achievementPoints', 'integer');
        $this->addType('achievementCategory', 'string');
        $this->addType('progressCurrent', 'integer');
        $this->addType('progressTarget', 'integer');
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array {
        return [
            'id' => $this->getId(),
            'userId' => $this->getUserId(),
            'achievementKey' => $this->getAchievementKey(),
            'unlockedAt' => $this->getUnlockedAt(),
            'notified' => $this->getNotified(),
            'achievementPoints' => $this->getAchievementPoints(),
            'achievementCategory' => $this->getAchievementCategory(),
            'progressCurrent' => $this->getProgressCurrent(),
            'progressTarget' => $this->getProgressTarget(),
        ];
    }

    /**
     * Check if the achievement is unlocked
     * @return bool
     */
    public function isUnlocked(): bool {
        return !empty($this->getUnlockedAt());
    }

    /**
     * Get progress percentage (0-100)
     * @return float
     */
    public function getProgressPercentage(): float {
        $target = $this->getProgressTarget();
        if ($target <= 0) {
            return 0.0;
        }
        
        $current = $this->getProgressCurrent();
        return min(100.0, round(($current / $target) * 100, 1));
    }

    /**
     * Check if achievement is in progress (has progress but not unlocked)
     * @return bool
     */
    public function isInProgress(): bool {
        return !$this->isUnlocked() && $this->getProgressCurrent() > 0;
    }

    /**
     * Get achievement status string
     * @return string
     */
    public function getStatus(): string {
        if ($this->isUnlocked()) {
            return 'unlocked';
        } elseif ($this->isInProgress()) {
            return 'in-progress';
        } else {
            return 'locked';
        }
    }
}