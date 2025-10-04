<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Service\AchievementService;
use OCA\NextcloudQuest\Service\StreakService;
use OCA\NextcloudQuest\Service\LevelService;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Integration\TasksApiIntegration;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class QuestController extends Controller {
    /** @var IUserSession */
    private $userSession;
    /** @var XPService */
    private $xpService;
    /** @var AchievementService */
    private $achievementService;
    /** @var StreakService */
    private $streakService;
    /** @var LevelService */
    private $levelService;
    /** @var QuestMapper */
    private $questMapper;
    /** @var HistoryMapper */
    private $historyMapper;
    /** @var TasksApiIntegration */
    private $tasksIntegration;

    public function __construct(
        $appName,
        IRequest $request,
        IUserSession $userSession,
        XPService $xpService,
        AchievementService $achievementService,
        StreakService $streakService,
        LevelService $levelService,
        QuestMapper $questMapper,
        HistoryMapper $historyMapper,
        TasksApiIntegration $tasksIntegration = null
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->xpService = $xpService;
        $this->achievementService = $achievementService;
        $this->streakService = $streakService;
        $this->levelService = $levelService;
        $this->questMapper = $questMapper;
        $this->historyMapper = $historyMapper;
        $this->tasksIntegration = $tasksIntegration;
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
            // Simplified without dependencies
            $userId = 'test-user';
            
            // Return default stats for new user (simplified version)
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
                        'current_xp' => 0,
                        'lifetime_xp' => 0,
                        'xp_for_next_level' => 100,
                        'xp_progress' => 0,
                        'xp_to_next_level' => 100
                    ],
                    'streak' => [
                        'current_streak' => 0,
                        'longest_streak' => 0,
                        'is_active_today' => false,
                        'last_completion' => null
                    ],
                    'achievements' => [
                        'total' => 17,
                        'unlocked' => 0,
                        'percentage' => 0
                    ],
                    'leaderboard_rank' => null
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
     * Get quest lists (task lists from Tasks app)
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
            
            if (!$this->tasksIntegration) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Tasks integration not available',
                    'data' => []
                ]);
            }
            
            // Check if Tasks app is available
            if (!$this->tasksIntegration->isTasksAppAvailable()) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Tasks app not installed or tables not found',
                    'data' => []
                ]);
            }
            
            $taskLists = $this->tasksIntegration->getTaskLists($userId);
            
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
     * Test endpoint to verify achievement routing works
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function testAchievements() {
        try {
            return new JSONResponse([
                'status' => 'success',
                'message' => 'Achievement routing works!',
                'timestamp' => date('Y-m-d H:i:s'),
                'controller_loaded' => true
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get all achievements with unlock status
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAchievements() {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $achievements = $this->achievementService->getAllAchievements($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'achievements' => $achievements
            ]);
        } catch (\Exception $e) {
            // Return error details for debugging
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get achievements grouped by category
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAchievementsByCategory() {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $categories = $this->achievementService->getAchievementsByCategory($userId);
            $achievements = $this->achievementService->getAllAchievements($userId);
            
            // Add progress information for milestone-based achievements
            foreach ($achievements as &$achievement) {
                if ($achievement['progress_type'] === 'milestone' && !$achievement['unlocked']) {
                    $progress = $this->achievementService->getAchievementProgress($userId, $achievement['key']);
                    if ($progress) {
                        $achievement['progress'] = $progress;
                    }
                }
            }
            
            return new JSONResponse([
                'status' => 'success',
                'categories' => $categories,
                'achievements' => $achievements
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent achievements for current user
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getRecentAchievements() {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $recentAchievements = $this->achievementService->getRecentAchievements($userId, 10);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $recentAchievements
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'success', 
                'data' => []
            ]);
        }
    }

    /**
     * Get achievement statistics for current user
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAchievementStats() {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $stats = $this->achievementService->getAchievementStats($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Get achievements by rarity level
     * 
     * @NoAdminRequired
     * @param string $rarity
     * @return JSONResponse
     */
    public function getAchievementsByRarity($rarity) {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $achievements = $this->achievementService->getAchievementsByRarity($userId, $rarity);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $achievements
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get achievement progress for a specific achievement
     * 
     * @NoAdminRequired
     * @param string $achievementKey
     * @return JSONResponse
     */
    public function getAchievementProgress($achievementKey) {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $progress = $this->achievementService->getAchievementProgress($userId, $achievementKey);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $progress
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Complete a task and award XP
     * 
     * @NoAdminRequired
     * @param string $taskId
     * @param string $taskTitle
     * @param string $priority
     * @return JSONResponse
     */
    public function completeTask($taskId, $taskTitle, $priority = 'medium') {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            // Create and dispatch task completion event
            $eventDispatcher = \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class);
            
            $taskData = [
                'taskId' => $taskId,
                'userId' => $userId,
                'taskTitle' => $taskTitle,
                'priority' => $priority
            ];
            
            $event = new \OCA\NextcloudQuest\Event\TaskCompletedEvent($taskData);
            $eventDispatcher->dispatch(\OCA\NextcloudQuest\Event\TaskCompletedEvent::class, $event);
            
            // Process the task completion directly (since we dispatched our own event)
            // Update streak first
            $streakResult = $this->streakService->updateStreak($userId);
            
            // Award XP
            $xpResult = $this->xpService->awardXP($userId, $taskId, $taskTitle, $priority);
            
            // Get updated quest data
            $quest = $this->questMapper->findByUserId($userId);
            
            // Check for new achievements
            $completionTime = new \DateTime();
            $newAchievements = $this->achievementService->checkAchievements($userId, $quest, $completionTime);
            
            // Mark achievements as notified
            $this->achievementService->markAchievementsAsNotified($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'xp' => $xpResult,
                    'streak' => $streakResult,
                    'new_achievements' => array_map(function($achievement) {
                        return [
                            'key' => $achievement->getAchievementKey(),
                            'unlocked_at' => $achievement->getUnlockedAt()
                        ];
                    }, $newAchievements)
                ]
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to complete task: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Simple test endpoint to verify controller works
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function testEndpoint() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'QuestController is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Complete a task from Tasks app and award XP
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function completeTaskFromList() {
        error_log('=== QUEST DEBUG: completeTaskFromList method called ===');
        file_put_contents('/tmp/quest_debug.log', '[' . date('Y-m-d H:i:s') . '] completeTaskFromList called' . PHP_EOL, FILE_APPEND);
        try {
            error_log('Quest: completeTaskFromList called');
            
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

            // Mark task as completed in Tasks app
            if ($this->tasksIntegration) {
                $taskCompleted = $this->tasksIntegration->markTaskCompleted($taskId, $userId);
                error_log("Quest: Task completion sync to Tasks app: " . ($taskCompleted ? 'SUCCESS' : 'FAILED'));
            } else {
                error_log("Quest: TasksIntegration not available, skipping sync to Tasks app");
            }

            // Calculate XP reward based on medium priority (simplified)
            $xpReward = 25;
            $priority = 'medium'; // Default priority

            // Get current user data using simple DB operations
            $userData = $this->getSimpleUserData($userId);
            $currentLevel = $userData['level'];
            $currentXP = $userData['xp'];

            // Calculate health regeneration BEFORE database update
            $currentHealth = (int)($userData['current_health'] ?? 100);
            $maxHealth = (int)($userData['max_health'] ?? 100);

            // Priority-based regeneration: high=10, medium=5, low=3
            $healthRegenAmount = 5; // default medium
            switch ($priority) {
                case 'high':
                    $healthRegenAmount = 10;
                    break;
                case 'low':
                    $healthRegenAmount = 3;
                    break;
            }

            // Calculate new health (don't exceed max)
            $newHealth = min($maxHealth, $currentHealth + $healthRegenAmount);

            // Award XP and update database
            $newXP = $currentXP + $xpReward;
            $newLevel = $this->calculateLevelFromXP($newXP);
            $levelUp = $newLevel > $currentLevel;

            // Update user XP AND HEALTH in database using simple operations
            $updateResult = $this->updateSimpleUserXP($userId, $newXP, $newLevel, $xpReward, $newHealth, $maxHealth);
            
            // Record XP earned in history table for daily tracking
            error_log("Quest: About to call recordXPHistory - User: $userId, Task: $taskId, XP: $xpReward");
            $this->recordXPHistory($userId, $taskId, $xpReward);

            // Update streak directly in ncquest_users table
            error_log("Quest: Updating streak for user: $userId");
            $streakData = $this->updateStreakInUnifiedTable($userId);
            error_log("Quest: Streak updated - current: {$streakData['current_streak']}, longest: {$streakData['longest_streak']}");

            // Check for new achievements (don't fail request if this errors)
            $newAchievements = [];
            try {
                error_log("Quest: Checking achievements for user: $userId");
                $quest = $this->questMapper->findByUserId($userId);
                $completionTime = new \DateTime();
                $newAchievements = $this->achievementService->checkAchievements($userId, $quest, $completionTime);
                error_log("Quest: Found " . count($newAchievements) . " new achievements");
            } catch (\Throwable $e) {
                error_log('Quest: Achievement check failed (non-fatal): ' . $e->getMessage());
                error_log('Quest: Achievement check stack trace: ' . $e->getTraceAsString());
                // Continue processing task completion even if achievements fail
            }

            // Get updated data from database including task counts
            $updatedUserData = $this->getSimpleUserData($userId);
            $finalXP = $updatedUserData['xp'];
            $finalLevel = $updatedUserData['level'];

            // Get updated task counts from database
            $taskCounts = $this->getTaskCountsFromUnifiedTable($userId);

            // Calculate progress for next level
            $xpForNextLevel = $this->getXPForLevel($finalLevel + 1);
            $xpForCurrentLevel = $this->getXPForLevel($finalLevel);
            $xpToNext = $xpForNextLevel - $finalXP;

            $xpProgressInLevel = $finalXP - $xpForCurrentLevel;
            $xpRequiredForLevel = $xpForNextLevel - $xpForCurrentLevel;
            $progressPercentage = $xpRequiredForLevel > 0 ? ($xpProgressInLevel / $xpRequiredForLevel) * 100 : 0;

            // Calculate health percentage for response
            $healthPercentage = $maxHealth > 0 ? ($newHealth / $maxHealth) * 100 : 100;

            $responseData = [
                'xp_earned' => $xpReward,
                'user_stats' => [
                    'level' => $finalLevel,
                    'xp' => $finalXP,
                    'xp_to_next' => $xpToNext,
                    'progress_percentage' => round($progressPercentage, 1),
                    'rank_title' => $this->getRankTitle($finalLevel)
                ],
                'health' => [
                    'current_health' => $newHealth,
                    'max_health' => $maxHealth,
                    'percentage' => round($healthPercentage, 1)
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

            if ($levelUp) {
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
                    'update_result' => $updateResult
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
     * Simple POST test method
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function testPost() {
        return new JSONResponse([
            'status' => 'success',
            'message' => 'POST method works!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
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
     * Get simple user data from ncquest_users table
     */
    private function getSimpleUserData(string $userId): array {
        try {
            $db = \OC::$server->getDatabaseConnection();
            $qb = $db->getQueryBuilder();
            $qb->select('*')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            if ($userData) {
                return [
                    'xp' => (int)($userData['lifetime_xp'] ?? 0),
                    'level' => (int)($userData['level'] ?? 1)
                ];
            }
        } catch (\Exception $e) {
            error_log('Quest: Error getting simple user data: ' . $e->getMessage());
        }
        
        return ['xp' => 0, 'level' => 1];
    }
    
    /**
     * Update user XP using simple DB operations
     */
    private function updateSimpleUserXP(string $userId, int $xp, int $level, int $xpEarned = 0, int $currentHealth = null, int $maxHealth = null): array {
        try {
            $db = \OC::$server->getDatabaseConnection();
            $qb = $db->getQueryBuilder();

            // Check if user exists and get current values including health
            $qb->select('user_id', 'tasks_completed_today', 'tasks_completed_this_week', 'total_tasks_completed', 'xp_gained_today', 'last_daily_reset', 'current_health', 'max_health')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            file_put_contents('/tmp/quest_debug.log', '[' . date('Y-m-d H:i:s') . '] Database query result: ' . json_encode($userData) . PHP_EOL, FILE_APPEND);
            
            if ($userData) {
                // Check if we need to reset daily counters
                $today = date('Y-m-d');
                $lastReset = $userData['last_daily_reset'];
                $needsDailyReset = !$lastReset || $lastReset !== $today;

                // Calculate new counters (handle null values)
                $tasksToday = $needsDailyReset ? 1 : (int)($userData['tasks_completed_today'] ?? 0) + 1;
                $xpToday = $needsDailyReset ? $xpEarned : (int)($userData['xp_gained_today'] ?? 0) + $xpEarned;
                $tasksThisWeek = (int)($userData['tasks_completed_this_week'] ?? 0) + 1;
                $totalTasks = (int)($userData['total_tasks_completed'] ?? 0) + 1;

                // Use provided health values or keep existing
                $newCurrentHealth = $currentHealth !== null ? max(0, min($currentHealth, $maxHealth ?? 100)) : (int)($userData['current_health'] ?? 100);
                $newMaxHealth = $maxHealth !== null ? $maxHealth : (int)($userData['max_health'] ?? 100);

                error_log("Quest: XP update calculation - needsDailyReset: " . ($needsDailyReset ? 'true' : 'false'));
                error_log("Quest: XP update calculation - current xp_gained_today: " . ($userData['xp_gained_today'] ?? 'NULL'));
                error_log("Quest: XP update calculation - xpEarned: $xpEarned");
                error_log("Quest: XP update calculation - new xpToday: $xpToday");
                error_log("Quest: Health update - new health: $newCurrentHealth / $newMaxHealth");

                file_put_contents('/tmp/quest_debug.log', '[' . date('Y-m-d H:i:s') . '] XP calculation - needsDailyReset: ' . ($needsDailyReset ? 'true' : 'false') . ', current_xp_gained_today: ' . ($userData['xp_gained_today'] ?? 'NULL') . ', xpEarned: ' . $xpEarned . ', new_xpToday: ' . $xpToday . PHP_EOL, FILE_APPEND);

                // Update existing user (including health)
                $qb = $db->getQueryBuilder();
                $qb->update('ncquest_users')
                    ->set('lifetime_xp', $qb->createNamedParameter($xp))
                    ->set('current_xp', $qb->createNamedParameter($xp))
                    ->set('level', $qb->createNamedParameter($level))
                    ->set('current_health', $qb->createNamedParameter($newCurrentHealth))
                    ->set('max_health', $qb->createNamedParameter($newMaxHealth))
                    ->set('tasks_completed_today', $qb->createNamedParameter($tasksToday))
                    ->set('tasks_completed_this_week', $qb->createNamedParameter($tasksThisWeek))
                    ->set('total_tasks_completed', $qb->createNamedParameter($totalTasks))
                    ->set('xp_gained_today', $qb->createNamedParameter($xpToday))
                    ->set('last_daily_reset', $qb->createNamedParameter($today))
                    ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
                $qb->executeStatement();
                
                return ['status' => 'success', 'operation' => 'update'];
            } else {
                // Insert new user with health values
                $newCurrentHealth = $currentHealth !== null ? max(0, min($currentHealth, $maxHealth ?? 100)) : 100;
                $newMaxHealth = $maxHealth !== null ? $maxHealth : 100;

                $qb = $db->getQueryBuilder();
                $qb->insert('ncquest_users')
                    ->values([
                        'user_id' => $qb->createNamedParameter($userId),
                        'current_xp' => $qb->createNamedParameter($xp),
                        'lifetime_xp' => $qb->createNamedParameter($xp),
                        'level' => $qb->createNamedParameter($level),
                        'current_health' => $qb->createNamedParameter($newCurrentHealth),
                        'max_health' => $qb->createNamedParameter($newMaxHealth),
                        'current_streak' => $qb->createNamedParameter(0),
                        'longest_streak' => $qb->createNamedParameter(0),
                        'theme_preference' => $qb->createNamedParameter('game'),
                        'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
                        'updated_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'))
                    ]);
                $qb->executeStatement();

                return ['status' => 'success', 'operation' => 'insert'];
            }
        } catch (\Exception $e) {
            error_log('Quest: Error updating simple user XP: ' . $e->getMessage());
            return ['status' => 'failed', 'message' => $e->getMessage()];
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
     * Get task counts from ncquest_users table
     */
    private function getTaskCountsFromUnifiedTable(string $userId): array {
        try {
            $db = \OC::$server->getDatabaseConnection();
            $qb = $db->getQueryBuilder();

            $qb->select('tasks_completed_today', 'tasks_completed_this_week', 'total_tasks_completed')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();

            if ($userData) {
                return [
                    'tasks_today' => (int)($userData['tasks_completed_today'] ?? 0),
                    'tasks_this_week' => (int)($userData['tasks_completed_this_week'] ?? 0),
                    'total_completed' => (int)($userData['total_tasks_completed'] ?? 0)
                ];
            }

            return [
                'tasks_today' => 0,
                'tasks_this_week' => 0,
                'total_completed' => 0
            ];

        } catch (\Exception $e) {
            error_log("Quest: Error getting task counts: " . $e->getMessage());
            return [
                'tasks_today' => 0,
                'tasks_this_week' => 0,
                'total_completed' => 0
            ];
        }
    }

    /**
     * Update streak in ncquest_users table
     */
    private function updateStreakInUnifiedTable(string $userId): array {
        try {
            $db = \OC::$server->getDatabaseConnection();
            $qb = $db->getQueryBuilder();

            // Get current user data
            $qb->select('last_completion_date', 'current_streak', 'longest_streak')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();

            $now = new \DateTime();
            $today = $now->format('Y-m-d');

            $currentStreak = 1; // Default for new users or first task
            $longestStreak = 1;

            if ($userData && $userData['last_completion_date']) {
                $lastCompletionDate = new \DateTime($userData['last_completion_date']);
                $lastCompletionDay = $lastCompletionDate->format('Y-m-d');

                $previousStreak = (int)$userData['current_streak'];
                $previousLongest = (int)$userData['longest_streak'];

                // Calculate days difference
                $interval = $now->diff($lastCompletionDate);
                $daysDiff = $interval->days;

                if ($lastCompletionDay === $today) {
                    // Same day - maintain streak
                    $currentStreak = $previousStreak;
                } elseif ($daysDiff === 1) {
                    // Next day - increment streak
                    $currentStreak = $previousStreak + 1;
                } else {
                    // Streak broken - reset to 1
                    $currentStreak = 1;
                }

                // Update longest streak if necessary
                $longestStreak = max($currentStreak, $previousLongest);
            }

            // Update the database
            $updateQb = $db->getQueryBuilder();
            $updateQb->update('ncquest_users')
                ->set('current_streak', $updateQb->createNamedParameter($currentStreak))
                ->set('longest_streak', $updateQb->createNamedParameter($longestStreak))
                ->set('last_completion_date', $updateQb->createNamedParameter($now->format('Y-m-d H:i:s')))
                ->set('updated_at', $updateQb->createNamedParameter($now->format('Y-m-d H:i:s')))
                ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)))
                ->executeStatement();

            return [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak
            ];

        } catch (\Exception $e) {
            error_log("Quest: Error updating streak: " . $e->getMessage());
            return [
                'current_streak' => 0,
                'longest_streak' => 0
            ];
        }
    }
    
    /**
     * Get task completion history
     * 
     * @NoAdminRequired
     * @param int $limit
     * @param int $offset
     * @return JSONResponse
     */
    public function getHistory($limit = 50, $offset = 0) {
        $userId = $this->userSession->getUser()->getUID();
        
        $history = $this->historyMapper->findByUserId($userId, $limit, $offset);
        $stats = $this->historyMapper->getCompletionStats($userId, 30);
        
        return new JSONResponse([
            'status' => 'success',
            'data' => [
                'history' => array_map(function($entry) {
                    return [
                        'id' => $entry->getId(),
                        'task_id' => $entry->getTaskId(),
                        'task_title' => $entry->getTaskTitle(),
                        'xp_earned' => $entry->getXpEarned(),
                        'completed_at' => $entry->getCompletedAt()
                    ];
                }, $history),
                'stats' => $stats
            ]
        ]);
    }
    
    /**
     * Get leaderboard
     * 
     * @NoAdminRequired
     * @param string $orderBy
     * @param int $limit
     * @param int $offset
     * @return JSONResponse
     */
    public function getLeaderboard($orderBy = 'lifetime_xp', $limit = 10, $offset = 0) {
        $userId = $this->userSession->getUser()->getUID();
        
        $leaderboard = $this->questMapper->getLeaderboard($limit, $offset, $orderBy);
        $userRank = $this->questMapper->getUserRank($userId, $orderBy);
        
        $leaderboardData = array_map(function($quest) {
            return [
                'user_id' => $quest->getUserId(),
                'level' => $quest->getLevel(),
                'rank_title' => $this->xpService->getRankTitle($quest->getLevel()),
                'lifetime_xp' => $quest->getLifetimeXp(),
                'current_streak' => $quest->getCurrentStreak(),
                'longest_streak' => $quest->getLongestStreak()
            ];
        }, $leaderboard);
        
        return new JSONResponse([
            'status' => 'success',
            'data' => [
                'leaderboard' => $leaderboardData,
                'user_rank' => $userRank,
                'total_users' => count($this->questMapper->findAll())
            ]
        ]);
    }
    
    /**
     * Record XP earned in ncquest_history table for daily tracking
     */
    private function recordXPHistory(string $userId, string $taskId, int $xpEarned): void {
        error_log("Quest: recordXPHistory called - User: $userId, Task: $taskId, XP: $xpEarned");
        try {
            $db = \OC::$server->getDatabaseConnection();
            $qb = $db->getQueryBuilder();
            
            $qb->insert('ncquest_history')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'task_id' => $qb->createNamedParameter($taskId),
                    'task_title' => $qb->createNamedParameter('Manual Task'),
                    'xp_earned' => $qb->createNamedParameter($xpEarned, \PDO::PARAM_INT),
                    'completed_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'))
                ]);
            
            $result = $qb->executeStatement();
            error_log("Quest: XP history insert successful - Affected rows: $result");
        } catch (\Exception $e) {
            error_log('Quest: Error recording XP history: ' . $e->getMessage());
            error_log('Quest: Stack trace: ' . $e->getTraceAsString());
        }
    }
}