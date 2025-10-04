<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\AppInfo;

use OCA\NextcloudQuest\Controller\DiagnosticController;
use OCA\NextcloudQuest\Controller\TaskCompletionController;
use OCA\NextcloudQuest\Controller\QuestController;
use OCA\NextcloudQuest\Service\XPService;
use OCA\NextcloudQuest\Service\AchievementService;
use OCA\NextcloudQuest\Service\LevelService;
use OCA\NextcloudQuest\Service\StreakService;
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
        // Register DiagnosticController (working)
        $context->registerService(DiagnosticController::class, function($c) {
            return new DiagnosticController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class)
            );
        });
        
        // Register core mappers needed for quest completion
        $context->registerService(\OCA\NextcloudQuest\Db\QuestMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\QuestMapper($c->get(\OCP\IDBConnection::class));
        });
        
        $context->registerService(\OCA\NextcloudQuest\Db\HistoryMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\HistoryMapper($c->get(\OCP\IDBConnection::class));
        });
        
        $context->registerService(\OCA\NextcloudQuest\Db\AchievementMapper::class, function($c) {
            return new \OCA\NextcloudQuest\Db\AchievementMapper($c->get(\OCP\IDBConnection::class));
        });
        
        // Register core services
        $context->registerService(XPService::class, function($c) {
            return new XPService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(AchievementService::class, function($c) {
            return new AchievementService(
                $c->get(\OCA\NextcloudQuest\Db\AchievementMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\OCP\Notification\IManager::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(LevelService::class, function($c) {
            return new LevelService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(XPService::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        $context->registerService(StreakService::class, function($c) {
            return new StreakService(
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                $c->get(\Psr\Log\LoggerInterface::class)
            );
        });
        
        // Register TaskCompletionController (temporarily simplified)
        // Comment out complex version temporarily
        // $context->registerService(TaskCompletionController::class, function($c) {
        //     return new TaskCompletionController(
        //         self::APP_ID,
        //         $c->get(\OCP\IRequest::class),
        //         $c->get(\OCP\IUserSession::class),
        //         $c->get(\OCP\IDBConnection::class),
        //         $c->get(XPService::class),
        //         $c->get(LevelService::class),
        //         $c->get(AchievementService::class)
        //     );
        // });
        
        // Register QuestController (was working before)
        $context->registerService(QuestController::class, function($c) {
            return new QuestController(
                self::APP_ID,
                $c->get(\OCP\IRequest::class),
                $c->get(\OCP\IUserSession::class),
                $c->get(XPService::class),
                $c->get(AchievementService::class),
                $c->get(StreakService::class),
                $c->get(LevelService::class),
                $c->get(\OCA\NextcloudQuest\Db\QuestMapper::class),
                $c->get(\OCA\NextcloudQuest\Db\HistoryMapper::class),
                null  // TasksApiIntegration - skip for now
            );
        });
    }

    public function boot(IBootContext $context): void {
        // Minimal boot - just to test app loading
        $serverContainer = $context->getServerContainer();
        
        try {
            $logger = $serverContainer->get(\Psr\Log\LoggerInterface::class);
            $logger->info('Quest app initialized successfully (minimal version)');
        } catch (\Exception $e) {
            // Silent fail for logging
        }
    }
}