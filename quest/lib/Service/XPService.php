<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCA\NextcloudQuest\Db\Quest;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Db\History;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class XPService {
    /** @var QuestMapper */
    private $questMapper;
    /** @var HistoryMapper */
    private $historyMapper;
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(
        QuestMapper $questMapper,
        HistoryMapper $historyMapper,
        LoggerInterface $logger
    ) {
        $this->questMapper = $questMapper;
        $this->historyMapper = $historyMapper;
        $this->logger = $logger;
    }
    
    /**
     * Calculate XP for a completed task
     * 
     * @param string $priority Task priority: 'high', 'medium', 'low'
     * @param int $currentStreak User's current streak
     * @return int
     */
    public function calculateXP(string $priority, int $currentStreak): int {
        // Base XP
        $baseXP = 10;
        
        // Priority bonus
        $priorityBonus = match(strtolower($priority)) {
            'high' => 10,
            'medium' => 5,
            'low' => 0,
            default => 0
        };
        
        // Streak multiplier (10% per day, max 2x)
        $streakMultiplier = min(1 + ($currentStreak * 0.1), 2.0);
        
        // Calculate total XP
        $totalXP = (int)(($baseXP + $priorityBonus) * $streakMultiplier);
        
        $this->logger->debug('XP calculation', [
            'priority' => $priority,
            'streak' => $currentStreak,
            'baseXP' => $baseXP,
            'priorityBonus' => $priorityBonus,
            'streakMultiplier' => $streakMultiplier,
            'totalXP' => $totalXP
        ]);
        
        return $totalXP;
    }
    
    /**
     * Award XP to a user for completing a task
     * 
     * @param string $userId
     * @param string $taskId
     * @param string $taskTitle
     * @param string $priority
     * @return array Result with XP awarded and level info
     */
    public function awardXP(string $userId, string $taskId, string $taskTitle, string $priority = 'medium'): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (DoesNotExistException $e) {
            // Create new quest profile for user
            $quest = new Quest();
            $quest->setUserId($userId);
            $quest->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        }
        
        // Calculate XP based on priority and streak
        $xpEarned = $this->calculateXP($priority, $quest->getCurrentStreak());
        
        // Update user's XP
        $oldLevel = $quest->getLevel();
        $quest->setCurrentXp($quest->getCurrentXp() + $xpEarned);
        $quest->setLifetimeXp($quest->getLifetimeXp() + $xpEarned);
        
        // Check for level up
        $newLevel = $this->calculateLevel($quest->getLifetimeXp());
        $leveledUp = $newLevel > $oldLevel;
        
        if ($leveledUp) {
            $quest->setLevel($newLevel);
            // Reset current XP for new level (optional: keep overflow)
            $xpForCurrentLevel = $this->getXPForLevel($newLevel);
            $quest->setCurrentXp($quest->getLifetimeXp() - $xpForCurrentLevel);
        }
        
        // Save updated quest data
        $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $this->questMapper->insertOrUpdate($quest);
        
        // Record in history
        $history = new History();
        $history->setUserId($userId);
        $history->setTaskId($taskId);
        $history->setTaskTitle($taskTitle);
        $history->setXpEarned($xpEarned);
        $history->setCompletedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $this->historyMapper->insert($history);
        
        return [
            'xp_earned' => $xpEarned,
            'current_xp' => $quest->getCurrentXp(),
            'lifetime_xp' => $quest->getLifetimeXp(),
            'level' => $quest->getLevel(),
            'leveled_up' => $leveledUp,
            'old_level' => $oldLevel,
            'next_level_xp' => $this->getXPForNextLevel($quest->getLevel()),
            'progress_to_next_level' => $this->getProgressToNextLevel($quest)
        ];
    }
    
    /**
     * Calculate level based on lifetime XP
     * 
     * @param int $lifetimeXP
     * @return int
     */
    public function calculateLevel(int $lifetimeXP): int {
        $level = 1;
        $xpRequired = 0;
        
        while ($xpRequired <= $lifetimeXP) {
            $xpRequired = $this->getXPForLevel($level + 1);
            if ($xpRequired > $lifetimeXP) {
                break;
            }
            $level++;
        }
        
        return $level;
    }
    
    /**
     * Get total XP required to reach a specific level
     * 
     * @param int $level
     * @return int
     */
    public function getXPForLevel(int $level): int {
        if ($level <= 1) {
            return 0;
        }
        
        // XP required = 100 * 1.5^(level-1)
        // This creates an exponential curve
        $totalXP = 0;
        for ($i = 1; $i < $level; $i++) {
            $totalXP += (int)(100 * pow(1.5, $i - 1));
        }
        
        return $totalXP;
    }
    
    /**
     * Get XP required for the next level
     * 
     * @param int $currentLevel
     * @return int
     */
    public function getXPForNextLevel(int $currentLevel): int {
        return (int)(100 * pow(1.5, $currentLevel - 1));
    }
    
    /**
     * Get progress percentage to next level
     * 
     * @param Quest $quest
     * @return float
     */
    public function getProgressToNextLevel(Quest $quest): float {
        $xpForNextLevel = $this->getXPForNextLevel($quest->getLevel());
        if ($xpForNextLevel === 0) {
            return 0.0;
        }
        
        return round(($quest->getCurrentXp() / $xpForNextLevel) * 100, 2);
    }
    
    /**
     * Get rank title based on level
     * 
     * @param int $level
     * @return string
     */
    public function getRankTitle(int $level): string {
        return match(true) {
            $level >= 100 => 'Legendary Quest Master',
            $level >= 75 => 'Epic Champion',
            $level >= 50 => 'Master Achiever',
            $level >= 40 => 'Expert Quester',
            $level >= 30 => 'Veteran Adventurer',
            $level >= 25 => 'Productivity Knight',
            $level >= 20 => 'Task Commander',
            $level >= 15 => 'Achievement Hunter',
            $level >= 10 => 'Quest Apprentice',
            $level >= 5 => 'Rising Star',
            $level >= 3 => 'Task Initiate',
            default => 'Task Novice'
        };
    }
}