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
    
    public function __construct($appName, IRequest $request, IUserSession $userSession, TasksApiIntegration $tasksIntegration = null) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
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
                'data' => $achievements
            ]);
        } catch (\Exception $e) {
            // Return empty achievements list for new user
            return new JSONResponse([
                'status' => 'success',
                'data' => []
            ]);
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
                'status' => 'success',
                'data' => [
                    'total' => 0,
                    'unlocked' => 0,
                    'percentage' => 0
                ]
            ]);
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
            $eventDispatcher->dispatch(TaskCompletedEvent::class, $event);
            
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
     * Complete a task from Tasks app and award XP
     * 
     * @NoAdminRequired
     * @param string $taskId
     * @param string $listId
     * @return JSONResponse
     */
    public function completeTaskFromList($taskId, $listId) {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                throw new \Exception('User not found');
            }
            $userId = $user->getUID();
            
            if (!$this->tasksIntegration) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Tasks integration not available'
                ], 500);
            }
            
            // Get task details first
            $task = $this->tasksIntegration->getTask((int)$taskId, $userId);
            if (!$task) {
                throw new \Exception('Task not found');
            }
            
            // Mark task as completed in Tasks app
            $completed = $this->tasksIntegration->markTaskCompleted((int)$taskId, $userId);
            if (!$completed) {
                throw new \Exception('Failed to mark task as completed');
            }
            
            // Award XP based on task details (simplified version)
            $priority = $this->mapTaskPriority($task['priority'] ?? 0);
            $baseXP = 10;
            $xpEarned = $baseXP;
            
            switch ($priority) {
                case 'high':
                    $xpEarned += 10;
                    break;
                case 'medium':
                    $xpEarned += 5;
                    break;
                case 'low':
                default:
                    break;
            }
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'xp' => [
                        'xp_earned' => $xpEarned,
                        'level' => 1,
                        'leveled_up' => false
                    ],
                    'streak' => [
                        'current_streak' => 1,
                        'updated' => true
                    ],
                    'new_achievements' => []
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
}