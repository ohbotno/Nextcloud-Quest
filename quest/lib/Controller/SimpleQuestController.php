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
use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Service\LevelService;

class SimpleQuestController extends Controller {
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
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function test() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'Simple Quest controller is working!'
        ]);
    }
    
    /**
     * Debug endpoint to check database content
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function debugDB() {
        try {
            $this->initializeTables();
            
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'No user']);
            }
            $userId = $user->getUID();
            
            // Query the quest_user_data table directly
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('quest_user_data')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $userData = $result->fetch();
            $result->closeCursor();
            
            // Also get all rows from the table to see what's there
            $allQb = $this->db->getQueryBuilder();
            $allQb->select('*')->from('quest_user_data');
            $allResult = $allQb->execute();
            $allRows = $allResult->fetchAll();
            $allResult->closeCursor();
            
            // Test a simple insert to see if database operations work
            $testResult = null;
            try {
                $testQb = $this->db->getQueryBuilder();
                $testQb->insert('quest_user_data')
                    ->values([
                        'user_id' => $testQb->createNamedParameter('test_user_' . time(), \PDO::PARAM_STR),
                        'total_xp' => $testQb->createNamedParameter(100, \PDO::PARAM_INT),
                        'level' => $testQb->createNamedParameter(2, \PDO::PARAM_INT),
                        'created_at' => $testQb->createNamedParameter(date('Y-m-d H:i:s'), \PDO::PARAM_STR),
                        'updated_at' => $testQb->createNamedParameter(date('Y-m-d H:i:s'), \PDO::PARAM_STR)
                    ]);
                $testResult = $testQb->execute();
            } catch (\Exception $e) {
                $testResult = 'ERROR: ' . $e->getMessage();
            }
            
            // Check what's in the XP history table
            $historyQb = $this->db->getQueryBuilder();
            $historyQb->select('*')
                ->from('quest_xp_history')
                ->where($historyQb->expr()->eq('user_id', $historyQb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->orderBy('completed_at', 'DESC')
                ->setMaxResults(10);
            
            $historyResult = $historyQb->execute();
            $historyRows = $historyResult->fetchAll();
            $historyResult->closeCursor();
            
            // Test the streak calculation directly
            $currentDate = date('Y-m-d');
            $testStreakData = $this->calculateStreak($userId, $currentDate);
            $testTaskCounts = $this->getTaskCounts($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'current_user' => $userId,
                'user_data' => $userData,
                'all_user_data' => $allRows,
                'getUserData_result' => $this->getUserData($userId),
                'test_insert_result' => $testResult,
                'table_exists' => $this->tableExists('quest_user_data'),
                'xp_history' => $historyRows,
                'test_streak_data' => $testStreakData,
                'test_task_counts' => $testTaskCounts,
                'current_date' => $currentDate
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Check if a table exists
     */
    private function tableExists(string $tableName): bool {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('1')->from($tableName)->setMaxResults(1);
            $result = $qb->execute();
            $result->closeCursor();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get current user's stats
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function getUserStats() {
        try {
            // Initialize tables if needed
            $this->initializeTables();
            
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new \Exception('User not found');
            }
            $userId = $user->getUID();
            
            // Get level information from database
            $userData = $this->getUserData($userId);
            $userLevel = $userData['level'];
            $currentXP = $userData['xp'];
            $xpForNextLevel = $this->getXPForLevel($userLevel + 1);
            $xpForCurrentLevel = $this->getXPForLevel($userLevel);
            $xpToNext = $xpForNextLevel - $currentXP;
            $xpProgress = $xpForNextLevel > $xpForCurrentLevel ? 
                (($currentXP - $xpForCurrentLevel) / ($xpForNextLevel - $xpForCurrentLevel)) * 100 : 100;
            
            // Get streak and task count data
            $currentDate = date('Y-m-d');
            $streakData = $this->calculateStreak($userId, $currentDate);
            $taskCounts = $this->getTaskCounts($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $userId,
                        'theme_preference' => 'game'
                    ],
                    'level' => [
                        'level' => $userLevel,
                        'rank_title' => $this->getRankTitle($userLevel),
                        'xp' => $currentXP,
                        'xp_to_next' => $xpToNext,
                        'progress_percentage' => round($xpProgress, 1)
                    ],
                    'streak' => [
                        'current_streak' => $streakData['current_streak'],
                        'longest_streak' => $streakData['longest_streak']
                    ],
                    'stats' => [
                        'total_completed' => $taskCounts['tasks_this_week'], // Use weekly tasks as a reasonable total
                        'total_xp' => $currentXP,
                        'achievements_unlocked' => 0,
                        'tasks_today' => $taskCounts['tasks_today'],
                        'tasks_this_week' => $taskCounts['tasks_this_week']
                    ],
                    'achievements' => [
                        'unlocked' => [],
                        'available' => []
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
     * @NoCSRFRequired
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
     * Get task lists for user
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
            }
            
            return $enhancedLists;
            
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Get tasks in a calendar
     */
    private function getTasksInCalendar(string $userId, int $calendarId): array {
        try {
            // Get CalDAV objects (tasks) from calendarobjects table
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('calendarid', $qb->createNamedParameter($calendarId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)))
                ->orderBy('lastmodified', 'DESC');
            
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
     * Parse VTODO CalDAV data
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
    
    /**
     * Complete a quest task from a specific list
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function completeTaskFromList() {
        try {
            // Log entry to method
            error_log('Quest: completeTaskFromList called');
            
            // Initialize tables if needed
            $this->initializeTables();
            
            $user = $this->userSession->getUser();
            if (!$user) {
                error_log('Quest: User not found in session');
                throw new \Exception('User not found');
            }
            $userId = $user->getUID();
            error_log('Quest: User ID: ' . $userId);
            
            // Get request data
            $input = json_decode(file_get_contents('php://input'), true);
            error_log('Quest: Request input: ' . json_encode($input));
            
            $taskId = $input['task_id'] ?? null;
            $listId = $input['list_id'] ?? null;
            
            if (!$taskId || !$listId) {
                error_log('Quest: Missing task_id or list_id');
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Missing task_id or list_id'
                ], 400);
            }
            
            error_log("Quest: Processing task $taskId from list $listId");
            
            // Get task details before marking complete
            $taskDetails = $this->getTaskDetails($userId, $taskId, $listId);
            if (!$taskDetails) {
                error_log("Quest: Task not found - taskId: $taskId, listId: $listId");
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], 404);
            }
            error_log('Quest: Task details found: ' . json_encode($taskDetails));
            
            // Check if already completed
            if ($taskDetails['completed']) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Task already completed'
                ], 400);
            }
            
            // Mark task as complete in CalDAV
            $success = $this->markTaskComplete($userId, $taskId, $listId);
            if (!$success) {
                throw new \Exception('Failed to mark task as complete');
            }
            
            // Calculate XP reward based on priority
            $xpReward = $this->calculateTaskXP($taskDetails['priority']);
            
            // Get current user data
            $userData = $this->getUserData($userId);
            $currentLevel = $userData['level'];
            $currentXP = $userData['xp'];
            
            // Award XP and update database
            $newXP = $currentXP + $xpReward;
            $newLevel = $this->calculateLevelFromXP($newXP);
            $levelUp = $newLevel > $currentLevel;
            
            // Try to update user XP in database (but don't fail if it doesn't work)
            $updateResult = null;
            try {
                $updateResult = $this->updateUserXP($userId, $newXP, $newLevel);
            } catch (\Exception $e) {
                $updateResult = ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
                error_log('Quest: Could not save XP to database: ' . $e->getMessage());
            }
            
            // Try to log XP gain (but don't fail if it doesn't work)
            $logResult = null;
            try {
                $logResult = $this->logXPGain($userId, $xpReward, $taskDetails['title'], $taskId);
            } catch (\Exception $e) {
                $logResult = ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
                error_log('Quest: Could not log XP gain: ' . $e->getMessage());
            }
            
            // IMPORTANT: Read the actual values from the database to ensure consistency
            // This ensures the response reflects what was actually saved
            $updatedUserData = $this->getUserData($userId);
            $finalXP = $updatedUserData['xp'];
            $finalLevel = $updatedUserData['level'];
            
            // Calculate current streak and task counts
            $currentDate = date('Y-m-d');
            $streakData = $this->calculateStreak($userId, $currentDate);
            $taskCounts = $this->getTaskCounts($userId);
            
            error_log("Quest: Final values after update - Calculated XP: $newXP, DB XP: $finalXP, Calculated Level: $newLevel, DB Level: $finalLevel");
            error_log("Quest: Streak data - Current: " . $streakData['current_streak'] . ", Longest: " . $streakData['longest_streak']);
            error_log("Quest: Task counts - Today: " . $taskCounts['tasks_today'] . ", This week: " . $taskCounts['tasks_this_week']);
            
            // Get XP for next level based on actual saved level
            $xpForNextLevel = $this->getXPForLevel($finalLevel + 1);
            $xpForCurrentLevel = $this->getXPForLevel($finalLevel);
            $xpToNext = $xpForNextLevel - $finalXP;
            
            // Calculate progress percentage within current level using actual values
            $xpProgressInLevel = $finalXP - $xpForCurrentLevel;
            $xpRequiredForLevel = $xpForNextLevel - $xpForCurrentLevel;
            $progressPercentage = $xpRequiredForLevel > 0 ? ($xpProgressInLevel / $xpRequiredForLevel) * 100 : 0;
            
            $responseData = [
                'xp_earned' => $xpReward,
                'user_stats' => [
                    'level' => $finalLevel,
                    'xp' => $finalXP,
                    'xp_to_next' => $xpToNext,
                    'progress_percentage' => round($progressPercentage, 1),
                    'rank_title' => $this->getRankTitle($finalLevel)
                ],
                'streak' => [
                    'current_streak' => $streakData['current_streak'],
                    'longest_streak' => $streakData['longest_streak']
                ],
                'stats' => [
                    'tasks_today' => $taskCounts['tasks_today'],
                    'tasks_this_week' => $taskCounts['tasks_this_week'],
                    'total_xp' => $finalXP
                ]
            ];
            
            // Check for level up using actual database values
            $actualLevelUp = $finalLevel > $currentLevel;
            if ($actualLevelUp) {
                $responseData['level_up'] = true;
                $responseData['new_level'] = $finalLevel;
                $responseData['new_rank'] = $this->getRankTitle($finalLevel);
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => 'Quest completed successfully!',
                'data' => $responseData,
                'debug' => [
                    'calculated_xp' => $newXP,
                    'database_xp' => $finalXP,
                    'calculated_level' => $newLevel,
                    'database_level' => $finalLevel,
                    'current_xp_before' => $currentXP,
                    'xp_reward' => $xpReward,
                    'update_result' => $updateResult,
                    'log_result' => $logResult,
                    'streak_data' => $streakData,
                    'task_counts' => $taskCounts,
                    'current_date' => $currentDate
                ]
            ]);
            
        } catch (\Throwable $e) {
            error_log('Quest: Fatal error in completeTaskFromList: ' . $e->getMessage());
            error_log('Quest: Stack trace: ' . $e->getTraceAsString());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'An error occurred while completing the task: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get task details
     */
    private function getTaskDetails(string $userId, int $taskId, int $listId): ?array {
        try {
            // Get CalDAV object
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->eq('calendarid', $qb->createNamedParameter($listId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->like('calendardata', $qb->createNamedParameter('%VTODO%', \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $object = $result->fetch();
            $result->closeCursor();
            
            if (!$object) {
                return null;
            }
            
            $taskData = $this->parseVTodoData($object['calendardata']);
            if (!$taskData) {
                return null;
            }
            
            return [
                'id' => $taskId,
                'title' => $taskData['summary'] ?: 'Untitled Task',
                'description' => $taskData['description'] ?: '',
                'completed' => $taskData['completed'],
                'priority' => $this->mapTaskPriority($taskData['priority']),
                'due_date' => $taskData['due']
            ];
            
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Mark task as complete in CalDAV
     */
    private function markTaskComplete(string $userId, int $taskId, int $listId): bool {
        try {
            // Get CalDAV object
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('calendarobjects')
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($taskId, \PDO::PARAM_INT)))
                ->andWhere($qb->expr()->eq('calendarid', $qb->createNamedParameter($listId, \PDO::PARAM_INT)));
            
            $result = $qb->execute();
            $object = $result->fetch();
            $result->closeCursor();
            
            if (!$object) {
                return false;
            }
            
            // Update CalDAV data to mark as complete
            $calendarData = $object['calendardata'];
            
            // Add COMPLETED status if not present
            if (strpos($calendarData, 'STATUS:COMPLETED') === false) {
                // Find the right place to insert the status
                $lines = explode("\n", $calendarData);
                $newLines = [];
                $inserted = false;
                
                foreach ($lines as $line) {
                    $newLines[] = $line;
                    if (strpos($line, 'BEGIN:VTODO') !== false && !$inserted) {
                        $newLines[] = 'STATUS:COMPLETED';
                        $newLines[] = 'COMPLETED:' . date('Ymd\THis\Z');
                        $inserted = true;
                    }
                }
                
                $calendarData = implode("\n", $newLines);
            }
            
            // Update the calendar object
            $updateQb = $this->db->getQueryBuilder();
            $updateQb->update('calendarobjects')
                ->set('calendardata', $updateQb->createNamedParameter($calendarData, \PDO::PARAM_STR))
                ->set('lastmodified', $updateQb->createNamedParameter(time(), \PDO::PARAM_INT))
                ->where($updateQb->expr()->eq('id', $updateQb->createNamedParameter($taskId, \PDO::PARAM_INT)));
            
            $updateQb->execute();
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Calculate XP for a task based on priority
     */
    private function calculateTaskXP(string $priority): int {
        $xpMap = [
            'high' => 50,
            'medium' => 25,
            'low' => 10
        ];
        return $xpMap[$priority] ?? 25;
    }
    
    /**
     * Get user data including XP and level
     */
    private function getUserData(string $userId): array {
        try {
            // Check if user data exists in quest_user_data table
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('quest_user_data')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $userData = $result->fetch();
            $result->closeCursor();
            
            if ($userData) {
                $xp = (int)$userData['total_xp'];
                $level = (int)$userData['level'];
                $currentStreak = isset($userData['current_streak']) ? (int)$userData['current_streak'] : 0;
                $longestStreak = isset($userData['longest_streak']) ? (int)$userData['longest_streak'] : 0;
                $lastActivityDate = $userData['last_activity_date'] ?? null;
                
                error_log("Quest: Found user data - XP: $xp, Level: $level, Streak: $currentStreak, Raw data: " . json_encode($userData));
                return [
                    'xp' => $xp,
                    'level' => $level,
                    'current_streak' => $currentStreak,
                    'longest_streak' => $longestStreak,
                    'last_activity_date' => $lastActivityDate
                ];
            } else {
                error_log("Quest: No user data found in database for user: $userId");
            }
        } catch (\Exception $e) {
            // Table might not exist yet, log it but don't crash
            error_log('Quest: Could not read user data (table may not exist): ' . $e->getMessage());
        }
        
        // Return default values for new user
        error_log("Quest: Returning default values for user: $userId");
        return [
            'xp' => 0,
            'level' => 1,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null
        ];
    }
    
    /**
     * Calculate streak for user based on task completion history
     */
    private function calculateStreak(string $userId, string $currentDate): array {
        try {
            // Get unique completion dates from XP history, ordered by date descending
            $qb = $this->db->getQueryBuilder();
            $qb->select('completed_at')
                ->from('quest_xp_history')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->orderBy('completed_at', 'DESC');
            
            $result = $qb->execute();
            $completionDates = $result->fetchAll();
            $result->closeCursor();
            
            error_log("Quest: Found " . count($completionDates) . " completion dates for streak calculation");
            
            if (empty($completionDates)) {
                return ['current_streak' => 0, 'longest_streak' => 0];
            }
            
            // Extract unique dates from datetime stamps
            $uniqueDates = [];
            foreach ($completionDates as $row) {
                $dateOnly = substr($row['completed_at'], 0, 10); // Get YYYY-MM-DD part
                if (!in_array($dateOnly, $uniqueDates)) {
                    $uniqueDates[] = $dateOnly;
                }
            }
            // Sort dates descending
            rsort($uniqueDates);
            $dates = $uniqueDates;
            
            error_log("Quest: Completion dates: " . json_encode($dates));
            
            // Simple current streak calculation: consecutive days from today backwards
            $currentStreak = 0;
            $checkDate = new \DateTime($currentDate);
            
            // Check if user completed tasks today or yesterday (to start streak)
            foreach ($dates as $dateStr) {
                if ($dateStr === $checkDate->format('Y-m-d')) {
                    $currentStreak = 1;
                    break;
                } elseif ($dateStr === $checkDate->modify('-1 day')->format('Y-m-d')) {
                    $currentStreak = 1;
                    $checkDate = new \DateTime($dateStr); // Reset to yesterday
                    break;
                }
            }
            
            // If we found a starting point, count consecutive days backwards
            if ($currentStreak > 0) {
                foreach ($dates as $dateStr) {
                    $expectedDate = $checkDate->format('Y-m-d');
                    if ($dateStr === $expectedDate) {
                        // This date matches expected consecutive date
                        if ($currentStreak > 1 || $dateStr === $expectedDate) {
                            // Continue counting
                        }
                    } else {
                        // Check if it's the previous day
                        $checkDate->modify('-1 day');
                        $expectedDate = $checkDate->format('Y-m-d');
                        if ($dateStr === $expectedDate) {
                            $currentStreak++;
                        } else {
                            // Break in streak
                            break;
                        }
                    }
                }
            }
            
            // For now, set longest_streak to current_streak (can be enhanced later)
            $longestStreak = max($currentStreak, 0);
            
            error_log("Quest: Calculated streak - Current: $currentStreak, Longest: $longestStreak");
            
            return [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak
            ];
            
        } catch (\Exception $e) {
            error_log('Quest: Error calculating streak: ' . $e->getMessage());
            return ['current_streak' => 0, 'longest_streak' => 0];
        }
    }
    
    /**
     * Get task completion counts for today and this week
     */
    private function getTaskCounts(string $userId): array {
        try {
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            
            // Tasks completed today (compare date part of timestamp)
            $todayStart = $today . ' 00:00:00';
            $todayEnd = $today . ' 23:59:59';
            
            $todayQb = $this->db->getQueryBuilder();
            $todayQb->select($todayQb->func()->count('*', 'task_count'))
                ->from('quest_xp_history')
                ->where($todayQb->expr()->eq('user_id', $todayQb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($todayQb->expr()->gte('completed_at', $todayQb->createNamedParameter($todayStart, \PDO::PARAM_STR)))
                ->andWhere($todayQb->expr()->lte('completed_at', $todayQb->createNamedParameter($todayEnd, \PDO::PARAM_STR)));
            
            $todayResult = $todayQb->execute();
            $tasksToday = (int)$todayResult->fetch()['task_count'];
            $todayResult->closeCursor();
            
            // Tasks completed this week (compare date part of timestamp)
            $weekStartDateTime = $weekStart . ' 00:00:00';
            
            $weekQb = $this->db->getQueryBuilder();
            $weekQb->select($weekQb->func()->count('*', 'task_count'))
                ->from('quest_xp_history')
                ->where($weekQb->expr()->eq('user_id', $weekQb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($weekQb->expr()->gte('completed_at', $weekQb->createNamedParameter($weekStartDateTime, \PDO::PARAM_STR)));
            
            $weekResult = $weekQb->execute();
            $tasksThisWeek = (int)$weekResult->fetch()['task_count'];
            $weekResult->closeCursor();
            
            error_log("Quest: Task counts - Today: $tasksToday (since $today), This week: $tasksThisWeek (since $weekStart)");
            
            return [
                'tasks_today' => $tasksToday,
                'tasks_this_week' => $tasksThisWeek
            ];
            
        } catch (\Exception $e) {
            error_log('Quest: Error getting task counts: ' . $e->getMessage());
            return [
                'tasks_today' => 0,
                'tasks_this_week' => 0
            ];
        }
    }
    
    /**
     * Update user XP and level
     */
    private function updateUserXP(string $userId, int $xp, int $level): array {
        static $retryCount = 0;
        
        // Prevent infinite recursion
        if ($retryCount > 1) {
            error_log('Quest: Failed to update user XP after retry');
            $retryCount = 0;
            return ['status' => 'failed', 'reason' => 'retry_limit_exceeded'];
        }
        
        try {
            error_log("Quest: Starting updateUserXP - User: $userId, XP: $xp, Level: $level");
            $qb = $this->db->getQueryBuilder();
            
            // Check if user exists
            $qb->select('user_id', 'total_xp', 'level')
                ->from('quest_user_data')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $existingData = $result->fetch();
            $result->closeCursor();
            
            if ($existingData) {
                error_log("Quest: Found existing data - Current XP: " . $existingData['total_xp'] . ", Level: " . $existingData['level']);
                
                // Update existing record
                $qb = $this->db->getQueryBuilder();
                $updateResult = $qb->update('quest_user_data')
                    ->set('total_xp', $qb->createNamedParameter($xp, \PDO::PARAM_INT))
                    ->set('level', $qb->createNamedParameter($level, \PDO::PARAM_INT))
                    ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s'), \PDO::PARAM_STR))
                    ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                    ->execute();
                    
                // Ensure the transaction is committed (if supported)
                try {
                    $this->db->commit();
                } catch (\Exception $commitE) {
                    error_log("Quest: Commit not supported or failed: " . $commitE->getMessage());
                }
                    
                error_log("Quest: Updated existing user XP - User: $userId, XP: $xp, Level: $level, Affected rows: $updateResult");
                
                // Verify the update by reading it back
                $verifyQb = $this->db->getQueryBuilder();
                $verifyQb->select('total_xp', 'level')
                    ->from('quest_user_data')
                    ->where($verifyQb->expr()->eq('user_id', $verifyQb->createNamedParameter($userId, \PDO::PARAM_STR)));
                $verifyResult = $verifyQb->execute();
                $verifyData = $verifyResult->fetch();
                $verifyResult->closeCursor();
                
                if ($verifyData) {
                    error_log("Quest: Verification read - XP: " . $verifyData['total_xp'] . ", Level: " . $verifyData['level']);
                } else {
                    error_log("Quest: ERROR - Could not verify update, user data disappeared!");
                }
            } else {
                error_log("Quest: No existing data found, inserting new record");
                
                // Insert new record
                $qb = $this->db->getQueryBuilder();
                $qb->insert('quest_user_data')
                    ->values([
                        'user_id' => $qb->createNamedParameter($userId, \PDO::PARAM_STR),
                        'total_xp' => $qb->createNamedParameter($xp, \PDO::PARAM_INT),
                        'level' => $qb->createNamedParameter($level, \PDO::PARAM_INT),
                        'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'), \PDO::PARAM_STR),
                        'updated_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'), \PDO::PARAM_STR)
                    ]);
                $insertResult = $qb->execute();
                
                // Ensure the transaction is committed (if supported)
                try {
                    $this->db->commit();
                } catch (\Exception $commitE) {
                    error_log("Quest: Commit not supported or failed: " . $commitE->getMessage());
                }
                
                error_log("Quest: Inserted new user XP - User: $userId, XP: $xp, Level: $level, Insert ID: $insertResult");
                
                // Verify the insert
                $verifyQb = $this->db->getQueryBuilder();
                $verifyQb->select('total_xp', 'level')
                    ->from('quest_user_data')
                    ->where($verifyQb->expr()->eq('user_id', $verifyQb->createNamedParameter($userId, \PDO::PARAM_STR)));
                $verifyResult = $verifyQb->execute();
                $verifyData = $verifyResult->fetch();
                $verifyResult->closeCursor();
                
                if ($verifyData) {
                    error_log("Quest: Verification read after insert - XP: " . $verifyData['total_xp'] . ", Level: " . $verifyData['level']);
                } else {
                    error_log("Quest: ERROR - Could not verify insert, user data not found after insert!");
                }
            }
            $retryCount = 0; // Reset on success
            return ['status' => 'success', 'operation' => $existingData ? 'update' : 'insert'];
        } catch (\Exception $e) {
            error_log('Quest: Error updating user XP: ' . $e->getMessage());
            // Create table if it doesn't exist
            $retryCount++;
            $this->createQuestDataTable();
            // Retry the update ONCE
            if ($retryCount <= 1) {
                return $this->updateUserXP($userId, $xp, $level);
            } else {
                return ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
            }
        }
    }
    
    /**
     * Log XP gain to history
     */
    private function logXPGain(string $userId, int $xpGained, string $taskTitle, int $taskId): array {
        static $historyRetryCount = 0;
        
        // Prevent infinite recursion
        if ($historyRetryCount > 1) {
            error_log('Quest: Failed to log XP gain after retry');
            $historyRetryCount = 0;
            return ['status' => 'failed', 'reason' => 'retry_limit_exceeded'];
        }
        
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('quest_xp_history')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId, \PDO::PARAM_STR),
                    'task_id' => $qb->createNamedParameter($taskId, \PDO::PARAM_INT),
                    'task_title' => $qb->createNamedParameter($taskTitle, \PDO::PARAM_STR),
                    'xp_gained' => $qb->createNamedParameter($xpGained, \PDO::PARAM_INT),
                    'completed_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'), \PDO::PARAM_STR)
                ]);
            $insertResult = $qb->execute();
            $historyRetryCount = 0; // Reset on success
            
            error_log("Quest: Successfully logged XP gain - User: $userId, XP: $xpGained, Task: $taskTitle, Insert result: $insertResult");
            
            return ['status' => 'success', 'insert_result' => $insertResult];
        } catch (\Exception $e) {
            error_log('Quest: Error logging XP gain: ' . $e->getMessage());
            // Create history table if it doesn't exist
            $historyRetryCount++;
            $this->createXPHistoryTable();
            // Retry the insert ONCE
            if ($historyRetryCount <= 1) {
                return $this->logXPGain($userId, $xpGained, $taskTitle, $taskId);
            } else {
                return ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
            }
        }
    }
    
    /**
     * Calculate level from total XP
     */
    private function calculateLevelFromXP(int $totalXP): int {
        $level = 1;
        $xpRequired = 0;
        
        while ($xpRequired <= $totalXP) {
            $xpRequired = $this->getXPForLevel($level + 1);
            if ($xpRequired > $totalXP) {
                break;
            }
            $level++;
        }
        
        return $level;
    }
    
    /**
     * Get XP required for a specific level
     */
    private function getXPForLevel(int $level): int {
        if ($level <= 1) {
            return 0;
        }
        
        // Simple progression: 100 XP per level with slight increase
        $totalXP = 0;
        for ($i = 1; $i < $level; $i++) {
            $totalXP += 100 * $i;
        }
        
        return $totalXP;
    }
    
    /**
     * Get rank title for a level
     */
    private function getRankTitle(int $level): string {
        if ($level >= 50) return 'Legendary Hero';
        if ($level >= 40) return 'Master Adventurer';
        if ($level >= 30) return 'Elite Warrior';
        if ($level >= 25) return 'Seasoned Fighter';
        if ($level >= 20) return 'Veteran Explorer';
        if ($level >= 15) return 'Skilled Hunter';
        if ($level >= 10) return 'Experienced Ranger';
        if ($level >= 5) return 'Apprentice Warrior';
        return 'Novice Adventurer';
    }
    
    /**
     * Initialize quest tables if they don't exist
     */
    private function initializeTables(): void {
        static $initialized = false;
        if ($initialized) return;
        
        $this->createQuestDataTable();
        $this->createXPHistoryTable();
        $this->updateQuestDataTableSchema(); // Ensure new fields exist
        $initialized = true;
    }
    
    /**
     * Update quest_user_data table to add new fields if they don't exist
     */
    private function updateQuestDataTableSchema(): void {
        try {
            // Try to add the new columns if they don't exist
            $alterSql = "ALTER TABLE `*PREFIX*quest_user_data` 
                ADD COLUMN IF NOT EXISTS `current_streak` INT NOT NULL DEFAULT 0,
                ADD COLUMN IF NOT EXISTS `longest_streak` INT NOT NULL DEFAULT 0,
                ADD COLUMN IF NOT EXISTS `last_activity_date` DATE NULL";
            
            $this->db->executeStatement($alterSql);
            error_log('Quest: Updated quest_user_data table schema with new streak fields');
        } catch (\Exception $e) {
            error_log('Quest: Could not update table schema (may already exist): ' . $e->getMessage());
            
            // Try individual column additions (some databases don't support IF NOT EXISTS)
            $columns = [
                'current_streak' => 'INT NOT NULL DEFAULT 0',
                'longest_streak' => 'INT NOT NULL DEFAULT 0',
                'last_activity_date' => 'DATE NULL'
            ];
            
            foreach ($columns as $columnName => $columnDef) {
                try {
                    $sql = "ALTER TABLE `*PREFIX*quest_user_data` ADD COLUMN `{$columnName}` {$columnDef}";
                    $this->db->executeStatement($sql);
                    error_log("Quest: Added column {$columnName} to quest_user_data table");
                } catch (\Exception $colE) {
                    error_log("Quest: Column {$columnName} may already exist: " . $colE->getMessage());
                }
            }
        }
    }
    
    /**
     * Create quest user data table if it doesn't exist
     */
    private function createQuestDataTable(): void {
        try {
            // Use raw SQL with the standard Nextcloud table prefix pattern
            $sql = "CREATE TABLE IF NOT EXISTS `*PREFIX*quest_user_data` (
                `user_id` VARCHAR(64) NOT NULL PRIMARY KEY,
                `total_xp` INT NOT NULL DEFAULT 0,
                `level` INT NOT NULL DEFAULT 1,
                `current_streak` INT NOT NULL DEFAULT 0,
                `longest_streak` INT NOT NULL DEFAULT 0,
                `last_activity_date` DATE NULL,
                `created_at` DATETIME NOT NULL,
                `updated_at` DATETIME NOT NULL
            )";
            $this->db->executeStatement($sql);
            error_log('Quest: Created quest_user_data table using *PREFIX* notation');
        } catch (\Exception $e) {
            error_log('Quest: Error creating quest_user_data table: ' . $e->getMessage());
        }
    }
    
    /**
     * Create XP history table if it doesn't exist
     */
    private function createXPHistoryTable(): void {
        try {
            // Use raw SQL with the standard Nextcloud table prefix pattern
            $sql = "CREATE TABLE IF NOT EXISTS `*PREFIX*quest_xp_history` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` VARCHAR(64) NOT NULL,
                `task_id` INT NOT NULL,
                `task_title` VARCHAR(255) NOT NULL,
                `xp_gained` INT NOT NULL,
                `completed_at` DATETIME NOT NULL,
                INDEX `idx_user_id` (`user_id`),
                INDEX `idx_completed_at` (`completed_at`)
            )";
            $this->db->executeStatement($sql);
            error_log('Quest: Created quest_xp_history table using *PREFIX* notation');
        } catch (\Exception $e) {
            error_log('Quest: Error creating quest_xp_history table: ' . $e->getMessage());
        }
    }
}