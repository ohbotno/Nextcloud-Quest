<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Hooks;

use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Service\AchievementService;
use OCA\NextcloudQuest\Service\StreakService;
use OCP\IUserSession;
use OCP\ILogger;

class TaskCompletionHook {
    private IUserSession $userSession;
    private XPService $xpService;
    private AchievementService $achievementService;
    private StreakService $streakService;
    private ILogger $logger;
    
    public function __construct(
        IUserSession $userSession,
        XPService $xpService,
        AchievementService $achievementService,
        StreakService $streakService,
        ILogger $logger
    ) {
        $this->userSession = $userSession;
        $this->xpService = $xpService;
        $this->achievementService = $achievementService;
        $this->streakService = $streakService;
        $this->logger = $logger;
    }
    
    /**
     * Handle task completion event
     * This will be called by the event system when a task is completed
     * 
     * @param array $event Event data containing task information
     */
    public function handleTaskCompletion(array $event): void {
        try {
            $userId = $event['user_id'] ?? null;
            $taskId = $event['task_id'] ?? null;
            $taskTitle = $event['task_title'] ?? 'Unknown Task';
            $priority = $event['priority'] ?? 'medium';
            
            if (!$userId || !$taskId) {
                $this->logger->warning('Invalid task completion event', $event);
                return;
            }
            
            $this->logger->info('Processing task completion', [
                'user' => $userId,
                'task' => $taskId,
                'title' => $taskTitle,
                'priority' => $priority
            ]);
            
            // Update streak
            $streakResult = $this->streakService->updateStreak($userId);
            
            // Award XP
            $xpResult = $this->xpService->awardXP($userId, $taskId, $taskTitle, $priority);
            
            // Check for new achievements
            $quest = $this->questMapper->findByUserId($userId);
            $completionTime = new \DateTime();
            $newAchievements = $this->achievementService->checkAchievements($userId, $quest, $completionTime);
            
            $this->logger->info('Task completion processed', [
                'user' => $userId,
                'xp_earned' => $xpResult['xp_earned'],
                'new_level' => $xpResult['level'],
                'leveled_up' => $xpResult['leveled_up'],
                'streak' => $streakResult['current_streak'],
                'new_achievements_count' => count($newAchievements)
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to process task completion', [
                'error' => $e->getMessage(),
                'event' => $event
            ]);
        }
    }
    
    /**
     * Handle task creation event (future use)
     * 
     * @param array $event
     */
    public function handleTaskCreation(array $event): void {
        // Future: Award XP for creating tasks
        // Future: Track task creation statistics
    }
    
    /**
     * Handle task deletion event (future use)
     * 
     * @param array $event
     */
    public function handleTaskDeletion(array $event): void {
        // Future: Remove XP if task was completed and then deleted (optional)
        // Future: Track deletion statistics
    }
}