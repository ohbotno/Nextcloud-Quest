<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

/**
 * Adventure Theme Service
 * Provides age-themed content for adventure areas (enemies, rewards, visual themes)
 */
class AdventureThemeService {

    /**
     * Get theme configuration for an age
     */
    public function getThemeForAge(string $ageKey): array {
        $themes = $this->getAllThemes();
        return $themes[$ageKey] ?? $themes['stone']; // Default to stone age
    }

    /**
     * Get all age themes
     */
    private function getAllThemes(): array {
        return [
            'stone' => [
                'age_key' => 'stone',
                'age_name' => 'Stone Age',
                'color_primary' => '#8b7355',
                'color_secondary' => '#a0826d',
                'enemies' => [
                    ['name' => 'Wild Wolf', 'health' => 30, 'attack' => 5, 'xp' => 15],
                    ['name' => 'Cave Bear', 'health' => 50, 'attack' => 8, 'xp' => 25],
                    ['name' => 'Sabertooth Cat', 'health' => 40, 'attack' => 7, 'xp' => 20],
                    ['name' => 'Giant Boar', 'health' => 45, 'attack' => 6, 'xp' => 22],
                ],
                'boss' => [
                    'name' => 'Mammoth Alpha',
                    'health' => 120,
                    'attack' => 12,
                    'xp' => 100,
                    'description' => 'A massive woolly mammoth that rules the frozen plains'
                ],
                'treasure_pool' => ['stone_spear', 'stone_axe', 'stone_fur_decorated', 'stone_shell_bracelet'],
                'event_themes' => ['cave_paintings', 'hunting_grounds', 'ritual_site', 'ancient_burial'],
            ],
            'bronze' => [
                'age_key' => 'bronze',
                'age_name' => 'Bronze Age',
                'color_primary' => '#cd7f32',
                'color_secondary' => '#daa520',
                'enemies' => [
                    ['name' => 'Raider Scout', 'health' => 50, 'attack' => 10, 'xp' => 30],
                    ['name' => 'Bronze Warrior', 'health' => 60, 'attack' => 12, 'xp' => 35],
                    ['name' => 'Desert Nomad', 'health' => 55, 'attack' => 11, 'xp' => 32],
                    ['name' => 'Temple Guard', 'health' => 65, 'attack' => 13, 'xp' => 38],
                ],
                'boss' => [
                    'name' => 'Bronze Chieftain',
                    'health' => 180,
                    'attack' => 18,
                    'xp' => 150,
                    'description' => 'A legendary warlord clad in gleaming bronze armor'
                ],
                'treasure_pool' => ['bronze_sword', 'bronze_armor', 'bronze_amulet', 'bronze_dagger'],
                'event_themes' => ['ancient_forge', 'merchant_caravan', 'sacred_temple', 'tribal_gathering'],
            ],
            'iron' => [
                'age_key' => 'iron',
                'age_name' => 'Iron Age',
                'color_primary' => '#71706e',
                'color_secondary' => '#a9a9a9',
                'enemies' => [
                    ['name' => 'Iron Legionnaire', 'health' => 70, 'attack' => 15, 'xp' => 45],
                    ['name' => 'Barbarian Raider', 'health' => 80, 'attack' => 17, 'xp' => 50],
                    ['name' => 'Shield Maiden', 'health' => 75, 'attack' => 16, 'xp' => 48],
                    ['name' => 'Celtic Warrior', 'health' => 78, 'attack' => 16, 'xp' => 49],
                ],
                'boss' => [
                    'name' => 'Iron Warlord',
                    'health' => 250,
                    'attack' => 25,
                    'xp' => 200,
                    'description' => 'A fearsome conqueror wielding an iron longsword'
                ],
                'treasure_pool' => ['iron_longsword', 'iron_armor', 'iron_helmet', 'iron_battle_axe'],
                'event_themes' => ['iron_mine', 'war_camp', 'fortified_village', 'battlefield'],
            ],
            'medieval' => [
                'age_key' => 'medieval',
                'age_name' => 'Medieval Age',
                'color_primary' => '#8b4513',
                'color_secondary' => '#cd853f',
                'enemies' => [
                    ['name' => 'Knight Errant', 'health' => 90, 'attack' => 20, 'xp' => 60],
                    ['name' => 'Crossbowman', 'health' => 85, 'attack' => 19, 'xp' => 58],
                    ['name' => 'Mounted Knight', 'health' => 95, 'attack' => 22, 'xp' => 65],
                    ['name' => 'Tower Guard', 'health' => 100, 'attack' => 21, 'xp' => 63],
                ],
                'boss' => [
                    'name' => 'Dragon Knight',
                    'health' => 320,
                    'attack' => 32,
                    'xp' => 250,
                    'description' => 'A legendary knight who has slain many dragons'
                ],
                'treasure_pool' => ['medieval_mace', 'medieval_plate_armor', 'medieval_crown', 'medieval_shield'],
                'event_themes' => ['castle_siege', 'tournament_grounds', 'monastery', 'royal_court'],
            ],
            'renaissance' => [
                'age_key' => 'renaissance',
                'age_name' => 'Renaissance',
                'color_primary' => '#daa520',
                'color_secondary' => '#ffd700',
                'enemies' => [
                    ['name' => 'Musketeer', 'health' => 110, 'attack' => 25, 'xp' => 75],
                    ['name' => 'Rapier Duelist', 'health' => 105, 'attack' => 24, 'xp' => 72],
                    ['name' => 'Mercenary Captain', 'health' => 115, 'attack' => 26, 'xp' => 78],
                    ['name' => 'Naval Officer', 'health' => 120, 'attack' => 27, 'xp' => 80],
                ],
                'boss' => [
                    'name' => 'Grand Master',
                    'health' => 400,
                    'attack' => 40,
                    'xp' => 300,
                    'description' => 'A master strategist and undefeated duelist'
                ],
                'treasure_pool' => ['renaissance_rapier', 'renaissance_doublet', 'renaissance_hat', 'renaissance_pistol'],
                'event_themes' => ['art_gallery', 'opera_house', 'printing_press', 'navigation_guild'],
            ],
            'industrial' => [
                'age_key' => 'industrial',
                'age_name' => 'Industrial Age',
                'color_primary' => '#696969',
                'color_secondary' => '#808080',
                'enemies' => [
                    ['name' => 'Factory Guard', 'health' => 130, 'attack' => 30, 'xp' => 90],
                    ['name' => 'Steam Automaton', 'health' => 140, 'attack' => 32, 'xp' => 95],
                    ['name' => 'Railway Bandit', 'health' => 135, 'attack' => 31, 'xp' => 92],
                    ['name' => 'Coal Baron Enforcer', 'health' => 145, 'attack' => 33, 'xp' => 98],
                ],
                'boss' => [
                    'name' => 'Iron Titan',
                    'health' => 500,
                    'attack' => 50,
                    'xp' => 400,
                    'description' => 'A massive steam-powered war machine'
                ],
                'treasure_pool' => ['industrial_wrench', 'industrial_goggles', 'industrial_coat', 'industrial_gear'],
                'event_themes' => ['steam_factory', 'railway_station', 'mining_operation', 'inventors_lab'],
            ],
            'modern' => [
                'age_key' => 'modern',
                'age_name' => 'Modern Age',
                'color_primary' => '#4169e1',
                'color_secondary' => '#6495ed',
                'enemies' => [
                    ['name' => 'Corporate Security', 'health' => 150, 'attack' => 35, 'xp' => 110],
                    ['name' => 'Spec Ops Soldier', 'health' => 160, 'attack' => 38, 'xp' => 115],
                    ['name' => 'Cyber Hacker', 'health' => 155, 'attack' => 36, 'xp' => 112],
                    ['name' => 'Elite Agent', 'health' => 165, 'attack' => 40, 'xp' => 120],
                ],
                'boss' => [
                    'name' => 'Megacorp CEO',
                    'health' => 600,
                    'attack' => 60,
                    'xp' => 500,
                    'description' => 'A ruthless corporate overlord with unlimited resources'
                ],
                'treasure_pool' => ['modern_suit', 'modern_briefcase', 'modern_sunglasses', 'modern_phone'],
                'event_themes' => ['skyscraper', 'research_lab', 'stock_exchange', 'airport'],
            ],
            'digital' => [
                'age_key' => 'digital',
                'age_name' => 'Digital Age',
                'color_primary' => '#00ced1',
                'color_secondary' => '#00ffff',
                'enemies' => [
                    ['name' => 'AI Sentinel', 'health' => 180, 'attack' => 45, 'xp' => 135],
                    ['name' => 'Virtual Warrior', 'health' => 190, 'attack' => 48, 'xp' => 140],
                    ['name' => 'Data Ghost', 'health' => 185, 'attack' => 46, 'xp' => 137],
                    ['name' => 'Cybernetic Hunter', 'health' => 195, 'attack' => 50, 'xp' => 145],
                ],
                'boss' => [
                    'name' => 'System Administrator',
                    'health' => 750,
                    'attack' => 75,
                    'xp' => 600,
                    'description' => 'A sentient AI that controls the entire network'
                ],
                'treasure_pool' => ['digital_visor', 'digital_gloves', 'digital_implant', 'digital_neural_link'],
                'event_themes' => ['server_room', 'virtual_reality_hub', 'data_center', 'quantum_lab'],
            ],
            'space' => [
                'age_key' => 'space',
                'age_name' => 'Space Age',
                'color_primary' => '#9370db',
                'color_secondary' => '#ba55d3',
                'enemies' => [
                    ['name' => 'Alien Scout', 'health' => 220, 'attack' => 55, 'xp' => 165],
                    ['name' => 'Space Pirate', 'health' => 230, 'attack' => 58, 'xp' => 170],
                    ['name' => 'Plasma Soldier', 'health' => 240, 'attack' => 60, 'xp' => 175],
                    ['name' => 'Void Wanderer', 'health' => 250, 'attack' => 62, 'xp' => 180],
                ],
                'boss' => [
                    'name' => 'Galactic Overlord',
                    'health' => 1000,
                    'attack' => 100,
                    'xp' => 800,
                    'description' => 'An ancient cosmic being from beyond the stars'
                ],
                'treasure_pool' => ['space_laser', 'space_suit', 'space_helmet', 'space_jetpack'],
                'event_themes' => ['space_station', 'alien_ruins', 'asteroid_field', 'wormhole'],
            ],
        ];
    }

    /**
     * Get random enemy for age
     */
    public function getRandomEnemy(string $ageKey): array {
        $theme = $this->getThemeForAge($ageKey);
        $enemies = $theme['enemies'];
        return $enemies[array_rand($enemies)];
    }

    /**
     * Get boss for age
     */
    public function getBoss(string $ageKey): array {
        $theme = $this->getThemeForAge($ageKey);
        return $theme['boss'];
    }

    /**
     * Get random treasure reward for age
     */
    public function getRandomTreasure(string $ageKey): array {
        $theme = $this->getThemeForAge($ageKey);
        $treasurePool = $theme['treasure_pool'];
        $itemKey = $treasurePool[array_rand($treasurePool)];

        return [
            'type' => 'equipment',
            'item_key' => $itemKey,
            'gold' => mt_rand(50, 150), // Additional gold bonus
        ];
    }

    /**
     * Get random event for age
     */
    public function getRandomEvent(string $ageKey): array {
        $theme = $this->getThemeForAge($ageKey);
        $eventThemes = $theme['event_themes'];
        $eventTheme = $eventThemes[array_rand($eventThemes)];

        // Event types: buff, gold, xp, health
        $eventTypes = [
            [
                'type' => 'gold_find',
                'description' => "You discover hidden treasure at the {$eventTheme}!",
                'reward' => ['gold' => mt_rand(100, 300)],
            ],
            [
                'type' => 'xp_bonus',
                'description' => "You gain valuable knowledge from the {$eventTheme}.",
                'reward' => ['xp' => mt_rand(50, 150)],
            ],
            [
                'type' => 'health_restore',
                'description' => "You rest and recover at the {$eventTheme}.",
                'reward' => ['health' => mt_rand(20, 50)],
            ],
            [
                'type' => 'mystery_reward',
                'description' => "Something mysterious happens at the {$eventTheme}...",
                'reward' => [
                    'gold' => mt_rand(50, 100),
                    'xp' => mt_rand(25, 75),
                ],
            ],
        ];

        return $eventTypes[array_rand($eventTypes)];
    }

    /**
     * Get age key for current player level
     */
    public function getAgeKeyForLevel(int $level): string {
        if ($level < 10) return 'stone';
        if ($level < 20) return 'bronze';
        if ($level < 30) return 'iron';
        if ($level < 40) return 'medieval';
        if ($level < 50) return 'renaissance';
        if ($level < 60) return 'industrial';
        if ($level < 75) return 'modern';
        if ($level < 100) return 'digital';
        return 'space';
    }

    /**
     * Get theme colors for frontend rendering
     */
    public function getThemeColors(string $ageKey): array {
        $theme = $this->getThemeForAge($ageKey);
        return [
            'primary' => $theme['color_primary'],
            'secondary' => $theme['color_secondary'],
        ];
    }
}
