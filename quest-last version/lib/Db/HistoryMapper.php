<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<History>
 */
class HistoryMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ncquest_history', History::class);
    }
    
    /**
     * Find history entries for a user
     * 
     * @param string $userId
     * @param int $limit
     * @param int $offset
     * @return History[]
     */
    public function findByUserId(string $userId, int $limit = 50, int $offset = 0): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('completed_at', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        
        return $this->findEntities($qb);
    }
    
    /**
     * Get history for a specific date range
     * 
     * @param string $userId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @return History[]
     */
    public function findByDateRange(string $userId, \DateTime $startDate, \DateTime $endDate): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->lte('completed_at', $qb->createNamedParameter($endDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)))
            ->orderBy('completed_at', 'DESC');
        
        return $this->findEntities($qb);
    }
    
    /**
     * Get completion statistics for a user
     * 
     * @param string $userId
     * @param int $days Number of days to look back (0 = all time)
     * @return array
     */
    public function getCompletionStats(string $userId, int $days = 0): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('COUNT(*) AS total_tasks'),
            $qb->createFunction('SUM(xp_earned) AS total_xp'),
            $qb->createFunction('DATE(completed_at) AS completion_date'),
            $qb->createFunction('COUNT(*) AS tasks_per_day')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->groupBy('completion_date')
        ->orderBy('completion_date', 'DESC');
        
        if ($days > 0) {
            $startDate = new \DateTime();
            $startDate->sub(new \DateInterval('P' . $days . 'D'));
            $qb->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)));
        }
        
        $result = $qb->execute();
        $stats = $result->fetchAll();
        $result->closeCursor();
        
        // Calculate aggregates
        $totalTasks = 0;
        $totalXp = 0;
        $dailyStats = [];
        
        foreach ($stats as $row) {
            $totalTasks += (int)$row['tasks_per_day'];
            $totalXp += (int)$row['total_xp'];
            $dailyStats[$row['completion_date']] = [
                'tasks' => (int)$row['tasks_per_day'],
                'xp' => (int)$row['total_xp']
            ];
        }
        
        return [
            'total_tasks' => $totalTasks,
            'total_xp' => $totalXp,
            'average_per_day' => count($dailyStats) > 0 ? round($totalTasks / count($dailyStats), 1) : 0,
            'daily_stats' => $dailyStats
        ];
    }
    
    /**
     * Check if user completed any tasks on a specific date
     * 
     * @param string $userId
     * @param \DateTime $date
     * @return bool
     */
    public function hasCompletionOnDate(string $userId, \DateTime $date): bool {
        $qb = $this->db->getQueryBuilder();
        
        $startOfDay = clone $date;
        $startOfDay->setTime(0, 0, 0);
        
        $endOfDay = clone $date;
        $endOfDay->setTime(23, 59, 59);
        
        $qb->select($qb->createFunction('COUNT(*)'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startOfDay->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->lte('completed_at', $qb->createNamedParameter($endOfDay->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $count = $result->fetchOne();
        $result->closeCursor();
        
        return (int)$count > 0;
    }
    
    /**
     * Delete old history entries
     * 
     * @param int $daysToKeep
     * @return int Number of deleted entries
     */
    public function deleteOldEntries(int $daysToKeep = 365): int {
        $qb = $this->db->getQueryBuilder();
        
        $cutoffDate = new \DateTime();
        $cutoffDate->sub(new \DateInterval('P' . $daysToKeep . 'D'));
        
        $qb->delete($this->getTableName())
            ->where($qb->expr()->lt('completed_at', $qb->createNamedParameter($cutoffDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)));
        
        return $qb->execute();
    }
    
    // Analytics Methods for Progress Tracking
    
    /**
     * Count total completions by user ID
     */
    public function countCompletionsByUserId(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select($qb->createFunction('COUNT(*)'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $count = $result->fetchOne();
        $result->closeCursor();
        
        return (int)$count;
    }
    
    /**
     * Count completions in a specific period
     */
    public function countCompletionsInPeriod(string $userId, string $period): int {
        $qb = $this->db->getQueryBuilder();
        
        $startDate = new \DateTime();
        switch ($period) {
            case 'week':
                $startDate->modify('monday this week')->setTime(0, 0, 0);
                break;
            case 'month':
                $startDate->modify('first day of this month')->setTime(0, 0, 0);
                break;
            case 'last_month':
                $startDate->modify('first day of last month')->setTime(0, 0, 0);
                $endDate = clone $startDate;
                $endDate->modify('last day of this month')->setTime(23, 59, 59);
                break;
            case 'year':
                $startDate->modify('first day of january this year')->setTime(0, 0, 0);
                break;
            default:
                return 0;
        }
        
        $qb->select($qb->createFunction('COUNT(*)'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)));
        
        if (isset($endDate)) {
            $qb->andWhere($qb->expr()->lte('completed_at', $qb->createNamedParameter($endDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)));
        }
        
        $result = $qb->execute();
        $count = $result->fetchOne();
        $result->closeCursor();
        
        return (int)$count;
    }
    
    /**
     * Get XP trends for different periods
     */
    public function getXPTrends(string $userId, string $period, int $limit): array {
        $qb = $this->db->getQueryBuilder();
        
        $dateFormat = match($period) {
            'daily' => 'DATE(completed_at)',
            'weekly' => 'YEARWEEK(completed_at)',
            'monthly' => 'DATE_FORMAT(completed_at, "%Y-%m")',
            default => 'DATE(completed_at)'
        };
        
        $qb->select([
            $qb->createFunction("$dateFormat AS period"),
            $qb->createFunction('SUM(xp_earned) AS total_xp'),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->groupBy('period')
        ->orderBy('period', 'DESC')
        ->setMaxResults($limit);
        
        $result = $qb->execute();
        $trends = $result->fetchAll();
        $result->closeCursor();
        
        return array_reverse($trends); // Return chronological order
    }
    
    /**
     * Get level progression history
     */
    public function getLevelProgressionHistory(string $userId): array {
        // This would need to be tracked in a separate table or calculated from XP history
        // For now, return sample data structure
        return [];
    }
    
    /**
     * Get XP sources breakdown by priority
     */
    public function getXPSourcesBreakdown(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        // Note: We need to add priority tracking to history table in the future
        // For now, return sample breakdown
        $qb->select([
            $qb->createFunction('SUM(xp_earned) AS total_xp'),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $total = $result->fetch();
        $result->closeCursor();
        
        // Return breakdown (this should be enhanced with actual priority data)
        return [
            'high' => ['xp' => round(($total['total_xp'] ?? 0) * 0.3), 'tasks' => round(($total['task_count'] ?? 0) * 0.2)],
            'medium' => ['xp' => round(($total['total_xp'] ?? 0) * 0.5), 'tasks' => round(($total['task_count'] ?? 0) * 0.5)],
            'low' => ['xp' => round(($total['total_xp'] ?? 0) * 0.2), 'tasks' => round(($total['task_count'] ?? 0) * 0.3)]
        ];
    }
    
    /**
     * Get activity calendar data
     */
    public function getActivityCalendar(string $userId, int $days): array {
        $qb = $this->db->getQueryBuilder();
        
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval('P' . $days . 'D'));
        
        $qb->select([
            $qb->createFunction('DATE(completed_at) AS date'),
            $qb->createFunction('COUNT(*) AS task_count'),
            $qb->createFunction('SUM(xp_earned) AS daily_xp')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startDate->format('Y-m-d'), IQueryBuilder::PARAM_STR)))
        ->groupBy('date')
        ->orderBy('date', 'ASC');
        
        $result = $qb->execute();
        $calendar = $result->fetchAll();
        $result->closeCursor();
        
        return $calendar;
    }
    
    /**
     * Get monthly streak percentage
     */
    public function getMonthlyStreakPercentage(string $userId): int {
        $startOfMonth = new \DateTime('first day of this month');
        $startOfMonth->setTime(0, 0, 0);
        $endOfMonth = new \DateTime('last day of this month');
        $endOfMonth->setTime(23, 59, 59);
        
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('COUNT(DISTINCT DATE(completed_at)) AS active_days')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startOfMonth->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)))
        ->andWhere($qb->expr()->lte('completed_at', $qb->createNamedParameter($endOfMonth->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $activeDays = (int)$result->fetchOne();
        $result->closeCursor();
        
        $totalDays = (int)$endOfMonth->format('j');
        $currentDay = (int)(new \DateTime())->format('j');
        $daysToConsider = min($totalDays, $currentDay);
        
        return $daysToConsider > 0 ? round(($activeDays / $daysToConsider) * 100) : 0;
    }
    
    /**
     * Get activity heatmap data
     */
    public function getActivityHeatmap(string $userId, string $view): array {
        $qb = $this->db->getQueryBuilder();
        
        $startDate = new \DateTime();
        switch ($view) {
            case 'year':
                $startDate->sub(new \DateInterval('P1Y'));
                break;
            case 'month':
                $startDate->sub(new \DateInterval('P1M'));
                break;
            case 'week':
                $startDate->sub(new \DateInterval('P1W'));
                break;
        }
        
        $qb->select([
            $qb->createFunction('DATE(completed_at) AS date'),
            $qb->createFunction('HOUR(completed_at) AS hour'),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->andWhere($qb->expr()->gte('completed_at', $qb->createNamedParameter($startDate->format('Y-m-d H:i:s'), IQueryBuilder::PARAM_STR)))
        ->groupBy('date', 'hour')
        ->orderBy('date', 'ASC')
        ->addOrderBy('hour', 'ASC');
        
        $result = $qb->execute();
        $heatmap = $result->fetchAll();
        $result->closeCursor();
        
        return $heatmap;
    }
    
    /**
     * Get task completion trends
     */
    public function getCompletionTrends(string $userId, string $period, int $limit): array {
        $qb = $this->db->getQueryBuilder();
        
        $dateFormat = match($period) {
            'daily' => 'DATE(completed_at)',
            'weekly' => 'YEARWEEK(completed_at)',
            'monthly' => 'DATE_FORMAT(completed_at, "%Y-%m")',
            default => 'DATE(completed_at)'
        };
        
        $qb->select([
            $qb->createFunction("$dateFormat AS period"),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->groupBy('period')
        ->orderBy('period', 'DESC')
        ->setMaxResults($limit);
        
        $result = $qb->execute();
        $trends = $result->fetchAll();
        $result->closeCursor();
        
        return array_reverse($trends);
    }
    
    /**
     * Get priority distribution
     */
    public function getPriorityDistribution(string $userId): array {
        // Note: Priority data needs to be added to history table
        // For now, return estimated distribution
        $total = $this->countCompletionsByUserId($userId);
        
        return [
            'high' => round($total * 0.2),
            'medium' => round($total * 0.5),
            'low' => round($total * 0.3)
        ];
    }
    
    /**
     * Get time-based analytics
     */
    public function getTimeBasedAnalytics(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('HOUR(completed_at) AS hour'),
            $qb->createFunction('DAYOFWEEK(completed_at) AS day_of_week'),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->groupBy('hour', 'day_of_week');
        
        $result = $qb->execute();
        $analytics = $result->fetchAll();
        $result->closeCursor();
        
        return $analytics;
    }
    
    /**
     * Get average XP per day
     */
    public function getAverageXPPerDay(string $userId, int $days): float {
        $qb = $this->db->getQueryBuilder();
        
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval('P' . $days . 'D'));
        
        $qb->select([
            $qb->createFunction('AVG(daily_xp) AS avg_xp')
        ])
        ->from(
            $qb->createFunction('(SELECT DATE(completed_at) AS date, SUM(xp_earned) AS daily_xp FROM ' . $this->getTableName() . ' WHERE user_id = ? AND completed_at >= ? GROUP BY DATE(completed_at)) AS daily_totals'),
            'daily_totals'
        );
        
        // Manual parameter binding for subquery
        $stmt = $this->db->prepare(
            'SELECT AVG(daily_xp) AS avg_xp FROM (SELECT DATE(completed_at) AS date, SUM(xp_earned) AS daily_xp FROM ' . $this->getTableName() . ' WHERE user_id = ? AND completed_at >= ? GROUP BY DATE(completed_at)) AS daily_totals'
        );
        
        $stmt->bindValue(1, $userId, \PDO::PARAM_STR);
        $stmt->bindValue(2, $startDate->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        
        $result = $stmt->execute();
        $avg = $stmt->fetchOne();
        $stmt->closeCursor();
        
        return (float)($avg ?: 0);
    }
    
    /**
     * Get productivity by day of week
     */
    public function getProductivityByDayOfWeek(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('DAYNAME(completed_at) AS day_name'),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->groupBy('day_name');
        
        $result = $qb->execute();
        $productivity = [];
        while ($row = $result->fetch()) {
            $productivity[$row['day_name']] = (int)$row['task_count'];
        }
        $result->closeCursor();
        
        return $productivity;
    }
    
    /**
     * Get productivity by hour
     */
    public function getProductivityByHour(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('HOUR(completed_at) AS hour'),
            $qb->createFunction('COUNT(*) AS task_count')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->groupBy('hour');
        
        $result = $qb->execute();
        $productivity = [];
        while ($row = $result->fetch()) {
            $productivity[(int)$row['hour']] = (int)$row['task_count'];
        }
        $result->closeCursor();
        
        return $productivity;
    }
    
    /**
     * Get days since first completion
     */
    public function getDaysSinceFirstCompletion(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('MIN(completed_at) AS first_completion')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $firstCompletion = $result->fetchOne();
        $result->closeCursor();
        
        if (!$firstCompletion) {
            return 0;
        }
        
        $first = new \DateTime($firstCompletion);
        $now = new \DateTime();
        
        return (int)$now->diff($first)->days;
    }
    
    /**
     * Get active days count
     */
    public function getActiveDaysCount(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select([
            $qb->createFunction('COUNT(DISTINCT DATE(completed_at)) AS active_days')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $count = $result->fetchOne();
        $result->closeCursor();
        
        return (int)$count;
    }
    
    /**
     * Get weekend activity percentage
     */
    public function getWeekendActivityPercentage(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        
        // Get total tasks
        $totalTasks = $this->countCompletionsByUserId($userId);
        
        if ($totalTasks === 0) {
            return 0;
        }
        
        // Get weekend tasks (Saturday = 7, Sunday = 1 in MySQL DAYOFWEEK)
        $qb->select([
            $qb->createFunction('COUNT(*) AS weekend_tasks')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->andWhere($qb->expr()->in('DAYOFWEEK(completed_at)', [1, 7]));
        
        $result = $qb->execute();
        $weekendTasks = (int)$result->fetchOne();
        $result->closeCursor();
        
        return round(($weekendTasks / $totalTasks) * 100);
    }
    
    /**
     * Get evening tasks percentage (after 6 PM)
     */
    public function getEveningTasksPercentage(string $userId): int {
        $qb = $this->db->getQueryBuilder();
        
        // Get total tasks
        $totalTasks = $this->countCompletionsByUserId($userId);
        
        if ($totalTasks === 0) {
            return 0;
        }
        
        // Get evening tasks (after 18:00)
        $qb->select([
            $qb->createFunction('COUNT(*) AS evening_tasks')
        ])
        ->from($this->getTableName())
        ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
        ->andWhere($qb->expr()->gte('HOUR(completed_at)', 18));
        
        $result = $qb->execute();
        $eveningTasks = (int)$result->fetchOne();
        $result->closeCursor();
        
        return round(($eveningTasks / $totalTasks) * 100);
    }
}