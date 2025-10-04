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
            $startTimestamp = $startOfDay->getTimestamp();
            $endTimestamp = $endOfDay->getTimestamp();
            
            // Get user's task calendars
            $calendars = $this->getUserTaskCalendars($userId);
            $calendarIds = array_column($calendars, 'id');
            
            if (empty($calendarIds)) {
                return [];
            }
            
            // Query CalDAV objects for completed tasks
            $qb = $this->db->getQueryBuilder();
            $qb->select('co.*', 'c.displayname as calendar_name')
                ->from('calendarobjects', 'co')
                ->leftJoin('co', 'calendars', 'c', 'co.calendarid = c.id')
                ->where($qb->expr()->in('co.calendarid', $qb->createNamedParameter($calendarIds, \PDO::PARAM_INT_ARRAY)))
                ->andWhere($qb->expr()->like('co.calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->like('co.calendardata', $qb->createNamedParameter('%STATUS:COMPLETED%', \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->gte('co.lastmodified', $qb->createNamedParameter($startTimestamp, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->lte('co.lastmodified', $qb->createNamedParameter($endTimestamp, \PDO::PARAM_INT)))
                ->orderBy('co.lastmodified', 'DESC');
            
            $result = $qb->execute();
            $objects = $result->fetchAll();
            $result->closeCursor();
            
            // Parse VTODO data for each task
            $tasks = [];
            foreach ($objects as $object) {
                $taskData = $this->parseVTodoData($object['calendardata']);
                if ($taskData && $taskData['completed']) {
                    $tasks[] = [
                        'id' => $object['id'],
                        'title' => $taskData['summary'] ?: 'Untitled Task',
                        'description' => $taskData['description'] ?: '',
                        'completed' => 1,
                        'completed_at' => $taskData['completed_date'] ?: date('Y-m-d H:i:s', $object['lastmodified']),
                        'priority' => $this->mapTaskPriority($taskData['priority']),
                        'calendar_name' => $object['calendar_name']
                    ];
                }
            }
            
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
            $todayStr = $today->format('Ymd');
            
            // Get user's task calendars
            $calendars = $this->getUserTaskCalendars($userId);
            $calendarIds = array_column($calendars, 'id');
            
            if (empty($calendarIds)) {
                return [];
            }
            
            // Query CalDAV objects for pending tasks
            $qb = $this->db->getQueryBuilder();
            $qb->select('co.*', 'c.displayname as calendar_name')
                ->from('calendarobjects', 'co')
                ->leftJoin('co', 'calendars', 'c', 'co.calendarid = c.id')
                ->where($qb->expr()->in('co.calendarid', $qb->createNamedParameter($calendarIds, \PDO::PARAM_INT_ARRAY)))
                ->andWhere($qb->expr()->like('co.calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->orderBy('co.lastmodified', 'DESC');
            
            $result = $qb->execute();
            $objects = $result->fetchAll();
            $result->closeCursor();
            
            // Parse VTODO data and filter for pending tasks
            $tasks = [];
            foreach ($objects as $object) {
                $taskData = $this->parseVTodoData($object['calendardata']);
                if ($taskData && !$taskData['completed']) {
                    // Check if task is due today or has no due date
                    $isDueToday = false;
                    if ($taskData['due']) {
                        $dueDate = $this->parseICalDate($taskData['due']);
                        if ($dueDate && $dueDate->format('Ymd') <= $todayStr) {
                            $isDueToday = true;
                        }
                    } else {
                        $isDueToday = true; // No due date means show it
                    }
                    
                    if ($isDueToday) {
                        $tasks[] = [
                            'id' => $object['id'],
                            'title' => $taskData['summary'] ?: 'Untitled Task',
                            'description' => $taskData['description'] ?: '',
                            'completed' => 0,
                            'priority' => $this->mapTaskPriority($taskData['priority']),
                            'due_date' => $taskData['due'],
                            'calendar_name' => $object['calendar_name']
                        ];
                    }
                }
            }
            
            // Sort by priority and due date
            usort($tasks, function($a, $b) {
                // First sort by priority
                $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
                $aPriority = $priorityOrder[$a['priority']] ?? 2;
                $bPriority = $priorityOrder[$b['priority']] ?? 2;
                
                if ($aPriority !== $bPriority) {
                    return $aPriority - $bPriority;
                }
                
                // Then by due date
                return strcmp($a['due_date'] ?: 'Z', $b['due_date'] ?: 'Z');
            });
            
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
                $tasks = $this->getTasksInList($userId, $list['id']); // Now only returns incomplete tasks
                $listData = [
                    'id' => $list['id'],
                    'name' => $list['displayname'],
                    'color' => $list['calendarcolor'] ?? '#0082c9',
                    'tasks' => $tasks
                ];
                // Since getTasksInList now only returns incomplete tasks:
                $listData['pending_tasks'] = count($listData['tasks']);
                $listData['total_tasks'] = $listData['pending_tasks']; // For Quest purposes, only show incomplete tasks
                $listData['completed_tasks'] = 0; // Not shown in Quest interface
                
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
            // Get CalDAV objects (tasks) from calendarobjects table
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($listId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->orderBy('lastmodified', 'DESC')
                ->setMaxResults(100); // Limit to 100 tasks per calendar to prevent slowdown
            
            $result = $qb->execute();
            $tasks = $result->fetchAll();
            $result->closeCursor();
            
            // Parse CalDAV task data and filter out completed tasks
            $questTasks = [];
            foreach ($tasks as $object) {
                $taskData = $this->parseVTodoData($object['calendardata']);
                // Only include tasks that are NOT completed
                if ($taskData && !$taskData['completed']) {
                    $questTasks[] = [
                        'id' => $object['id'],
                        'title' => $taskData['summary'] ?: 'Untitled Task',
                        'description' => $taskData['description'] ?: '',
                        'completed' => 0, // Always 0 since we're filtering out completed tasks
                        'priority' => $this->mapTaskPriority($taskData['priority']),
                        'due_date' => $taskData['due'],
                        'created_at' => $object['firstoccurence'],
                        'modified_at' => $object['lastmodified']
                    ];
                }
            }
            
            return $questTasks;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch tasks in list', [
                'userId' => $userId,
                'listId' => $listId,
                'error' => $e->getMessage()
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
     * Get user's task calendars
     * 
     * @param string $userId
     * @return array
     */
    private function getUserTaskCalendars(string $userId): array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('id', 'displayname', 'calendarcolor')
                ->from('calendars')
                ->where($qb->expr()->eq('principaluri', $qb->createNamedParameter('principals/users/' . $userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->like('components', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $calendars = $result->fetchAll();
            $result->closeCursor();
            
            return $calendars;
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user task calendars', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
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
            // Get user's task calendars
            $calendars = $this->getUserTaskCalendars($userId);
            $calendarIds = array_column($calendars, 'id');
            
            if (empty($calendarIds)) {
                return [
                    'total_completed' => 0,
                    'total_pending' => 0,
                    'completion_rate' => 0,
                    'average_per_day' => 0
                ];
            }
            
            // Build date filter
            $startTimestamp = 0;
            if ($days > 0) {
                $startDate = new \DateTime();
                $startDate->modify("-{$days} days");
                $startTimestamp = $startDate->getTimestamp();
            }
            
            // Query all tasks from CalDAV
            $qb = $this->db->getQueryBuilder();
            $qb->select('calendardata', 'lastmodified')
                ->from('calendarobjects')
                ->where($qb->expr()->in('calendarid', $qb->createNamedParameter($calendarIds, \PDO::PARAM_INT_ARRAY)))
                ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)));
            
            if ($startTimestamp > 0) {
                $qb->andWhere($qb->expr()->gte('lastmodified', $qb->createNamedParameter($startTimestamp, \PDO::PARAM_INT)));
            }
            
            $result = $qb->execute();
            $objects = $result->fetchAll();
            $result->closeCursor();
            
            // Count completed and pending tasks
            $completedCount = 0;
            $totalCount = 0;
            
            foreach ($objects as $object) {
                $taskData = $this->parseVTodoData($object['calendardata']);
                if ($taskData) {
                    $totalCount++;
                    if ($taskData['completed']) {
                        $completedCount++;
                    }
                }
            }
            
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
                'uid' => '',
                'summary' => '',
                'description' => '',
                'completed' => false,
                'completed_date' => null,
                'priority' => 0,
                'due' => null,
                'percent_complete' => 0,
                'categories' => [],
                'created' => null,
                'last_modified' => null
            ];
            
            $inDescription = false;
            $descriptionLines = [];
            
            foreach ($lines as $line) {
                $line = str_replace("\r", '', $line); // Remove carriage returns
                
                // Handle multi-line descriptions
                if ($inDescription) {
                    if (strpos($line, ' ') === 0 || strpos($line, "\t") === 0) {
                        // Continuation of description
                        $descriptionLines[] = trim($line);
                        continue;
                    } else {
                        // End of description
                        $taskData['description'] = implode(' ', $descriptionLines);
                        $inDescription = false;
                    }
                }
                
                if (strpos($line, 'UID:') === 0) {
                    $taskData['uid'] = substr($line, 4);
                } elseif (strpos($line, 'SUMMARY:') === 0) {
                    $taskData['summary'] = $this->unescapeICalString(substr($line, 8));
                } elseif (strpos($line, 'DESCRIPTION:') === 0) {
                    $descriptionLines = [$this->unescapeICalString(substr($line, 12))];
                    $inDescription = true;
                } elseif (strpos($line, 'STATUS:') === 0) {
                    $status = substr($line, 7);
                    $taskData['completed'] = ($status === 'COMPLETED');
                } elseif (strpos($line, 'COMPLETED:') === 0) {
                    $taskData['completed'] = true;
                    $taskData['completed_date'] = $this->parseICalDate(substr($line, 10));
                } elseif (strpos($line, 'PRIORITY:') === 0) {
                    $taskData['priority'] = (int)substr($line, 9);
                } elseif (strpos($line, 'DUE:') === 0 || strpos($line, 'DUE;') === 0) {
                    // Handle both DUE: and DUE;VALUE=DATE: formats
                    $dueStr = substr($line, strpos($line, ':') + 1);
                    $taskData['due'] = $dueStr;
                } elseif (strpos($line, 'PERCENT-COMPLETE:') === 0) {
                    $taskData['percent_complete'] = (int)substr($line, 17);
                } elseif (strpos($line, 'CATEGORIES:') === 0) {
                    $categories = substr($line, 11);
                    $taskData['categories'] = array_map('trim', explode(',', $categories));
                } elseif (strpos($line, 'CREATED:') === 0) {
                    $taskData['created'] = $this->parseICalDate(substr($line, 8));
                } elseif (strpos($line, 'LAST-MODIFIED:') === 0) {
                    $taskData['last_modified'] = $this->parseICalDate(substr($line, 14));
                }
            }
            
            // Handle any remaining description lines
            if ($inDescription && !empty($descriptionLines)) {
                $taskData['description'] = implode(' ', $descriptionLines);
            }
            
            // If percent-complete is 100, mark as completed
            if ($taskData['percent_complete'] >= 100) {
                $taskData['completed'] = true;
            }
            
            return $taskData;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to parse VTODO data', ['error' => $e->getMessage()]);
            return null;
        }
    }
    
    /**
     * Parse iCal date string to DateTime object
     * 
     * @param string $dateStr
     * @return \DateTime|null
     */
    private function parseICalDate(string $dateStr): ?\DateTime {
        try {
            // Remove any timezone info for now
            $dateStr = str_replace('Z', '', $dateStr);
            
            // Handle different date formats
            if (strlen($dateStr) === 8) {
                // Date only: YYYYMMDD
                return \DateTime::createFromFormat('Ymd', $dateStr);
            } elseif (strlen($dateStr) === 15) {
                // Date and time: YYYYMMDDTHHMMSS
                return \DateTime::createFromFormat('Ymd\THis', $dateStr);
            } elseif (strlen($dateStr) >= 16) {
                // With timezone: YYYYMMDDTHHMMSSZ
                return \DateTime::createFromFormat('Ymd\THis', substr($dateStr, 0, 15));
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Unescape iCal string (handle escaped characters)
     * 
     * @param string $str
     * @return string
     */
    private function unescapeICalString(string $str): string {
        $str = str_replace('\\n', "\n", $str);
        $str = str_replace('\\,', ',', $str);
        $str = str_replace('\\;', ';', $str);
        $str = str_replace('\\\\', '\\', $str);
        return $str;
    }
}