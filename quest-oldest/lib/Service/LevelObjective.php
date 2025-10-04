<?php

declare(strict_types=1);

namespace OCA\NextcloudQuest\Service;

use OCP\IDBConnection;

/**
 * Level Objective Service - Manages level goals and auto-regeneration
 * Handles objective validation, completion checking, and regeneration when tasks become unavailable
 */
class LevelObjective {

    /** @var IDBConnection */
    private $db;

    /** @var array Valid objective types and their handlers */
    private const OBJECTIVE_TYPES = [
        'complete_task' => 'handleCompleteTask',
        'quantity_time' => 'handleQuantityTime',
        'mixed_challenge' => 'handleMixedChallenge',
        'streak' => 'handleStreak',
        'quantity_streak' => 'handleQuantityStreak',
        'routine_streak' => 'handleRoutineStreak',
        'diverse_quantity' => 'handleDiverseQuantity',
        'overdue_master' => 'handleOverdueMaster',
        'master_challenge' => 'handleMasterChallenge',
        'daily_quantity' => 'handleDailyQuantity',
        'category_diversity' => 'handleCategoryDiversity',
        'priority_clear' => 'handlePriorityClear'
    ];

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Validate if an objective can be completed with current tasks
     * @param array $objective Objective data
     * @param array $availableTasks Current available tasks
     * @param array $userStats Current user statistics
     * @return bool Whether objective is achievable
     */
    public function validateObjective(array $objective, array $availableTasks, array $userStats = []): bool {
        $type = $objective['type'];
        
        if (!isset(self::OBJECTIVE_TYPES[$type])) {
            return false;
        }

        $handlerMethod = self::OBJECTIVE_TYPES[$type];
        return $this->$handlerMethod($objective, $availableTasks, $userStats, 'validate');
    }

    /**
     * Check if an objective has been completed
     * @param array $objective Objective data
     * @param array $completedTasks Recently completed tasks
     * @param array $userStats Current user statistics
     * @return bool Whether objective is completed
     */
    public function checkCompletion(array $objective, array $completedTasks, array $userStats = []): bool {
        $type = $objective['type'];
        
        if (!isset(self::OBJECTIVE_TYPES[$type])) {
            return false;
        }

        $handlerMethod = self::OBJECTIVE_TYPES[$type];
        return $this->$handlerMethod($objective, $completedTasks, $userStats, 'check');
    }

    /**
     * Auto-regenerate an objective if it becomes invalid
     * @param array $objective Current objective
     * @param array $availableTasks Available tasks
     * @param string $worldTheme World theme for appropriate regeneration
     * @param float $difficultyModifier World difficulty modifier
     * @return array New objective or original if still valid
     */
    public function regenerateObjective(array $objective, array $availableTasks, string $worldTheme, float $difficultyModifier = 1.0): array {
        // Check if current objective is still valid
        if ($this->validateObjective($objective, $availableTasks)) {
            return $objective;
        }

        // Generate new objective based on type and available tasks
        $newObjective = $this->generateSimilarObjective($objective, $availableTasks, $worldTheme, $difficultyModifier);
        
        
        return $newObjective;
    }

    /**
     * Generate a similar objective when regeneration is needed
     * @param array $originalObjective Original objective
     * @param array $availableTasks Available tasks
     * @param string $worldTheme World theme
     * @param float $difficultyModifier Difficulty modifier
     * @return array New objective
     */
    private function generateSimilarObjective(array $originalObjective, array $availableTasks, string $worldTheme, float $difficultyModifier): array {
        $type = $originalObjective['type'];
        
        switch ($type) {
            case 'complete_task':
                return $this->regenerateTaskObjective($availableTasks, $worldTheme);
                
            case 'daily_quantity':
                $count = max(1, round(3 * $difficultyModifier));
                return [
                    'type' => 'daily_quantity',
                    'data' => ['count' => $count],
                    'description' => "Complete $count tasks today"
                ];
                
            case 'category_diversity':
                $categoryCount = min(4, max(2, round(3 * $difficultyModifier)));
                return [
                    'type' => 'category_diversity',
                    'data' => ['category_count' => $categoryCount],
                    'description' => "Complete tasks from $categoryCount different categories"
                ];
                
            case 'priority_clear':
                return [
                    'type' => 'priority_clear',
                    'data' => ['priority' => 'high'],
                    'description' => 'Complete all high-priority tasks'
                ];
                
            default:
                // Fallback to simple task completion
                return $this->regenerateTaskObjective($availableTasks, $worldTheme);
        }
    }

    /**
     * Regenerate a task-based objective
     * @param array $availableTasks Available tasks
     * @param string $worldTheme World theme
     * @return array New task objective
     */
    private function regenerateTaskObjective(array $availableTasks, string $worldTheme): array {
        if (empty($availableTasks)) {
            // No tasks available - create a generic objective
            return [
                'type' => 'daily_quantity',
                'data' => ['count' => 1],
                'description' => 'Complete 1 task today'
            ];
        }

        // Filter tasks by theme if possible
        $themedTasks = $this->filterTasksByTheme($availableTasks, $worldTheme);
        $taskPool = !empty($themedTasks) ? $themedTasks : $availableTasks;
        
        // Select a random available task
        $selectedTask = $taskPool[array_rand($taskPool)];
        
        return [
            'type' => 'complete_task',
            'task_id' => $selectedTask['id'],
            'task_title' => $selectedTask['title'],
            'description' => "Complete: " . $selectedTask['title']
        ];
    }

    // Objective Handler Methods

    /**
     * Handle complete_task objective type
     */
    private function handleCompleteTask(array $objective, array $tasks, array $userStats, string $mode): bool {
        if ($mode === 'validate') {
            // Check if the specific task exists and is not completed
            $taskId = $objective['task_id'] ?? null;
            if (!$taskId) return false;
            
            foreach ($tasks as $task) {
                if ($task['id'] == $taskId && !($task['completed'] ?? false)) {
                    return true;
                }
            }
            return false;
        }
        
        if ($mode === 'check') {
            // Check if the specific task was completed
            $taskId = $objective['task_id'] ?? null;
            if (!$taskId) return false;
            
            foreach ($tasks as $task) {
                if ($task['id'] == $taskId && ($task['completed'] ?? false)) {
                    return true;
                }
            }
            return false;
        }
        
        return false;
    }

    /**
     * Handle daily_quantity objective type
     */
    private function handleDailyQuantity(array $objective, array $tasks, array $userStats, string $mode): bool {
        $requiredCount = $objective['data']['count'] ?? 1;
        
        if ($mode === 'validate') {
            // Check if there are enough incomplete tasks
            $incompleteTasks = array_filter($tasks, fn($task) => !($task['completed'] ?? false));
            return count($incompleteTasks) >= $requiredCount;
        }
        
        if ($mode === 'check') {
            // Check if enough tasks were completed today
            $today = date('Y-m-d');
            $completedToday = 0;
            
            foreach ($tasks as $task) {
                if (($task['completed'] ?? false) && 
                    isset($task['completed_date']) && 
                    date('Y-m-d', strtotime($task['completed_date'])) === $today) {
                    $completedToday++;
                }
            }
            
            return $completedToday >= $requiredCount;
        }
        
        return false;
    }

    /**
     * Handle category_diversity objective type
     */
    private function handleCategoryDiversity(array $objective, array $tasks, array $userStats, string $mode): bool {
        $requiredCategories = $objective['data']['category_count'] ?? 2;
        
        if ($mode === 'validate') {
            // Check if there are enough different categories available
            $categories = [];
            foreach ($tasks as $task) {
                if (!($task['completed'] ?? false)) {
                    $category = $task['category'] ?? 'uncategorized';
                    $categories[$category] = true;
                }
            }
            return count($categories) >= $requiredCategories;
        }
        
        if ($mode === 'check') {
            // Check if tasks from enough categories were completed
            $completedCategories = [];
            foreach ($tasks as $task) {
                if ($task['completed'] ?? false) {
                    $category = $task['category'] ?? 'uncategorized';
                    $completedCategories[$category] = true;
                }
            }
            return count($completedCategories) >= $requiredCategories;
        }
        
        return false;
    }

    /**
     * Handle priority_clear objective type
     */
    private function handlePriorityClear(array $objective, array $tasks, array $userStats, string $mode): bool {
        $targetPriority = $objective['data']['priority'] ?? 'high';
        
        if ($mode === 'validate') {
            // Check if there are any tasks with the target priority
            foreach ($tasks as $task) {
                if (!($task['completed'] ?? false) && 
                    ($task['priority'] ?? 'medium') === $targetPriority) {
                    return true;
                }
            }
            return false;
        }
        
        if ($mode === 'check') {
            // Check if all tasks with target priority are completed
            $targetTasks = [];
            $completedTargets = 0;
            
            foreach ($tasks as $task) {
                if (($task['priority'] ?? 'medium') === $targetPriority) {
                    $targetTasks[] = $task;
                    if ($task['completed'] ?? false) {
                        $completedTargets++;
                    }
                }
            }
            
            // All target priority tasks must be completed
            return count($targetTasks) > 0 && $completedTargets === count($targetTasks);
        }
        
        return false;
    }

    /**
     * Handle streak objective type
     */
    private function handleStreak(array $objective, array $tasks, array $userStats, string $mode): bool {
        $requiredDays = $objective['data']['days'] ?? 7;
        $category = $objective['data']['category'] ?? null;
        
        if ($mode === 'validate') {
            // Always valid if there are tasks in the category
            if ($category) {
                $categoryTasks = array_filter($tasks, fn($task) => ($task['category'] ?? '') === $category);
                return count($categoryTasks) > 0;
            }
            return count($tasks) > 0;
        }
        
        if ($mode === 'check') {
            // Check current streak from user stats
            $currentStreak = $userStats['current_streak'] ?? 0;
            return $currentStreak >= $requiredDays;
        }
        
        return false;
    }

    /**
     * Handle quantity_time objective type (e.g., "Complete 10 tasks in 5 days")
     */
    private function handleQuantityTime(array $objective, array $tasks, array $userStats, string $mode): bool {
        $requiredCount = $objective['data']['count'] ?? 10;
        $days = $objective['data']['days'] ?? 5;
        $category = $objective['data']['category'] ?? null;
        
        if ($mode === 'validate') {
            // Check if there are enough tasks available
            $availableTasks = $tasks;
            if ($category && $category !== 'mixed') {
                $availableTasks = array_filter($tasks, fn($task) => ($task['category'] ?? '') === $category);
            }
            return count($availableTasks) >= $requiredCount;
        }
        
        if ($mode === 'check') {
            // Check if enough tasks were completed in the time period
            $startDate = date('Y-m-d', strtotime("-$days days"));
            $completedInPeriod = 0;
            
            foreach ($tasks as $task) {
                if (($task['completed'] ?? false) && 
                    isset($task['completed_date']) && 
                    $task['completed_date'] >= $startDate) {
                    
                    if (!$category || $category === 'mixed' || ($task['category'] ?? '') === $category) {
                        $completedInPeriod++;
                    }
                }
            }
            
            return $completedInPeriod >= $requiredCount;
        }
        
        return false;
    }

    /**
     * Handle overdue_master objective type
     */
    private function handleOverdueMaster(array $objective, array $tasks, array $userStats, string $mode): bool {
        if ($mode === 'validate') {
            // Check if there are overdue tasks
            $overdueTasks = array_filter($tasks, function($task) {
                return !($task['completed'] ?? false) && 
                       isset($task['due_date']) && 
                       $task['due_date'] < date('Y-m-d');
            });
            return count($overdueTasks) > 0;
        }
        
        if ($mode === 'check') {
            // Check if all overdue tasks are completed and none remain
            $overdueTasks = array_filter($tasks, function($task) {
                return isset($task['due_date']) && $task['due_date'] < date('Y-m-d');
            });
            
            $completedOverdue = array_filter($overdueTasks, fn($task) => $task['completed'] ?? false);
            
            return count($overdueTasks) > 0 && count($completedOverdue) === count($overdueTasks);
        }
        
        return false;
    }

    /**
     * Handle master_challenge objective type
     */
    private function handleMasterChallenge(array $objective, array $tasks, array $userStats, string $mode): bool {
        $requiredCount = $objective['data']['count'] ?? 25;
        $days = $objective['data']['days'] ?? 7;
        
        if ($mode === 'validate') {
            // Always valid if there are enough tasks
            return count($tasks) >= $requiredCount;
        }
        
        if ($mode === 'check') {
            // Check if enough tasks were completed across all categories in time period
            $startDate = date('Y-m-d', strtotime("-$days days"));
            $completedInPeriod = 0;
            $categories = [];
            
            foreach ($tasks as $task) {
                if (($task['completed'] ?? false) && 
                    isset($task['completed_date']) && 
                    $task['completed_date'] >= $startDate) {
                    
                    $completedInPeriod++;
                    $categories[$task['category'] ?? 'uncategorized'] = true;
                }
            }
            
            // Must complete required count AND have diversity
            return $completedInPeriod >= $requiredCount && count($categories) >= 3;
        }
        
        return false;
    }

    /**
     * Filter tasks by world theme
     * @param array $tasks All tasks
     * @param string $theme World theme
     * @return array Filtered tasks
     */
    private function filterTasksByTheme(array $tasks, string $theme): array {
        if ($theme === 'mixed') {
            return $tasks;
        }
        
        return array_filter($tasks, function($task) use ($theme) {
            $title = strtolower($task['title'] ?? '');
            $category = strtolower($task['category'] ?? '');
            
            switch ($theme) {
                case 'fitness':
                    return strpos($title, 'gym') !== false || 
                           strpos($title, 'exercise') !== false ||
                           strpos($title, 'workout') !== false ||
                           strpos($category, 'health') !== false;
                           
                case 'work':
                    return strpos($title, 'work') !== false ||
                           strpos($title, 'meeting') !== false ||
                           strpos($category, 'work') !== false;
                           
                case 'personal':
                    return strpos($category, 'personal') !== false ||
                           strpos($title, 'personal') !== false;
                           
                default:
                    return true; // Include all tasks for other themes
            }
        });
    }
}