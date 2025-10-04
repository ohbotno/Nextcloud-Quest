#!/usr/bin/env php
<?php
/**
 * Standalone script to check and fix age system
 * Run with: docker exec -u www-data ncdev php /var/www/html/apps-extra/quest/check-and-fix-age.php [username]
 */

// Bootstrap Nextcloud
require_once '/var/www/html/lib/base.php';

use OCA\NextcloudQuest\Db\CharacterAgeMapper;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Service\CharacterService;

try {
    $server = \OC::$server;

    // Get services
    $ageMapper = $server->get(CharacterAgeMapper::class);
    $questMapper = $server->get(QuestMapper::class);
    $characterService = $server->get(CharacterService::class);

    echo "=== Age System Check ===\n\n";

    // Check ages in database
    echo "Ages in database:\n";
    echo str_repeat("-", 60) . "\n";

    $ages = $ageMapper->findAllActive();

    if (empty($ages)) {
        echo "❌ NO AGES FOUND! Table is empty.\n";
        echo "Run migration: php occ migrations:execute quest Version1014Date20250930130000\n";
        exit(1);
    }

    foreach ($ages as $age) {
        $maxLevel = $age->getMaxLevel() ? $age->getMaxLevel() : '∞';
        printf("%-3s %-20s Levels %-4d - %-4s\n",
            $age->getAgeIcon(),
            $age->getAgeName(),
            $age->getMinLevel(),
            $maxLevel
        );
    }

    echo "\n✅ Found " . count($ages) . " ages\n\n";

    // Get username from command line or find level 11 users
    $username = $argv[1] ?? null;

    if ($username) {
        echo "Checking user: $username\n";
        echo str_repeat("-", 60) . "\n";

        try {
            $quest = $questMapper->findByUserId($username);
            $level = (int)$quest->getLevel();
            $lifetimeXp = (int)$quest->getLifetimeXp();
            $currentAgeKey = $quest->getCharacterCurrentAge();
            if ($currentAgeKey === '') {
                $currentAgeKey = 'stone'; // Default to stone if empty
            }

            echo "Level: $level\n";
            echo "Lifetime XP: $lifetimeXp\n";
            echo "Current Age (stored): " . ($currentAgeKey ?? 'NULL') . "\n\n";

            // Check what age they should be
            $shouldBeAge = $ageMapper->getAgeForLevel($level);

            if ($shouldBeAge) {
                printf("Should be: %s %s (Levels %d-%s)\n",
                    $shouldBeAge->getAgeIcon(),
                    $shouldBeAge->getAgeName(),
                    $shouldBeAge->getMinLevel(),
                    $shouldBeAge->getMaxLevel() ? $shouldBeAge->getMaxLevel() : '∞'
                );

                if ($currentAgeKey !== $shouldBeAge->getAgeKey()) {
                    echo "\n⚠️  Age mismatch detected!\n";
                    echo "Fixing...\n";

                    try {
                        $newAge = $characterService->checkAgeProgression($username, $level, $lifetimeXp);
                    } catch (\Exception $e) {
                        // If checkAgeProgression fails due to missing column, just update directly
                        echo "  (Using direct update due to: " . $e->getMessage() . ")\n";
                        $quest->setCharacterCurrentAge($shouldBeAge->getAgeKey());
                        $questMapper->update($quest);
                        $newAge = null;
                    }

                    if ($newAge) {
                        printf("\n✅ Age updated to: %s %s\n",
                            $newAge->getAgeIcon(),
                            $newAge->getAgeName()
                        );
                    } else {
                        echo "\n✅ Age already correct (already reached this age before)\n";
                        // But update the current_age field anyway
                        try {
                            $quest->setCharacterCurrentAge($shouldBeAge->getAgeKey());
                            $questMapper->update($quest);
                            echo "✅ Updated character_current_age field\n";
                        } catch (\Exception $e) {
                            echo "⚠️  Could not update via mapper: " . $e->getMessage() . "\n";
                            echo "   Trying direct SQL update...\n";
                            $db = \OC::$server->getDatabaseConnection();
                            $db->executeStatement(
                                'UPDATE oc_ncquest_users SET character_current_age = ? WHERE user_id = ?',
                                [$shouldBeAge->getAgeKey(), $username]
                            );
                            echo "✅ Updated via SQL\n";
                        }
                    }
                } else {
                    echo "\n✅ Age is correct!\n";
                }
            } else {
                echo "\n❌ Could not determine age for level $level\n";
            }

        } catch (\Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            exit(1);
        }
    } else {
        // Find all users at level 10+
        echo "Finding users at level 10+ (Bronze Age or higher):\n";
        echo str_repeat("-", 60) . "\n";

        $db = $server->getDatabaseConnection();
        $qb = $db->getQueryBuilder();
        $qb->select('user_id', 'level', 'character_current_age')
            ->from('ncquest_users')
            ->where($qb->expr()->gte('level', $qb->createNamedParameter(10)))
            ->orderBy('level', 'DESC');

        $result = $qb->executeQuery();
        $users = $result->fetchAll();
        $result->closeCursor();

        if (empty($users)) {
            echo "No users found at level 10+\n";
        } else {
            foreach ($users as $user) {
                $shouldBeAge = $ageMapper->getAgeForLevel($user['level']);
                $ageIcon = $shouldBeAge ? $shouldBeAge->getAgeIcon() : '❓';
                $ageName = $shouldBeAge ? $shouldBeAge->getAgeName() : 'Unknown';
                $currentAgeDisplay = $user['character_current_age'] ?? 'NULL';

                printf("%-15s Level %-3d  Current: %-10s  Should be: %s %s\n",
                    $user['user_id'],
                    $user['level'],
                    $currentAgeDisplay,
                    $ageIcon,
                    $ageName
                );
            }

            echo "\nTo fix a specific user, run:\n";
            echo "  php check-and-fix-age.php [username]\n";
        }
    }

    echo "\n=== End ===\n";

} catch (\Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
