<?php
require '/var/www/html/lib/base.php';

$db = \OC::$server->getDatabaseConnection();
$result = $db->executeQuery('SELECT COUNT(*) as count FROM oc_quest_char_items');
$row = $result->fetch();

echo "Total items: " . $row['count'] . PHP_EOL;

if ($row['count'] > 0) {
    $items = $db->executeQuery('SELECT item_key, item_name, item_type FROM oc_quest_char_items LIMIT 10');
    echo "\nFirst 10 items:\n";
    while ($item = $items->fetch()) {
        echo "  - " . $item['item_key'] . " (" . $item['item_type'] . "): " . $item['item_name'] . PHP_EOL;
    }
}
