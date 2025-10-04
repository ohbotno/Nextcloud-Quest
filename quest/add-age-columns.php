#!/usr/bin/env php
<?php
/**
 * Add missing columns to age table
 */

require_once '/var/www/html/lib/base.php';

try {
    $schema = \OC::$server->getDatabaseConnection()->createSchema();

    $tableName = '*PREFIX*ncquest_character_ages';
    if (!$schema->hasTable($tableName)) {
        echo "❌ Table ncquest_character_ages does not exist!\n";
        exit(1);
    }

    $table = $schema->getTable($tableName);
    $changes = [];

    // Add min_level
    if (!$table->hasColumn('min_level')) {
        $table->addColumn('min_level', 'integer', [
            'notnull' => true,
            'default' => 1,
        ]);
        $changes[] = 'min_level';
        echo "  ➕ Adding column: min_level\n";
    } else {
        echo "  ✅ Column min_level already exists\n";
    }

    // Add max_level
    if (!$table->hasColumn('max_level')) {
        $table->addColumn('max_level', 'integer', [
            'notnull' => false,
        ]);
        $changes[] = 'max_level';
        echo "  ➕ Adding column: max_level\n";
    } else {
        echo "  ✅ Column max_level already exists\n";
    }

    // Add age_description
    if (!$table->hasColumn('age_description')) {
        $table->addColumn('age_description', 'text', [
            'notnull' => false,
        ]);
        $changes[] = 'age_description';
        echo "  ➕ Adding column: age_description\n";
    } else {
        echo "  ✅ Column age_description already exists\n";
    }

    // Add age_color
    if (!$table->hasColumn('age_color')) {
        $table->addColumn('age_color', 'string', [
            'notnull' => false,
            'length' => 7,
        ]);
        $changes[] = 'age_color';
        echo "  ➕ Adding column: age_color\n";
    } else {
        echo "  ✅ Column age_color already exists\n";
    }

    // Add age_icon
    if (!$table->hasColumn('age_icon')) {
        $table->addColumn('age_icon', 'string', [
            'notnull' => false,
            'length' => 10,
        ]);
        $changes[] = 'age_icon';
        echo "  ➕ Adding column: age_icon\n";
    } else {
        echo "  ✅ Column age_icon already exists\n";
    }

    // Add created_at
    if (!$table->hasColumn('created_at')) {
        $table->addColumn('created_at', 'datetime', [
            'notnull' => false,
        ]);
        $changes[] = 'created_at';
        echo "  ➕ Adding column: created_at\n";
    } else {
        echo "  ✅ Column created_at already exists\n";
    }

    if (!empty($changes)) {
        echo "\n🔧 Applying changes...\n";
        \OC::$server->getDatabaseConnection()->migrateToSchema($schema);
        echo "✅ Schema updated successfully!\n";
        echo "   Added columns: " . implode(', ', $changes) . "\n";
    } else {
        echo "\n✅ All columns already exist, no changes needed.\n";
    }

} catch (\Throwable $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
