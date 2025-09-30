<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

declare(strict_types=1);

namespace OCA\NextcloudQuest\Service;

use OCP\IDBConnection;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

/**
 * Unified User Data Service
 * 
 * Provides centralized access to user data across the dual database schema.
 * Handles migration between quest_user_data (legacy) and ncquest_users (unified) tables.
 * 
 * Key Features:
 * - Unified interface for user data operations
 * - Automatic fallback from ncquest_users to quest_user_data
 * - Migration utilities for schema consolidation
 * - Proper error handling and logging
 * - Performance optimized with caching
 */
class UserDataService {
    
    /** @var IDBConnection */
    private $db;
    
    /** @var LoggerInterface */
    private $logger;
    
    /** @var array Cache for user data */
    private $userDataCache = [];
    
    /** @var bool Flag to track if tables are initialized */
    private static $tablesInitialized = false;

    public function __construct(
        IDBConnection $db,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->logger = $logger;
    }

    // ========== PUBLIC API METHODS ==========

    /**
     * Get comprehensive user data
     * 
     * @param string $userId User ID
     * @param bool $forceRefresh Force refresh from database
     * @return array User data array
     */
    public function getUserData(string $userId, bool $forceRefresh = false): array {
        if (!$forceRefresh && isset($this->userDataCache[$userId])) {
            return $this->userDataCache[$userId];
        }

        $this->ensureTablesExist();
        
        try {
            // Try unified table first
            $userData = $this->getUserDataFromUnified($userId);
            
            if (!$userData) {
                // Fallback to legacy table and migrate
                $userData = $this->getUserDataFromLegacy($userId);
                if ($userData) {
                    $this->migrateSingleUser($userId, $userData);
                    // Re-fetch from unified table after migration
                    $userData = $this->getUserDataFromUnified($userId);
                }
            }
            
            // If still no data, create default user
            if (!$userData) {
                $userData = $this->createDefaultUser($userId);
            }
            
            // Calculate derived fields
            $userData = $this->enrichUserData($userData);
            
            // Cache the result
            $this->userDataCache[$userId] = $userData;
            
            return $userData;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get user data for ' . $userId, [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultUserStats();
        }
    }

    /**
     * Update user data (XP, level, health, etc.)
     * 
     * @param string $userId User ID
     * @param array $data Data to update
     * @return bool Success status
     */
    public function updateUserData(string $userId, array $data): bool {
        $this->ensureTablesExist();
        
        try {
            $qb = $this->db->getQueryBuilder();
            
            // Check if user exists
            $exists = $this->userExists($userId);
            
            if ($exists) {
                // Update existing user
                $updateQb = $qb->update('ncquest_users')
                    ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
                
                foreach ($data as $field => $value) {
                    if ($this->isValidUserField($field)) {
                        $updateQb->set($field, $qb->createNamedParameter($value));
                    }
                }
                
                $result = $updateQb->executeStatement();
                
                // Clear cache
                unset($this->userDataCache[$userId]);
                
                $this->logger->debug('Updated user data', [
                    'user_id' => $userId,
                    'fields' => array_keys($data),
                    'affected_rows' => $result
                ]);
                
                return $result > 0;
                
            } else {
                // Create new user with provided data
                return $this->createUser($userId, $data);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user data', [
                'user_id' => $userId,
                'data' => $data,
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Create a new user record
     * 
     * @param string $userId User ID
     * @param array $initialData Optional initial data
     * @return bool Success status
     */
    public function createUser(string $userId, array $initialData = []): bool {
        $this->ensureTablesExist();
        
        try {
            // Merge with defaults
            $userData = array_merge($this->getDefaultUserStats(), $initialData);
            
            $qb = $this->db->getQueryBuilder();
            $qb->insert('ncquest_users')
                ->values([
                    'user_id' => $qb->createNamedParameter($userId),
                    'current_xp' => $qb->createNamedParameter($userData['current_xp']),
                    'lifetime_xp' => $qb->createNamedParameter($userData['lifetime_xp']),
                    'level' => $qb->createNamedParameter($userData['level']),
                    'current_streak' => $qb->createNamedParameter($userData['current_streak']),
                    'longest_streak' => $qb->createNamedParameter($userData['longest_streak']),
                    'current_health' => $qb->createNamedParameter($userData['current_health']),
                    'max_health' => $qb->createNamedParameter($userData['max_health']),
                    'tasks_completed_today' => $qb->createNamedParameter($userData['tasks_completed_today']),
                    'tasks_completed_this_week' => $qb->createNamedParameter($userData['tasks_completed_this_week']),
                    'total_tasks_completed' => $qb->createNamedParameter($userData['total_tasks_completed']),
                    'theme_preference' => $qb->createNamedParameter($userData['theme_preference'] ?? 'game'),
                    'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
                    'updated_at' => $qb->createNamedParameter(date('Y-m-d H:i:s'))
                ]);
            
            $qb->executeStatement();
            
            // Clear cache
            unset($this->userDataCache[$userId]);
            
            $this->logger->info('Created new user', [
                'user_id' => $userId,
                'initial_data' => $initialData
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to create user', [
                'user_id' => $userId,
                'initial_data' => $initialData,
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Award XP to user and handle level ups
     * 
     * @param string $userId User ID
     * @param int $xpAmount XP to award
     * @param string $source Source of XP (e.g., 'task_completion')
     * @return array Result with level up information
     */
    public function awardXP(string $userId, int $xpAmount, string $source = 'unknown'): array {
        if ($xpAmount <= 0) {
            return ['success' => false, 'message' => 'Invalid XP amount'];
        }
        
        try {
            $userData = $this->getUserData($userId);
            $oldLevel = $userData['level'];
            $oldXP = $userData['current_xp'];
            $newLifetimeXP = $userData['lifetime_xp'] + $xpAmount;
            $newCurrentXP = $oldXP + $xpAmount;
            
            // Calculate new level
            $newLevel = $this->calculateLevelFromXP($newCurrentXP);
            $leveledUp = $newLevel > $oldLevel;
            
            // Update user data
            $updateData = [
                'current_xp' => $newCurrentXP,
                'lifetime_xp' => $newLifetimeXP,
                'level' => $newLevel
            ];
            
            $success = $this->updateUserData($userId, $updateData);
            
            if ($success) {
                $this->logger->info('Awarded XP to user', [
                    'user_id' => $userId,
                    'xp_amount' => $xpAmount,
                    'source' => $source,
                    'old_level' => $oldLevel,
                    'new_level' => $newLevel,
                    'leveled_up' => $leveledUp
                ]);
            }
            
            return [
                'success' => $success,
                'xp_awarded' => $xpAmount,
                'old_level' => $oldLevel,
                'new_level' => $newLevel,
                'leveled_up' => $leveledUp,
                'total_xp' => $newCurrentXP,
                'lifetime_xp' => $newLifetimeXP
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to award XP', [
                'user_id' => $userId,
                'xp_amount' => $xpAmount,
                'source' => $source,
                'exception' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'Failed to award XP'];
        }
    }

    /**
     * Update task completion statistics
     * 
     * @param string $userId User ID
     * @param bool $resetIfNeeded Whether to reset daily/weekly counts if needed
     * @return bool Success status
     */
    public function updateTaskStats(string $userId, bool $resetIfNeeded = true): bool {
        try {
            if ($resetIfNeeded) {
                $this->resetDailyWeeklyCountsIfNeeded($userId);
            }
            
            // Increment task counts
            $qb = $this->db->getQueryBuilder();
            $qb->update('ncquest_users')
                ->set('tasks_completed_today', $qb->createNamedParameter($qb->createFunction('tasks_completed_today + 1')))
                ->set('tasks_completed_this_week', $qb->createNamedParameter($qb->createFunction('tasks_completed_this_week + 1')))
                ->set('total_tasks_completed', $qb->createNamedParameter($qb->createFunction('total_tasks_completed + 1')))
                ->set('last_task_completion_date', $qb->createNamedParameter(date('Y-m-d H:i:s')))
                ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeStatement();
            
            // Clear cache
            unset($this->userDataCache[$userId]);
            
            return $result > 0;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update task stats', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Synchronize user data between tables (migration utility)
     * 
     * @param string $userId User ID to synchronize
     * @return bool Success status
     */
    public function synchronizeUserData(string $userId): bool {
        try {
            $legacyData = $this->getUserDataFromLegacy($userId);
            $unifiedData = $this->getUserDataFromUnified($userId);
            
            if ($legacyData && !$unifiedData) {
                // Migrate from legacy to unified
                return $this->migrateSingleUser($userId, $legacyData);
            } elseif ($legacyData && $unifiedData) {
                // Synchronize data (use highest values)
                $syncData = [
                    'lifetime_xp' => max($legacyData['total_xp'] ?? 0, $unifiedData['lifetime_xp'] ?? 0),
                    'level' => max($legacyData['level'] ?? 1, $unifiedData['level'] ?? 1),
                    'current_streak' => max($legacyData['current_streak'] ?? 0, $unifiedData['current_streak'] ?? 0),
                    'longest_streak' => max($legacyData['longest_streak'] ?? 0, $unifiedData['longest_streak'] ?? 0)
                ];
                
                return $this->updateUserData($userId, $syncData);
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to synchronize user data', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    // ========== STREAK MANAGEMENT ==========

    /**
     * Update user streak based on task completion
     * 
     * @param string $userId User ID
     * @param string $completionDate Completion date (Y-m-d format)
     * @return array Streak information
     */
    public function updateStreak(string $userId, string $completionDate = null): array {
        if ($completionDate === null) {
            $completionDate = date('Y-m-d');
        }
        
        try {
            $streakData = $this->calculateStreakFromHistory($userId, $completionDate);
            
            $updateData = [
                'current_streak' => $streakData['current_streak'],
                'longest_streak' => $streakData['longest_streak'],
                'last_completion_date' => $completionDate . ' ' . date('H:i:s')
            ];
            
            $this->updateUserData($userId, $updateData);
            
            return $streakData;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update streak', [
                'user_id' => $userId,
                'completion_date' => $completionDate,
                'exception' => $e->getMessage()
            ]);
            
            return ['current_streak' => 0, 'longest_streak' => 0];
        }
    }

    // ========== HEALTH MANAGEMENT ==========

    /**
     * Update user health
     * 
     * @param string $userId User ID
     * @param int $healthChange Health change (positive or negative)
     * @param string $reason Reason for health change
     * @return array Health status
     */
    public function updateHealth(string $userId, int $healthChange, string $reason = ''): array {
        try {
            $userData = $this->getUserData($userId);
            $currentHealth = $userData['current_health'];
            $maxHealth = $userData['max_health'];
            
            $newHealth = max(0, min($maxHealth, $currentHealth + $healthChange));
            
            $this->updateUserData($userId, ['current_health' => $newHealth]);
            
            $this->logger->info('Updated user health', [
                'user_id' => $userId,
                'health_change' => $healthChange,
                'old_health' => $currentHealth,
                'new_health' => $newHealth,
                'reason' => $reason
            ]);
            
            return [
                'current_health' => $newHealth,
                'max_health' => $maxHealth,
                'health_percentage' => $maxHealth > 0 ? ($newHealth / $maxHealth) * 100 : 100,
                'health_change' => $healthChange
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update health', [
                'user_id' => $userId,
                'health_change' => $healthChange,
                'reason' => $reason,
                'exception' => $e->getMessage()
            ]);
            
            return ['current_health' => 100, 'max_health' => 100, 'health_percentage' => 100, 'health_change' => 0];
        }
    }

    // ========== PRIVATE HELPER METHODS ==========

    /**
     * Get user data from unified table (ncquest_users)
     */
    private function getUserDataFromUnified(string $userId): ?array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            return $userData ?: null;
            
        } catch (\Exception $e) {
            $this->logger->debug('Failed to get user data from unified table', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Get user data from legacy table (quest_user_data)
     */
    private function getUserDataFromLegacy(string $userId): ?array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('quest_user_data')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $userData = $result->fetch();
            $result->closeCursor();
            
            return $userData ?: null;
            
        } catch (\Exception $e) {
            $this->logger->debug('Failed to get user data from legacy table', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Migrate single user from legacy to unified table
     */
    private function migrateSingleUser(string $userId, array $legacyData): bool {
        try {
            $migratedData = [
                'current_xp' => 0, // Reset current XP for fresh progression
                'lifetime_xp' => $legacyData['total_xp'] ?? 0,
                'level' => $legacyData['level'] ?? 1,
                'current_streak' => $legacyData['current_streak'] ?? 0,
                'longest_streak' => $legacyData['longest_streak'] ?? 0,
                'last_completion_date' => $legacyData['last_activity_date'] ?? null
            ];
            
            $success = $this->createUser($userId, $migratedData);
            
            if ($success) {
                $this->logger->info('Migrated user from legacy table', [
                    'user_id' => $userId,
                    'legacy_data' => $legacyData
                ]);
            }
            
            return $success;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to migrate user', [
                'user_id' => $userId,
                'legacy_data' => $legacyData,
                'exception' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Create default user with initial stats
     */
    private function createDefaultUser(string $userId): array {
        $defaultData = $this->getDefaultUserStats();
        $this->createUser($userId, $defaultData);
        return $defaultData;
    }

    /**
     * Check if user exists in unified table
     */
    private function userExists(string $userId): bool {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('user_id')
                ->from('ncquest_users')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
            
            $result = $qb->executeQuery();
            $exists = $result->fetchOne() !== false;
            $result->closeCursor();
            
            return $exists;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Validate if field is a valid user data field
     */
    private function isValidUserField(string $field): bool {
        $validFields = [
            'current_xp', 'lifetime_xp', 'level', 'current_streak', 'longest_streak',
            'current_health', 'max_health', 'tasks_completed_today', 'tasks_completed_this_week',
            'total_tasks_completed', 'last_task_completion_date', 'last_completion_date',
            'last_daily_reset', 'last_weekly_reset', 'theme_preference'
        ];
        
        return in_array($field, $validFields);
    }

    /**
     * Enrich user data with calculated fields
     */
    private function enrichUserData(array $userData): array {
        $level = (int)($userData['level'] ?? 1);
        $currentXp = (int)($userData['current_xp'] ?? 0);
        $lifetimeXp = (int)($userData['lifetime_xp'] ?? 0);
        $currentHealth = (int)($userData['current_health'] ?? 100);
        $maxHealth = (int)($userData['max_health'] ?? 100);
        
        // Calculate XP progress
        $xpForNextLevel = $this->getXPForLevel($level + 1);
        $xpForCurrentLevel = $this->getXPForLevel($level);
        $xpToNext = $xpForNextLevel - $currentXp;
        $xpProgress = $xpForNextLevel > $xpForCurrentLevel ? 
            (($currentXp - $xpForCurrentLevel) / ($xpForNextLevel - $xpForCurrentLevel)) * 100 : 100;
        
        // Calculate health percentage
        $healthPercentage = $maxHealth > 0 ? ($currentHealth / $maxHealth) * 100 : 100;
        
        // Check if streak is active today
        $lastCompletion = $userData['last_completion_date'] ?? null;
        $isActiveToday = false;
        if ($lastCompletion) {
            $lastDate = new \DateTime($lastCompletion);
            $today = new \DateTime();
            $isActiveToday = $lastDate->format('Y-m-d') === $today->format('Y-m-d');
        }
        
        return array_merge($userData, [
            'xp_for_next_level' => $xpForNextLevel,
            'xp_to_next_level' => $xpToNext,
            'xp_progress' => round($xpProgress, 1),
            'health_percentage' => round($healthPercentage, 1),
            'is_active_today' => $isActiveToday,
            'rank_title' => $this->getRankTitle($level)
        ]);
    }

    /**
     * Reset daily/weekly counts if needed
     */
    private function resetDailyWeeklyCountsIfNeeded(string $userId): void {
        try {
            $userData = $this->getUserDataFromUnified($userId);
            if (!$userData) {
                return;
            }
            
            $today = date('Y-m-d');
            $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
            
            $lastDailyReset = $userData['last_daily_reset'] ?? null;
            $lastWeeklyReset = $userData['last_weekly_reset'] ?? null;
            
            $updates = [];
            
            // Reset daily count if it's a new day
            if ($lastDailyReset !== $today) {
                $updates['tasks_completed_today'] = 0;
                $updates['last_daily_reset'] = $today;
            }
            
            // Reset weekly count if it's a new week
            if ($lastWeeklyReset !== $thisWeekStart) {
                $updates['tasks_completed_this_week'] = 0;
                $updates['last_weekly_reset'] = $thisWeekStart;
            }
            
            if (!empty($updates)) {
                $this->updateUserData($userId, $updates);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to reset daily/weekly counts', [
                'user_id' => $userId,
                'exception' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate streak from task completion history
     */
    private function calculateStreakFromHistory(string $userId, string $currentDate): array {
        try {
            // Get unique completion dates from history, ordered by date descending
            $qb = $this->db->getQueryBuilder();
            $qb->select('completed_at')
                ->from('ncquest_history')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
                ->orderBy('completed_at', 'DESC');
            
            $result = $qb->executeQuery();
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
            
            // Calculate current streak
            $currentStreak = 0;
            $checkDate = new \DateTime($currentDate);
            
            // Check if user completed tasks today or yesterday (to start streak)
            foreach ($uniqueDates as $dateStr) {
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
                foreach ($uniqueDates as $dateStr) {
                    $expectedDate = $checkDate->format('Y-m-d');
                    if ($dateStr === $expectedDate) {
                        // Continue counting
                        $checkDate->modify('-1 day');
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
            
            // For longest streak, we use current streak for now (can be enhanced)
            $longestStreak = max($currentStreak, 0);
            
            return [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to calculate streak from history', [
                'user_id' => $userId,
                'current_date' => $currentDate,
                'exception' => $e->getMessage()
            ]);
            
            return ['current_streak' => 0, 'longest_streak' => 0];
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
     * Get default user stats
     */
    private function getDefaultUserStats(): array {
        return [
            'user_id' => '',
            'current_xp' => 0,
            'lifetime_xp' => 0,
            'level' => 1,
            'current_streak' => 0,
            'longest_streak' => 0,
            'current_health' => 100,
            'max_health' => 100,
            'tasks_completed_today' => 0,
            'tasks_completed_this_week' => 0,
            'total_tasks_completed' => 0,
            'last_task_completion_date' => null,
            'last_completion_date' => null,
            'last_daily_reset' => null,
            'last_weekly_reset' => null,
            'theme_preference' => 'game',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'xp_for_next_level' => 100,
            'xp_to_next_level' => 100,
            'xp_progress' => 0,
            'health_percentage' => 100,
            'is_active_today' => false,
            'rank_title' => 'Novice Adventurer'
        ];
    }

    /**
     * Ensure database tables exist
     */
    private function ensureTablesExist(): void {
        if (self::$tablesInitialized) {
            return;
        }
        
        try {
            // Check if ncquest_users table exists by attempting a simple query
            $qb = $this->db->getQueryBuilder();
            $qb->select('1')->from('ncquest_users')->setMaxResults(1);
            $result = $qb->executeQuery();
            $result->closeCursor();
            
            self::$tablesInitialized = true;
            
        } catch (\Exception $e) {
            $this->logger->warning('Database tables may not exist yet', [
                'exception' => $e->getMessage()
            ]);
            
            // Tables don't exist - this is expected during initial setup
            // The migration system will handle table creation
        }
    }
}