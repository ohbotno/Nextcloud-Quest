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

class QuestControllerSimple extends Controller {
    /** @var IUserSession */
    private $userSession;
    /** @var IDBConnection */
    private $db;
    
    public function __construct($appName, IRequest $request, IUserSession $userSession, IDBConnection $db) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->db = $db;
    }
    
    /**
     * Simple test endpoint
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function test() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'Quest controller is working!'
        ]);
    }
    
    /**
     * Get current user's stats
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getUserStats() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new \Exception('User not found');
            }
            $userId = $user->getUID();
            
            // Return default stats for now
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $userId,
                        'theme_preference' => 'game'
                    ],
                    'level' => [
                        'level' => 1,
                        'rank_title' => 'Task Novice',
                        'xp' => 0,
                        'xp_to_next' => 100,
                        'progress_percentage' => 0
                    ],
                    'streak' => [
                        'current_streak' => 0,
                        'longest_streak' => 0
                    ],
                    'stats' => [
                        'total_completed' => 0,
                        'total_xp' => 0,
                        'achievements_unlocked' => 0
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get quest lists from Tasks app
     * 
     * @NoAdminRequired  
     * @return JSONResponse
     */
    public function getQuestLists() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new \Exception('User not found');
            }
            $userId = $user->getUID();
            
            // Check if Tasks app tables exist
            if (!$this->isTasksAppAvailable()) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Tasks app not installed or tables not found',
                    'data' => []
                ]);
            }
            
            $taskLists = $this->getTaskLists($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $taskLists,
                'message' => 'Found ' . count($taskLists) . ' task lists'
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if Tasks app is available
     */
    private function isTasksAppAvailable(): bool {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('tasks_tasks')
                ->setMaxResults(1);
            
            $result = $qb->execute();
            $result->closeCursor();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get task lists for user
     */
    private function getTaskLists(string $userId): array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('tasks_lists')
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->orderBy('displayname', 'ASC');
            
            $result = $qb->execute();
            $lists = $result->fetchAll();
            $result->closeCursor();
            
            $enhancedLists = [];
            foreach ($lists as $list) {
                $tasks = $this->getTasksInList($userId, $list['id']);
                $listData = [
                    'id' => $list['id'],
                    'name' => $list['displayname'],
                    'color' => $list['color'] ?? '#0082c9',
                    'tasks' => $tasks,
                    'total_tasks' => count($tasks),
                    'completed_tasks' => count(array_filter($tasks, function($task) {
                        return $task['completed'] == 1;
                    }))
                ];
                $listData['pending_tasks'] = $listData['total_tasks'] - $listData['completed_tasks'];
                $enhancedLists[] = $listData;
            }
            
            return $enhancedLists;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get tasks in a list
     */
    private function getTasksInList(string $userId, string $listId): array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('tasks_tasks')
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, \PDO::PARAM_STR)))
                ->orderBy('completed', 'ASC')
                ->addOrderBy('priority', 'ASC')
                ->addOrderBy('summary', 'ASC');
            
            $result = $qb->execute();
            $tasks = $result->fetchAll();
            $result->closeCursor();
            
            $questTasks = [];
            foreach ($tasks as $task) {
                $questTasks[] = [
                    'id' => $task['id'],
                    'title' => $task['summary'] ?: 'Untitled Task',
                    'description' => $task['note'] ?: '',
                    'completed' => (int)$task['completed'],
                    'priority' => $this->mapTaskPriority((int)($task['priority'] ?? 0)),
                    'due_date' => $task['due'],
                    'created_at' => $task['created_at'],
                    'modified_at' => $task['last_modified']
                ];
            }
            
            return $questTasks;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Map task priority to quest priority
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