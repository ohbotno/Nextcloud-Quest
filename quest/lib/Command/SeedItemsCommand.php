<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Command;

use OCA\NextcloudQuest\Db\CharacterItemMapper;
use OCP\IDBConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SeedItemsCommand extends Command {

    private $db;
    private $itemMapper;

    public function __construct(IDBConnection $db, CharacterItemMapper $itemMapper) {
        parent::__construct();
        $this->db = $db;
        $this->itemMapper = $itemMapper;
    }

    protected function configure() {
        $this->setName('quest:seed-items')
            ->setDescription('Seed character items into the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $output->writeln('Seeding character items...');

        // Load migration class to get items
        require_once __DIR__ . '/../Migration/Version1015Date20250930140000.php';

        $migration = new \OCA\NextcloudQuest\Migration\Version1015Date20250930140000();

        // Use reflection to call the private method
        $reflection = new \ReflectionClass($migration);
        $method = $reflection->getMethod('getCharacterItems');
        $method->setAccessible(true);
        $items = $method->invoke($migration);

        $insertCount = 0;
        $now = new \DateTime();

        foreach ($items as $item) {
            // Check if item already exists
            $qb = $this->db->getQueryBuilder();
            $qb->select('id')
                ->from('quest_char_items')
                ->where($qb->expr()->eq('item_key', $qb->createNamedParameter($item['item_key'])));

            $result = $qb->execute();
            $exists = $result->fetch();
            $result->closeCursor();

            if (!$exists) {
                // Insert the item
                $qb = $this->db->getQueryBuilder();
                $qb->insert('quest_char_items')
                    ->values([
                        'item_key' => $qb->createNamedParameter($item['item_key']),
                        'item_name' => $qb->createNamedParameter($item['item_name']),
                        'item_type' => $qb->createNamedParameter($item['item_type']),
                        'age_key' => $qb->createNamedParameter($item['age_key']),
                        'item_description' => $qb->createNamedParameter($item['item_description']),
                        'unlock_level' => isset($item['unlock_level'])
                            ? $qb->createNamedParameter($item['unlock_level'], \PDO::PARAM_INT)
                            : $qb->createNamedParameter(null, \PDO::PARAM_NULL),
                        'unlock_achievement' => isset($item['unlock_achievement'])
                            ? $qb->createNamedParameter($item['unlock_achievement'])
                            : $qb->createNamedParameter(null, \PDO::PARAM_NULL),
                        'item_rarity' => $qb->createNamedParameter($item['item_rarity']),
                        'sprite_path' => $qb->createNamedParameter($item['sprite_path']),
                        'sprite_layer' => $qb->createNamedParameter($item['sprite_layer'], \PDO::PARAM_INT),
                        'is_default' => $qb->createNamedParameter($item['is_default'] ?? false, \PDO::PARAM_BOOL),
                        'is_active' => $qb->createNamedParameter(true, \PDO::PARAM_BOOL),
                        'created_at' => $qb->createNamedParameter($now, 'datetime'),
                    ]);

                $qb->execute();
                $insertCount++;
                $output->writeln("  Created: {$item['item_name']} ({$item['item_type']})");
            }
        }

        $output->writeln("\n<info>Successfully seeded $insertCount character items</info>");

        // Show total count
        $totalItems = count($this->itemMapper->findAllActive());
        $output->writeln("<info>Total items in database: $totalItems</info>");

        return 0;
    }
}
