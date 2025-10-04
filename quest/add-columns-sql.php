#!/usr/bin/env php
<?php
/**
 * Add missing columns using raw SQL
 */

require_once '/var/www/html/lib/base.php';

try {
    $db = \OC::$server->getDatabaseConnection();

    echo "Adding columns to oc_ncquest_character_ages...\n\n";

    $columns = [
        ['name' => 'min_level', 'sql' => 'ALTER TABLE oc_ncquest_character_ages ADD COLUMN min_level INTEGER NOT NULL DEFAULT 1'],
        ['name' => 'max_level', 'sql' => 'ALTER TABLE oc_ncquest_character_ages ADD COLUMN max_level INTEGER'],
        ['name' => 'age_description', 'sql' => 'ALTER TABLE oc_ncquest_character_ages ADD COLUMN age_description TEXT'],
        ['name' => 'age_color', 'sql' => 'ALTER TABLE oc_ncquest_character_ages ADD COLUMN age_color VARCHAR(7)'],
        ['name' => 'age_icon', 'sql' => 'ALTER TABLE oc_ncquest_character_ages ADD COLUMN age_icon VARCHAR(10)'],
        ['name' => 'created_at', 'sql' => 'ALTER TABLE oc_ncquest_character_ages ADD COLUMN created_at DATETIME'],
    ];

    foreach ($columns as $col) {
        try {
            $db->executeStatement($col['sql']);
            echo "  ✅ Added column: {$col['name']}\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false ||
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "  ⏭️  Column {$col['name']} already exists\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Done!\n";

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
