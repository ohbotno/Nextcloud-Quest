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

class QuestStatsController extends Controller {
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
     * UNIFIED STATS ENDPOINT - Get all user stats in consistent format
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function getStats() {
        try {
            // Legacy table initialization removed - using ncquest_* tables only
            
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }
            $userId = $user->getUID();
            
            // Get user data from unified ncquest_users table
            $userData = $this->getUnifiedUserData($userId);
            
            // Get achievement count
            $achievementData = $this->getAchievementData($userId);
            
            // Get XP gained today from stored field
            $xpGainedToday = (int)($userData['xp_gained_today'] ?? 0);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'level' => [
                        'level' => $userData['level'],
                        'rank_title' => $this->getRankTitle($userData['level']),
                        'xp' => $userData['current_xp'], // Frontend expects this field
                        'current_xp' => $userData['current_xp'],
                        'lifetime_xp' => $userData['lifetime_xp'],
                        'xp_for_next_level' => $this->getXPForLevel($userData['level'] + 1),
                        'xp_progress' => $userData['xp_progress'],
                        'xp_to_next_level' => $userData['xp_to_next_level'],
                        'xp_gained_today' => $xpGainedToday
                    ],
                    'health' => [
                        'current_health' => $userData['current_health'],
                        'max_health' => $userData['max_health'],
                        'health_percentage' => $userData['health_percentage']
                    ],
                    'streak' => [
                        'current_streak' => $userData['current_streak'],
                        'longest_streak' => $userData['longest_streak'],
                        'last_completion' => $userData['last_completion_date'],
                        'is_active_today' => $userData['is_active_today']
                    ],
                    'tasks' => [
                        'completed_today' => $userData['tasks_completed_today'],
                        'completed_this_week' => $userData['tasks_completed_this_week'],
                        'total_completed' => $userData['total_tasks_completed']
                    ],
                    'achievements' => [
                        'total' => $achievementData['total'],
                        'unlocked' => $achievementData['unlocked'],
                        'percentage' => $achievementData['percentage']
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load stats: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current user's stats (LEGACY - use getStats() instead)
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function getUserStats() {
        try {
            
            // Legacy table initialization removed - using ncquest_* tables only
            
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
     * Get unified user data from ncquest_users table (NEW)
     */
    private function getUnifiedUserData(string $userId): array {
        try {
            // Get user data from ncquest_users table
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            
            if (!$userData) {
                // Create new user with default stats
                $this->createDefaultUser($userId);
                return $this->getDefaultUserStats();
            }
            
            // Calculate XP progress
            $level = (int)$userData['level'];
            $currentXp = (int)$userData['current_xp'];
            $lifetimeXp = (int)$userData['lifetime_xp'];
            $xpForNextLevel = $this->getXPForLevel($level + 1);
            $xpForCurrentLevel = $this->getXPForLevel($level);
            $xpToNext = $xpForNextLevel - $currentXp;
            $xpProgress = $xpForNextLevel > $xpForCurrentLevel ? 
                (($currentXp - $xpForCurrentLevel) / ($xpForNextLevel - $xpForCurrentLevel)) * 100 : 100;
            
            // Calculate health percentage
            $currentHealth = (int)($userData['current_health'] ?? 100);
            $maxHealth = (int)($userData['max_health'] ?? 100);
            $healthPercentage = $maxHealth > 0 ? ($currentHealth / $maxHealth) * 100 : 100;
            
            // Check if streak is active today
            $lastCompletion = $userData['last_completion_date'];
            $isActiveToday = false;
            if ($lastCompletion) {
                $lastDate = new \DateTime($lastCompletion);
                $today = new \DateTime();
                $isActiveToday = $lastDate->format('Y-m-d') === $today->format('Y-m-d');
            }
            
            // Reset daily/weekly counts if needed
            $this->resetDailyWeeklyCountsIfNeeded($userId, $userData);
            
            return [
                'level' => $level,
                'current_xp' => $currentXp,
                'lifetime_xp' => $lifetimeXp,
                'xp_progress' => round($xpProgress, 1),
                'xp_to_next_level' => $xpToNext,
                'current_health' => $currentHealth,
                'max_health' => $maxHealth,
                'health_percentage' => round($healthPercentage, 1),
                'current_streak' => (int)($userData['current_streak'] ?? 0),
                'longest_streak' => (int)($userData['longest_streak'] ?? 0),
                'last_completion_date' => $userData['last_completion_date'],
                'is_active_today' => $isActiveToday,
                'tasks_completed_today' => (int)($userData['tasks_completed_today'] ?? 0),
                'tasks_completed_this_week' => (int)($userData['tasks_completed_this_week'] ?? 0),
                'total_tasks_completed' => (int)($userData['total_tasks_completed'] ?? 0),
                'xp_gained_today' => (int)($userData['xp_gained_today'] ?? 0)
            ];
            
        } catch (\Exception $e) {
            return $this->getDefaultUserStats();
        }
    }
    
    /**
     * Get achievement data for user
     */
    private function getAchievementData(string $userId): array {
        try {
            // Count unlocked achievements
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->count('*', 'unlocked_count'))
                ->from('ncquest_achievements')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $unlockedCount = (int)($result->fetchOne() ?: 0);
            $result->closeCursor();
            
            // For now, assume there are 50 total achievements
            $totalAchievements = 50;
            $percentage = $totalAchievements > 0 ? round(($unlockedCount / $totalAchievements) * 100, 1) : 0;
            
            return [
                'total' => $totalAchievements,
                'unlocked' => $unlockedCount,
                'percentage' => $percentage
            ];
            
        } catch (\Exception $e) {
            return [
                'total' => 50,
                'unlocked' => 0,
                'percentage' => 0
            ];
        }
    }
    
    /**
     * Create default user in ncquest_users table
     */
    private function createDefaultUser(string $userId): void {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->insert('ncquest_users')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'current_xp' => $qb->createNamedParameter(0),
                    'lifetime_xp' => $qb->createNamedParameter(0),
                    'level' => $qb->createNamedParameter(1),
                    'current_streak' => $qb->createNamedParameter(0),
                    'longest_streak' => $qb->createNamedParameter(0),
                    'current_health' => $qb->createNamedParameter(100),
                    'max_health' => $qb->createNamedParameter(100),
                    'tasks_completed_today' => $qb->createNamedParameter(0),
                    'tasks_completed_this_week' => $qb->createNamedParameter(0),
                    'total_tasks_completed' => $qb->createNamedParameter(0),
                    'theme_preference' => $qb->createNamedParameter('game'),
                    'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
                    'updated_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'))
                ]);
            
            $qb->executeStatement();
            
        } catch (\Exception $e) {
        }
    }
    
    /**
     * Get default user stats
     */
    private function getDefaultUserStats(): array {
        return [
            'level' => 1,
            'current_xp' => 0,
            'lifetime_xp' => 0,
            'xp_progress' => 0,
            'xp_to_next_level' => 100,
            'current_health' => 100,
            'max_health' => 100,
            'health_percentage' => 100,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_completion_date' => null,
            'is_active_today' => false,
            'tasks_completed_today' => 0,
            'tasks_completed_this_week' => 0,
            'total_tasks_completed' => 0
        ];
    }
    
    /**
     * Reset daily/weekly counts if needed
     */
    private function resetDailyWeeklyCountsIfNeeded(string $userId, array $userData): void {
        try {
            $today = date('Y-m-d');
            $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
            
            $lastDailyReset = $userData['last_daily_reset'] ?? null;
            $lastWeeklyReset = $userData['last_weekly_reset'] ?? null;
            
            $needsUpdate = false;
            $updates = [];
            
            // Reset daily count if it's a new day
            if ($lastDailyReset !== $today) {
                $updates['tasks_completed_today'] = 0;
                $updates['last_daily_reset'] = $today;
                $needsUpdate = true;
            }
            
            // Reset weekly count if it's a new week
            if ($lastWeeklyReset !== $thisWeekStart) {
                $updates['tasks_completed_this_week'] = 0;
                $updates['last_weekly_reset'] = $thisWeekStart;
                $needsUpdate = true;
            }
            
            if ($needsUpdate) {
                $qb = $this->db->getQueryBuilder();
                $updateQb = $qb->update('ncquest_users')
                    ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
                
                foreach ($updates as $field => $value) {
                    $updateQb->set($field, $qb->createNamedParameter($value));
                }
                
                $updateQb->executeStatement();
            }
            
        } catch (\Exception $e) {
        }
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
            // Table might not exist yet, log it but don't crash
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
                ->andWhere($todayQb->expr()->lte('completed_at', $todayQb->createNamedParameter($todayEnd, \PDO::PARAM_STR)));
            
            $todayResult = $todayQb->execute();
            $tasksToday = (int)$todayResult->fetch()['task_count'];
            $todayResult->closeCursor();
            
            // Tasks completed this week (compare date part of timestamp)
            $weekStartDateTime = $weekStart . ' 00:00:00';
            
            $weekQb = $this->db->getQueryBuilder();
            $weekQb->select($weekQb->func()->count('*', 'task_count'))
                ->from('ncquest_history')
                ->where($weekQb->expr()->eq('user_id', $weekQb->createNamedParameter($userId)))
                ->andWhere($weekQb->expr()->gte('completed_at', $weekQb->createNamedParameter($weekStartDateTime)));
            
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
     * Get XP gained today for a user
     */
    private function getXPGainedToday(string $userId): int {
        try {
            // Get today's date range
            $today = date('Y-m-d');
            $todayStart = $today . ' 00:00:00';
            $todayEnd = $today . ' 23:59:59';
            
            
            // Sum XP gained today from ncquest_history table
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->sum('xp_earned', 'total_xp'))
                ->from('ncquest_history')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($todayStart, \PDO::PARAM_STR)))
                ->andWhere($qb->expr()->lte('completed_at', $qb->createNamedParameter($todayEnd, \PDO::PARAM_STR)));
            
            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();
            
            $xpTotal = (int)($row['total_xp'] ?? 0);
            
            return $xpTotal;
        } catch (\Exception $e) {
            error_log('Quest: Error getting XP gained today: ' . $e->getMessage());
            return 0;
        }
    }
}