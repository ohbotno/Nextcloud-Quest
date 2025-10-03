<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Command;

use OC\Core\Command\Base;
use OCA\NextcloudQuest\Db\CharacterAgeMapper;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Service\CharacterService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class CheckAgeSystem extends Base {

    private $ageMapper;
    private $questMapper;
    private $characterService;

    public function __construct(
        CharacterAgeMapper $ageMapper,
        QuestMapper $questMapper,
        CharacterService $characterService
    ) {
        parent::__construct();
        $this->ageMapper = $ageMapper;
        $this->questMapper = $questMapper;
        $this->characterService = $characterService;
    }

    protected function configure() {
        parent::configure();
        $this->setName('quest:check-age-system')
            ->setDescription('Check and fix age system')
            ->addArgument('user', InputArgument::OPTIONAL, 'User ID to check/fix');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $userId = $input->getArgument('user');

        // Check ages in database
        $output->writeln('<info>Ages in database:</info>');
        $ages = $this->ageMapper->findAllActive();

        if (empty($ages)) {
            $output->writeln('<error>❌ NO AGES FOUND! Table is empty.</error>');
            return 1;
        }

        foreach ($ages as $age) {
            $maxLevel = $age->getMaxLevel() ? $age->getMaxLevel() : '∞';
            $output->writeln(sprintf('  %s: %s (Levels %d-%s)',
                $age->getAgeIcon(),
                $age->getAgeName(),
                $age->getMinLevel(),
                $maxLevel
            ));
        }

        $output->writeln('');

        // If user specified, check their age
        if ($userId) {
            try {
                $quest = $this->questMapper->findByUserId($userId);
                $level = $quest->getLevel();
                $currentAge = $this->ageMapper->getAgeForLevel($level);

                $output->writeln(sprintf('<info>User %s:</info>', $userId));
                $output->writeln(sprintf('  Level: %d', $level));
                $output->writeln(sprintf('  Current Age in DB: %s', $quest->getCharacterCurrentAge() ?? 'NULL'));

                if ($currentAge) {
                    $output->writeln(sprintf('  Should be: %s %s (Levels %d-%s)',
                        $currentAge->getAgeIcon(),
                        $currentAge->getAgeName(),
                        $currentAge->getMinLevel(),
                        $currentAge->getMaxLevel() ? $currentAge->getMaxLevel() : '∞'
                    ));

                    // Force recalculation
                    $output->writeln('');
                    $output->writeln('<info>Recalculating age...</info>');
                    $newAge = $this->characterService->checkAgeProgression(
                        $userId,
                        $level,
                        $quest->getLifetimeXp()
                    );

                    if ($newAge) {
                        $output->writeln(sprintf('<info>✅ Age updated to: %s %s</info>',
                            $newAge->getAgeIcon(),
                            $newAge->getAgeName()
                        ));
                    } else {
                        $output->writeln('<comment>Age already correct, no update needed</comment>');
                    }
                } else {
                    $output->writeln('<error>❌ Could not determine age for level ' . $level . '</error>');
                }

            } catch (\Exception $e) {
                $output->writeln('<error>Error: ' . $e->getMessage() . '</error>');
                return 1;
            }
        } else {
            // List all users at level 10+
            $output->writeln('<info>Users at level 10+ (should be Bronze Age or higher):</info>');
            // Note: This would require additional query, skipping for now
        }

        return 0;
    }
}
