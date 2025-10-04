<?php

declare(strict_types=1);

namespace OCA\NextcloudQuest\Service;

/**
 * Path Generator Service - Creates diamond-pattern paths within worlds
 * Generates Mario Bros 3 style branching paths with reconvergence points
 */
class PathGenerator {

    /** @var int Maximum number of parallel paths at any point */
    private const MAX_PARALLEL_PATHS = 4;

    /** @var int Minimum number of parallel paths at any point */
    private const MIN_PARALLEL_PATHS = 2;

    /** @var WorldGenerator */
    private $worldGenerator;

    public function __construct(WorldGenerator $worldGenerator) {
        $this->worldGenerator = $worldGenerator;
    }

    /**
     * Generate a complete path structure for a world
     * @param array $worldData World information from WorldGenerator
     * @param array $availableTasks Available tasks for objective generation
     * @return array Complete path structure with levels and connections
     */
    public function generateWorldPath(array $worldData, array $availableTasks): array {
        $levelCount = $worldData['level_count'];
        $miniBossPosition = $worldData['mini_boss_position'];
        
        // Create the path structure with diamond patterns
        $pathStructure = $this->createPathStructure($levelCount, $miniBossPosition);
        
        // Generate levels for each position in the path
        $levels = $this->generateLevels($pathStructure, $worldData, $availableTasks);
        
        // Create connections between levels
        $connections = $this->generateConnections($pathStructure);
        
        return [
            'world_id' => $worldData['world_number'],
            'structure' => $pathStructure,
            'levels' => $levels,
            'connections' => $connections,
            'total_levels' => count($levels),
            'branch_points' => $this->identifyBranchPoints($pathStructure),
            'convergence_points' => $this->identifyConvergencePoints($pathStructure)
        ];
    }

    /**
     * Create the basic diamond-pattern path structure
     * @param int $levelCount Total number of levels in world
     * @param int $miniBossPosition Position of mini-boss
     * @return array Path structure with positions and branching info
     */
    private function createPathStructure(int $levelCount, int $miniBossPosition): array {
        $structure = [];
        
        // Start with single path
        $structure[1] = [
            'position' => 1,
            'parallel_paths' => 1,
            'level_type' => 'start',
            'x' => 100,
            'y' => 200
        ];

        $currentY = 200;
        $xSpacing = 150;
        $ySpacing = 80;

        for ($pos = 2; $pos <= $levelCount; $pos++) {
            $x = $pos * $xSpacing;
            
            // Determine level type
            if ($pos === $miniBossPosition) {
                $levelType = 'mini_boss';
            } elseif ($pos === $levelCount) {
                $levelType = 'boss';
            } else {
                $levelType = 'regular';
            }

            // Determine branching pattern
            $pathCount = $this->calculatePathCount($pos, $levelCount, $miniBossPosition);
            
            if ($pathCount === 1) {
                // Single path
                $structure[$pos] = [
                    'position' => $pos,
                    'parallel_paths' => 1,
                    'level_type' => $levelType,
                    'x' => $x,
                    'y' => $currentY,
                    'path_index' => 0
                ];
            } else {
                // Multiple paths - create diamond pattern
                $startY = $currentY - (($pathCount - 1) * $ySpacing / 2);
                
                for ($pathIndex = 0; $pathIndex < $pathCount; $pathIndex++) {
                    $y = $startY + ($pathIndex * $ySpacing);
                    
                    $structure[$pos . '_' . $pathIndex] = [
                        'position' => $pos,
                        'parallel_paths' => $pathCount,
                        'level_type' => $levelType,
                        'x' => $x,
                        'y' => $y,
                        'path_index' => $pathIndex,
                        'branch_id' => $pos . '_branch'
                    ];
                }
            }
        }

        return $structure;
    }

    /**
     * Calculate how many parallel paths should exist at a given position
     * @param int $position Current position in world
     * @param int $totalLevels Total levels in world
     * @param int $miniBossPosition Mini-boss position
     * @return int Number of parallel paths
     */
    private function calculatePathCount(int $position, int $totalLevels, int $miniBossPosition): int {
        // Always converge to single path for mini-boss and boss
        if ($position === $miniBossPosition || $position === $totalLevels) {
            return 1;
        }

        // Start with single path
        if ($position <= 2) {
            return 1;
        }

        // Create branching patterns based on world structure
        $worldSection = $position / $totalLevels;
        
        // Early section: Start branching
        if ($worldSection < 0.3) {
            return rand(2, 3);
        }
        
        // Mid section: Maximum branching
        if ($worldSection < 0.6) {
            return rand(2, self::MAX_PARALLEL_PATHS);
        }
        
        // Before mini-boss: Converge to single path
        if ($position >= $miniBossPosition - 1) {
            return 1;
        }
        
        // After mini-boss: Branch again
        if ($position > $miniBossPosition && $position < $totalLevels - 2) {
            return rand(2, 3);
        }
        
        // End section: Converge to boss
        return 1;
    }

    /**
     * Generate level data for each position in the path structure
     * @param array $pathStructure Path structure from createPathStructure
     * @param array $worldData World information
     * @param array $availableTasks Available tasks
     * @return array Level data for each path position
     */
    private function generateLevels(array $pathStructure, array $worldData, array $availableTasks): array {
        $levels = [];
        $levelId = 1;

        foreach ($pathStructure as $structureKey => $pathInfo) {
            $position = $pathInfo['position'];
            $levelType = $pathInfo['level_type'];
            
            // Generate level based on type
            if ($levelType === 'boss') {
                $level = $this->generateBossLevel($worldData, $pathInfo, $levelId);
            } elseif ($levelType === 'mini_boss') {
                $level = $this->generateMiniBossLevel($worldData, $pathInfo, $levelId);
            } else {
                $level = $this->generateRegularLevel($worldData, $pathInfo, $availableTasks, $levelId);
            }
            
            $level['structure_key'] = $structureKey;
            $level['position'] = $position;
            $level['x'] = $pathInfo['x'];
            $level['y'] = $pathInfo['y'];
            
            $levels[$structureKey] = $level;
            $levelId++;
        }

        return $levels;
    }

    /**
     * Generate a regular level with task objectives
     * @param array $worldData World information
     * @param array $pathInfo Path position information
     * @param array $availableTasks Available tasks
     * @param int $levelId Unique level ID
     * @return array Level data
     */
    private function generateRegularLevel(array $worldData, array $pathInfo, array $availableTasks, int $levelId): array {
        $difficulty = $worldData['difficulty_modifier'];
        $taskFocus = $worldData['task_focus'];
        
        // Filter tasks based on world theme
        $relevantTasks = $this->filterTasksByTheme($availableTasks, $taskFocus);
        
        // Determine objective complexity based on position and parallel paths
        $isComplex = $pathInfo['parallel_paths'] > 2 || rand(1, 100) < (30 * $difficulty);
        
        if ($isComplex && count($relevantTasks) >= 2) {
            // Multiple objectives
            $objectiveCount = min(rand(2, 3), count($relevantTasks));
            $selectedTasks = array_rand($relevantTasks, $objectiveCount);
            if (!is_array($selectedTasks)) $selectedTasks = [$selectedTasks];
            
            $objectives = [];
            foreach ($selectedTasks as $taskIndex) {
                $objectives[] = [
                    'type' => 'complete_task',
                    'task_id' => $relevantTasks[$taskIndex]['id'],
                    'task_title' => $relevantTasks[$taskIndex]['title'],
                    'description' => "Complete: " . $relevantTasks[$taskIndex]['title']
                ];
            }
            
            $name = "Multi-Challenge Level";
            $description = "Complete " . count($objectives) . " " . $taskFocus . " tasks";
        } else {
            // Single objective
            if (empty($relevantTasks)) {
                $relevantTasks = $availableTasks; // Fallback to any tasks
            }
            
            $selectedTask = $relevantTasks[array_rand($relevantTasks)];
            $objectives = [[
                'type' => 'complete_task',
                'task_id' => $selectedTask['id'],
                'task_title' => $selectedTask['title'],
                'description' => "Complete: " . $selectedTask['title']
            ]];
            
            $name = $this->generateLevelName($taskFocus, $selectedTask);
            $description = "Complete the " . $taskFocus . " task";
        }

        return [
            'id' => $levelId,
            'name' => $name,
            'description' => $description,
            'type' => 'regular',
            'theme' => $worldData['theme'],
            'objectives' => $objectives,
            'reward_xp' => $this->worldGenerator->calculateLevelXP($worldData['world_number'], 'regular'),
            'status' => 'locked',
            'icon' => $this->getLevelIcon($taskFocus)
        ];
    }

    /**
     * Generate a mini-boss level
     * @param array $worldData World information
     * @param array $pathInfo Path position information
     * @param int $levelId Unique level ID
     * @return array Level data
     */
    private function generateMiniBossLevel(array $worldData, array $pathInfo, int $levelId): array {
        $miniBoss = $this->worldGenerator->generateMiniBoss($worldData['world_number'], $pathInfo['position']);
        
        return [
            'id' => $levelId,
            'name' => $miniBoss['name'],
            'description' => $miniBoss['description'],
            'type' => 'mini_boss',
            'theme' => $worldData['theme'],
            'objectives' => [[
                'type' => $miniBoss['objective_type'],
                'data' => $miniBoss['objective_data'],
                'description' => $miniBoss['description']
            ]],
            'reward_xp' => $miniBoss['reward_xp'],
            'status' => 'locked',
            'icon' => $miniBoss['icon']
        ];
    }

    /**
     * Generate a boss level
     * @param array $worldData World information
     * @param array $pathInfo Path position information
     * @param int $levelId Unique level ID
     * @return array Level data
     */
    private function generateBossLevel(array $worldData, array $pathInfo, int $levelId): array {
        $boss = $worldData['boss_definition'];
        
        return [
            'id' => $levelId,
            'name' => $boss['name'],
            'description' => $boss['description'],
            'type' => 'boss',
            'theme' => $worldData['theme'],
            'objectives' => [[
                'type' => $boss['objective_type'],
                'data' => $boss['objective_data'],
                'description' => $boss['description']
            ]],
            'reward_xp' => $boss['reward_xp'],
            'status' => 'locked',
            'icon' => $boss['icon'],
            'is_global' => true // Same for all players
        ];
    }

    /**
     * Generate connections between levels in the path
     * @param array $pathStructure Path structure data
     * @return array Connection data
     */
    private function generateConnections(array $pathStructure): array {
        $connections = [];
        $positions = [];
        
        // Group levels by position
        foreach ($pathStructure as $key => $pathInfo) {
            $pos = $pathInfo['position'];
            if (!isset($positions[$pos])) {
                $positions[$pos] = [];
            }
            $positions[$pos][] = $key;
        }
        
        $sortedPositions = array_keys($positions);
        sort($sortedPositions);
        
        for ($i = 0; $i < count($sortedPositions) - 1; $i++) {
            $currentPos = $sortedPositions[$i];
            $nextPos = $sortedPositions[$i + 1];
            
            $currentLevels = $positions[$currentPos];
            $nextLevels = $positions[$nextPos];
            
            // Connect each current level to next levels
            foreach ($currentLevels as $currentKey) {
                foreach ($nextLevels as $nextKey) {
                    $connections[] = [
                        'from' => $currentKey,
                        'to' => $nextKey,
                        'type' => 'path'
                    ];
                }
            }
        }
        
        return $connections;
    }

    /**
     * Filter tasks based on world theme
     * @param array $tasks All available tasks
     * @param string $theme World theme
     * @return array Filtered tasks
     */
    private function filterTasksByTheme(array $tasks, string $theme): array {
        if ($theme === 'mixed') {
            return $tasks; // Return all tasks for mixed theme
        }
        
        $filtered = [];
        foreach ($tasks as $task) {
            // Simple keyword matching based on theme
            $title = strtolower($task['title'] ?? '');
            $category = strtolower($task['category'] ?? '');
            
            $matches = false;
            switch ($theme) {
                case 'fitness':
                    $matches = strpos($title, 'gym') !== false || 
                              strpos($title, 'exercise') !== false ||
                              strpos($title, 'workout') !== false ||
                              strpos($title, 'run') !== false ||
                              strpos($category, 'health') !== false;
                    break;
                case 'work':
                    $matches = strpos($title, 'work') !== false ||
                              strpos($title, 'meeting') !== false ||
                              strpos($title, 'project') !== false ||
                              strpos($category, 'work') !== false;
                    break;
                case 'personal':
                    $matches = strpos($title, 'personal') !== false ||
                              strpos($category, 'personal') !== false ||
                              (!$matches && rand(1, 100) < 30); // Some random tasks count as personal
                    break;
                default:
                    $matches = rand(1, 100) < 50; // 50% chance for other themes
            }
            
            if ($matches) {
                $filtered[] = $task;
            }
        }
        
        // If no themed tasks found, return some random tasks
        if (empty($filtered) && !empty($tasks)) {
            $filtered = array_slice($tasks, 0, min(3, count($tasks)));
        }
        
        return $filtered;
    }

    /**
     * Generate a level name based on theme and task
     * @param string $theme World theme
     * @param array $task Task data
     * @return string Level name
     */
    private function generateLevelName(string $theme, array $task): string {
        $themes = [
            'personal' => ['Cozy Cottage', 'Village Square', 'Home Garden', 'Peaceful Path'],
            'work' => ['Office Tower', 'Business District', 'Conference Hall', 'Project Hub'],
            'fitness' => ['Mountain Trail', 'Athletic Field', 'Training Ground', 'Summit Challenge'],
            'creative' => ['Art Studio', 'Magic Workshop', 'Inspiration Grove', 'Creative Haven'],
            'routine' => ['Discipline Hall', 'Order Temple', 'Habit Haven', 'Structure Shrine'],
            'social' => ['Community Center', 'Connection Bridge', 'Social Square', 'Fellowship Hall'],
            'urgent' => ['Crisis Center', 'Emergency Ward', 'Urgent Alert', 'Priority Plaza']
        ];
        
        $nameOptions = $themes[$theme] ?? ['Challenge Level', 'Task Center', 'Goal Point', 'Mission Hub'];
        return $nameOptions[array_rand($nameOptions)];
    }

    /**
     * Get appropriate icon for level theme
     * @param string $theme World theme
     * @return string Icon emoji
     */
    private function getLevelIcon(string $theme): string {
        $icons = [
            'personal' => '🏠',
            'work' => '🏢',
            'fitness' => '🏃‍♂️',
            'creative' => '🎨',
            'routine' => '⚖️',
            'social' => '👥',
            'urgent' => '🚨'
        ];
        
        return $icons[$theme] ?? '🎯';
    }

    /**
     * Identify branch points in the path structure
     * @param array $pathStructure Path structure data
     * @return array Branch point information
     */
    private function identifyBranchPoints(array $pathStructure): array {
        $branchPoints = [];
        $positions = [];
        
        foreach ($pathStructure as $pathInfo) {
            $pos = $pathInfo['position'];
            if (!isset($positions[$pos])) {
                $positions[$pos] = 0;
            }
            $positions[$pos]++;
        }
        
        foreach ($positions as $pos => $count) {
            if ($count > 1) {
                $branchPoints[] = $pos;
            }
        }
        
        return $branchPoints;
    }

    /**
     * Identify convergence points in the path structure
     * @param array $pathStructure Path structure data
     * @return array Convergence point information
     */
    private function identifyConvergencePoints(array $pathStructure): array {
        $convergencePoints = [];
        $positions = [];
        
        foreach ($pathStructure as $pathInfo) {
            $pos = $pathInfo['position'];
            if (!isset($positions[$pos])) {
                $positions[$pos] = 0;
            }
            $positions[$pos]++;
        }
        
        $sortedPositions = array_keys($positions);
        sort($sortedPositions);
        
        for ($i = 1; $i < count($sortedPositions); $i++) {
            $currentPos = $sortedPositions[$i];
            $previousPos = $sortedPositions[$i - 1];
            
            // If current position has fewer paths than previous, it's a convergence
            if ($positions[$currentPos] < $positions[$previousPos]) {
                $convergencePoints[] = $currentPos;
            }
        }
        
        return $convergencePoints;
    }
}