<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\BackgroundJob;

use OCA\NextcloudQuest\Service\StreakService;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Background job to maintain streaks and send reminders
 * Runs every hour to check for broken streaks and send notifications
 */
class StreakMaintenanceJob extends TimedJob {
    private StreakService $streakService;
    private LoggerInterface $logger;
    
    public function __construct(
        StreakService $streakService,
        LoggerInterface $logger
    ) {
        $this->streakService = $streakService;
        $this->logger = $logger;
        
        // Run every hour
        $this->setInterval(3600);
    }
    
    /**
     * @param array $argument
     */
    protected function run($argument): void {
        try {
            $this->logger->info('Starting streak maintenance job');
            
            // Check and reset broken streaks
            $brokenStreaks = $this->streakService->checkBrokenStreaks();
            
            if ($brokenStreaks > 0) {
                $this->logger->info('Reset broken streaks', ['count' => $brokenStreaks]);
            }
            
            // Send streak reminder notifications
            $this->sendStreakReminders();
            
            $this->logger->info('Streak maintenance job completed successfully');
            
        } catch (\Exception $e) {
            $this->logger->error('Streak maintenance job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Send reminder notifications to users with expiring streaks
     */
    private function sendStreakReminders(): void {
        try {
            // Get users with streaks expiring in the next 4 hours
            $expiringUsers = $this->streakService->getUsersWithExpiringStreaks(4);
            
            foreach ($expiringUsers as $userInfo) {
                $this->sendStreakReminderNotification(
                    $userInfo['user_id'],
                    $userInfo['current_streak'],
                    $userInfo['expires_at']
                );
            }
            
            if (count($expiringUsers) > 0) {
                $this->logger->info('Sent streak reminder notifications', [
                    'count' => count($expiringUsers)
                ]);
            }
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send streak reminders', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Send streak reminder notification to a user
     * 
     * @param string $userId
     * @param int $streak
     * @param string $expiresAt
     */
    private function sendStreakReminderNotification(string $userId, int $streak, string $expiresAt): void {
        try {
            $expiryDate = new \DateTime($expiresAt);
            $now = new \DateTime();
            $hoursLeft = max(1, ceil(($expiryDate->getTimestamp() - $now->getTimestamp()) / 3600));
            
            // Create notification using Nextcloud's notification system
            $notificationManager = \OC::$server->getNotificationManager();
            
            $notification = $notificationManager->createNotification();
            $notification->setApp('nextcloudquest')
                ->setUser($userId)
                ->setDateTime($now)
                ->setObject('streak_reminder', (string)$streak)
                ->setSubject('streak_reminder', [
                    'streak' => $streak,
                    'hours_left' => $hoursLeft
                ])
                ->setMessage('streak_reminder_message', [
                    'streak' => $streak,
                    'hours_left' => $hoursLeft
                ])
                ->setIcon('streak-warning');
            
            $notificationManager->notify($notification);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send streak reminder notification', [
                'userId' => $userId,
                'streak' => $streak,
                'error' => $e->getMessage()
            ]);
        }
    }
}