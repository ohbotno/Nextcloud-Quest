#!/usr/bin/env php
<?php
/**
 * Script to check and fix age system data
 */

require_once '/var/www/html/lib/base.php';

try {
    $db = \OC::$server->getDatabaseConnection();

    echo "=== Checking Age Data ===\n\n";

    // Check ages in database
    echo "Ages in oc_ncquest_character_ages:\n";
    echo "-----------------------------------\n";
    $qb = $db->getQueryBuilder();
    $qb->select('age_key', 'age_name', 'min_level', 'max_level')
       ->from('oc_ncquest_character_ages')
       ->orderBy('min_level', 'ASC');

    try {
        $result = $qb->executeQuery();
        $ages = $result->fetchAll();
        $result->closeCursor();

        if (empty($ages)) {
            echo "❌ NO AGES FOUND! Table is empty.\n\n";
        } else {
            foreach ($ages as $age) {
                $maxLevel = $age['max_level'] ?? 'NULL';
                echo sprintf("%-12s | %-20s | %-4s | %-4s\n",
                    $age['age_key'],
                    $age['age_name'],
                    $age['min_level'],
                    $maxLevel
                );
            }
            echo "\n✅ Found " . count($ages) . " ages\n\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error querying ages: " . $e->getMessage() . "\n\n";
    }

    // Find level 11 users
    echo "Users at level 11:\n";
    echo "------------------\n";
    $qb = $db->getQueryBuilder();
    $qb->select('user_id', 'level', 'lifetime_xp', 'character_current_age')
       ->from('oc_ncquest_users')
       ->where($qb->expr()->eq('level', $qb->createNamedParameter(11)));

    try {
        $result = $qb->executeQuery();
        $users = $result->fetchAll();
        $result->closeCursor();

        if (empty($users)) {
            echo "No users found at level 11\n\n";
        } else {
            foreach ($users as $user) {
                echo sprintf("User: %s | Level: %d | XP: %d | Current Age: %s\n",
                    $user['user_id'],
                    $user['level'],
                    $user['lifetime_xp'],
                    $user['character_current_age'] ?? 'NULL'
                );
            }
            echo "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error querying users: " . $e->getMessage() . "\n\n";
    }

    echo "=== End of Report ===\n";

} catch (\Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
