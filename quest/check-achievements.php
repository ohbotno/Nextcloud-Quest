<?php
// Simple script to check achievements in database
require_once '/var/www/html/lib/base.php';

// Try both database files
$dbFiles = ['/var/www/html/data/nextcloud.db', '/var/www/html/data/owncloud.db'];

foreach ($dbFiles as $dbFile) {
    echo "Checking $dbFile:\n";
    if (!file_exists($dbFile)) {
        echo "  File doesn't exist\n\n";
        continue;
    }

    $db = new PDO('sqlite:' . $dbFile);

    // List all tables
    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%quest%' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "  No quest tables found\n\n";
        continue;
    }

    echo "  Quest-related tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";

        // Count rows
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "    Rows: $count\n";
    }
    echo "\n";
}
