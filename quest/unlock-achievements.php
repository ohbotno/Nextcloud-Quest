<?php
// Script to retroactively unlock achievements
require_once '/var/www/html/lib/base.php';

use OCA\NextcloudQuest\Service\AchievementService;
use OCA\NextcloudQuest\Db\AchievementMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use OCP\Notification\IManager as INotificationManager;

$userId = 'admin';

// Get services from DI container
$container = \OCP\Server::get(\OCP\IServerContainer::class);
$db = $container->get(IDBConnection::class);
$logger = $container->get(LoggerInterface::class);
$notificationManager = $container->get(INotificationManager::class);

// Create mappers
$achievementMapper = new AchievementMapper($db);
$historyMapper = new HistoryMapper($db);
$questMapper = new QuestMapper($db, $logger);

// Create achievement service
$achievementService = new AchievementService(
    $achievementMapper,
    $historyMapper,
    $notificationManager,
    $logger,
    $questMapper
);

echo "Checking and unlocking achievements for user: $userId\n";
$unlocked = $achievementService->checkAndUnlockAchievements($userId);

echo "Unlocked " . count($unlocked) . " achievements:\n";
foreach ($unlocked as $achievement) {
    echo "- " . $achievement['name'] . "\n";
}
