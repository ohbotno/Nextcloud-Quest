<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

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
 */
class Achievement extends Entity {
    protected ?int $id = null;
    protected string $userId;
    protected string $achievementKey;
    protected string $unlockedAt;
    protected int $notified = 0;
    
    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('userId', 'string');
        $this->addType('achievementKey', 'string');
        $this->addType('unlockedAt', 'datetime');
        $this->addType('notified', 'integer');
    }
}