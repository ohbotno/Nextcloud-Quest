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
use OCA\NextcloudQuest\Service\AchievementService;

class TaskCompletionController extends Controller {
    /** @var IUserSession */
    private $userSession;
    /** @var IDBConnection */
    private $db;
    /** @var XPService */
    private $xpService;
    /** @var LevelService */
    private $levelService;
    /** @var AchievementService */
    private $achievementService;
    
    public function __construct(
        $appName, 
        IRequest $request, 
        IUserSession $userSession, 
        IDBConnection $db,
        XPService $xpService,
        LevelService $levelService,
        AchievementService $achievementService
    ) {
        error_log("=== QUEST DEBUG: TaskCompletionController constructor called ===");
        error_log("=== QUEST DEBUG: TaskCompletionController appName: " . $appName . " ===");
        
        try {
            parent::__construct($appName, $request);
            $this->userSession = $userSession;
            $this->db = $db;
            $this->xpService = $xpService;
            $this->levelService = $levelService;
            $this->achievementService = $achievementService;
            
            error_log("=== QUEST DEBUG: TaskCompletionController constructor completed successfully ===");
        } catch (\Exception $e) {
            error_log("=== QUEST DEBUG: TaskCompletionController constructor FAILED: " . $e->getMessage() . " ===");
            error_log("=== QUEST DEBUG: Constructor stack trace: " . $e->getTraceAsString() . " ===");
            throw $e;
        }
    }
    
    /**
     * Test endpoint to verify TaskCompletionController is working
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function test() {
        error_log("=== QUEST DEBUG: TaskCompletionController.test() called ===");
        return new JSONResponse([
            'status' => 'success',
            'message' => 'TaskCompletionController is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'controller' => 'TaskCompletionController'
        ]);
    }
    
    /**
     * Minimal test endpoint without any dependencies
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function basicTest() {
        error_log("=== QUEST DEBUG: TaskCompletionController.basicTest() called - NO DEPENDENCIES ===");
        error_log("=== QUEST DEBUG: Basic test timestamp: " . date('Y-m-d H:i:s') . " ===");
        return new JSONResponse([
            'status' => 'success',
            'message' => 'Basic routing works - TaskCompletionController instantiated successfully',
            'timestamp' => date('Y-m-d H:i:s'),
            'test_type' => 'basic_routing'
        ]);
    }
    
    /**
     * Ultra-minimal test that just returns a simple response
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function ultraMinimalTest() {
        error_log("=== QUEST ULTRA DEBUG: ultraMinimalTest() START ===");
        error_log("=== QUEST ULTRA DEBUG: Timestamp: " . date('Y-m-d H:i:s') . " ===");
        try {
            $response = new JSONResponse(['hello' => 'world', 'timestamp' => date('Y-m-d H:i:s')]);
            error_log("=== QUEST ULTRA DEBUG: Response created successfully ===");
            return $response;
        } catch (\Exception $e) {
            error_log("=== QUEST ULTRA DEBUG: Exception in ultraMinimalTest: " . $e->getMessage() . " ===");
            error_log("=== QUEST ULTRA DEBUG: Exception trace: " . $e->getTraceAsString() . " ===");
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Simple POST test method
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function testPost() {
        error_log("=== QUEST DEBUG: TaskCompletionController.testPost() called ===");
        return new JSONResponse([
            'status' => 'success',
            'message' => 'POST request works!',
            'method' => 'POST',
            'timestamp' => date('Y-m-d H:i:s'),
            'received_data' => $this->request->getParams()
        ]);
    }
    
    /**
     * Complete a quest task from a specific list
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function completeTaskFromList() {
        error_log("=== QUEST DEBUG: TaskCompletionController.completeTaskFromList() called ===");
        error_log("Quest: Method entry - timestamp: " . date('Y-m-d H:i:s'));
        try {
            // Check user session first
            $user = $this->userSession->getUser();
            error_log("Quest: User session check - " . ($user ? "User: " . $user->getUID() : "No user found"));
            
            // Legacy table initialization removed - using ncquest_* tables only
            
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new \Exception('User not found');
            }
            $userId = $user->getUID();
            
            // Get request data using proper framework methods
            $taskId = $this->request->getParam('task_id');
            $listId = $this->request->getParam('list_id');
            
            // Validate inputs
            $validationErrors = $this->validateTaskInput([
                'task_id' => $taskId,
                'list_id' => $listId
            ]);
            
            if (!empty($validationErrors)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Invalid input: ' . implode(', ', $validationErrors)
                ], 400);
            }
            
            
            // Get task details before marking complete
            $taskDetails = $this->getTaskDetails($userId, $taskId, $listId);
            if (!$taskDetails) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Task not found'
                ], 404);
            }
            
            // Note: We no longer check if the task is already completed since
            // the TasksApiIntegration now filters out completed tasks from the Quest interface.
            // If a user somehow tries to complete an already-completed task, we'll just
            // skip the CalDAV update but still award XP (shouldn't happen in normal flow).
            
            // Mark task as complete in CalDAV (skip if already completed)
            if (!$taskDetails['completed']) {
                $success = $this->markTaskComplete($userId, $taskId, $listId);
                if (!$success) {
                    // Log the failure but don't stop XP awarding
                    error_log("Quest: Failed to mark task {$taskId} as complete in CalDAV, but continuing with XP award");
                }
            } else {
                // Task already completed, skip CalDAV update but continue with XP
                error_log("Quest: Task {$taskId} already completed, skipping CalDAV update");
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
            
            // Update user XP in ncquest_users table
            $updateResult = null;
            try {
                $updateResult = $this->updateUserXP($userId, $newXP, $newLevel);
                error_log("Quest: User XP update result: " . json_encode($updateResult));
            } catch (\Exception $e) {
                error_log("Quest: CRITICAL ERROR - Failed to update ncquest_users table: " . $e->getMessage());
                error_log("Quest: This means XP stats won't display correctly in frontend");
                $updateResult = ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
            }
            
            // Try to log XP gain (but don't fail if it doesn't work)
            $logResult = null;
            try {
                $logResult = $this->logXPGain($userId, $xpReward, $taskDetails['title'], $taskId);
            } catch (\Exception $e) {
                $logResult = ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
            }
            
            // IMPORTANT: Read the actual values from the database to ensure consistency
            // This ensures the response reflects what was actually saved
            // Add a small delay to ensure database write is committed
            usleep(100000); // 100ms delay
            $updatedUserData = $this->getUserData($userId);
            $finalXP = $updatedUserData['xp'];
            $finalLevel = $updatedUserData['level'];
            
            // Additional verification - if the database read doesn't match what we tried to save,
            // log this as a critical error for debugging
            if ($finalXP !== $newXP) {
                
                // Force another update attempt
                try {
                    $this->updateUserXP($userId, $newXP, $newLevel);
                    // Read again after forced update
                    $updatedUserData = $this->getUserData($userId);
                    $finalXP = $updatedUserData['xp'];
                    $finalLevel = $updatedUserData['level'];
                } catch (\Exception $forceUpdateError) {
                }
            }
            
            // Calculate current streak and task counts
            $currentDate = date('Y-m-d');
            $streakData = $this->calculateStreak($userId, $currentDate);
            $taskCounts = $this->getTaskCounts($userId);
            
            // TODO: Re-implement achievement checking with proper Quest object
            // For now, skip achievement checks to fix immediate HTTP 500 error
            $achievementResults = [];
            // Note: checkAchievements() requires Quest object which isn't available here
            // Consider moving achievement logic to XPService or creating simpler achievement method
            
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
                ],
                'achievements' => $achievementResults
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
     * Update user XP and level in ncquest_users table
     */
    private function updateUserXP(string $userId, int $newXP, int $newLevel): array {
        try {
            // Check if user exists in ncquest_users table
            $qb = $this->db->getQueryBuilder();
            $qb->select('user_id', 'current_xp', 'lifetime_xp', 'level')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            if ($userData) {
                // Update existing record
                $currentLifetimeXP = max((int)$userData['lifetime_xp'], $newXP); // Ensure lifetime_xp never decreases
                
                // Get current daily XP and task counts, increment them
                $currentDailyXP = $this->getCurrentDailyXP($userId);
                $xpGainedThisCompletion = $newXP - (int)$userData['lifetime_xp'];
                $newDailyXP = $currentDailyXP + $xpGainedThisCompletion;
                
                // Get and increment task counters
                $taskCounts = $this->getCurrentTaskCounts($userId);
                $newTasksToday = $taskCounts['tasks_today'] + 1;
                $newTasksWeek = $taskCounts['tasks_week'] + 1;
                $newTasksTotal = $taskCounts['tasks_total'] + 1;
                
                $updateQb = $this->db->getQueryBuilder();
                $updateQb->update('ncquest_users')
                    ->set('current_xp', $updateQb->createNamedParameter($newXP))
                    ->set('lifetime_xp', $updateQb->createNamedParameter($currentLifetimeXP))
                    ->set('level', $updateQb->createNamedParameter($newLevel))
                    ->set('xp_gained_today', $updateQb->createNamedParameter($newDailyXP))
                    ->set('tasks_completed_today', $updateQb->createNamedParameter($newTasksToday))
                    ->set('tasks_completed_this_week', $updateQb->createNamedParameter($newTasksWeek))
                    ->set('total_tasks_completed', $updateQb->createNamedParameter($newTasksTotal))
                    ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)))
                    ->executeStatement();
                
                return ['status' => 'success', 'operation' => 'update', 'daily_xp_updated' => $newDailyXP];
            } else {
                // Create new user in ncquest_users table
                $insertQb = $this->db->getQueryBuilder();
                $insertQb->insert('ncquest_users')
                    ->values([
                        'user_id' => $insertQb->createNamedParameter($userId),
                        'current_xp' => $insertQb->createNamedParameter($newXP),
                        'lifetime_xp' => $insertQb->createNamedParameter($newXP),
                        'level' => $insertQb->createNamedParameter($newLevel),
                        'current_streak' => $insertQb->createNamedParameter(0),
                        'longest_streak' => $insertQb->createNamedParameter(0),
                        'current_health' => $insertQb->createNamedParameter(100),
                        'max_health' => $insertQb->createNamedParameter(100),
                        'tasks_completed_today' => $insertQb->createNamedParameter(0),
                        'tasks_completed_this_week' => $insertQb->createNamedParameter(0),
                        'total_tasks_completed' => $insertQb->createNamedParameter(0),
                        'xp_gained_today' => $insertQb->createNamedParameter(0),
                        'theme_preference' => $insertQb->createNamedParameter('game'),
                        'created_at' => $insertQb->createNamedParameter(date('Y-m-d H:i:s')),
                        'updated_at' => $insertQb->createNamedParameter(date('Y-m-d H:i:s'))
                    ])
                    ->executeStatement();
                
                return ['status' => 'success', 'operation' => 'insert'];
            }
            
        } catch (\Exception $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }
    
    
    /**
     * Log XP gain to history
     */
    private function logXPGain(string $userId, int $xpGained, string $taskTitle, int $taskId): array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('ncquest_history')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'task_id' => $qb->createNamedParameter((string)$taskId), // Convert to string for ncquest_history
                    'task_title' => $qb->createNamedParameter($taskTitle),
                    'xp_earned' => $qb->createNamedParameter($xpGained), // Note: field name is xp_earned not xp_gained
                    'completed_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'))
                ]);
            $insertResult = $qb->executeStatement();
            
            return ['status' => 'success', 'insert_result' => $insertResult];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'reason' => 'exception', 'message' => $e->getMessage()];
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
     * Validate input data for task operations
     * 
     * @param array $input
     * @return array Array of validation errors (empty if valid)
     */
    private function validateTaskInput(array $input): array {
        $errors = [];
        
        // Validate task_id
        if (!isset($input['task_id'])) {
            $errors[] = 'task_id is required';
        } elseif (!is_numeric($input['task_id']) && !is_string($input['task_id'])) {
            $errors[] = 'task_id must be numeric or string';
        } elseif (is_string($input['task_id']) && strlen($input['task_id']) > 64) {
            $errors[] = 'task_id too long (max 64 characters)';
        }
        
        // Validate list_id
        if (!isset($input['list_id'])) {
            $errors[] = 'list_id is required';
        } elseif (!is_numeric($input['list_id']) && !is_string($input['list_id'])) {
            $errors[] = 'list_id must be numeric or string';
        } elseif (is_string($input['list_id']) && strlen($input['list_id']) > 64) {
            $errors[] = 'list_id too long (max 64 characters)';
        }
        
        // Validate task_title if provided
        if (isset($input['task_title'])) {
            if (!is_string($input['task_title'])) {
                $errors[] = 'task_title must be a string';
            } elseif (strlen($input['task_title']) > 255) {
                $errors[] = 'task_title too long (max 255 characters)';
            }
        }
        
        // Validate priority if provided
        if (isset($input['priority'])) {
            $validPriorities = ['high', 'medium', 'low'];
            if (!in_array($input['priority'], $validPriorities)) {
                $errors[] = 'priority must be one of: ' . implode(', ', $validPriorities);
            }
        }
        
        return $errors;
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
    
    // ========== SHARED UTILITY METHODS ==========
    
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
     * Get user data including XP and level from ncquest_users
     */
    private function getUserData(string $userId): array {
        try {
            // Check if user data exists in ncquest_users table
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            if ($userData) {
                return [
                    'xp' => (int)$userData['lifetime_xp'], // Use lifetime_xp as total XP
                    'level' => (int)$userData['level'],
                    'current_streak' => (int)$userData['current_streak'],
                    'longest_streak' => (int)$userData['longest_streak'],
                    'last_activity_date' => $userData['last_completion_date']
                ];
            }
        } catch (\Exception $e) {
            // Table might not exist yet or other error
            error_log('Failed to get user data: ' . $e->getMessage());
        }
        
        // Return default values for new user
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
                ->from('ncquest_history')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
                ->orderBy('completed_at', 'DESC');
            
            $result = $qb->execute();
            $completionDates = $result->fetchAll();
            $result->closeCursor();
            
            
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
            
            
            return [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak
            ];
            
        } catch (\Exception $e) {
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
                ->from('ncquest_history')
                ->where($todayQb->expr()->eq('user_id', $todayQb->createNamedParameter($userId)))
                ->andWhere($todayQb->expr()->gte('completed_at', $todayQb->createNamedParameter($todayStart)))
                ->andWhere($todayQb->expr()->lte('completed_at', $todayQb->createNamedParameter($todayEnd)));
            
            $todayResult = $todayQb->executeQuery();
            $tasksToday = (int)$todayResult->fetchOne();
            $todayResult->closeCursor();
            
            // Tasks completed this week (compare date part of timestamp)
            $weekStartDateTime = $weekStart . ' 00:00:00';
            
            $weekQb = $this->db->getQueryBuilder();
            $weekQb->select($weekQb->func()->count('*', 'task_count'))
                ->from('ncquest_history')
                ->where($weekQb->expr()->eq('user_id', $weekQb->createNamedParameter($userId)))
                ->andWhere($weekQb->expr()->gte('completed_at', $weekQb->createNamedParameter($weekStartDateTime)));
            
            $weekResult = $weekQb->executeQuery();
            $tasksThisWeek = (int)$weekResult->fetchOne();
            $weekResult->closeCursor();
            
            
            return [
                'tasks_today' => $tasksToday,
                'tasks_this_week' => $tasksThisWeek
            ];
            
        } catch (\Exception $e) {
            return [
                'tasks_today' => 0,
                'tasks_this_week' => 0
            ];
        }
    }
    
    /**
     * Get current daily XP and reset if it's a new day
     */
    private function getCurrentDailyXP(string $userId): int {
        try {
            // Get current daily XP and last update date
            $qb = $this->db->getQueryBuilder();
            $qb->select('xp_gained_today', 'updated_at')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            if (!$userData) {
                return 0; // User doesn't exist yet
            }
            
            $currentDailyXP = (int)($userData['xp_gained_today'] ?? 0);
            $lastUpdateDate = $userData['updated_at'] ? date('Y-m-d', strtotime($userData['updated_at'])) : null;
            $today = date('Y-m-d');
            
            // If it's a new day, reset daily XP to 0
            if ($lastUpdateDate && $lastUpdateDate !== $today) {
                // Reset daily XP for new day
                $resetQb = $this->db->getQueryBuilder();
                $resetQb->update('ncquest_users')
                    ->set('xp_gained_today', $resetQb->createNamedParameter(0))
                    ->where($resetQb->expr()->eq('user_id', $resetQb->createNamedParameter($userId)))
                    ->executeStatement();
                
                return 0;
            }
            
            return $currentDailyXP;
        } catch (\Exception $e) {
            error_log("Quest: Error getting daily XP: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get current task counts and reset daily/weekly if needed
     */
    private function getCurrentTaskCounts(string $userId): array {
        try {
            // Get current task counts and last update date
            $qb = $this->db->getQueryBuilder();
            $qb->select('tasks_completed_today', 'tasks_completed_this_week', 'total_tasks_completed', 'updated_at')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            if (!$userData) {
                return ['tasks_today' => 0, 'tasks_week' => 0, 'tasks_total' => 0];
            }
            
            $tasksToday = (int)($userData['tasks_completed_today'] ?? 0);
            $tasksWeek = (int)($userData['tasks_completed_this_week'] ?? 0);
            $tasksTotal = (int)($userData['total_tasks_completed'] ?? 0);
            
            $lastUpdateDate = $userData['updated_at'] ? date('Y-m-d', strtotime($userData['updated_at'])) : null;
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $lastUpdateWeek = $lastUpdateDate ? date('Y-m-d', strtotime('monday', strtotime($lastUpdateDate))) : null;
            
            // Reset counters if needed
            $needsReset = false;
            if ($lastUpdateDate && $lastUpdateDate !== $today) {
                $tasksToday = 0; // Reset daily count for new day
                $needsReset = true;
            }
            
            if ($lastUpdateWeek && $lastUpdateWeek !== $weekStart) {
                $tasksWeek = 0; // Reset weekly count for new week
                $needsReset = true;
            }
            
            // Update database with reset counts if needed
            if ($needsReset) {
                $resetQb = $this->db->getQueryBuilder();
                $resetQb->update('ncquest_users')
                    ->set('tasks_completed_today', $resetQb->createNamedParameter($tasksToday))
                    ->set('tasks_completed_this_week', $resetQb->createNamedParameter($tasksWeek))
                    ->where($resetQb->expr()->eq('user_id', $resetQb->createNamedParameter($userId)))
                    ->executeStatement();
            }
            
            return [
                'tasks_today' => $tasksToday,
                'tasks_week' => $tasksWeek,
                'tasks_total' => $tasksTotal
            ];
        } catch (\Exception $e) {
            error_log("Quest: Error getting task counts: " . $e->getMessage());
            return ['tasks_today' => 0, 'tasks_week' => 0, 'tasks_total' => 0];
        }
    }
    
}