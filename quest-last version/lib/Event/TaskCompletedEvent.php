<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Event;

use OCP\EventDispatcher\Event;

/**
 * Event triggered when a task is completed in Nextcloud Quest
 * This is used for manual task completions through our interface
 */
class TaskCompletedEvent extends Event {
    private array $taskData;
    
    public function __construct(array $taskData) {
        parent::__construct();
        $this->taskData = $taskData;
    }
    
    /**
     * Get task data
     * 
     * @return array
     */
    public function getTaskData(): array {
        return $this->taskData;
    }
    
    /**
     * Get task ID
     * 
     * @return string|null
     */
    public function getTaskId(): ?string {
        return $this->taskData['taskId'] ?? null;
    }
    
    /**
     * Get user ID
     * 
     * @return string|null
     */
    public function getUserId(): ?string {
        return $this->taskData['userId'] ?? null;
    }
    
    /**
     * Get task title
     * 
     * @return string
     */
    public function getTaskTitle(): string {
        return $this->taskData['taskTitle'] ?? 'Completed Task';
    }
    
    /**
     * Get task priority
     * 
     * @return string
     */
    public function getPriority(): string {
        return $this->taskData['priority'] ?? 'medium';
    }
}