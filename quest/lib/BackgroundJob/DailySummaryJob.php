<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\BackgroundJob;

use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\Notification\IManager as INotificationManager;

/**
 * Background job to send daily summary notifications
 * Runs once per day at midnight to send users their daily progress
 */
class DailySummaryJob extends TimedJob {
    private HistoryMapper $historyMapper;
    private QuestMapper $questMapper;
    private INotificationManager $notificationManager;
    private IConfig $config;
    private LoggerInterface $logger;
    
    public function __construct(
        HistoryMapper $historyMapper,
        QuestMapper $questMapper,
        INotificationManager $notificationManager,
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->historyMapper = $historyMapper;
        $this->questMapper = $questMapper;
        $this->notificationManager = $notificationManager;
        $this->config = $config;
        $this->logger = $logger;
        
        // Run once per day at midnight
        $this->setInterval(24 * 3600);
    }
    
    /**
     * @param array $argument
     */
    protected function run($argument): void {
        try {
            $this->logger->info('Starting daily summary job');
            
            // Get all users who have quest data and want daily summaries
            $users = $this->getUsersForDailySummary();
            
            $summariesSent = 0;
            
            foreach ($users as $user) {
                try {
                    $this->sendDailySummary($user['user_id']);
                    $summariesSent++;
                } catch (\Exception $e) {
                    $this->logger->error('Failed to send daily summary for user', [
                        'userId' => $user['user_id'],
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->logger->info('Daily summary job completed', [
                'summaries_sent' => $summariesSent,
                'total_users' => count($users)
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Daily summary job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Get users who should receive daily summaries
     * 
     * @return array
     */
    private function getUsersForDailySummary(): array {
        try {
            // Get all users with quest data
            $allQuests = $this->questMapper->findAll();
            $users = [];
            
            foreach ($allQuests as $quest) {
                $userId = $quest->getUserId();
                
                // Check if user wants daily summaries
                $wantsSummary = $this->config->getUserValue(
                    $userId, 
                    'nextcloudquest', 
                    'notify_daily_summary', 
                    'false'
                ) === 'true';
                
                if ($wantsSummary) {
                    $users[] = ['user_id' => $userId];
                }
            }
            
            return $users;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get users for daily summary', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Send daily summary notification to a user
     * 
     * @param string $userId
     */
    private function sendDailySummary(string $userId): void {
        $yesterday = new \DateTime();
        $yesterday->modify('-1 day');
        $startOfDay = clone $yesterday;
        $startOfDay->setTime(0, 0, 0);
        $endOfDay = clone $yesterday;
        $endOfDay->setTime(23, 59, 59);
        
        // Get yesterday's completion data
        $history = $this->historyMapper->findByDateRange($userId, $startOfDay, $endOfDay);
        
        $tasksCompleted = count($history);
        $xpEarned = array_sum(array_map(function($entry) {
            return $entry->getXpEarned();
        }, $history));
        
        // Get user's current stats
        try {
            $quest = $this->questMapper->findByUserId($userId);
            $currentLevel = $quest->getLevel();
            $currentStreak = $quest->getCurrentStreak();
        } catch (\Exception $e) {
            $currentLevel = 1;
            $currentStreak = 0;
        }
        
        // Create notification
        $notification = $this->notificationManager->createNotification();
        $notification->setApp('nextcloudquest')
            ->setUser($userId)
            ->setDateTime(new \DateTime())
            ->setObject('daily_summary', $yesterday->format('Y-m-d'))
            ->setSubject('daily_summary', [
                'tasks_completed' => $tasksCompleted,
                'xp_earned' => $xpEarned,
                'level' => $currentLevel,
                'streak' => $currentStreak
            ])
            ->setMessage('daily_summary_message', [
                'tasks_completed' => $tasksCompleted,
                'xp_earned' => $xpEarned,
                'level' => $currentLevel,
                'streak' => $currentStreak,
                'date' => $yesterday->format('F j, Y')
            ])
            ->setIcon('daily-summary');
        
        $this->notificationManager->notify($notification);
        
        $this->logger->debug('Sent daily summary notification', [
            'userId' => $userId,
            'tasksCompleted' => $tasksCompleted,
            'xpEarned' => $xpEarned
        ]);
    }
}