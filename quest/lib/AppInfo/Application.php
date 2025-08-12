<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\AppInfo;

use OCA\NextcloudQuest\BackgroundJob\DailySummaryJob;
use OCA\NextcloudQuest\BackgroundJob\StreakMaintenanceJob;
use OCA\NextcloudQuest\Controller\SimpleQuestController;
use OCA\NextcloudQuest\Controller\AdventureWorldController;
use OCA\NextcloudQuest\Controller\TestController;
use OCA\NextcloudQuest\Service\WorldGenerator;
use OCA\NextcloudQuest\Service\PathGenerator;
use OCA\NextcloudQuest\Service\LevelObjective;
use OCA\NextcloudQuest\Event\TaskCompletedEvent;
use OCA\NextcloudQuest\Integration\TasksApiIntegration;
use OCA\NextcloudQuest\Listener\TaskCompletionListener;
use OCA\NextcloudQuest\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class Application extends App implements IBootstrap {
    public const APP_ID = 'quest';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        // Register notification provider
        $context->registerNotifierService(Notifier::class);
        
        // Register SimpleQuestController
        $context->registerService(SimpleQuestController::class, function($c) {
            return new SimpleQuestController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCP\IDBConnection::class)
            );
        });
        
        // Register TestController
        $context->registerService(TestController::class, function($c) {
            return new TestController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class)
            );
        });
        
        // TODO: Register Tasks API integration service later
        
        // Register event listeners
        $context->registerEventListener(TaskCompletedEvent::class, TaskCompletionListener::class);
        
        // Register event listeners for external apps (Tasks)
        $context->registerEventListener('OCA\\Tasks\\Event\\TaskCompletedEvent', TaskCompletionListener::class);
        $context->registerEventListener('tasks.task.completed', TaskCompletionListener::class);
        
        // Register Adventure services
        $context->registerService(WorldGenerator::class, function($c) {
            return new WorldGenerator(
                $c->get(\OCP\IDBConnection::class)
            );
        });
        
        $context->registerService(PathGenerator::class, function($c) {
            return new PathGenerator(
                $c->get(WorldGenerator::class)
            );
        });
        
        $context->registerService(LevelObjective::class, function($c) {
            return new LevelObjective(
                $c->get(\OCP\IDBConnection::class)
            );
        });
        
        $context->registerService(TasksApiIntegration::class, function($c) {
            return new TasksApiIntegration(
                $c->get(\OCP\IDBConnection::class),
                $c->get(\Psr\Log\LoggerInterface::class),
                $c->get(\OCP\IUserSession::class)
            );
        });
        
        $context->registerService(\OCA\NextcloudQuest\Service\XPService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\XPService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(\OCA\NextcloudQuest\Db\QuestMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\QuestMapper($c->get(\OCP\IDBConnection::class));
        });
        
        $context->registerService(\OCA\NextcloudQuest\Db\HistoryMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\HistoryMapper($c->get(\OCP\IDBConnection::class));
        });
        
        $context->registerService(\OCA\NextcloudQuest\Service\StreakService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\StreakService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        // Register Adventure controller
        $context->registerService(AdventureWorldController::class, function($c) {
            return new AdventureWorldController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IDBConnection::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(WorldGenerator::class),
                $c->get(PathGenerator::class),
                $c->get(LevelObjective::class),
                $c->get(\OCA\NextcloudQuest\Integration\TasksApiIntegration::class),
                $c->get(\OCA\NextcloudQuest\Service\XPService::class)
            );
        });

        // Register background jobs
        $context->registerService(StreakMaintenanceJob::class, function($c) {
            return new StreakMaintenanceJob(
                $c->get(\OCA\NextcloudQuest\Service\StreakService::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(DailySummaryJob::class, function($c) {
            return new DailySummaryJob(
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCP\Notification\IManager::class),
                $c->get(\OCP\IConfig::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
    }

    public function boot(IBootContext $context): void {
        $serverContainer = $context->getServerContainer();
        
        // Register background jobs with the job list
        $jobList = $serverContainer->get(\OCP\BackgroundJob\IJobList::class);
        
        // Register streak maintenance job (runs every hour)
        if (!$jobList->has(StreakMaintenanceJob::class, null)) {
            $jobList->add(StreakMaintenanceJob::class);
        }
        
        // Register daily summary job (runs daily at midnight)
        if (!$jobList->has(DailySummaryJob::class, null)) {
            $jobList->add(DailySummaryJob::class);
        }
        
        // Additional initialization can go here
        try {
            $logger = $serverContainer->get(\Psr\Log\LoggerInterface::class);
            $logger->info('Quest app initialized successfully');
        } catch (\Exception $e) {
            // Silent fail for logging
        }
    }
}