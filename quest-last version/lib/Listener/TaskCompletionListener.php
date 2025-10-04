<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Listener;

use OCA\NextcloudQuest\Integration\TasksApiIntegration;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\ILogger;

/**
 * Event listener for task completion events from the Tasks app
 * This listener triggers the quest system when tasks are completed
 */
class TaskCompletionListener implements IEventListener {
    private TasksApiIntegration $tasksIntegration;
    private ILogger $logger;
    
    public function __construct(
        TasksApiIntegration $tasksIntegration,
        ILogger $logger
    ) {
        $this->tasksIntegration = $tasksIntegration;
        $this->logger = $logger;
    }
    
    /**
     * Handle the event
     * 
     * @param Event $event
     */
    public function handle(Event $event): void {
        try {
            // Extract task information from the event
            $taskData = $this->extractTaskData($event);
            
            if (!$taskData) {
                $this->logger->warning('Could not extract task data from event', [
                    'eventClass' => get_class($event)
                ]);
                return;
            }
            
            $this->logger->info('Processing task completion event', [
                'taskId' => $taskData['taskId'],
                'userId' => $taskData['userId'],
                'taskTitle' => $taskData['taskTitle']
            ]);
            
            // Process the task completion through our integration
            $result = $this->tasksIntegration->handleTaskCompletion(
                $taskData['taskId'],
                $taskData['userId']
            );
            
            if ($result['success']) {
                $this->logger->info('Task completion processed successfully', [
                    'taskId' => $taskData['taskId'],
                    'userId' => $taskData['userId'],
                    'xpEarned' => $result['xp']['xp_earned'] ?? 0,
                    'newLevel' => $result['xp']['level'] ?? 1,
                    'newAchievements' => count($result['achievements'] ?? [])
                ]);
            } else {
                $this->logger->error('Failed to process task completion', [
                    'taskId' => $taskData['taskId'],
                    'userId' => $taskData['userId'],
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Task completion listener failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Extract task data from the event object
     * This method handles different event types from the Tasks app
     * 
     * @param Event $event
     * @return array|null
     */
    private function extractTaskData(Event $event): ?array {
        // Handle different event types based on the Tasks app's event structure
        $eventClass = get_class($event);
        
        try {
            // For generic events with task data
            if (method_exists($event, 'getTask')) {
                $task = $event->getTask();
                return [
                    'taskId' => $task['id'] ?? null,
                    'userId' => $task['uid'] ?? null,
                    'taskTitle' => $task['summary'] ?? 'Completed Task',
                    'priority' => $task['priority'] ?? 0
                ];
            }
            
            // For events with individual getters
            if (method_exists($event, 'getTaskId') && 
                method_exists($event, 'getUserId')) {
                
                $taskId = $event->getTaskId();
                $userId = $event->getUserId();
                $taskTitle = method_exists($event, 'getTaskTitle') ? 
                    $event->getTaskTitle() : 'Completed Task';
                $priority = method_exists($event, 'getPriority') ? 
                    $event->getPriority() : 0;
                
                return [
                    'taskId' => $taskId,
                    'userId' => $userId,
                    'taskTitle' => $taskTitle,
                    'priority' => $priority
                ];
            }
            
            // For events with a data array
            if (method_exists($event, 'getData')) {
                $data = $event->getData();
                if (is_array($data) && 
                    isset($data['taskId'], $data['userId'])) {
                    
                    return [
                        'taskId' => $data['taskId'],
                        'userId' => $data['userId'],
                        'taskTitle' => $data['taskTitle'] ?? 'Completed Task',
                        'priority' => $data['priority'] ?? 0
                    ];
                }
            }
            
            // For custom NextcloudQuest events (manual task completion)
            if ($eventClass === 'OCA\\NextcloudQuest\\Event\\TaskCompletedEvent') {
                if (method_exists($event, 'getTaskData')) {
                    return $event->getTaskData();
                }
            }
            
            $this->logger->debug('Unsupported event type for task completion', [
                'eventClass' => $eventClass,
                'availableMethods' => get_class_methods($event)
            ]);
            
            return null;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to extract task data from event', [
                'eventClass' => $eventClass,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}