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
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->db = $db;
        $this->xpService = $xpService;
        $this->levelService = $levelService;
        $this->achievementService = $achievementService;
    }
    
    /**
     * Complete a quest task from a specific list
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function completeTaskFromList() {
        try {
            
            // Initialize tables if needed
            $this->initializeTables();
            
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
            }
            
            // CRITICAL FIX: Also update the ncquest_users table to keep both tables synchronized
            try {
                $this->updateUnifiedUserXP($userId, $newXP, $newLevel);
            } catch (\Exception $e) {
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
            
            // Check for achievements
            $achievementResults = [];
            try {
                $achievementResults = $this->achievementService->checkAndUnlockAchievements($userId);
            } catch (\Exception $e) {
                // Continue without achievements if service fails
            }
            
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
     * Update user XP and level
     */
    private function updateUserXP(string $userId, int $xp, int $level): array {
        static $retryCount = 0;
        
        // Prevent infinite recursion
        if ($retryCount > 1) {
            $retryCount = 0;
            return ['status' => 'failed', 'reason' => 'retry_limit_exceeded'];
        }
        
        try {
            $qb = $this->db->getQueryBuilder();
            
            // Check if user exists
            $qb->select('user_id', 'total_xp', 'level')
                ->from('quest_user_data')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)));
            
            $result = $qb->execute();
            $existingData = $result->fetch();
            $result->closeCursor();
            
            if ($existingData) {
                
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
                    // Commit not supported or failed
                }
                
                // Verify the update by reading it back
                $verifyQb = $this->db->getQueryBuilder();
                $verifyQb->select('total_xp', 'level')
                    ->from('quest_user_data')
                    ->where($verifyQb->expr()->eq('user_id', $verifyQb->createNamedParameter($userId, \PDO::PARAM_STR)));
                $verifyResult = $verifyQb->execute();
                $verifyData = $verifyResult->fetch();
                $verifyResult->closeCursor();
                
                if ($verifyData) {
                } else {
                }
            } else {
                
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
                    // Commit not supported or failed
                }
                
                // Verify the insert
                $verifyQb = $this->db->getQueryBuilder();
                $verifyQb->select('total_xp', 'level')
                    ->from('quest_user_data')
                    ->where($verifyQb->expr()->eq('user_id', $verifyQb->createNamedParameter($userId, \PDO::PARAM_STR)));
                $verifyResult = $verifyQb->execute();
                $verifyData = $verifyResult->fetch();
                $verifyResult->closeCursor();
                
                if ($verifyData) {
                } else {
                }
            }
            $retryCount = 0; // Reset on success
            return ['status' => 'success', 'operation' => $existingData ? 'update' : 'insert'];
        } catch (\Exception $e) {
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
     * Update user XP in the ncquest_users table (unified table)
     * This keeps both tables synchronized
     */
    private function updateUnifiedUserXP(string $userId, int $newXP, int $newLevel): array {
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
                
                $updateQb = $this->db->getQueryBuilder();
                $updateQb->update('ncquest_users')
                    ->set('current_xp', $updateQb->createNamedParameter($newXP))
                    ->set('lifetime_xp', $updateQb->createNamedParameter($currentLifetimeXP))
                    ->set('level', $updateQb->createNamedParameter($newLevel))
                    ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)))
                    ->executeStatement();
                
                return ['status' => 'success', 'operation' => 'update'];
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
        static $historyRetryCount = 0;
        
        // Prevent infinite recursion
        if ($historyRetryCount > 1) {
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
            
            
            return ['status' => 'success', 'insert_result' => $insertResult];
        } catch (\Exception $e) {
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
     * Get user data including XP and level (LEGACY)
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
                
                return [
                    'xp' => $xp,
                    'level' => $level,
                    'current_streak' => $currentStreak,
                    'longest_streak' => $longestStreak,
                    'last_activity_date' => $lastActivityDate
                ];
            } else {
            }
        } catch (\Exception $e) {
            // Table might not exist yet, log it but don't crash
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
                ->from('quest_xp_history')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
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
        } catch (\Exception $e) {
            
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
                } catch (\Exception $colE) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
        }
    }
}