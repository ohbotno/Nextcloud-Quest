<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCA\NextcloudQuest\Db\Quest;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class StreakService {
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
     * Update streak for a user after task completion
     * 
     * @param string $userId
     * @return array Streak information
     */
    public function updateStreak(string $userId): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (DoesNotExistException $e) {
            // Create new quest profile
            $quest = new Quest();
            $quest->setUserId($userId);
            $quest->setCreatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        }
        
        $now = new \DateTime();
        $today = clone $now;
        $today->setTime(0, 0, 0);
        
        $lastCompletionDate = $quest->getLastCompletionDate();
        $previousStreak = $quest->getCurrentStreak();
        $streakBroken = false;
        $newStreak = 1;
        
        if ($lastCompletionDate) {
            $lastCompletion = new \DateTime($lastCompletionDate);
            $lastCompletionDay = clone $lastCompletion;
            $lastCompletionDay->setTime(0, 0, 0);
            
            // Calculate days between last completion and today
            $interval = $today->diff($lastCompletionDay);
            $daysDiff = $interval->days;
            
            if ($daysDiff === 0) {
                // Same day - maintain streak
                $newStreak = $previousStreak;
            } elseif ($daysDiff === 1) {
                // Next day - increment streak
                $newStreak = $previousStreak + 1;
            } else {
                // Streak broken - check for grace period
                if ($this->isWithinGracePeriod($lastCompletion, $now)) {
                    // Within grace period - maintain streak
                    $newStreak = $previousStreak;
                } else {
                    // Streak broken
                    $streakBroken = true;
                    $newStreak = 1;
                }
            }
        }
        
        // Update quest data
        $quest->setCurrentStreak($newStreak);
        $quest->setLastCompletionDate($now->format('Y-m-d H:i:s'));
        
        // Update longest streak if necessary
        if ($newStreak > $quest->getLongestStreak()) {
            $quest->setLongestStreak($newStreak);
        }
        
        $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $this->questMapper->insertOrUpdate($quest);
        
        $this->logger->info('Streak updated', [
            'user' => $userId,
            'previous_streak' => $previousStreak,
            'new_streak' => $newStreak,
            'streak_broken' => $streakBroken
        ]);
        
        return [
            'current_streak' => $newStreak,
            'longest_streak' => $quest->getLongestStreak(),
            'streak_broken' => $streakBroken,
            'previous_streak' => $previousStreak
        ];
    }
    
    /**
     * Check if user is within grace period for maintaining streak
     * Grace period: until midnight of the next day
     * 
     * @param \DateTime $lastCompletion
     * @param \DateTime $now
     * @return bool
     */
    private function isWithinGracePeriod(\DateTime $lastCompletion, \DateTime $now): bool {
        // Calculate midnight of the day after last completion
        $gracePeriodEnd = clone $lastCompletion;
        $gracePeriodEnd->modify('+1 day');
        $gracePeriodEnd->setTime(23, 59, 59);
        
        return $now <= $gracePeriodEnd;
    }
    
    /**
     * Check and fix broken streaks (maintenance task)
     * This can be run periodically to check for users who haven't completed tasks
     * 
     * @return int Number of streaks reset
     */
    public function checkBrokenStreaks(): int {
        $resetCount = 0;
        $now = new \DateTime();
        
        // Get all users with active streaks
        $quests = $this->questMapper->findAll();
        
        foreach ($quests as $quest) {
            if ($quest->getCurrentStreak() === 0) {
                continue;
            }
            
            $lastCompletionDate = $quest->getLastCompletionDate();
            if (!$lastCompletionDate) {
                continue;
            }
            
            $lastCompletion = new \DateTime($lastCompletionDate);
            
            // Check if streak should be broken
            if (!$this->isWithinGracePeriod($lastCompletion, $now)) {
                // Check if user has completed any task today
                $hasCompletedToday = $this->historyMapper->hasCompletionOnDate(
                    $quest->getUserId(),
                    $now
                );
                
                if (!$hasCompletedToday) {
                    // Reset streak
                    $quest->setCurrentStreak(0);
                    $quest->setUpdatedAt($now->format('Y-m-d H:i:s'));
                    $this->questMapper->update($quest);
                    $resetCount++;
                    
                    $this->logger->info('Streak reset due to inactivity', [
                        'user' => $quest->getUserId(),
                        'last_completion' => $lastCompletionDate
                    ]);
                }
            }
        }
        
        return $resetCount;
    }
    
    /**
     * Get streak statistics for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getStreakStats(string $userId): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (DoesNotExistException $e) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_completion' => null,
                'is_active_today' => false,
                'grace_period_ends' => null
            ];
        }
        
        $now = new \DateTime();
        $today = clone $now;
        $today->setTime(0, 0, 0);
        
        $isActiveToday = false;
        $gracePeriodEnds = null;
        
        if ($quest->getLastCompletionDate()) {
            $lastCompletion = new \DateTime($quest->getLastCompletionDate());
            
            // Check if completed today
            $lastCompletionDay = clone $lastCompletion;
            $lastCompletionDay->setTime(0, 0, 0);
            $isActiveToday = $lastCompletionDay->format('Y-m-d') === $today->format('Y-m-d');
            
            // Calculate grace period end
            if (!$isActiveToday && $quest->getCurrentStreak() > 0) {
                $gracePeriodEnds = clone $lastCompletion;
                $gracePeriodEnds->modify('+1 day');
                $gracePeriodEnds->setTime(23, 59, 59);
            }
        }
        
        return [
            'current_streak' => $quest->getCurrentStreak(),
            'longest_streak' => $quest->getLongestStreak(),
            'last_completion' => $quest->getLastCompletionDate(),
            'is_active_today' => $isActiveToday,
            'grace_period_ends' => $gracePeriodEnds ? $gracePeriodEnds->format('Y-m-d H:i:s') : null
        ];
    }
    
    /**
     * Get users with expiring streaks (for notifications)
     * 
     * @param int $hoursBeforeExpiry
     * @return array
     */
    public function getUsersWithExpiringStreaks(int $hoursBeforeExpiry = 4): array {
        $expiringUsers = [];
        $now = new \DateTime();
        
        $quests = $this->questMapper->findAll();
        
        foreach ($quests as $quest) {
            if ($quest->getCurrentStreak() === 0) {
                continue;
            }
            
            $lastCompletionDate = $quest->getLastCompletionDate();
            if (!$lastCompletionDate) {
                continue;
            }
            
            $lastCompletion = new \DateTime($lastCompletionDate);
            $gracePeriodEnd = clone $lastCompletion;
            $gracePeriodEnd->modify('+1 day');
            $gracePeriodEnd->setTime(23, 59, 59);
            
            // Check if within warning period
            $warningTime = clone $gracePeriodEnd;
            $warningTime->modify('-' . $hoursBeforeExpiry . ' hours');
            
            if ($now >= $warningTime && $now < $gracePeriodEnd) {
                // Check if user hasn't completed task today
                $hasCompletedToday = $this->historyMapper->hasCompletionOnDate(
                    $quest->getUserId(),
                    $now
                );
                
                if (!$hasCompletedToday) {
                    $expiringUsers[] = [
                        'user_id' => $quest->getUserId(),
                        'current_streak' => $quest->getCurrentStreak(),
                        'expires_at' => $gracePeriodEnd->format('Y-m-d H:i:s')
                    ];
                }
            }
        }
        
        return $expiringUsers;
    }
}