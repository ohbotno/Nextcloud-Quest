<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IDBConnection;
use OCP\AppFramework\Http;

/**
 * Controller for handling task list operations
 * Manages integration with Nextcloud Tasks app via CalDAV
 */
class TaskListController extends Controller {
    /** @var IUserSession */
    private $userSession;
    
    /** @var IDBConnection */
    private $db;
    
    public function __construct(
        string $appName,
        IRequest $request,
        IUserSession $userSession,
        IDBConnection $db
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->db = $db;
    }
    
    /**
     * Get quest lists from Tasks app
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function getQuestLists(): JSONResponse {
        // First, let's test if the endpoint is reachable with a simple response
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], Http::STATUS_UNAUTHORIZED);
            }
            
            $userId = $user->getUID();
            
            // Check if Tasks app tables exist
            $tasksAvailable = $this->isTasksAppAvailable();
            
            if (!$tasksAvailable) {
                // Return empty success response with debug info
                return new JSONResponse([
                    'status' => 'success',
                    'message' => 'Tasks app not available - showing empty task lists',
                    'data' => [],
                    'debug' => [
                        'user_id' => $userId,
                        'tasks_app_available' => false,
                        'tables_checked' => 'calendars',
                        'suggestion' => 'Install and enable the Tasks app in Nextcloud to see task lists',
                        'endpoint_working' => true
                    ]
                ]);
            }
            
            // Tasks app is available, try to get task lists
            $taskLists = $this->getTaskLists($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $taskLists,
                'message' => empty($taskLists) ? 'No task lists found' : 'Found ' . count($taskLists) . ' task lists',
                'debug' => [
                    'user_id' => $userId,
                    'tasks_app_available' => true,
                    'task_lists_count' => count($taskLists),
                    'endpoint_working' => true
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to retrieve task lists: ' . $e->getMessage(),
                'debug' => [
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'endpoint_working' => true
                ]
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Check if Tasks app is available by verifying CalDAV tables exist
     * 
     * @return bool
     */
    private function isTasksAppAvailable(): bool {
        try {
            // Tasks app uses CalDAV, check for CalDAV tables
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendars')
                ->setMaxResults(1);
            
            $result = $qb->execute();
            $result->closeCursor();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get task lists for user from CalDAV calendars
     * 
     * @param string $userId
     * @return array
     */
    private function getTaskLists(string $userId): array {
        try {
            // Get task calendars (task lists) from CalDAV
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendars')
                ->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->like('components', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->orderBy('displayname', 'ASC');
            
            $result = $qb->execute();
            $calendars = $result->fetchAll();
            $result->closeCursor();
            
            $enhancedLists = [];
            foreach ($calendars as $calendar) {
                try {
                    $tasks = $this->getTasksInCalendar($userId, $calendar['id']);
                    $listData = [
                        'id' => $calendar['id'],
                        'name' => $calendar['displayname'],
                        'color' => $calendar['calendarcolor'] ?? '#0082c9',
                        'tasks' => $tasks,
                        'total_tasks' => count($tasks),
                        'completed_tasks' => count(array_filter($tasks, function($task) {
                            return $task['completed'] == 1;
                        }))
                    ];
                    $listData['pending_tasks'] = $listData['total_tasks'] - $listData['completed_tasks'];
                    $enhancedLists[] = $listData;
                    
                } catch (\Exception $e) {
                    // Continue with other calendars if one fails
                    continue;
                }
            }
            
            return $enhancedLists;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get tasks in a specific calendar
     * 
     * @param string $userId
     * @param int $calendarId
     * @return array
     */
    private function getTasksInCalendar(string $userId, int $calendarId): array {
        try {
            // Get CalDAV objects (tasks) from calendarobjects table (limit to prevent slowdown)
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->orderBy('lastmodified', 'DESC')
                ->setMaxResults(100); // Limit to 100 tasks per calendar to prevent slowdown
            
            $result = $qb->execute();
            $calendarObjects = $result->fetchAll();
            $result->closeCursor();
            
            $questTasks = [];
            foreach ($calendarObjects as $object) {
                $taskData = $this->parseVTodoData($object['calendardata']);
                if ($taskData) {
                    $questTasks[] = [
                        'id' => $object['id'],
                        'title' => $taskData['summary'] ?: 'Untitled Task',
                        'description' => $taskData['description'] ?: '',
                        'completed' => $taskData['completed'] ? 1 : 0,
                        'priority' => $this->mapTaskPriority($taskData['priority']),
                        'due_date' => $taskData['due'],
                        'created_at' => $object['firstoccurence'],
                        'modified_at' => $object['lastmodified']
                    ];
                }
            }
            
            return $questTasks;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Parse VTODO CalDAV data to extract task information
     * 
     * @param string $calendarData
     * @return array|null
     */
    private function parseVTodoData(string $calendarData): ?array {
        try {
            $lines = explode("\n", $calendarData);
            $taskData = [
                'summary' => '',
                'description' => '',
                'completed' => false,
                'priority' => 0,
                'due' => null
            ];
            
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'SUMMARY:') === 0) {
                    $taskData['summary'] = substr($line, 8);
                } elseif (strpos($line, 'DESCRIPTION:') === 0) {
                    $taskData['description'] = substr($line, 12);
                } elseif (strpos($line, 'STATUS:COMPLETED') === 0) {
                    $taskData['completed'] = true;
                } elseif (strpos($line, 'PRIORITY:') === 0) {
                    $taskData['priority'] = (int)substr($line, 9);
                } elseif (strpos($line, 'DUE:') === 0) {
                    $taskData['due'] = substr($line, 4);
                }
            }
            
            return $taskData;
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Map CalDAV task priority to quest priority levels
     * 
     * @param int $tasksPriority CalDAV priority (1-9, where 1 is highest)
     * @return string Quest priority level
     */
    private function mapTaskPriority(int $tasksPriority): string {
        if ($tasksPriority >= 1 && $tasksPriority <= 3) {
            return 'high';
        } elseif ($tasksPriority >= 7 && $tasksPriority <= 9) {
            return 'low';
        } else {
            return 'medium';
        }
    }
}