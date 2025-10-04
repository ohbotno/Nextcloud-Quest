#!/usr/bin/env php
<?php
/**
 * Insert age data into database
 */

require_once '/var/www/html/lib/base.php';

try {
    $db = \OC::$server->getDatabaseConnection();

    echo "Inserting age data...\n\n";

    $ages = [
        ['age_key' => 'stone', 'age_name' => 'Stone Age', 'min_level' => 1, 'max_level' => 9],
        ['age_key' => 'bronze', 'age_name' => 'Bronze Age', 'min_level' => 10, 'max_level' => 19],
        ['age_key' => 'iron', 'age_name' => 'Iron Age', 'min_level' => 20, 'max_level' => 29],
        ['age_key' => 'medieval', 'age_name' => 'Medieval Age', 'min_level' => 30, 'max_level' => 39],
        ['age_key' => 'renaissance', 'age_name' => 'Renaissance', 'min_level' => 40, 'max_level' => 49],
        ['age_key' => 'industrial', 'age_name' => 'Industrial Age', 'min_level' => 50, 'max_level' => 59],
        ['age_key' => 'modern', 'age_name' => 'Modern Age', 'min_level' => 60, 'max_level' => 74],
        ['age_key' => 'digital', 'age_name' => 'Digital Age', 'min_level' => 75, 'max_level' => 99],
        ['age_key' => 'space', 'age_name' => 'Space Age', 'min_level' => 100, 'max_level' => null],
    ];

    $now = new \DateTime();

    foreach ($ages as $age) {
        // Check if exists
        $qb = $db->getQueryBuilder();
        $qb->select('id')
            ->from('ncquest_character_ages')
            ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($age['age_key'])));

        $result = $qb->executeQuery();
        $exists = $result->fetch();
        $result->closeCursor();

        if ($exists) {
            echo "  ⏭️  Skipping {$age['age_name']} (already exists)\n";
            continue;
        }

        // Insert
        $qb = $db->getQueryBuilder();
        $values = [
            'age_key' => $qb->createNamedParameter($age['age_key']),
            'age_name' => $qb->createNamedParameter($age['age_name']),
            'min_level' => $qb->createNamedParameter($age['min_level'], \PDO::PARAM_INT),
            'age_description' => $qb->createNamedParameter(''),
            'age_color' => $qb->createNamedParameter('#000000'),
            'age_icon' => $qb->createNamedParameter(''),
            'is_active' => $qb->createNamedParameter(1, \PDO::PARAM_INT),
            'created_at' => $qb->createNamedParameter($now, 'datetime'),
        ];

        if ($age['max_level'] !== null) {
            $values['max_level'] = $qb->createNamedParameter($age['max_level'], \PDO::PARAM_INT);
        }

        $qb->insert('ncquest_character_ages')->values($values);
        $qb->executeStatement();

        echo "  ✅ Inserted {$age['age_name']} (Levels {$age['min_level']}-";
        echo $age['max_level'] ? $age['max_level'] : '∞';
        echo ")\n";
    }

    echo "\n✅ Done!\n";

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
