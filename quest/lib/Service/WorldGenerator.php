<?php

declare(strict_types=1);

namespace OCA\Quest\Service;

use OCP\IDBConnection;

/**
 * World Generator Service - Creates themed worlds with boss levels for the Adventure Path System
 * Generates 8 themed worlds following Mario-style progression with mini-bosses and final bosses
 */
class WorldGenerator {

    /** @var IDBConnection */
    private $db;

    /** @var array World definitions with themes and characteristics */
    private const WORLD_DEFINITIONS = [
        1 => [
            'name' => 'Grassland Village',
            'theme' => 'personal',
            'description' => 'A peaceful village where personal tasks await completion',
            'color_primary' => '#4CAF50',
            'color_secondary' => '#81C784',
            'task_focus' => 'personal',
            'difficulty_modifier' => 1.0,
            'icon' => '🏘️'
        ],
        2 => [
            'name' => 'Desert Pyramid',
            'theme' => 'work',
            'description' => 'Ancient pyramids hiding work challenges in the burning sands',
            'color_primary' => '#FF9800',
            'color_secondary' => '#FFB74D',
            'task_focus' => 'work',
            'difficulty_modifier' => 1.2,
            'icon' => '🏜️'
        ],
        3 => [
            'name' => 'Mountain Peak',
            'theme' => 'fitness',
            'description' => 'Rocky mountains where fitness goals reach new heights',
            'color_primary' => '#795548',
            'color_secondary' => '#A1887F',
            'task_focus' => 'fitness',
            'difficulty_modifier' => 1.3,
            'icon' => '🏔️'
        ],
        4 => [
            'name' => 'Enchanted Forest',
            'theme' => 'creative',
            'description' => 'Magical woods where creativity blooms and ideas take flight',
            'color_primary' => '#9C27B0',
            'color_secondary' => '#BA68C8',
            'task_focus' => 'creative',
            'difficulty_modifier' => 1.4,
            'icon' => '🌲'
        ],
        5 => [
            'name' => 'Ice Castle',
            'theme' => 'discipline',
            'description' => 'Frozen fortress testing discipline and routine mastery',
            'color_primary' => '#2196F3',
            'color_secondary' => '#64B5F6',
            'task_focus' => 'routine',
            'difficulty_modifier' => 1.5,
            'icon' => '🏰'
        ],
        6 => [
            'name' => 'Sky Kingdom',
            'theme' => 'social',
            'description' => 'Floating islands where social connections bridge the clouds',
            'color_primary' => '#03DAC6',
            'color_secondary' => '#4DD0E1',
            'task_focus' => 'social',
            'difficulty_modifier' => 1.6,
            'icon' => '☁️'
        ],
        7 => [
            'name' => 'Volcano Depths',
            'theme' => 'urgent',
            'description' => 'Fiery caverns where urgent tasks burn with importance',
            'color_primary' => '#F44336',
            'color_secondary' => '#EF5350',
            'task_focus' => 'urgent',
            'difficulty_modifier' => 1.8,
            'icon' => '🌋'
        ],
        8 => [
            'name' => 'Shadow Realm',
            'theme' => 'master',
            'description' => 'The ultimate challenge where task mastery is proven',
            'color_primary' => '#424242',
            'color_secondary' => '#616161',
            'task_focus' => 'mixed',
            'difficulty_modifier' => 2.0,
            'icon' => '🌑'
        ]
    ];

    /** @var array Boss level definitions for each world */
    private const BOSS_DEFINITIONS = [
        1 => [
            'name' => 'Village Elder Challenge',
            'description' => 'Complete 10 personal tasks in 5 days',
            'objective_type' => 'quantity_time',
            'objective_data' => ['count' => 10, 'category' => 'personal', 'days' => 5],
            'reward_xp' => 500,
            'icon' => '👨‍🌾'
        ],
        2 => [
            'name' => 'Pharaoh\'s Trial',
            'description' => 'Finish all work tasks and complete 5 high-priority items',
            'objective_type' => 'mixed_challenge',
            'objective_data' => ['clear_category' => 'work', 'high_priority' => 5],
            'reward_xp' => 750,
            'icon' => '👑'
        ],
        3 => [
            'name' => 'Peak Conqueror',
            'description' => 'Achieve 10-day fitness streak',
            'objective_type' => 'streak',
            'objective_data' => ['days' => 10, 'category' => 'fitness'],
            'reward_xp' => 1000,
            'icon' => '🏆'
        ],
        4 => [
            'name' => 'Forest Guardian',
            'description' => 'Complete 15 creative tasks and maintain 7-day streak',
            'objective_type' => 'quantity_streak',
            'objective_data' => ['count' => 15, 'category' => 'creative', 'streak_days' => 7],
            'reward_xp' => 1250,
            'icon' => '🧚‍♀️'
        ],
        5 => [
            'name' => 'Ice King\'s Challenge',
            'description' => 'Complete daily routines for 14 consecutive days',
            'objective_type' => 'routine_streak',
            'objective_data' => ['days' => 14, 'category' => 'routine'],
            'reward_xp' => 1500,
            'icon' => '❄️'
        ],
        6 => [
            'name' => 'Sky Lord\'s Test',
            'description' => 'Complete 20 social tasks across different categories in 7 days',
            'objective_type' => 'diverse_quantity',
            'objective_data' => ['count' => 20, 'category' => 'social', 'days' => 7, 'min_categories' => 3],
            'reward_xp' => 1750,
            'icon' => '👨‍💼'
        ],
        7 => [
            'name' => 'Volcano Demon',
            'description' => 'Clear all overdue tasks and maintain 0 overdue for 5 days',
            'objective_type' => 'overdue_master',
            'objective_data' => ['clear_overdue' => true, 'maintain_days' => 5],
            'reward_xp' => 2000,
            'icon' => '👹'
        ],
        8 => [
            'name' => 'Shadow Master',
            'description' => 'Complete 25 tasks across all categories in 7 days',
            'objective_type' => 'master_challenge',
            'objective_data' => ['count' => 25, 'days' => 7, 'all_categories' => true],
            'reward_xp' => 3000,
            'icon' => '🌟'
        ]
    ];

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Generate a new world for the given world number
     * @param int $worldNumber World number (1-8)
     * @param string $userId User ID
     * @return array World data including levels and paths
     */
    public function generateWorld(int $worldNumber, string $userId): array {
        if ($worldNumber < 1 || $worldNumber > 8) {
            throw new \InvalidArgumentException('World number must be between 1 and 8');
        }

        $worldDef = self::WORLD_DEFINITIONS[$worldNumber];
        $bossDef = self::BOSS_DEFINITIONS[$worldNumber];

        // Generate world structure (8-12 levels)
        $levelCount = rand(8, 12);
        $miniBossPosition = rand(4, $levelCount - 2); // Mini-boss not at start or end
        
        $world = [
            'world_number' => $worldNumber,
            'name' => $worldDef['name'],
            'theme' => $worldDef['theme'],
            'description' => $worldDef['description'],
            'color_primary' => $worldDef['color_primary'],
            'color_secondary' => $worldDef['color_secondary'],
            'task_focus' => $worldDef['task_focus'],
            'difficulty_modifier' => $worldDef['difficulty_modifier'],
            'icon' => $worldDef['icon'],
            'level_count' => $levelCount,
            'mini_boss_position' => $miniBossPosition,
            'boss_definition' => $bossDef,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'locked' // Will be 'unlocked', 'in_progress', 'completed'
        ];

        // Unlock World 1 by default
        if ($worldNumber === 1) {
            $world['status'] = 'unlocked';
        }

        return $world;
    }

    /**
     * Get all world definitions
     * @return array All world definitions
     */
    public function getWorldDefinitions(): array {
        return self::WORLD_DEFINITIONS;
    }

    /**
     * Get boss definition for a world
     * @param int $worldNumber World number
     * @return array Boss definition
     */
    public function getBossDefinition(int $worldNumber): array {
        if (!isset(self::BOSS_DEFINITIONS[$worldNumber])) {
            throw new \InvalidArgumentException("No boss definition for world $worldNumber");
        }
        return self::BOSS_DEFINITIONS[$worldNumber];
    }

    /**
     * Get mini-boss challenges appropriate for a world and position
     * @param int $worldNumber World number
     * @param int $position Position in world
     * @return array Mini-boss challenge
     */
    public function generateMiniBoss(int $worldNumber, int $position): array {
        $worldDef = self::WORLD_DEFINITIONS[$worldNumber];
        $difficulty = $worldDef['difficulty_modifier'];
        
        // Scale mini-boss difficulty based on world and position
        $baseCount = max(3, round(3 * $difficulty));
        $positionMultiplier = ($position / 10) + 0.5; // Scale with position in world
        $finalCount = max(3, round($baseCount * $positionMultiplier));

        $miniBosses = [
            [
                'name' => 'Guardian Sentinel',
                'description' => "Complete $finalCount tasks today",
                'objective_type' => 'daily_quantity',
                'objective_data' => ['count' => $finalCount],
                'reward_xp' => round(100 * $difficulty),
                'icon' => '🛡️'
            ],
            [
                'name' => 'Category Master',
                'description' => "Finish tasks from $finalCount different categories",
                'objective_type' => 'category_diversity',
                'objective_data' => ['category_count' => min($finalCount, 4)],
                'reward_xp' => round(120 * $difficulty),
                'icon' => '📋'
            ],
            [
                'name' => 'Priority Crusher',
                'description' => 'Complete all high-priority tasks',
                'objective_type' => 'priority_clear',
                'objective_data' => ['priority' => 'high'],
                'reward_xp' => round(150 * $difficulty),
                'icon' => '⚡'
            ]
        ];

        return $miniBosses[array_rand($miniBosses)];
    }

    /**
     * Check if a world should be unlocked based on previous world completion
     * @param int $worldNumber World to check
     * @param string $userId User ID
     * @return bool Whether world should be unlocked
     */
    public function shouldUnlockWorld(int $worldNumber, string $userId): bool {
        if ($worldNumber === 1) {
            return true; // World 1 is always unlocked
        }

        // Check if previous world is completed
        $qb = $this->db->getQueryBuilder();
        $qb->select('status')
           ->from('*PREFIX*adventure_player_progress')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber - 1)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row && $row['status'] === 'completed';
    }

    /**
     * Calculate XP reward for a level based on world difficulty and level type
     * @param int $worldNumber World number
     * @param string $levelType Type of level (regular, mini_boss, boss)
     * @return int XP reward
     */
    public function calculateLevelXP(int $worldNumber, string $levelType): int {
        $worldDef = self::WORLD_DEFINITIONS[$worldNumber];
        $baseDifficulty = $worldDef['difficulty_modifier'];

        $baseXP = [
            'regular' => 50,
            'mini_boss' => 150,
            'boss' => 500
        ];

        return round($baseXP[$levelType] * $baseDifficulty);
    }
}