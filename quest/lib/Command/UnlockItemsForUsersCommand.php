<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Command;

use OCA\NextcloudQuest\Db\CharacterItemMapper;
use OCA\NextcloudQuest\Db\CharacterUnlockMapper;
use OCA\NextcloudQuest\Db\CharacterUnlock;
use OCA\NextcloudQuest\Db\QuestMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockItemsForUsersCommand extends Command {

    private $questMapper;
    private $itemMapper;
    private $unlockMapper;

    public function __construct(
        QuestMapper $questMapper,
        CharacterItemMapper $itemMapper,
        CharacterUnlockMapper $unlockMapper
    ) {
        parent::__construct();
        $this->questMapper = $questMapper;
        $this->itemMapper = $itemMapper;
        $this->unlockMapper = $unlockMapper;
    }

    protected function configure() {
        $this->setName('quest:unlock-items')
            ->setDescription('Unlock items for existing users based on their level')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Specific user ID to unlock items for (if not provided, unlocks for all users)'
            )
            ->addOption(
                'defaults-only',
                'd',
                InputOption::VALUE_NONE,
                'Only unlock default items for each age'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $userId = $input->getOption('user');
        $defaultsOnly = $input->getOption('defaults-only');

        if ($userId) {
            $output->writeln("Unlocking items for user: {$userId}");
            $this->unlockItemsForUser($userId, $defaultsOnly, $output);
        } else {
            $output->writeln("Unlocking items for all users...");
            // Get all users via leaderboard (returns all users)
            $users = $this->questMapper->getLeaderboard(1000, 0, 'level');

            foreach ($users as $quest) {
                $this->unlockItemsForUser($quest->getUserId(), $defaultsOnly, $output);
            }
        }

        $output->writeln("<info>Item unlocking complete!</info>");
        return 0;
    }

    private function unlockItemsForUser(string $userId, bool $defaultsOnly, OutputInterface $output): void {
        try {
            // Get user's current level
            $quest = $this->questMapper->findByUserId($userId);
            $userLevel = $quest->getLevel();

            $output->writeln("  User: {$userId} (Level {$userLevel})");

            // Get items to unlock
            if ($defaultsOnly) {
                // Only unlock default items for reached ages
                $items = $this->getDefaultItemsUpToLevel($userLevel);
            } else {
                // Unlock all items up to user's level
                $items = $this->getAllItemsUpToLevel($userLevel);
            }

            if (empty($items)) {
                $output->writeln("    No items to unlock");
                return;
            }

            // Get item keys
            $itemKeys = array_map(function($item) {
                return $item->getItemKey();
            }, $items);

            // Bulk unlock
            $unlockedCount = $this->unlockMapper->bulkUnlock(
                $userId,
                $itemKeys,
                CharacterUnlock::METHOD_LEVEL,
                "Retroactive unlock for level {$userLevel}"
            );

            $output->writeln("    <info>Unlocked {$unlockedCount} new items</info>");

        } catch (\Exception $e) {
            $output->writeln("    <error>Error: {$e->getMessage()}</error>");
        }
    }

    private function getDefaultItemsUpToLevel(int $userLevel): array {
        // Get all default items
        $allItems = $this->itemMapper->findAllActive();
        $defaultItems = [];

        foreach ($allItems as $item) {
            if ($item->getIsDefault() &&
                ($item->getUnlockLevel() === null || $item->getUnlockLevel() <= $userLevel)) {
                $defaultItems[] = $item;
            }
        }

        return $defaultItems;
    }

    private function getAllItemsUpToLevel(int $userLevel): array {
        $allItems = $this->itemMapper->findAllActive();
        $unlockableItems = [];

        foreach ($allItems as $item) {
            // Skip items that require achievements (manual unlock only)
            if ($item->requiresAchievement()) {
                continue;
            }

            // Include items with no level requirement or level requirement met
            if ($item->getUnlockLevel() === null || $item->getUnlockLevel() <= $userLevel) {
                $unlockableItems[] = $item;
            }
        }

        return $unlockableItems;
    }
}
