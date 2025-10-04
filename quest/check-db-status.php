<?php
// Check database status
require_once '/var/www/html/lib/base.php';

$dbFile = '/var/www/html/data/owncloud.db';
$db = new PDO('sqlite:' . $dbFile);

// Check achievements
$stmt = $db->query("SELECT COUNT(*) FROM oc_ncquest_achievements WHERE user_id = 'admin'");
echo "Achievements unlocked: " . $stmt->fetchColumn() . "\n";

// Check history
$stmt = $db->query("SELECT COUNT(*) FROM oc_ncquest_history WHERE user_id = 'admin'");
echo "Tasks completed: " . $stmt->fetchColumn() . "\n";

// Check user data
$stmt = $db->query("SELECT total_tasks_completed FROM oc_ncquest_users WHERE user_id = 'admin'");
echo "Total tasks in user record: " . $stmt->fetchColumn() . "\n";

// Get recent achievements if any
$stmt = $db->query("SELECT achievement_key, unlocked_at FROM oc_ncquest_achievements WHERE user_id = 'admin' ORDER BY unlocked_at DESC LIMIT 5");
$achievements = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!empty($achievements)) {
    echo "\nRecent achievements:\n";
    foreach ($achievements as $ach) {
        echo "  - " . $ach['achievement_key'] . " at " . $ach['unlocked_at'] . "\n";
    }
}
