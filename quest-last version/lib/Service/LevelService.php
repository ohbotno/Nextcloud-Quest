<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCA\NextcloudQuest\Db\Quest;
use OCA\NextcloudQuest\Db\QuestMapper;
use Psr\Log\LoggerInterface;

class LevelService {
    /** @var QuestMapper */
    private $questMapper;
    /** @var XPService */
    private $xpService;
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(
        QuestMapper $questMapper,
        XPService $xpService,
        LoggerInterface $logger
    ) {
        $this->questMapper = $questMapper;
        $this->xpService = $xpService;
        $this->logger = $logger;
    }
    
    /**
     * Get level information for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getLevelInfo(string $userId): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (\Exception $e) {
            // Return default values for new user
            return [
                'level' => 1,
                'rank_title' => $this->xpService->getRankTitle(1),
                'current_xp' => 0,
                'lifetime_xp' => 0,
                'xp_for_next_level' => $this->xpService->getXPForNextLevel(1),
                'xp_progress' => 0,
                'xp_to_next_level' => $this->xpService->getXPForNextLevel(1)
            ];
        }
        
        $level = $quest->getLevel();
        $currentXP = $quest->getCurrentXp();
        $lifetimeXP = $quest->getLifetimeXp();
        $xpForNextLevel = $this->xpService->getXPForNextLevel($level);
        $xpProgress = $this->xpService->getProgressToNextLevel($quest);
        
        return [
            'level' => $level,
            'rank_title' => $this->xpService->getRankTitle($level),
            'current_xp' => $currentXP,
            'lifetime_xp' => $lifetimeXP,
            'xp_for_next_level' => $xpForNextLevel,
            'xp_progress' => $xpProgress,
            'xp_to_next_level' => $xpForNextLevel - $currentXP
        ];
    }
    
    /**
     * Get upcoming levels preview
     * 
     * @param int $currentLevel
     * @param int $levelsToShow
     * @return array
     */
    public function getUpcomingLevels(int $currentLevel, int $levelsToShow = 5): array {
        $levels = [];
        
        for ($i = 1; $i <= $levelsToShow; $i++) {
            $level = $currentLevel + $i;
            $levels[] = [
                'level' => $level,
                'rank_title' => $this->xpService->getRankTitle($level),
                'xp_required' => $this->xpService->getXPForLevel($level),
                'xp_from_current' => $this->xpService->getXPForLevel($level) - $this->xpService->getXPForLevel($currentLevel)
            ];
        }
        
        return $levels;
    }
    
    /**
     * Get level milestones
     * 
     * @return array
     */
    public function getLevelMilestones(): array {
        return [
            [
                'level' => 5,
                'rank' => 'Rising Star',
                'reward' => 'Unlock custom themes',
                'xp_required' => $this->xpService->getXPForLevel(5)
            ],
            [
                'level' => 10,
                'rank' => 'Quest Apprentice',
                'reward' => 'Access to statistics dashboard',
                'xp_required' => $this->xpService->getXPForLevel(10)
            ],
            [
                'level' => 25,
                'rank' => 'Productivity Knight',
                'reward' => 'Unlock advanced achievements',
                'xp_required' => $this->xpService->getXPForLevel(25)
            ],
            [
                'level' => 50,
                'rank' => 'Master Achiever',
                'reward' => 'Custom avatar options',
                'xp_required' => $this->xpService->getXPForLevel(50)
            ],
            [
                'level' => 100,
                'rank' => 'Legendary Quest Master',
                'reward' => 'Exclusive legendary badge',
                'xp_required' => $this->xpService->getXPForLevel(100)
            ]
        ];
    }
    
    /**
     * Calculate estimated time to reach a level
     * 
     * @param string $userId
     * @param int $targetLevel
     * @return array
     */
    public function estimateTimeToLevel(string $userId, int $targetLevel): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (\Exception $e) {
            return [
                'days' => null,
                'tasks' => null,
                'message' => 'Not enough data to estimate'
            ];
        }
        
        // Get user's completion rate from last 30 days
        $stats = $this->historyMapper->getCompletionStats($userId, 30);
        
        if ($stats['total_tasks'] === 0) {
            return [
                'days' => null,
                'tasks' => null,
                'message' => 'Complete some tasks to see estimates'
            ];
        }
        
        $averageTasksPerDay = $stats['average_per_day'];
        $averageXPPerTask = $stats['total_xp'] / $stats['total_tasks'];
        
        $currentLevel = $quest->getLevel();
        $currentLifetimeXP = $quest->getLifetimeXp();
        $targetXP = $this->xpService->getXPForLevel($targetLevel);
        $xpNeeded = $targetXP - $currentLifetimeXP;
        
        if ($xpNeeded <= 0) {
            return [
                'days' => 0,
                'tasks' => 0,
                'message' => 'Already at or above target level'
            ];
        }
        
        $tasksNeeded = ceil($xpNeeded / $averageXPPerTask);
        $daysNeeded = ceil($tasksNeeded / $averageTasksPerDay);
        
        return [
            'days' => $daysNeeded,
            'tasks' => $tasksNeeded,
            'xp_needed' => $xpNeeded,
            'average_xp_per_task' => round($averageXPPerTask, 1),
            'average_tasks_per_day' => round($averageTasksPerDay, 1),
            'message' => sprintf(
                'At your current pace, you\'ll reach level %d in approximately %d days',
                $targetLevel,
                $daysNeeded
            )
        ];
    }
    
    /**
     * Get level distribution across all users
     * 
     * @return array
     */
    public function getLevelDistribution(): array {
        $allQuests = $this->questMapper->findAll();
        $distribution = [];
        
        // Group by level ranges
        $ranges = [
            '1-5' => 0,
            '6-10' => 0,
            '11-25' => 0,
            '26-50' => 0,
            '51-100' => 0,
            '100+' => 0
        ];
        
        foreach ($allQuests as $quest) {
            $level = $quest->getLevel();
            
            if ($level <= 5) {
                $ranges['1-5']++;
            } elseif ($level <= 10) {
                $ranges['6-10']++;
            } elseif ($level <= 25) {
                $ranges['11-25']++;
            } elseif ($level <= 50) {
                $ranges['26-50']++;
            } elseif ($level <= 100) {
                $ranges['51-100']++;
            } else {
                $ranges['100+']++;
            }
        }
        
        $total = count($allQuests);
        
        foreach ($ranges as $range => $count) {
            $distribution[] = [
                'range' => $range,
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0
            ];
        }
        
        return $distribution;
    }
}