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
 * @method string getTaskId()
 * @method void setTaskId(string $taskId)
 * @method string getTaskTitle()
 * @method void setTaskTitle(string $taskTitle)
 * @method int getXpEarned()
 * @method void setXpEarned(int $xpEarned)
 * @method string getCompletedAt()
 * @method void setCompletedAt(string $completedAt)
 */
class History extends Entity {
    protected ?int $id = null;
    protected string $userId;
    protected string $taskId;
    protected string $taskTitle;
    protected int $xpEarned;
    protected string $completedAt;
    
    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('userId', 'string');
        $this->addType('taskId', 'string');
        $this->addType('taskTitle', 'string');
        $this->addType('xpEarned', 'integer');
        $this->addType('completedAt', 'datetime');
    }
}