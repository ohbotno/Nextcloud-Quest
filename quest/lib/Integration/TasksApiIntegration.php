<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Integration;

use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Service\AchievementService;
use OCA\NextcloudQuest\Service\StreakService;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use OCP\IUserSession;

/**
 * Integration with Nextcloud Tasks app
 * This class handles reading task data and triggering quest events
 */
class TasksApiIntegration {
    /** @var IDBConnection */
    private $db;
    /** @var LoggerInterface */
    private $logger;
    /** @var IUserSession */
    private $userSession;
    /** @var XPService */
    private $xpService;
    /** @var AchievementService */
    private $achievementService;
    /** @var StreakService */
    private $streakService;
    /** @var QuestMapper */
    private $questMapper;
    
    public function __construct(
        IDBConnection $db,
        LoggerInterface $logger,
        IUserSession $userSession,
        XPService $xpService = null,
        AchievementService $achievementService = null,
        StreakService $streakService = null,
        QuestMapper $questMapper = null
    ) {
        $this->db = $db;
        $this->logger = $logger;
        $this->userSession = $userSession;
        $this->xpService = $xpService;
        $this->achievementService = $achievementService;
        $this->streakService = $streakService;
        $this->questMapper = $questMapper;
    }
    
    /**
     * Check if Tasks app is installed and enabled
     * 
     * @return bool
     */
    public function isTasksAppAvailable(): bool {
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
            $this->logger->debug('Tasks app not available', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get task by ID from Tasks app
     * 
     * @param int $taskId
     * @param string $userId
     * @return array|null
     */
    public function getTask(int $taskId, string $userId): ?array {
        if (!$this->isTasksAppAvailable()) {
            return null;
        }
        
        try {
            // Get task from CalDAV calendarobjects table
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $object = $result->fetch();
            $result->closeCursor();
            
            if (!$object) {
                return null;
            }
            
            // Parse CalDAV data and return in expected format
            $taskData = $this->parseVTodoData($object['calendardata']);
            if (!$taskData) {
                return null;
            }
            
            return [
                'id' => $object['id'],
                'title' => $taskData['summary'] ?: 'Untitled Task',
                'description' => $taskData['description'] ?: '',
                'completed' => $taskData['completed'] ? 1 : 0,
                'priority' => $taskData['priority'],
                'due_date' => $taskData['due'],
                'created_at' => $object['firstoccurence'],
                'modified_at' => $object['lastmodified']
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch task', [
                'taskId' => $taskId,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get user's completed tasks for today
     * 
     * @param string $userId
     * @return array
     */
    public function getTodaysCompletedTasks(string $userId): array {
        if (!$this->isTasksAppAvailable()) {
            return [];
        }
        
        try {
            $today = new \DateTime();
            $startOfDay = clone $today;
            $startOfDay->setTime(0, 0, 0);
            $endOfDay = clone $today;
            $endOfDay->setTime(23, 59, 59);
            
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('tasks_tasks')
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->eq('completed', $qb->createNamedParameter(1, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startOfDay->format('Y-m-d H:i:s'), \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->lte('completed_at', $qb->createNamedParameter($endOfDay->format('Y-m-d H:i:s'), \PDO::PARAM_STR)))
                ->orderBy('completed_at', 'DESC');
            
            $result = $qb->execute();
            $tasks = $result->fetchAll();
            $result->closeCursor();
            
            return $tasks;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch today\'s completed tasks', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get user's pending tasks for today
     * 
     * @param string $userId
     * @return array
     */
    public function getTodaysPendingTasks(string $userId): array {
        if (!$this->isTasksAppAvailable()) {
            return [];
        }
        
        try {
            $today = new \DateTime();
            $todayStr = $today->format('Y-m-d');
            
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('tasks_tasks')
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->eq('completed', $qb->createNamedParameter(0, \PDO::PARAM_INT)))
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->lte('due', $qb->createNamedParameter($todayStr . ' 23:59:59', \PDO::PARAM_STR)),
                        $qb->expr()->isNull('due')
                    )
                )
                ->orderBy('due', 'ASC')
                ->addOrderBy('priority', 'DESC');
            
            $result = $qb->execute();
            $tasks = $result->fetchAll();
            $result->closeCursor();
            
            return $tasks;
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch today\'s pending tasks', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Handle task completion from Tasks app
     * This method is called when a task is marked as completed
     * 
     * @param int $taskId
     * @param string $userId
     * @return array Result of quest processing
     */
    public function handleTaskCompletion(int $taskId, string $userId): array {
        try {
            // Get task details
            $task = $this->getTask($taskId, $userId);
            if (!$task) {
                throw new \Exception('Task not found');
            }
            
            // Determine task priority
            $priority = $this->mapTaskPriority($task['priority'] ?? 0);
            
            // Update streak
            $streakResult = $this->streakService->updateStreak($userId);
            
            // Award XP
            $xpResult = $this->xpService->awardXP(
                $userId,
                (string)$taskId,
                $task['summary'] ?? 'Completed Task',
                $priority
            );
            
            // Get updated quest data
            $quest = $this->questMapper->findByUserId($userId);
            
            // Check for new achievements
            $completionTime = new \DateTime();
            $newAchievements = $this->achievementService->checkAchievements($userId, $quest, $completionTime);
            
            // Check for perfect day achievement
            $this->checkPerfectDayAchievement($userId);
            
            $this->logger->info('Task completion processed successfully', [
                'taskId' => $taskId,
                'userId' => $userId,
                'xpEarned' => $xpResult['xp_earned'],
                'newLevel' => $xpResult['level'],
                'newAchievements' => count($newAchievements)
            ]);
            
            return [
                'success' => true,
                'xp' => $xpResult,
                'streak' => $streakResult,
                'achievements' => array_map(function($achievement) {
                    return [
                        'key' => $achievement->getAchievementKey(),
                        'unlocked_at' => $achievement->getUnlockedAt()
                    ];
                }, $newAchievements)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process task completion', [
                'taskId' => $taskId,
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Map Tasks app priority to quest priority
     * 
     * @param int $tasksPriority Tasks app priority (1-9)
     * @return string Quest priority (low, medium, high)
     */
    private function mapTaskPriority(int $tasksPriority): string {
        // Tasks app uses 1-9 priority scale
        // 1-3 = high, 4-6 = medium, 7-9 = low, 0 = no priority (medium)
        if ($tasksPriority >= 1 && $tasksPriority <= 3) {
            return 'high';
        } elseif ($tasksPriority >= 7 && $tasksPriority <= 9) {
            return 'low';
        } else {
            return 'medium';
        }
    }
    
    /**
     * Check if user completed all tasks today (perfect day achievement)
     * 
     * @param string $userId
     */
    private function checkPerfectDayAchievement(string $userId): void {
        try {
            $pendingTasks = $this->getTodaysPendingTasks($userId);
            
            // If no pending tasks for today, award perfect day achievement
            if (empty($pendingTasks)) {
                $this->achievementService->unlockAchievement($userId, 'perfect_day');
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to check perfect day achievement', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get all task lists (collections) for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getTaskLists(string $userId): array {
        if (!$this->isTasksAppAvailable()) {
            return [];
        }
        
        try {
            // Get task calendars (task lists) from CalDAV
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendars')
                ->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->like('components', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->orderBy('displayname', 'ASC');
            
            $result = $qb->execute();
            $lists = $result->fetchAll();
            $result->closeCursor();
            
            // Add task counts for each list
            $enhancedLists = [];
            foreach ($lists as $list) {
                $tasks = $this->getTasksInList($userId, $list['id']);
                $listData = [
                    'id' => $list['id'],
                    'name' => $list['displayname'],
                    'color' => $list['calendarcolor'] ?? '#0082c9',
                    'tasks' => $tasks
                ];
                $listData['total_tasks'] = count($listData['tasks']);
                $listData['completed_tasks'] = count(array_filter($listData['tasks'], function($task) {
                    return $task['completed'] == 1;
                }));
                $listData['pending_tasks'] = $listData['total_tasks'] - $listData['completed_tasks'];
                
                $enhancedLists[] = $listData;
            }
            
            return $enhancedLists;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch task lists', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Get tasks in a specific list
     * 
     * @param string $userId
     * @param string $listId
     * @return array
     */
    public function getTasksInList(string $userId, string $listId): array {
        if (!$this->isTasksAppAvailable()) {
            return [];
        }

        try {
            // Query calendarobjects table using componenttype column (not LIKE on calendardata!)
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($listId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->eq('componenttype', $qb->createNamedParameter('VTODO', \PDO::PARAM_STR)))
                ->orderBy('lastmodified', 'DESC')
                ->setMaxResults(100);

            $result = $qb->execute();
            $tasks = $result->fetchAll();
            $result->closeCursor();

            $this->logger->info('Tasks query from calendarobjects', [
                'listId' => $listId,
                'userId' => $userId,
                'tasksFound' => count($tasks)
            ]);

            // Parse VTODO data using Sabre\VObject
            $questTasks = [];
            $parseFailures = 0;

            foreach ($tasks as $task) {
                try {
                    $vobject = \Sabre\VObject\Reader::read($task['calendardata']);
                    if (!isset($vobject->VTODO)) {
                        $parseFailures++;
                        continue;
                    }

                    $vtodo = $vobject->VTODO;

                    $questTasks[] = [
                        'id' => $task['id'],
                        'title' => isset($vtodo->SUMMARY) ? (string)$vtodo->SUMMARY : 'Untitled Task',
                        'description' => isset($vtodo->DESCRIPTION) ? (string)$vtodo->DESCRIPTION : '',
                        'completed' => isset($vtodo->STATUS) && (string)$vtodo->STATUS === 'COMPLETED' ? 1 : 0,
                        'priority' => isset($vtodo->PRIORITY) ? $this->mapTaskPriority((int)$vtodo->PRIORITY) : 'low',
                        'due_date' => isset($vtodo->DUE) ? (string)$vtodo->DUE : null,
                        'created_at' => $task['firstoccurence'],
                        'modified_at' => $task['lastmodified']
                    ];
                } catch (\Exception $e) {
                    $parseFailures++;
                    $this->logger->warning('Failed to parse VTODO', [
                        'taskId' => $task['id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }

            $this->logger->info('Tasks parsed successfully', [
                'listId' => $listId,
                'totalTasks' => count($questTasks),
                'parseFailures' => $parseFailures
            ]);

            return $questTasks;

        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch tasks from calendarobjects', [
                'userId' => $userId,
                'listId' => $listId,
                'error' => $e->getMessage(),
                'errorClass' => get_class($e)
            ]);
            return [];
        }
    }
    
    /**
     * Calculate estimated XP for a task based on priority
     * 
     * @param int $tasksPriority
     * @return int
     */
    private function calculateEstimatedXP(int $tasksPriority): int {
        $priority = $this->mapTaskPriority($tasksPriority);
        $baseXP = 10;
        
        switch ($priority) {
            case 'high':
                return $baseXP + 10;
            case 'medium':
                return $baseXP + 5;
            case 'low':
            default:
                return $baseXP;
        }
    }
    
    /**
     * Mark a task as completed in the Tasks app
     * Updates the calendarobjects table and modifies VTODO data
     *
     * @param int $taskId The calendarobject ID
     * @param string $userId User ID
     * @return bool Success status
     */
    public function markTaskCompleted(int $taskId, string $userId): bool {
        if (!$this->isTasksAppAvailable()) {
            return false;
        }

        try {
            // Get the task from calendarobjects
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->eq('componenttype', $qb->createNamedParameter('VTODO', \PDO::PARAM_STR)));

            $result = $qb->execute();
            $task = $result->fetch();
            $result->closeCursor();

            if (!$task) {
                $this->logger->warning('Task not found for completion', ['taskId' => $taskId]);
                return false;
            }

            // Parse VTODO and update STATUS to COMPLETED
            $vobject = \Sabre\VObject\Reader::read($task['calendardata']);
            if (!isset($vobject->VTODO)) {
                $this->logger->warning('No VTODO component found', ['taskId' => $taskId]);
                return false;
            }

            $vtodo = $vobject->VTODO;

            // Set task as completed
            $vtodo->STATUS = 'COMPLETED';
            $vtodo->{'PERCENT-COMPLETE'} = 100;

            // Set completion timestamp
            $now = new \DateTime();
            $vtodo->COMPLETED = $now;

            // Update LAST-MODIFIED
            $vtodo->{'LAST-MODIFIED'} = $now;

            // Serialize the updated VTODO
            $updatedData = $vobject->serialize();

            // Update calendarobjects table
            $qb = $this->db->getQueryBuilder();
            $qb->update('calendarobjects')
                ->set('calendardata', $qb->createNamedParameter($updatedData, \PDO::PARAM_STR))
                ->set('lastmodified', $qb->createNamedParameter(time(), \PDO::PARAM_INT))
                ->set('etag', $qb->createNamedParameter(md5($updatedData), \PDO::PARAM_STR))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId, \PDO::PARAM_INT)));

            $affected = $qb->execute();

            $this->logger->info('Task marked as completed', [
                'taskId' => $taskId,
                'userId' => $userId,
                'rowsAffected' => $affected
            ]);

            return $affected > 0;

        } catch (\Exception $e) {
            $this->logger->error('Failed to mark task as completed', [
                'taskId' => $taskId,
                'userId' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
    
    /**
     * Get task statistics for a user
     * 
     * @param string $userId
     * @param int $days Number of days to analyze (0 = all time)
     * @return array
     */
    public function getTaskStatistics(string $userId, int $days = 30): array {
        if (!$this->isTasksAppAvailable()) {
            return [
                'total_completed' => 0,
                'total_pending' => 0,
                'completion_rate' => 0,
                'average_per_day' => 0
            ];
        }
        
        try {
            $qb = $this->db->getQueryBuilder();
            
            // Build date filter
            $dateFilter = '';
            if ($days > 0) {
                $startDate = new \DateTime();
                $startDate->modify("-{$days} days");
                $dateFilter = $qb->expr()->gte('created_at', $qb->createNamedParameter($startDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR));
            }
            
            // Get completed tasks count
            $qb->select($qb->createFunction('COUNT(*) as count'))
                ->from('tasks_tasks')
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->eq('completed', $qb->createNamedParameter(1, \PDO::PARAM_INT)));
            
            if ($dateFilter) {
                $qb->andWhere($dateFilter);
            }
            
            $result = $qb->execute();
            $completedCount = (int)$result->fetchOne();
            $result->closeCursor();
            
            // Get total tasks count
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->createFunction('COUNT(*) as count'))
                ->from('tasks_tasks')
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId, \PDO::PARAM_STR)));
            
            if ($dateFilter) {
                $qb->andWhere($dateFilter);
            }
            
            $result = $qb->execute();
            $totalCount = (int)$result->fetchOne();
            $result->closeCursor();
            
            $pendingCount = $totalCount - $completedCount;
            $completionRate = $totalCount > 0 ? ($completedCount / $totalCount) * 100 : 0;
            $averagePerDay = $days > 0 ? $completedCount / $days : 0;
            
            return [
                'total_completed' => $completedCount,
                'total_pending' => $pendingCount,
                'completion_rate' => round($completionRate, 1),
                'average_per_day' => round($averagePerDay, 2)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get task statistics', [
                'userId' => $userId,
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_completed' => 0,
                'total_pending' => 0,
                'completion_rate' => 0,
                'average_per_day' => 0
            ];
        }
    }
}