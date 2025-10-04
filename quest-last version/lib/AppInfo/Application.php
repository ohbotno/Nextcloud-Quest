<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\AppInfo;

use OCA\NextcloudQuest\BackgroundJob\DailySummaryJob;
use OCA\NextcloudQuest\BackgroundJob\StreakMaintenanceJob;
use OCA\NextcloudQuest\BackgroundJob\HealthPenaltyJob;
use OCA\NextcloudQuest\Controller\QuestController;
use OCA\NextcloudQuest\Controller\QuestStatsController;
use OCA\NextcloudQuest\Controller\TaskCompletionController;
use OCA\NextcloudQuest\Controller\TaskListController;
use OCA\NextcloudQuest\Controller\AdventureWorldController;
use OCA\NextcloudQuest\Controller\TestController;
use OCA\NextcloudQuest\Controller\PageController;
use OCA\NextcloudQuest\Controller\SettingsController;
use OCA\NextcloudQuest\Controller\CharacterController;
use OCA\NextcloudQuest\Controller\ProgressAnalyticsController;
use OCA\NextcloudQuest\Controller\DiagnosticController;
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
        
        // Register database mappers first (no dependencies)
        $context->registerService(\OCA\NextcloudQuest\Db\QuestMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\QuestMapper($c->get(\OCP\IDBConnection::class));
        });
        
        $context->registerService(\OCA\NextcloudQuest\Db\HistoryMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\HistoryMapper($c->get(\OCP\IDBConnection::class));
        });
        
        $context->registerService(\OCA\NextcloudQuest\Db\AchievementMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\AchievementMapper($c->get(\OCP\IDBConnection::class));
        });
        
        // Character mappers temporarily left commented out to avoid dependencies
        
        // Register core services (depend only on mappers)
        $context->registerService(\OCA\NextcloudQuest\Service\XPService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\XPService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(\OCA\NextcloudQuest\Service\StreakService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\StreakService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(\OCA\NextcloudQuest\Service\AchievementService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\AchievementService(
                $c->get(\OCA\NextcloudQuest\Db\AchievementMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\OCP\Notification\IManager::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(\OCA\NextcloudQuest\Service\LevelService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\LevelService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Service\XPService::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        // CharacterService temporarily left commented out
        
        // Register integration services (depend on core services)
        $context->registerService(TasksApiIntegration::class, function($c) {
            return new TasksApiIntegration(
                $c->get(\OCP\IDBConnection::class),
                $c->get(\Psr\Log\LoggerInterface::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCA\NextcloudQuest\Service\XPService::class),
                $c->get(\OCA\NextcloudQuest\Service\AchievementService::class),
                $c->get(\OCA\NextcloudQuest\Service\StreakService::class),
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class)
            );
        });
        
        // Register API controllers (depend on services and integrations)
        $context->registerService(QuestStatsController::class, function($c) {
            return new QuestStatsController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCP\IDBConnection::class)
            );
        });
        
        $context->registerService(TaskCompletionController::class, function($c) {
            return new TaskCompletionController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCP\IDBConnection::class),
                $c->get(\OCA\NextcloudQuest\Service\XPService::class),
                $c->get(\OCA\NextcloudQuest\Service\LevelService::class),
                $c->get(\OCA\NextcloudQuest\Service\AchievementService::class)
            );
        });
        
        $context->registerService(TaskListController::class, function($c) {
            return new TaskListController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCP\IDBConnection::class)
            );
        });
        
        // Register QuestController following the same pattern as other controllers
        $context->registerService(QuestController::class, function($c) {
            return new QuestController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(\OCA\NextcloudQuest\Service\XPService::class),
                $c->get(\OCA\NextcloudQuest\Service\AchievementService::class),
                $c->get(\OCA\NextcloudQuest\Service\StreakService::class),
                $c->get(\OCA\NextcloudQuest\Service\LevelService::class),
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\OCA\NextcloudQuest\Integration\TasksApiIntegration::class)
            );
        });
        
        // Register TestController
        $context->registerService(TestController::class, function($c) {
            return new TestController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class)
            );
        });
        
        // Register DiagnosticController (ultra-simple, no dependencies)
        $context->registerService(DiagnosticController::class, function($c) {
            return new DiagnosticController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class)
            );
        });
        
        // Register PageController
        $context->registerService(PageController::class, function($c) {
            return new PageController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IInitialStateService::class),
                $c->get(\OCP\IUserSession::class)
            );
        });
        
        // Register SettingsController (temporarily simplified)
        // $context->registerService(SettingsController::class, function($c) {
        //     return new SettingsController(
        //         self::APP_ID,
        //         $c->get(\OCP\IRequest::class),
        //         $c->get(\OCP\IUserSession::class),
        //         $c->get(\OCP\IConfig::class),
        //         $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\AchievementMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\CharacterProgressionMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\CharacterItemMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\CharacterUnlockMapper::class),
        //         $c->get(\OCP\IL10NFactory::class)->get(self::APP_ID)
        //     );
        // });
        
        // Register CharacterController (temporarily commented out)
        // $context->registerService(CharacterController::class, function($c) {
        //     return new CharacterController(
        //         self::APP_ID,
        //         $c->get(\OCP\IRequest::class),
        //         $c->get(\OCP\IUserSession::class),
        //         $c->get(\OCA\NextcloudQuest\Service\CharacterService::class)
        //     );
        // });
        
        // Register ProgressAnalyticsController (temporarily commented out)
        // $context->registerService(ProgressAnalyticsController::class, function($c) {
        //     return new ProgressAnalyticsController(
        //         self::APP_ID,
        //         $c->get(\OCP\IRequest::class),
        //         $c->get(\OCP\IUserSession::class),
        //         $c->get(\OCA\NextcloudQuest\Service\XPService::class),
        //         $c->get(\OCA\NextcloudQuest\Service\StreakService::class),
        //         $c->get(\OCA\NextcloudQuest\Service\CharacterService::class),
        //         $c->get(\OCA\NextcloudQuest\Service\AchievementService::class),
        //         $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
        //         $c->get(\OCA\NextcloudQuest\Db\CharacterAgeMapper::class),
        //         $c->get(\Psr\Log\LoggerInterface::class)
        //     );
        // });
        
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
        
        // Register Infinite Level Generator
        $context->registerService(\OCA\NextcloudQuest\Service\InfiniteLevelGenerator::class, function($c) {
            return new \OCA\NextcloudQuest\Service\InfiniteLevelGenerator(
                $c->get(\OCP\IDBConnection::class)
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
                $c->get(\OCA\NextcloudQuest\Service\XPService::class),
                $c->get(\OCA\NextcloudQuest\Service\InfiniteLevelGenerator::class)
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

        // Register HealthService
        $context->registerService(\OCA\NextcloudQuest\Service\HealthService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\HealthService(
                $c->get(\OCP\IDBConnection::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        // Register UserDataService for unified database access
        $context->registerService(\OCA\NextcloudQuest\Service\UserDataService::class, function($c) {
            return new \OCA\NextcloudQuest\Service\UserDataService(
                $c->get(\OCP\IDBConnection::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });

        // Register Health Penalty background job
        $context->registerService(HealthPenaltyJob::class, function($c) {
            return new HealthPenaltyJob(
                $c->get(\OCP\AppFramework\Utility\ITimeFactory::class),
                $c->get(\OCA\NextcloudQuest\Service\HealthService::class),
                $c->get(\OCP\IDBConnection::class),
                $c->get(\OCP\Notification\IManager::class),
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
        
        // Register health penalty job (runs daily at midnight)
        if (!$jobList->has(HealthPenaltyJob::class, null)) {
            $jobList->add(HealthPenaltyJob::class);
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