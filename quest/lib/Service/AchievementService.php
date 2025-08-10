<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCA\NextcloudQuest\Db\Achievement;
use OCA\NextcloudQuest\Db\AchievementMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\Quest;
use Psr\Log\LoggerInterface;
use OCP\Notification\IManager as INotificationManager;

class AchievementService {
    /** @var AchievementMapper */
    private $achievementMapper;
    /** @var HistoryMapper */
    private $historyMapper;
    /** @var INotificationManager */
    private $notificationManager;
    /** @var LoggerInterface */
    private $logger;
    
    // Achievement definitions with categories and rarity levels
    private const ACHIEVEMENTS = [
        // Task Master Category - Common to Legendary
        'first_task' => [
            'name' => 'First Step',
            'description' => 'Complete your first task',
            'icon' => 'first-step.svg',
            'category' => 'Task Master',
            'rarity' => 'Common',
            'progress_type' => 'milestone',
            'milestone' => 1
        ],
        'tasks_10' => [
            'name' => 'Task Initiator',
            'description' => 'Complete 10 tasks',
            'icon' => 'tasks-10.svg',
            'category' => 'Task Master',
            'rarity' => 'Common',
            'progress_type' => 'milestone',
            'milestone' => 10
        ],
        'tasks_50' => [
            'name' => 'Task Apprentice',
            'description' => 'Complete 50 tasks',
            'icon' => 'tasks-50.svg',
            'category' => 'Task Master',
            'rarity' => 'Common',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],
        'tasks_100' => [
            'name' => 'Productivity Pro',
            'description' => 'Complete 100 tasks',
            'icon' => 'tasks-100.svg',
            'category' => 'Task Master',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 100
        ],
        'tasks_250' => [
            'name' => 'Task Virtuoso',
            'description' => 'Complete 250 tasks',
            'icon' => 'tasks-250.svg',
            'category' => 'Task Master',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 250
        ],
        'tasks_500' => [
            'name' => 'Task Champion',
            'description' => 'Complete 500 tasks',
            'icon' => 'tasks-500.svg',
            'category' => 'Task Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 500
        ],
        'tasks_1000' => [
            'name' => 'Task Legend',
            'description' => 'Complete 1000 tasks',
            'icon' => 'tasks-1000.svg',
            'category' => 'Task Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 1000
        ],
        'tasks_2500' => [
            'name' => 'Task Overlord',
            'description' => 'Complete 2500 tasks',
            'icon' => 'tasks-2500.svg',
            'category' => 'Task Master',
            'rarity' => 'Legendary',
            'progress_type' => 'milestone',
            'milestone' => 2500
        ],
        'tasks_5000' => [
            'name' => 'Task Deity',
            'description' => 'Complete 5000 tasks - The ultimate achievement!',
            'icon' => 'tasks-5000.svg',
            'category' => 'Task Master',
            'rarity' => 'Legendary',
            'progress_type' => 'milestone',
            'milestone' => 5000
        ],

        // Streak Keeper Category
        'streak_3' => [
            'name' => 'Streak Starter',
            'description' => 'Maintain a 3-day streak',
            'icon' => 'streak-3.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Common',
            'progress_type' => 'streak',
            'milestone' => 3
        ],
        'streak_7' => [
            'name' => 'Week Warrior',
            'description' => 'Maintain a 7-day streak',
            'icon' => 'streak-7.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Common',
            'progress_type' => 'streak',
            'milestone' => 7
        ],
        'streak_14' => [
            'name' => 'Fortnight Fighter',
            'description' => 'Maintain a 14-day streak',
            'icon' => 'streak-14.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Rare',
            'progress_type' => 'streak',
            'milestone' => 14
        ],
        'streak_30' => [
            'name' => 'Monthly Master',
            'description' => 'Maintain a 30-day streak',
            'icon' => 'streak-30.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Rare',
            'progress_type' => 'streak',
            'milestone' => 30
        ],
        'streak_60' => [
            'name' => 'Consistency Champion',
            'description' => 'Maintain a 60-day streak',
            'icon' => 'streak-60.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Epic',
            'progress_type' => 'streak',
            'milestone' => 60
        ],
        'streak_100' => [
            'name' => 'Century Champion',
            'description' => 'Maintain a 100-day streak',
            'icon' => 'streak-100.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Epic',
            'progress_type' => 'streak',
            'milestone' => 100
        ],
        'streak_365' => [
            'name' => 'Year-long Devotee',
            'description' => 'Maintain a full year streak - incredible dedication!',
            'icon' => 'streak-365.svg',
            'category' => 'Streak Keeper',
            'rarity' => 'Legendary',
            'progress_type' => 'streak',
            'milestone' => 365
        ],

        // Level Champion Category
        'level_5' => [
            'name' => 'Rising Star',
            'description' => 'Reach level 5',
            'icon' => 'level-5.svg',
            'category' => 'Level Champion',
            'rarity' => 'Common',
            'progress_type' => 'level',
            'milestone' => 5
        ],
        'level_10' => [
            'name' => 'Dedicated Achiever',
            'description' => 'Reach level 10',
            'icon' => 'level-10.svg',
            'category' => 'Level Champion',
            'rarity' => 'Common',
            'progress_type' => 'level',
            'milestone' => 10
        ],
        'level_25' => [
            'name' => 'Quest Expert',
            'description' => 'Reach level 25',
            'icon' => 'level-25.svg',
            'category' => 'Level Champion',
            'rarity' => 'Rare',
            'progress_type' => 'level',
            'milestone' => 25
        ],
        'level_50' => [
            'name' => 'Master Quester',
            'description' => 'Reach level 50',
            'icon' => 'level-50.svg',
            'category' => 'Level Champion',
            'rarity' => 'Epic',
            'progress_type' => 'level',
            'milestone' => 50
        ],
        'level_75' => [
            'name' => 'Elite Adventurer',
            'description' => 'Reach level 75',
            'icon' => 'level-75.svg',
            'category' => 'Level Champion',
            'rarity' => 'Epic',
            'progress_type' => 'level',
            'milestone' => 75
        ],
        'level_100' => [
            'name' => 'Legendary Hero',
            'description' => 'Reach level 100 - The pinnacle of achievement!',
            'icon' => 'level-100.svg',
            'category' => 'Level Champion',
            'rarity' => 'Legendary',
            'progress_type' => 'level',
            'milestone' => 100
        ],

        // Speed Demon Category
        'speed_3_in_hour' => [
            'name' => 'Quick Starter',
            'description' => 'Complete 3 tasks in one hour',
            'icon' => 'speed-3.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'speed_5_in_hour' => [
            'name' => 'Speed Demon',
            'description' => 'Complete 5 tasks in one hour',
            'icon' => 'speed-5.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'speed_10_in_hour' => [
            'name' => 'Lightning Fast',
            'description' => 'Complete 10 tasks in one hour',
            'icon' => 'speed-10.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'speed_15_in_hour' => [
            'name' => 'Task Hurricane',
            'description' => 'Complete 15 tasks in one hour - Incredible speed!',
            'icon' => 'speed-15.svg',
            'category' => 'Speed Demon',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // Consistency Master Category
        'perfect_day' => [
            'name' => 'Perfect Day',
            'description' => 'Complete all tasks in a day',
            'icon' => 'perfect-day.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'perfect_week' => [
            'name' => 'Perfect Week',
            'description' => 'Complete all tasks every day for a week',
            'icon' => 'perfect-week.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'daily_dozen' => [
            'name' => 'Daily Dozen',
            'description' => 'Complete 12 or more tasks in a single day',
            'icon' => 'daily-dozen.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'weekly_warrior' => [
            'name' => 'Weekly Warrior',
            'description' => 'Complete tasks every day for 7 consecutive days',
            'icon' => 'weekly-warrior.svg',
            'category' => 'Consistency Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],

        // Time Master Category
        'early_bird' => [
            'name' => 'Early Bird',
            'description' => 'Complete a task before 9 AM',
            'icon' => 'early-bird.svg',
            'category' => 'Time Master',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'dawn_raider' => [
            'name' => 'Dawn Raider',
            'description' => 'Complete a task before 6 AM',
            'icon' => 'dawn-raider.svg',
            'category' => 'Time Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'night_owl' => [
            'name' => 'Night Owl',
            'description' => 'Complete a task after 9 PM',
            'icon' => 'night-owl.svg',
            'category' => 'Time Master',
            'rarity' => 'Common',
            'progress_type' => 'special'
        ],
        'midnight_warrior' => [
            'name' => 'Midnight Warrior',
            'description' => 'Complete a task after midnight',
            'icon' => 'midnight-warrior.svg',
            'category' => 'Time Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'weekend_warrior' => [
            'name' => 'Weekend Warrior',
            'description' => 'Complete tasks on Saturday and Sunday',
            'icon' => 'weekend-warrior.svg',
            'category' => 'Time Master',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],

        // Special Achievements Category
        'holiday_hero' => [
            'name' => 'Holiday Hero',
            'description' => 'Complete tasks on a major holiday',
            'icon' => 'holiday-hero.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'birthday_bonus' => [
            'name' => 'Birthday Bonus',
            'description' => 'Complete tasks on your birthday',
            'icon' => 'birthday-bonus.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Epic',
            'progress_type' => 'special'
        ],
        'new_year_resolution' => [
            'name' => 'New Year Resolution',
            'description' => 'Complete a task on January 1st',
            'icon' => 'new-year.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Rare',
            'progress_type' => 'special'
        ],
        'leap_day_legend' => [
            'name' => 'Leap Day Legend',
            'description' => 'Complete a task on February 29th',
            'icon' => 'leap-day.svg',
            'category' => 'Special Achievements',
            'rarity' => 'Legendary',
            'progress_type' => 'special'
        ],

        // Priority Master Category
        'priority_perfectionist' => [
            'name' => 'Priority Perfectionist',
            'description' => 'Complete 50 high-priority tasks',
            'icon' => 'priority-perfect.svg',
            'category' => 'Priority Master',
            'rarity' => 'Rare',
            'progress_type' => 'milestone',
            'milestone' => 50
        ],
        'urgent_expert' => [
            'name' => 'Urgent Expert',
            'description' => 'Complete 25 urgent tasks within their due date',
            'icon' => 'urgent-expert.svg',
            'category' => 'Priority Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 25
        ],
        'deadline_destroyer' => [
            'name' => 'Deadline Destroyer',
            'description' => 'Complete 100 tasks before their due date',
            'icon' => 'deadline-destroyer.svg',
            'category' => 'Priority Master',
            'rarity' => 'Epic',
            'progress_type' => 'milestone',
            'milestone' => 100
        ]
    ];
    
    public function __construct(
        AchievementMapper $achievementMapper,
        HistoryMapper $historyMapper,
        INotificationManager $notificationManager,
        LoggerInterface $logger
    ) {
        $this->achievementMapper = $achievementMapper;
        $this->historyMapper = $historyMapper;
        $this->notificationManager = $notificationManager;
        $this->logger = $logger;
    }
    
    /**
     * Check and unlock achievements after task completion
     * 
     * @param string $userId
     * @param Quest $quest
     * @param \DateTime $completionTime
     * @return array Newly unlocked achievements
     */
    public function checkAchievements(string $userId, Quest $quest, \DateTime $completionTime): array {
        $unlockedAchievements = [];
        
        // Check task count achievements
        $stats = $this->historyMapper->getCompletionStats($userId);
        $totalTasks = $stats['total_tasks'];
        
        if ($totalTasks === 1) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'first_task');
        }
        if ($totalTasks === 10) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'tasks_10');
        }
        if ($totalTasks === 100) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'tasks_100');
        }
        if ($totalTasks === 1000) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'tasks_1000');
        }
        
        // Check streak achievements
        $currentStreak = $quest->getCurrentStreak();
        if ($currentStreak === 7) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'streak_7');
        }
        if ($currentStreak === 30) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'streak_30');
        }
        if ($currentStreak === 100) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'streak_100');
        }
        
        // Check level achievements
        $level = $quest->getLevel();
        if ($level === 5) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'level_5');
        }
        if ($level === 10) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'level_10');
        }
        if ($level === 25) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'level_25');
        }
        if ($level === 50) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'level_50');
        }
        if ($level === 100) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'level_100');
        }
        
        // Check time-based achievements
        $hour = (int)$completionTime->format('H');
        if ($hour < 9) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'early_bird');
        }
        if ($hour >= 21) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'night_owl');
        }
        
        // Check weekend warrior
        $dayOfWeek = (int)$completionTime->format('w');
        if ($dayOfWeek === 0 || $dayOfWeek === 6) {
            // Check if completed tasks on both weekend days
            $startOfWeek = clone $completionTime;
            $startOfWeek->modify('last monday');
            $endOfWeek = clone $startOfWeek;
            $endOfWeek->modify('+6 days');
            
            $weekHistory = $this->historyMapper->findByDateRange($userId, $startOfWeek, $endOfWeek);
            $saturdayCompleted = false;
            $sundayCompleted = false;
            
            foreach ($weekHistory as $entry) {
                $entryDate = new \DateTime($entry->getCompletedAt());
                $entryDay = (int)$entryDate->format('w');
                if ($entryDay === 6) $saturdayCompleted = true;
                if ($entryDay === 0) $sundayCompleted = true;
            }
            
            if ($saturdayCompleted && $sundayCompleted) {
                $unlockedAchievements[] = $this->unlockAchievement($userId, 'weekend_warrior');
            }
        }
        
        // Check speed demon (5 tasks in one hour)
        $oneHourAgo = clone $completionTime;
        $oneHourAgo->modify('-1 hour');
        $recentHistory = $this->historyMapper->findByDateRange($userId, $oneHourAgo, $completionTime);
        if (count($recentHistory) >= 5) {
            $unlockedAchievements[] = $this->unlockAchievement($userId, 'speed_demon');
        }
        
        // Filter out already unlocked achievements
        return array_filter($unlockedAchievements);
    }
    
    /**
     * Unlock an achievement for a user
     * 
     * @param string $userId
     * @param string $achievementKey
     * @return Achievement|null
     */
    private function unlockAchievement(string $userId, string $achievementKey): ?Achievement {
        // Check if already unlocked
        if ($this->achievementMapper->hasAchievement($userId, $achievementKey)) {
            return null;
        }
        
        // Create new achievement
        $achievement = new Achievement();
        $achievement->setUserId($userId);
        $achievement->setAchievementKey($achievementKey);
        $achievement->setUnlockedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $achievement->setNotified(0);
        
        $achievement = $this->achievementMapper->insert($achievement);
        
        // Send notification
        $this->sendAchievementNotification($userId, $achievementKey);
        
        $this->logger->info('Achievement unlocked', [
            'user' => $userId,
            'achievement' => $achievementKey
        ]);
        
        return $achievement;
    }
    
    /**
     * Send notification for unlocked achievement
     * 
     * @param string $userId
     * @param string $achievementKey
     */
    private function sendAchievementNotification(string $userId, string $achievementKey): void {
        $achievementData = self::ACHIEVEMENTS[$achievementKey] ?? null;
        if (!$achievementData) {
            return;
        }
        
        $notification = $this->notificationManager->createNotification();
        $notification->setApp('nextcloudquest')
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setObject('achievement', $achievementKey)
            ->setSubject('achievement_unlocked', [
                'achievement' => $achievementData['name']
            ])
            ->setMessage('achievement_unlocked_message', [
                'achievement' => $achievementData['name'],
                'description' => $achievementData['description']
            ])
            ->setIcon('achievement');
        
        $this->notificationManager->notify($notification);
    }
    
    /**
     * Get all achievements with unlock status for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getAllAchievements(string $userId): array {
        $unlockedAchievements = $this->achievementMapper->findAllByUserId($userId);
        $unlockedKeys = array_map(fn($a) => $a->getAchievementKey(), $unlockedAchievements);
        
        $achievements = [];
        foreach (self::ACHIEVEMENTS as $key => $data) {
            $isUnlocked = in_array($key, $unlockedKeys);
            $unlockedAt = null;
            
            if ($isUnlocked) {
                foreach ($unlockedAchievements as $achievement) {
                    if ($achievement->getAchievementKey() === $key) {
                        $unlockedAt = $achievement->getUnlockedAt();
                        break;
                    }
                }
            }
            
            $achievements[] = [
                'key' => $key,
                'name' => $data['name'],
                'description' => $data['description'],
                'icon' => $data['icon'],
                'category' => $data['category'],
                'rarity' => $data['rarity'],
                'progress_type' => $data['progress_type'],
                'milestone' => $data['milestone'] ?? null,
                'unlocked' => $isUnlocked,
                'unlocked_at' => $unlockedAt
            ];
        }
        
        return $achievements;
    }

    /**
     * Get achievements grouped by category
     * 
     * @param string $userId
     * @return array
     */
    public function getAchievementsByCategory(string $userId): array {
        $achievements = $this->getAllAchievements($userId);
        $categories = [];
        
        foreach ($achievements as $achievement) {
            $category = $achievement['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [
                    'name' => $category,
                    'achievements' => [],
                    'total' => 0,
                    'unlocked' => 0,
                    'percentage' => 0
                ];
            }
            
            $categories[$category]['achievements'][] = $achievement;
            $categories[$category]['total']++;
            if ($achievement['unlocked']) {
                $categories[$category]['unlocked']++;
            }
        }
        
        // Calculate percentages
        foreach ($categories as &$category) {
            $category['percentage'] = $category['total'] > 0 
                ? round(($category['unlocked'] / $category['total']) * 100, 1) 
                : 0;
        }
        
        return $categories;
    }

    /**
     * Get achievement progress for milestone-based achievements
     * 
     * @param string $userId
     * @param string $achievementKey
     * @return array|null
     */
    public function getAchievementProgress(string $userId, string $achievementKey): ?array {
        if (!isset(self::ACHIEVEMENTS[$achievementKey])) {
            return null;
        }
        
        $achievement = self::ACHIEVEMENTS[$achievementKey];
        
        // Only calculate progress for milestone-based achievements
        if ($achievement['progress_type'] !== 'milestone') {
            return null;
        }
        
        $currentValue = 0;
        $milestone = $achievement['milestone'];
        
        switch ($achievementKey) {
            // Task count achievements
            case (strpos($achievementKey, 'tasks_') === 0):
                $stats = $this->historyMapper->getCompletionStats($userId);
                $currentValue = $stats['total_tasks'];
                break;
            
            // Streak achievements
            case (strpos($achievementKey, 'streak_') === 0):
                // Get current streak from user's quest data
                // This would need to be implemented based on your streak tracking
                $currentValue = 0; // Placeholder
                break;
            
            // Level achievements
            case (strpos($achievementKey, 'level_') === 0):
                // Get current level from user's quest data
                // This would need to be implemented based on your level system
                $currentValue = 0; // Placeholder
                break;
        }
        
        return [
            'current' => $currentValue,
            'target' => $milestone,
            'percentage' => min(100, round(($currentValue / $milestone) * 100, 1))
        ];
    }

    /**
     * Get achievements by rarity level
     * 
     * @param string $userId
     * @param string $rarity
     * @return array
     */
    public function getAchievementsByRarity(string $userId, string $rarity): array {
        $achievements = $this->getAllAchievements($userId);
        return array_filter($achievements, fn($a) => $a['rarity'] === $rarity);
    }

    /**
     * Get recent achievements for a user
     * 
     * @param string $userId
     * @param int $limit
     * @return array
     */
    public function getRecentAchievements(string $userId, int $limit = 10): array {
        $achievements = $this->achievementMapper->findRecentByUserId($userId, $limit);
        $result = [];
        
        foreach ($achievements as $achievement) {
            $key = $achievement->getAchievementKey();
            if (isset(self::ACHIEVEMENTS[$key])) {
                $data = self::ACHIEVEMENTS[$key];
                $result[] = [
                    'key' => $key,
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'icon' => $data['icon'],
                    'category' => $data['category'],
                    'rarity' => $data['rarity'],
                    'unlocked_at' => $achievement->getUnlockedAt()
                ];
            }
        }
        
        return $result;
    }
    
    /**
     * Get achievement statistics for a user
     * 
     * @param string $userId
     * @return array
     */
    public function getAchievementStats(string $userId): array {
        $totalAchievements = count(self::ACHIEVEMENTS);
        $unlockedCount = count($this->achievementMapper->findAllByUserId($userId));
        
        return [
            'total' => $totalAchievements,
            'unlocked' => $unlockedCount,
            'percentage' => round(($unlockedCount / $totalAchievements) * 100, 1)
        ];
    }
    
    /**
     * Mark achievements as notified
     * 
     * @param string $userId
     */
    public function markAchievementsAsNotified(string $userId): void {
        $unnotified = $this->achievementMapper->findUnnotified($userId);
        foreach ($unnotified as $achievement) {
            $this->achievementMapper->markAsNotified($achievement->getId());
        }
    }
}