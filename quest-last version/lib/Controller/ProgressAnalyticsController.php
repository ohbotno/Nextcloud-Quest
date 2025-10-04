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
use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Service\StreakService;
use OCA\NextcloudQuest\Service\CharacterService;
use OCA\NextcloudQuest\Service\AchievementService;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\CharacterAgeMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;

class ProgressAnalyticsController extends Controller {
    /** @var IUserSession */
    private $userSession;
    /** @var XPService */
    private $xpService;
    /** @var StreakService */
    private $streakService;
    /** @var CharacterService */
    private $characterService;
    /** @var AchievementService */
    private $achievementService;
    /** @var QuestMapper */
    private $questMapper;
    /** @var HistoryMapper */
    private $historyMapper;
    /** @var CharacterAgeMapper */
    private $characterAgeMapper;
    /** @var LoggerInterface */
    private $logger;
    
    public function __construct(
        $appName,
        IRequest $request,
        IUserSession $userSession,
        XPService $xpService,
        StreakService $streakService,
        CharacterService $characterService,
        AchievementService $achievementService,
        QuestMapper $questMapper,
        HistoryMapper $historyMapper,
        CharacterAgeMapper $characterAgeMapper,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->xpService = $xpService;
        $this->streakService = $streakService;
        $this->characterService = $characterService;
        $this->achievementService = $achievementService;
        $this->questMapper = $questMapper;
        $this->historyMapper = $historyMapper;
        $this->characterAgeMapper = $characterAgeMapper;
        $this->logger = $logger;
    }
    
    /**
     * Get comprehensive progress overview
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getProgressOverview(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (DoesNotExistException $e) {
            // Return default values for new users
            return new JSONResponse([
                'current_level' => 1,
                'current_rank' => 'Task Novice',
                'total_xp' => 0,
                'xp_to_next' => 100,
                'current_streak' => 0,
                'longest_streak' => 0,
                'total_tasks' => 0,
                'tasks_this_week' => 0,
                'character_age' => 'Stone Age',
                'achievements_unlocked' => 0,
                'total_achievements' => $this->achievementService->getTotalAchievementCount()
            ]);
        }
        
        // Get basic stats
        $level = $quest->getLevel();
        $rank = $this->xpService->getRankTitle($level);
        $xpToNext = $this->xpService->getXPForNextLevel($level);
        $streakStats = $this->streakService->getStreakStats($userId);
        
        // Get task completion stats
        $totalTasks = $this->historyMapper->countCompletionsByUserId($userId);
        $tasksThisWeek = $this->historyMapper->countCompletionsInPeriod($userId, 'week');
        
        // Get character info
        $characterData = $this->characterService->getCharacterData($userId);
        $currentAge = $characterData['current_age']['name'] ?? 'Stone Age';
        
        // Get achievement stats
        $achievements = $this->achievementService->getUserAchievements($userId);
        $unlockedCount = count(array_filter($achievements, function($a) { return $a['unlocked']; }));
        $totalAchievements = $this->achievementService->getTotalAchievementCount();
        
        return new JSONResponse([
            'current_level' => $level,
            'current_rank' => $rank,
            'total_xp' => $quest->getLifetimeXp(),
            'xp_to_next' => $xpToNext - $quest->getCurrentXp(),
            'current_streak' => $streakStats['current_streak'],
            'longest_streak' => $streakStats['longest_streak'],
            'total_tasks' => $totalTasks,
            'tasks_this_week' => $tasksThisWeek,
            'character_age' => $currentAge,
            'achievements_unlocked' => $unlockedCount,
            'total_achievements' => $totalAchievements
        ]);
    }
    
    /**
     * Get XP analytics data with trends and predictions
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getXPAnalytics(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        // Get XP trends (daily, weekly, monthly)
        $dailyXP = $this->historyMapper->getXPTrends($userId, 'daily', 30);
        $weeklyXP = $this->historyMapper->getXPTrends($userId, 'weekly', 12);
        $monthlyXP = $this->historyMapper->getXPTrends($userId, 'monthly', 12);
        
        // Get level progression history
        $levelProgression = $this->historyMapper->getLevelProgressionHistory($userId);
        
        // Get XP sources breakdown (by priority)
        $xpSources = $this->historyMapper->getXPSourcesBreakdown($userId);
        
        // Calculate predictions
        $predictions = $this->calculateLevelPredictions($userId);
        
        return new JSONResponse([
            'trends' => [
                'daily' => $dailyXP,
                'weekly' => $weeklyXP,
                'monthly' => $monthlyXP
            ],
            'level_progression' => $levelProgression,
            'xp_sources' => $xpSources,
            'predictions' => $predictions
        ]);
    }
    
    /**
     * Get streak analytics and calendar data
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getStreakAnalytics(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        // Get current streak stats
        $streakStats = $this->streakService->getStreakStats($userId);
        
        // Get calendar data for the past year
        $calendarData = $this->historyMapper->getActivityCalendar($userId, 365);
        
        // Get streak milestones
        $milestones = $this->getStreakMilestones($streakStats['current_streak'], $streakStats['longest_streak']);
        
        // Calculate streak percentage for current month
        $monthlyStreak = $this->historyMapper->getMonthlyStreakPercentage($userId);
        
        return new JSONResponse([
            'current_streak' => $streakStats['current_streak'],
            'longest_streak' => $streakStats['longest_streak'],
            'monthly_percentage' => $monthlyStreak,
            'calendar_data' => $calendarData,
            'milestones' => $milestones,
            'is_active_today' => $streakStats['is_active_today'],
            'grace_period_ends' => $streakStats['grace_period_ends']
        ]);
    }
    
    /**
     * Get activity heatmap data
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getActivityHeatmap(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        // Get activity data for different time periods
        $yearlyActivity = $this->historyMapper->getActivityHeatmap($userId, 'year');
        $monthlyActivity = $this->historyMapper->getActivityHeatmap($userId, 'month');
        $weeklyActivity = $this->historyMapper->getActivityHeatmap($userId, 'week');
        
        // Get productivity insights
        $insights = $this->calculateProductivityInsights($userId);
        
        return new JSONResponse([
            'heatmap_data' => [
                'yearly' => $yearlyActivity,
                'monthly' => $monthlyActivity,
                'weekly' => $weeklyActivity
            ],
            'insights' => $insights
        ]);
    }
    
    /**
     * Get task completion trends and patterns
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getTaskCompletionTrends(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        // Get completion trends
        $dailyTrends = $this->historyMapper->getCompletionTrends($userId, 'daily', 30);
        $weeklyTrends = $this->historyMapper->getCompletionTrends($userId, 'weekly', 12);
        $monthlyTrends = $this->historyMapper->getCompletionTrends($userId, 'monthly', 12);
        
        // Get priority distribution
        $priorityDistribution = $this->historyMapper->getPriorityDistribution($userId);
        
        // Get time-based analytics
        $timeAnalytics = $this->historyMapper->getTimeBasedAnalytics($userId);
        
        return new JSONResponse([
            'trends' => [
                'daily' => $dailyTrends,
                'weekly' => $weeklyTrends,
                'monthly' => $monthlyTrends
            ],
            'priority_distribution' => $priorityDistribution,
            'time_analytics' => $timeAnalytics
        ]);
    }
    
    /**
     * Get comprehensive productivity insights
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getProductivityInsights(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        $insights = $this->calculateProductivityInsights($userId);
        
        // Add performance metrics
        $performanceMetrics = $this->calculatePerformanceMetrics($userId);
        
        return new JSONResponse([
            'insights' => $insights,
            'performance_metrics' => $performanceMetrics
        ]);
    }
    
    /**
     * Get level progression data and predictions
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getLevelProgressionData(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        // Get current quest data
        try {
            $quest = $this->questMapper->findByUserId($userId);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'No quest data found'], 404);
        }
        
        // Get level progression history
        $progressionHistory = $this->historyMapper->getLevelProgressionHistory($userId);
        
        // Calculate predictions
        $predictions = $this->calculateLevelPredictions($userId);
        
        // Get XP requirements for levels
        $levelRequirements = [];
        for ($level = 1; $level <= 100; $level++) {
            $levelRequirements[$level] = $this->xpService->getXPForLevel($level);
        }
        
        return new JSONResponse([
            'current_level' => $quest->getLevel(),
            'current_xp' => $quest->getCurrentXp(),
            'lifetime_xp' => $quest->getLifetimeXp(),
            'progression_history' => $progressionHistory,
            'predictions' => $predictions,
            'level_requirements' => $levelRequirements
        ]);
    }
    
    /**
     * Get character timeline data with age progression
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getCharacterTimelineData(): JSONResponse {
        $user = $this->userSession->getUser();
        if (!$user) {
            return new JSONResponse(['error' => 'User not found'], 401);
        }
        
        $userId = $user->getUID();
        
        // Get all character ages
        $ages = $this->characterAgeMapper->findAll();
        
        // Get user's character progression
        $characterData = $this->characterService->getCharacterData($userId);
        $currentAge = $characterData['current_age'];
        $progressionStats = $characterData['progression_stats'];
        
        // Get character progression history
        $progressionHistory = $this->characterService->getProgressionHistory($userId);
        
        // Calculate progress to next age
        $nextAge = null;
        $progressToNext = 0;
        
        if ($currentAge && $currentAge['order'] < 10) {
            $nextAgeOrder = $currentAge['order'] + 1;
            foreach ($ages as $age) {
                if ($age->getAgeOrder() === $nextAgeOrder) {
                    $nextAge = [
                        'id' => $age->getId(),
                        'name' => $age->getName(),
                        'description' => $age->getDescription(),
                        'order' => $age->getAgeOrder(),
                        'level_required' => $age->getLevelRequired(),
                        'tasks_required' => $age->getTasksRequired(),
                        'streak_required' => $age->getStreakRequired()
                    ];
                    break;
                }
            }
            
            if ($nextAge) {
                $currentLevel = $characterData['level'];
                $currentTasks = $progressionStats['total_tasks'];
                $currentStreak = $progressionStats['longest_streak'];
                
                $levelProgress = min(100, ($currentLevel / $nextAge['level_required']) * 100);
                $taskProgress = min(100, ($currentTasks / $nextAge['tasks_required']) * 100);
                $streakProgress = min(100, ($currentStreak / $nextAge['streak_required']) * 100);
                
                $progressToNext = min($levelProgress, $taskProgress, $streakProgress);
            }
        }
        
        // Format timeline data
        $timelineData = [];
        foreach ($ages as $age) {
            $isCompleted = $currentAge && $age->getAgeOrder() <= $currentAge['order'];
            $isCurrent = $currentAge && $age->getAgeOrder() === $currentAge['order'];
            
            $timelineData[] = [
                'id' => $age->getId(),
                'name' => $age->getName(),
                'description' => $age->getDescription(),
                'order' => $age->getAgeOrder(),
                'level_required' => $age->getLevelRequired(),
                'tasks_required' => $age->getTasksRequired(),
                'streak_required' => $age->getStreakRequired(),
                'is_completed' => $isCompleted,
                'is_current' => $isCurrent,
                'unlocked_at' => null // Add from progression history if available
            ];
        }
        
        return new JSONResponse([
            'timeline' => $timelineData,
            'current_age' => $currentAge,
            'next_age' => $nextAge,
            'progress_to_next' => $progressToNext,
            'progression_history' => $progressionHistory
        ]);
    }
    
    /**
     * Calculate level predictions based on current XP gain rate
     */
    private function calculateLevelPredictions(string $userId): array {
        // Get average daily XP for the last 30 days
        $recentXP = $this->historyMapper->getAverageXPPerDay($userId, 30);
        
        if ($recentXP <= 0) {
            return [
                'next_level' => null,
                'level_25' => null,
                'level_50' => null,
                'level_100' => null
            ];
        }
        
        try {
            $quest = $this->questMapper->findByUserId($userId);
            $currentLevel = $quest->getLevel();
            $currentXP = $quest->getLifetimeXp();
        } catch (DoesNotExistException $e) {
            return [
                'next_level' => null,
                'level_25' => null,
                'level_50' => null,
                'level_100' => null
            ];
        }
        
        $predictions = [];
        $targetLevels = [$currentLevel + 1, 25, 50, 100];
        
        foreach ($targetLevels as $targetLevel) {
            if ($targetLevel <= $currentLevel) {
                $predictions[($targetLevel === $currentLevel + 1) ? 'next_level' : "level_$targetLevel"] = 0;
                continue;
            }
            
            $xpRequired = $this->xpService->getXPForLevel($targetLevel) - $currentXP;
            $daysToLevel = ceil($xpRequired / $recentXP);
            
            $key = ($targetLevel === $currentLevel + 1) ? 'next_level' : "level_$targetLevel";
            $predictions[$key] = $daysToLevel;
        }
        
        return $predictions;
    }
    
    /**
     * Get streak milestones based on current and longest streaks
     */
    private function getStreakMilestones(int $currentStreak, int $longestStreak): array {
        $milestones = [
            ['days' => 3, 'name' => 'Getting Started', 'description' => 'Complete tasks for 3 days in a row'],
            ['days' => 7, 'name' => 'Weekly Warrior', 'description' => 'Maintain a 7-day streak'],
            ['days' => 14, 'name' => 'Fortnight Fighter', 'description' => 'Keep going for 2 weeks straight'],
            ['days' => 30, 'name' => 'Monthly Master', 'description' => 'Achieve a full month streak'],
            ['days' => 60, 'name' => 'Consistency Champion', 'description' => 'Stay consistent for 2 months'],
            ['days' => 100, 'name' => 'Century Achiever', 'description' => 'Reach the legendary 100-day streak'],
            ['days' => 365, 'name' => 'Year-Long Legend', 'description' => 'Maintain productivity for a full year']
        ];
        
        foreach ($milestones as &$milestone) {
            $milestone['completed'] = $longestStreak >= $milestone['days'];
            $milestone['current'] = $currentStreak >= $milestone['days'];
            $milestone['progress'] = min(100, ($currentStreak / $milestone['days']) * 100);
        }
        
        return $milestones;
    }
    
    /**
     * Get comprehensive productivity insights
     */
    private function calculateProductivityInsights(string $userId): array {
        $insights = [];
        
        // Most productive day of week
        $dayStats = $this->historyMapper->getProductivityByDayOfWeek($userId);
        $mostProductiveDay = '';
        $maxTasks = 0;
        foreach ($dayStats as $day => $count) {
            if ($count > $maxTasks) {
                $maxTasks = $count;
                $mostProductiveDay = $day;
            }
        }
        
        // Peak hour
        $hourStats = $this->historyMapper->getProductivityByHour($userId);
        $peakHour = '';
        $maxHourTasks = 0;
        foreach ($hourStats as $hour => $count) {
            if ($count > $maxHourTasks) {
                $maxHourTasks = $count;
                $peakHour = sprintf('%02d:00', $hour);
            }
        }
        
        // Average daily tasks
        $totalTasks = $this->historyMapper->countCompletionsByUserId($userId);
        $daysSinceFirst = $this->historyMapper->getDaysSinceFirstCompletion($userId);
        $avgDailyTasks = $daysSinceFirst > 0 ? round($totalTasks / $daysSinceFirst, 1) : 0;
        
        // Consistency score (percentage of days with at least one task)
        $activeDays = $this->historyMapper->getActiveDaysCount($userId);
        $consistencyScore = $daysSinceFirst > 0 ? round(($activeDays / $daysSinceFirst) * 100) : 0;
        
        return [
            'most_productive_day' => $mostProductiveDay,
            'peak_hour' => $peakHour,
            'avg_daily_tasks' => $avgDailyTasks,
            'consistency_score' => $consistencyScore
        ];
    }
    
    /**
     * Calculate comprehensive performance metrics
     */
    private function calculatePerformanceMetrics(string $userId): array {
        // Get basic stats
        $totalTasks = $this->historyMapper->countCompletionsByUserId($userId);
        $weekTasks = $this->historyMapper->countCompletionsInPeriod($userId, 'week');
        $monthTasks = $this->historyMapper->countCompletionsInPeriod($userId, 'month');
        
        // Calculate productivity score (based on tasks, streak, and consistency)
        $streakStats = $this->streakService->getStreakStats($userId);
        $insights = $this->calculateProductivityInsights($userId);
        
        $productivityScore = min(100, (
            ($weekTasks * 10) +
            ($streakStats['current_streak'] * 2) +
            $insights['consistency_score']
        ) / 3);
        
        // Consistency rating (star rating 1-5)
        $consistency = $insights['consistency_score'];
        $consistencyRating = '';
        if ($consistency >= 90) $consistencyRating = '★★★★★';
        elseif ($consistency >= 70) $consistencyRating = '★★★★☆';
        elseif ($consistency >= 50) $consistencyRating = '★★★☆☆';
        elseif ($consistency >= 30) $consistencyRating = '★★☆☆☆';
        else $consistencyRating = '★☆☆☆☆';
        
        // Growth trend (compare this month to last month)
        $lastMonthTasks = $this->historyMapper->countCompletionsInPeriod($userId, 'last_month');
        $growthTrend = 0;
        if ($lastMonthTasks > 0) {
            $growthTrend = round((($monthTasks - $lastMonthTasks) / $lastMonthTasks) * 100);
        }
        
        // Streak health
        $streakHealth = 'Excellent';
        if ($streakStats['current_streak'] === 0) $streakHealth = 'Needs Attention';
        elseif ($streakStats['current_streak'] < 3) $streakHealth = 'Building';
        elseif ($streakStats['current_streak'] < 7) $streakHealth = 'Good';
        elseif ($streakStats['current_streak'] < 30) $streakHealth = 'Very Good';
        
        return [
            'productivity_score' => round($productivityScore),
            'consistency_rating' => $consistencyRating,
            'growth_trend' => $growthTrend,
            'streak_health' => $streakHealth,
            'total_completed_tasks' => $totalTasks,
            'week_completed_tasks' => $weekTasks,
            'month_completed_tasks' => $monthTasks,
            'most_active_hour' => $insights['peak_hour'],
            'most_active_day_week' => $insights['most_productive_day'],
            'weekend_activity' => $this->historyMapper->getWeekendActivityPercentage($userId),
            'evening_tasks' => $this->historyMapper->getEveningTasksPercentage($userId)
        ];
    }
}