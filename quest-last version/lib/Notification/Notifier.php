<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Notification;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
    private IFactory $factory;
    private IURLGenerator $urlGenerator;
    
    public function __construct(
        IFactory $factory,
        IURLGenerator $urlGenerator
    ) {
        $this->factory = $factory;
        $this->urlGenerator = $urlGenerator;
    }
    
    public function getID(): string {
        return 'nextcloudquest';
    }
    
    public function getName(): string {
        return $this->factory->get('nextcloudquest')->t('Quest');
    }
    
    public function prepare(INotification $notification, string $languageCode): INotification {
        if ($notification->getApp() !== 'nextcloudquest') {
            throw new \InvalidArgumentException();
        }
        
        $l = $this->factory->get('nextcloudquest', $languageCode);
        
        switch ($notification->getSubject()) {
            case 'achievement_unlocked':
                return $this->prepareAchievementNotification($notification, $l);
            case 'level_up':
                return $this->prepareLevelUpNotification($notification, $l);
            case 'streak_reminder':
                return $this->prepareStreakReminderNotification($notification, $l);
            case 'daily_summary':
                return $this->prepareDailySummaryNotification($notification, $l);
            default:
                throw new \InvalidArgumentException();
        }
    }
    
    private function prepareAchievementNotification(INotification $notification, IL10N $l): INotification {
        $parameters = $notification->getSubjectParameters();
        $achievementName = $parameters['achievement'] ?? 'Unknown Achievement';
        
        $notification->setParsedSubject(
            $l->t('Achievement Unlocked: %s', [$achievementName])
        );
        
        $messageParameters = $notification->getMessageParameters();
        $description = $messageParameters['description'] ?? '';
        
        if ($description) {
            $notification->setParsedMessage(
                $l->t('You unlocked the "%s" achievement: %s', [$achievementName, $description])
            );
        }
        
        $notification->setIcon(
            $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->imagePath('nextcloudquest', 'achievements/trophy.svg')
            )
        );
        
        $notification->setLink(
            $this->urlGenerator->linkToRouteAbsolute('nextcloudquest.page.index')
        );
        
        return $notification;
    }
    
    private function prepareLevelUpNotification(INotification $notification, IL10N $l): INotification {
        $parameters = $notification->getSubjectParameters();
        $level = $parameters['level'] ?? 1;
        $rankTitle = $parameters['rank_title'] ?? 'Quest Novice';
        
        $notification->setParsedSubject(
            $l->t('Level Up! You reached level %d', [$level])
        );
        
        $notification->setParsedMessage(
            $l->t('Congratulations! You are now a %s', [$rankTitle])
        );
        
        $notification->setIcon(
            $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->imagePath('nextcloudquest', 'level-up.svg')
            )
        );
        
        $notification->setLink(
            $this->urlGenerator->linkToRouteAbsolute('nextcloudquest.page.index')
        );
        
        return $notification;
    }
    
    private function prepareStreakReminderNotification(INotification $notification, IL10N $l): INotification {
        $parameters = $notification->getSubjectParameters();
        $streak = $parameters['streak'] ?? 0;
        $hoursLeft = $parameters['hours_left'] ?? 0;
        
        $notification->setParsedSubject(
            $l->t('Don\'t break your %d-day streak!', [$streak])
        );
        
        $notification->setParsedMessage(
            $l->t('You have %d hours left to complete at least one task to maintain your streak.', [$hoursLeft])
        );
        
        $notification->setIcon(
            $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->imagePath('nextcloudquest', 'streak-warning.svg')
            )
        );
        
        $notification->setLink(
            $this->urlGenerator->linkToRouteAbsolute('nextcloudquest.page.index')
        );
        
        return $notification;
    }
    
    private function prepareDailySummaryNotification(INotification $notification, IL10N $l): INotification {
        $parameters = $notification->getSubjectParameters();
        $tasksCompleted = $parameters['tasks_completed'] ?? 0;
        $xpEarned = $parameters['xp_earned'] ?? 0;
        
        $notification->setParsedSubject(
            $l->t('Daily Quest Summary')
        );
        
        if ($tasksCompleted > 0) {
            $notification->setParsedMessage(
                $l->t('Great job! You completed %d tasks and earned %d XP today.', [$tasksCompleted, $xpEarned])
            );
        } else {
            $notification->setParsedMessage(
                $l->t('No tasks completed today. Start your quest tomorrow!')
            );
        }
        
        $notification->setIcon(
            $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->imagePath('nextcloudquest', 'daily-summary.svg')
            )
        );
        
        $notification->setLink(
            $this->urlGenerator->linkToRouteAbsolute('nextcloudquest.page.index')
        );
        
        return $notification;
    }
}