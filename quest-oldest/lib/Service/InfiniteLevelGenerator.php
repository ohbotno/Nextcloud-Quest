<?php

declare(strict_types=1);

namespace OCA\NextcloudQuest\Service;

use OCP\IDBConnection;

/**
 * Infinite Level Generator Service
 * Generates levels procedurally for endless adventure progression
 */
class InfiniteLevelGenerator {

    /** @var IDBConnection */
    private $db;

    /** @var array Level type patterns for variety */
    private const LEVEL_PATTERNS = [
        'regular' => 0.60,      // 60% chance
        'challenge' => 0.20,    // 20% chance
        'mini_boss' => 0.15,    // 15% chance
        'boss' => 0.05          // 5% chance
    ];

    /** @var array Objective templates for different level types */
    private const OBJECTIVE_TEMPLATES = [
        'quantity' => [
            'Complete {count} tasks',
            'Finish {count} items from your lists',
            'Clear {count} tasks today',
            'Complete {count} productive actions'
        ],
        'priority' => [
            'Complete all high priority tasks',
            'Finish {count} urgent items',
            'Clear priority tasks from your list',
            'Handle {count} important tasks'
        ],
        'streak' => [
            'Maintain a {days}-day streak',
            'Complete tasks for {days} consecutive days',
            'Keep your momentum for {days} days',
            'Build a {days}-day habit'
        ],
        'category' => [
            'Complete tasks from {count} different categories',
            'Diversify with {count} task types',
            'Balance {count} areas of your life',
            'Work across {count} different lists'
        ],
        'time' => [
            'Complete {count} tasks within {hours} hours',
            'Finish today\'s goals by {time}',
            'Clear {count} items before deadline',
            'Race against time: {count} tasks in {hours}h'
        ],
        'focus' => [
            'Complete {count} {category} tasks',
            'Focus on {category}: finish {count} items',
            'Master {category} with {count} completions',
            'Specialize in {category}: {count} tasks'
        ]
    ];

    /** @var array Categories for focused objectives */
    private const CATEGORIES = [
        'personal', 'work', 'fitness', 'creative', 
        'learning', 'social', 'home', 'finance'
    ];

    /** @var array Boss names for milestone levels */
    private const BOSS_NAMES = [
        'Guardian', 'Sentinel', 'Overlord', 'Warden',
        'Champion', 'Titan', 'Colossus', 'Nemesis',
        'Destroyer', 'Conqueror', 'Master', 'Legend'
    ];

    /** @var array Boss prefixes based on world theme */
    private const BOSS_PREFIXES = [
        1 => ['Village', 'Peaceful', 'Green'],
        2 => ['Desert', 'Sandy', 'Scorching'],
        3 => ['Mountain', 'Rocky', 'Peak'],
        4 => ['Forest', 'Enchanted', 'Mystical'],
        5 => ['Frozen', 'Ice', 'Crystal'],
        6 => ['Sky', 'Cloud', 'Celestial'],
        7 => ['Volcanic', 'Fiery', 'Molten'],
        8 => ['Shadow', 'Dark', 'Void']
    ];

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Generate a level based on world and progression
     * @param int $worldNumber Current world (1-8)
     * @param int $levelNumber Level number in world (1+)
     * @param int $totalCompleted Total levels completed by player
     * @param string $userId User ID for personalization
     * @return array Generated level data
     */
    public function generateLevel(
        int $worldNumber, 
        int $levelNumber, 
        int $totalCompleted, 
        string $userId
    ): array {
        // Determine level type based on patterns and progression
        $levelType = $this->determineLevelType($levelNumber, $totalCompleted);
        
        // Calculate difficulty scaling
        $difficulty = $this->calculateDifficulty($worldNumber, $levelNumber, $totalCompleted);
        
        // Calculate position and layout data
        $position = $this->calculatePosition($levelNumber);
        
        // Override level type with layout type if specified
        if (isset($position['type'])) {
            $layoutType = $position['type'];
            if ($layoutType === 'side_quest') {
                $levelType = 'challenge'; // Side quests are challenge levels
            } elseif ($layoutType === 'mini_boss' && $levelNumber % 5 === 0) {
                $levelType = 'mini_boss';
            } elseif ($layoutType === 'boss' && $levelNumber % 10 === 0) {
                $levelType = 'boss';
            }
        }
        
        // Generate level metadata
        $level = [
            'world_number' => $worldNumber,
            'level_number' => $levelNumber,
            'level_id' => "w{$worldNumber}_l{$levelNumber}",
            'type' => $levelType,
            'layout_type' => $position['type'] ?? 'main',
            'name' => $this->generateLevelName($levelType, $worldNumber, $levelNumber),
            'description' => $this->generateDescription($levelType, $difficulty),
            'difficulty' => $difficulty,
            'xp_reward' => $this->calculateXPReward($levelType, $difficulty),
            'x' => $position['x'],
            'y' => $position['y'],
            'position' => $position, // Keep for compatibility
            'connections' => $position['connections'] ?? [], // Available paths from this level
            'rewards' => $position['rewards'] ?? [], // Special rewards for side quests
            'status' => 'unlocked', // Frontend expects 'status' not 'unlocked'
            'unlocked' => true,
            'completed' => false,
            'objectives' => $this->generateObjectives($levelType, $difficulty, $worldNumber)
        ];

        // Add special properties for boss levels
        if ($levelType === 'boss' || $levelType === 'mini_boss') {
            $level['boss_data'] = $this->generateBossData($levelType, $worldNumber, $levelNumber);
        }

        return $level;
    }

    /**
     * Determine level type based on progression patterns
     */
    private function determineLevelType(int $levelNumber, int $totalCompleted): string {
        // Every 10th level is a boss
        if ($levelNumber % 10 === 0) {
            return 'boss';
        }
        
        // Every 5th level is a mini-boss
        if ($levelNumber % 5 === 0) {
            return 'mini_boss';
        }
        
        // Increase challenge frequency as player progresses
        $challengeChance = min(0.3, 0.2 + ($totalCompleted * 0.001));
        
        // Random selection for regular levels
        $rand = mt_rand(1, 100) / 100;
        if ($rand < $challengeChance) {
            return 'challenge';
        }
        
        return 'regular';
    }

    /**
     * Calculate difficulty based on progression
     */
    private function calculateDifficulty(int $worldNumber, int $levelNumber, int $totalCompleted): array {
        // Base difficulty from world
        $worldDifficulty = 1.0 + (($worldNumber - 1) * 0.2);
        
        // Level progression within world
        $levelProgression = 1.0 + (($levelNumber - 1) * 0.05);
        
        // Global progression bonus
        $globalProgression = 1.0 + ($totalCompleted * 0.01);
        
        // Combined multiplier
        $multiplier = $worldDifficulty * $levelProgression * sqrt($globalProgression);
        
        return [
            'multiplier' => round($multiplier, 2),
            'world_factor' => $worldDifficulty,
            'level_factor' => $levelProgression,
            'global_factor' => round($globalProgression, 2),
            'rating' => $this->getDifficultyRating($multiplier)
        ];
    }

    /**
     * Get difficulty rating label
     */
    private function getDifficultyRating(float $multiplier): string {
        if ($multiplier < 1.5) return 'Easy';
        if ($multiplier < 2.0) return 'Normal';
        if ($multiplier < 3.0) return 'Hard';
        if ($multiplier < 4.0) return 'Expert';
        return 'Master';
    }

    /**
     * Generate appropriate level name
     */
    private function generateLevelName(string $levelType, int $worldNumber, int $levelNumber): string {
        switch ($levelType) {
            case 'boss':
                $prefix = self::BOSS_PREFIXES[$worldNumber][array_rand(self::BOSS_PREFIXES[$worldNumber])];
                $name = self::BOSS_NAMES[array_rand(self::BOSS_NAMES)];
                return "$prefix $name";
                
            case 'mini_boss':
                return "Guardian of Level $levelNumber";
                
            case 'challenge':
                $challenges = ['Trial', 'Test', 'Challenge', 'Gauntlet', 'Ordeal'];
                return $challenges[array_rand($challenges)] . " $levelNumber";
                
            default:
                return "Level $levelNumber";
        }
    }

    /**
     * Generate level description
     */
    private function generateDescription(string $levelType, array $difficulty): string {
        $rating = $difficulty['rating'];
        
        switch ($levelType) {
            case 'boss':
                return "A powerful boss awaits! This $rating challenge will test your productivity mastery.";
            case 'mini_boss':
                return "Face the guardian in this $rating encounter. Prove your worth to continue!";
            case 'challenge':
                return "A special $rating challenge level. Extra rewards await those who succeed!";
            default:
                return "A $rating level on your adventure path. Complete objectives to progress!";
        }
    }

    /**
     * Calculate XP reward based on type and difficulty
     */
    private function calculateXPReward(string $levelType, array $difficulty): int {
        $baseXP = [
            'regular' => 50,
            'challenge' => 75,
            'mini_boss' => 150,
            'boss' => 300
        ];
        
        $base = $baseXP[$levelType] ?? 50;
        return (int)round($base * $difficulty['multiplier']);
    }

    /**
     * Calculate level position for map display with branching paths
     */
    private function calculatePosition(int $levelNumber): array {
        // Define the world layout structure
        $worldLayout = $this->generateWorldLayout();
        
        // Get position from the layout
        if (isset($worldLayout[$levelNumber])) {
            return $worldLayout[$levelNumber];
        }
        
        // Fallback for levels beyond the predefined layout
        return $this->generateExtendedPosition($levelNumber);
    }
    
    /**
     * Generate a branching world layout with single start/end and multiple paths
     */
    private function generateWorldLayout(): array {
        $layout = [];
        $baseX = 200;
        $baseY = 100;
        $levelSpacing = 150;
        $pathSpacing = 100;
        
        // Start point (Level 1)
        $layout[1] = [
            'x' => $baseX,
            'y' => $baseY + 200,
            'type' => 'start',
            'connections' => [2, 3] // Can go to level 2 or 3
        ];
        
        // Early branching - two initial paths
        $layout[2] = [
            'x' => $baseX + $levelSpacing,
            'y' => $baseY + 100, // Upper path
            'type' => 'main',
            'connections' => [4, 5]
        ];
        
        $layout[3] = [
            'x' => $baseX + $levelSpacing,
            'y' => $baseY + 300, // Lower path
            'type' => 'main',
            'connections' => [5, 6]
        ];
        
        // Mid-game convergence and new branches
        $layout[4] = [
            'x' => $baseX + $levelSpacing * 2,
            'y' => $baseY + 50, // High path
            'type' => 'main',
            'connections' => [7]
        ];
        
        $layout[5] = [
            'x' => $baseX + $levelSpacing * 2,
            'y' => $baseY + 200, // Center convergence
            'type' => 'main',
            'connections' => [7, 8, 9] // Major hub
        ];
        
        $layout[6] = [
            'x' => $baseX + $levelSpacing * 2,
            'y' => $baseY + 350, // Low path
            'type' => 'main',
            'connections' => [8]
        ];
        
        // Side quest detour (optional)
        $layout[7] = [
            'x' => $baseX + $levelSpacing * 2.5,
            'y' => $baseY - 20, // Detour above main path
            'type' => 'side_quest',
            'connections' => [9], // Rejoins main path
            'rewards' => ['item_chest', 'bonus_xp']
        ];
        
        $layout[8] = [
            'x' => $baseX + $levelSpacing * 3,
            'y' => $baseY + 300, // Lower branch continues
            'type' => 'main',
            'connections' => [10]
        ];
        
        $layout[9] = [
            'x' => $baseX + $levelSpacing * 3,
            'y' => $baseY + 150, // Main path continues
            'type' => 'main',
            'connections' => [10, 11]
        ];
        
        // Boss approach - paths converge
        $layout[10] = [
            'x' => $baseX + $levelSpacing * 4,
            'y' => $baseY + 225, // Pre-boss convergence
            'type' => 'mini_boss',
            'connections' => [12] // Must defeat to continue
        ];
        
        // Another side quest branch
        $layout[11] = [
            'x' => $baseX + $levelSpacing * 3.5,
            'y' => $baseY + 50, // Secret upper path
            'type' => 'side_quest',
            'connections' => [12],
            'rewards' => ['rare_item', 'skill_point']
        ];
        
        $layout[12] = [
            'x' => $baseX + $levelSpacing * 5,
            'y' => $baseY + 200, // Final approach
            'type' => 'main',
            'connections' => [13, 14] // Multiple routes to boss
        ];
        
        $layout[13] = [
            'x' => $baseX + $levelSpacing * 5.5,
            'y' => $baseY + 100, // Upper route to boss
            'type' => 'challenge',
            'connections' => [15]
        ];
        
        $layout[14] = [
            'x' => $baseX + $levelSpacing * 5.5,
            'y' => $baseY + 300, // Lower route to boss
            'type' => 'challenge',
            'connections' => [15]
        ];
        
        // Boss level (Level 15)
        $layout[15] = [
            'x' => $baseX + $levelSpacing * 6,
            'y' => $baseY + 200, // End point
            'type' => 'boss',
            'connections' => [] // End of world
        ];
        
        return $layout;
    }
    
    /**
     * Generate extended positions for levels beyond the predefined layout
     */
    private function generateExtendedPosition(int $levelNumber): array {
        // For levels beyond 15, create a simple extension
        $baseX = 200 + (6 * 150); // Continue from boss position
        $extensionLevel = $levelNumber - 15;
        
        // Create a simple winding pattern for extended levels
        $row = floor($extensionLevel / 3);
        $col = $extensionLevel % 3;
        
        return [
            'x' => $baseX + ($col * 150) + ($row * 50),
            'y' => 100 + 200 + ($row * 120) + (($col % 2) * 60),
            'type' => 'extended',
            'connections' => [$levelNumber + 1] // Simple linear progression
        ];
    }

    /**
     * Generate objectives based on level type and difficulty
     */
    private function generateObjectives(string $levelType, array $difficulty, int $worldNumber): array {
        $objectives = [];
        $diffMultiplier = $difficulty['multiplier'];
        
        switch ($levelType) {
            case 'boss':
                // Boss levels have multiple challenging objectives
                $objectives[] = $this->createObjective('quantity', 
                    (int)round(5 * $diffMultiplier), $worldNumber);
                $objectives[] = $this->createObjective('category', 
                    min(5, 2 + (int)floor($diffMultiplier)), $worldNumber);
                $objectives[] = $this->createObjective('time', 
                    (int)round(3 * $diffMultiplier), $worldNumber);
                break;
                
            case 'mini_boss':
                // Mini-boss has 2 objectives
                $objectives[] = $this->createObjective('quantity', 
                    (int)round(3 * $diffMultiplier), $worldNumber);
                $objectives[] = $this->createObjective('priority', 
                    max(1, (int)floor($diffMultiplier)), $worldNumber);
                break;
                
            case 'challenge':
                // Challenge levels have unique objectives
                $types = ['streak', 'time', 'focus', 'category'];
                $chosenType = $types[array_rand($types)];
                $objectives[] = $this->createObjective($chosenType, 
                    (int)round(2 * $diffMultiplier), $worldNumber);
                break;
                
            default:
                // Regular levels have simple objectives
                $objectives[] = $this->createObjective('quantity', 
                    max(2, (int)round(2 * sqrt($diffMultiplier))), $worldNumber);
                break;
        }
        
        return $objectives;
    }

    /**
     * Create a specific objective
     */
    private function createObjective(string $type, int $value, int $worldNumber): array {
        $templates = self::OBJECTIVE_TEMPLATES[$type] ?? self::OBJECTIVE_TEMPLATES['quantity'];
        $template = $templates[array_rand($templates)];
        
        // Replace placeholders
        $description = str_replace('{count}', (string)$value, $template);
        $description = str_replace('{days}', (string)max(1, (int)($value / 2)), $description);
        $description = str_replace('{hours}', (string)max(2, $value * 2), $description);
        $description = str_replace('{time}', date('g:ia', strtotime("+{$value} hours")), $description);
        
        // Add category if needed
        if (strpos($template, '{category}') !== false) {
            $category = self::CATEGORIES[($worldNumber - 1) % count(self::CATEGORIES)];
            $description = str_replace('{category}', $category, $description);
        }
        
        return [
            'type' => $type,
            'description' => $description,
            'target_value' => $value,
            'current_value' => 0,
            'completed' => false,
            'reward_xp' => (int)round(10 * sqrt($value))
        ];
    }

    /**
     * Generate boss-specific data
     */
    private function generateBossData(string $levelType, int $worldNumber, int $levelNumber): array {
        $prefix = self::BOSS_PREFIXES[$worldNumber][array_rand(self::BOSS_PREFIXES[$worldNumber])];
        
        return [
            'health' => $levelType === 'boss' ? 100 : 50,
            'attack_pattern' => $this->generateAttackPattern($levelNumber),
            'weakness' => self::CATEGORIES[array_rand(self::CATEGORIES)],
            'dialogue' => $this->generateBossDialogue($prefix, $levelType),
            'defeat_bonus' => $levelType === 'boss' ? 500 : 200
        ];
    }

    /**
     * Generate attack pattern for bosses
     */
    private function generateAttackPattern(int $levelNumber): array {
        $patterns = [
            ['type' => 'deadline_pressure', 'frequency' => 'daily'],
            ['type' => 'category_lock', 'frequency' => 'periodic'],
            ['type' => 'priority_surge', 'frequency' => 'random'],
            ['type' => 'streak_breaker', 'frequency' => 'targeted']
        ];
        
        // More complex patterns at higher levels
        $numPatterns = min(3, 1 + (int)floor($levelNumber / 20));
        return array_slice($patterns, 0, $numPatterns);
    }

    /**
     * Generate boss dialogue
     */
    private function generateBossDialogue(string $prefix, string $levelType): array {
        if ($levelType === 'boss') {
            return [
                'intro' => "I am the $prefix Lord! Your productivity ends here!",
                'taunt' => "You think completing tasks makes you strong?",
                'defeat' => "Impossible! Your determination is... remarkable."
            ];
        } else {
            return [
                'intro' => "None shall pass without proving their worth!",
                'taunt' => "Show me your productivity power!",
                'defeat' => "Well done. You may continue your journey."
            ];
        }
    }

    /**
     * Get the next level for a player
     */
    public function getNextLevel(string $userId, int $worldNumber): array {
        // Get player's current progression
        $progression = $this->getPlayerProgression($userId, $worldNumber);
        
        $nextLevelNumber = $progression['current_level'] + 1;
        $totalCompleted = $progression['total_completed'];
        
        return $this->generateLevel($worldNumber, $nextLevelNumber, $totalCompleted, $userId);
    }

    /**
     * Get player progression stats
     */
    private function getPlayerProgression(string $userId, int $worldNumber): array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('current_level', 'levels_completed')
               ->from('*PREFIX*quest_adv_progress')
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));
            
            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();
            
            if ($row) {
                // Get total completed across all worlds
                $qb2 = $this->db->getQueryBuilder();
                $qb2->select($qb2->func()->sum('levels_completed'))
                    ->from('*PREFIX*quest_adv_progress')
                    ->where($qb2->expr()->eq('user_id', $qb2->createNamedParameter($userId)));
                
                $result2 = $qb2->executeQuery();
                $total = $result2->fetchOne();
                $result2->closeCursor();
                
                return [
                    'current_level' => (int)$row['current_level'],
                    'levels_completed' => (int)$row['levels_completed'],
                    'total_completed' => (int)($total ?? 0)
                ];
            }
        } catch (\Exception $e) {
        }
        
        // Default for new players
        return [
            'current_level' => 0,
            'levels_completed' => 0,
            'total_completed' => 0
        ];
    }

    /**
     * Generate a batch of upcoming levels for preview
     */
    public function generateLevelBatch(
        string $userId, 
        int $worldNumber, 
        int $startLevel, 
        int $count = 10
    ): array {
        $progression = $this->getPlayerProgression($userId, $worldNumber);
        $levels = [];
        
        for ($i = 0; $i < $count; $i++) {
            $levelNumber = $startLevel + $i;
            $totalCompleted = $progression['total_completed'];
            
            $levels[] = $this->generateLevel(
                $worldNumber, 
                $levelNumber, 
                $totalCompleted, 
                $userId
            );
        }
        
        return $levels;
    }
}