<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getCurrentXp()
 * @method void setCurrentXp(int $currentXp)
 * @method int getLifetimeXp()
 * @method void setLifetimeXp(int $lifetimeXp)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method int getCurrentStreak()
 * @method void setCurrentStreak(int $currentStreak)
 * @method int getLongestStreak()
 * @method void setLongestStreak(int $longestStreak)
 * @method string|null getLastCompletionDate()
 * @method void setLastCompletionDate(?string $lastCompletionDate)
 * @method string getThemePreference()
 * @method void setThemePreference(string $themePreference)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 */
class Quest extends Entity {
    protected string $userId;
    protected int $currentXp = 0;
    protected int $lifetimeXp = 0;
    protected int $level = 1;
    protected int $currentStreak = 0;
    protected int $longestStreak = 0;
    protected ?string $lastCompletionDate = null;
    protected string $themePreference = 'game';
    protected string $createdAt;
    protected string $updatedAt;
    
    public function __construct() {
        $this->addType('userId', 'string');
        $this->addType('currentXp', 'integer');
        $this->addType('lifetimeXp', 'integer');
        $this->addType('level', 'integer');
        $this->addType('currentStreak', 'integer');
        $this->addType('longestStreak', 'integer');
        $this->addType('lastCompletionDate', 'datetime');
        $this->addType('themePreference', 'string');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
    }
}