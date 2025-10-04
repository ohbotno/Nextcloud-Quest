<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

declare(strict_types=1);

namespace OCA\NextcloudQuest\BackgroundJob;

use OCA\NextcloudQuest\Service\HealthService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IDBConnection;
use OCP\Notification\IManager as NotificationManager;
use Psr\Log\LoggerInterface;

/**
 * Background job to check for overdue tasks and apply health penalties
 * Runs daily at midnight
 */
class HealthPenaltyJob extends TimedJob {

    /** @var HealthService */
    private $healthService;

    /** @var IDBConnection */
    private $db;

    /** @var NotificationManager */
    private $notificationManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ITimeFactory $timeFactory,
        HealthService $healthService,
        IDBConnection $db,
        NotificationManager $notificationManager,
        LoggerInterface $logger
    ) {
        parent::__construct($timeFactory);
        $this->healthService = $healthService;
        $this->db = $db;
        $this->notificationManager = $notificationManager;
        $this->logger = $logger;

        // Run daily at midnight
        $this->setInterval(24 * 60 * 60);
        $this->setTimeSensitivity(self::TIME_SENSITIVE);
    }

    /**
     * Execute the health penalty check for all users
     */
    protected function run($argument): void {
        $this->logger->info('Starting daily health penalty check');

        try {
            $processedUsers = $this->processAllUsers();
            
            $this->logger->info('Health penalty check completed', [
                'users_processed' => $processedUsers['total'],
                'users_penalized' => $processedUsers['penalized'],
                'notifications_sent' => $processedUsers['notifications']
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Health penalty job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process health penalties for all users
     */
    private function processAllUsers(): array {
        $stats = [
            'total' => 0,
            'penalized' => 0,
            'notifications' => 0
        ];

        // Get all active users
        $qb = $this->db->getQueryBuilder();
        $qb->select('user_id', 'current_health')
           ->from('ncquest_users');

        $result = $qb->executeQuery();
        $users = $result->fetchAll();
        $result->closeCursor();

        foreach ($users as $user) {
            $userId = $user['user_id'];
            $currentHealth = (int)$user['current_health'];
            
            $stats['total']++;
            
            try {
                $processResult = $this->processUserHealthPenalty($userId, $currentHealth);
                
                if ($processResult['penalty_applied']) {
                    $stats['penalized']++;
                }
                
                if ($processResult['notification_sent']) {
                    $stats['notifications']++;
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to process health penalty for user', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $stats;
    }

    /**
     * Process health penalty for a single user
     */
    private function processUserHealthPenalty(string $userId, int $currentHealth): array {
        $result = [
            'penalty_applied' => false,
            'notification_sent' => false,
            'xp_penalty' => false
        ];

        // Check for overdue tasks
        $overdueCount = $this->healthService->getOverdueTaskCount($userId);
        
        if ($overdueCount > 0) {
            // Apply health penalty
            $newHealth = $this->healthService->applyHealthPenalty($userId, $overdueCount);
            $result['penalty_applied'] = true;
            
            $this->logger->info('Health penalty applied', [
                'user_id' => $userId,
                'overdue_tasks' => $overdueCount,
                'old_health' => $currentHealth,
                'new_health' => $newHealth
            ]);
            
            // Check if health reached zero
            if ($newHealth <= 0 && $currentHealth > 0) {
                // Apply XP penalty
                $xpPenalty = $this->healthService->applyXpPenalty($userId);
                if ($xpPenalty['penalty_applied']) {
                    $result['xp_penalty'] = true;
                    
                    // Send critical health notification
                    $this->sendCriticalHealthNotification($userId, $xpPenalty['xp_lost']);
                    $result['notification_sent'] = true;
                }
            } elseif ($newHealth < 20 && $currentHealth >= 20) {
                // Send low health warning
                $this->sendLowHealthNotification($userId, $newHealth, $overdueCount);
                $result['notification_sent'] = true;
            }
        } else {
            // No overdue tasks - regenerate health slightly
            if ($currentHealth < 100) {
                $this->healthService->regenerateHealth($userId, 5);
            }
        }

        return $result;
    }

    /**
     * Send notification for critically low health (health reached zero)
     */
    private function sendCriticalHealthNotification(string $userId, int $xpLost): void {
        try {
            $notification = $this->notificationManager->createNotification();
            $notification->setApp('quest')
                        ->setUser($userId)
                        ->setDateTime(new \DateTime())
                        ->setObject('health', 'critical')
                        ->setSubject('health_critical', [
                            'xp_lost' => $xpLost
                        ])
                        ->setMessage('Your health has reached zero due to overdue tasks! You lost {xp_lost} XP as a penalty.');

            $this->notificationManager->notify($notification);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send critical health notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send notification for low health warning
     */
    private function sendLowHealthNotification(string $userId, int $currentHealth, int $overdueCount): void {
        try {
            $notification = $this->notificationManager->createNotification();
            $notification->setApp('quest')
                        ->setUser($userId)
                        ->setDateTime(new \DateTime())
                        ->setObject('health', 'low')
                        ->setSubject('health_low', [
                            'health' => $currentHealth,
                            'overdue_count' => $overdueCount
                        ])
                        ->setMessage('Your health is running low ({health}/100)! You have {overdue_count} overdue tasks.');

            $this->notificationManager->notify($notification);
        } catch (\Exception $e) {
            $this->logger->error('Failed to send low health notification', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
}