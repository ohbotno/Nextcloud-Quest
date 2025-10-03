<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Character Items Seed Data Migration
 * Creates equipment items for all ages with progression
 */
class Version1015Date20250930140000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create or update character items table
        if (!$schema->hasTable('quest_char_items')) {
            $table = $schema->createTable('quest_char_items');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('item_key', 'string', [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('item_name', 'string', [
                'notnull' => true,
                'length' => 100,
            ]);
            $table->addColumn('item_type', 'string', [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('age_key', 'string', [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('item_description', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('unlock_level', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('unlock_achievement', 'string', [
                'notnull' => false,
                'length' => 50,
            ]);
            $table->addColumn('item_rarity', 'string', [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('sprite_path', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);
            $table->addColumn('sprite_layer', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('is_default', 'integer', [
                'notnull' => true,
                'default' => 0,
                'length' => 1,
            ]);
            $table->addColumn('is_active', 'integer', [
                'notnull' => true,
                'default' => 1,
                'length' => 1,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => false,
            ]);

            $table->setPrimaryKey(['id'], 'quest_items_pk');
            $table->addUniqueIndex(['item_key'], 'quest_items_key');
            $table->addIndex(['item_type'], 'quest_items_type');
            $table->addIndex(['age_key'], 'quest_items_age');
            $table->addIndex(['unlock_level'], 'quest_items_lvl');
        } else {
            // Table exists, ensure all necessary columns are present
            $table = $schema->getTable('quest_char_items');

            if (!$table->hasColumn('sprite_path')) {
                $table->addColumn('sprite_path', 'string', [
                    'notnull' => false,
                    'length' => 255,
                ]);
            }

            if (!$table->hasColumn('sprite_layer')) {
                $table->addColumn('sprite_layer', 'integer', [
                    'notnull' => true,
                    'default' => 0,
                ]);
            }

            if (!$table->hasColumn('created_at')) {
                $table->addColumn('created_at', 'datetime', [
                    'notnull' => false,
                ]);
            }
        }

        // Create character unlocks table (for tracking which items users have unlocked)
        if (!$schema->hasTable('quest_char_unlocks')) {
            $table = $schema->createTable('quest_char_unlocks');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('item_key', 'string', [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('unlocked_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('unlock_method', 'string', [
                'notnull' => false,
                'length' => 20,
            ]);
            $table->addColumn('unlock_reason', 'string', [
                'notnull' => false,
                'length' => 255,
            ]);

            $table->setPrimaryKey(['id'], 'quest_unlocks_pk');
            $table->addUniqueIndex(['user_id', 'item_key'], 'quest_unlocks_ui');
            $table->addIndex(['user_id'], 'quest_unlocks_u');
        }

        // Create character progression table (for tracking age milestones)
        if (!$schema->hasTable('quest_char_progress')) {
            $table = $schema->createTable('quest_char_progress');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('age_key', 'string', [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('reached_at', 'datetime', [
                'notnull' => true,
            ]);
            $table->addColumn('reached_at_level', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('reached_with_xp', 'integer', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id'], 'quest_prog_pk');
            $table->addUniqueIndex(['user_id', 'age_key'], 'quest_prog_ua');
            $table->addIndex(['user_id'], 'quest_prog_u');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        $connection = \OC::$server->getDatabaseConnection();
        $now = new \DateTime();

        // Define character items for each age and equipment type
        $items = $this->getCharacterItems();

        $insertCount = 0;

        foreach ($items as $item) {
            // Check if item already exists
            $qb = $connection->getQueryBuilder();
            $qb->select('id')
                ->from('quest_char_items')
                ->where($qb->expr()->eq('item_key', $qb->createNamedParameter($item['item_key'])));

            $result = $qb->execute();
            $exists = $result->fetch();
            $result->closeCursor();

            if (!$exists) {
                // Insert the item
                $qb = $connection->getQueryBuilder();
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
            }
        }

        $output->info("Created {$insertCount} character items");
    }

    private function getCharacterItems(): array {
        $items = [];

        // Stone Age Items
        $items = array_merge($items, $this->getStoneAgeItems());
        $items = array_merge($items, $this->getBronzeAgeItems());
        $items = array_merge($items, $this->getIronAgeItems());
        $items = array_merge($items, $this->getMedievalAgeItems());
        $items = array_merge($items, $this->getRenaissanceItems());
        $items = array_merge($items, $this->getIndustrialItems());
        $items = array_merge($items, $this->getModernItems());
        $items = array_merge($items, $this->getDigitalItems());
        $items = array_merge($items, $this->getSpaceItems());

        return $items;
    }

    private function getStoneAgeItems(): array {
        return [
            // Clothing
            ['item_key' => 'stone_fur_basic', 'item_name' => 'Animal Hide', 'item_type' => 'clothing', 'age_key' => 'stone', 'item_description' => 'Basic animal hide clothing', 'unlock_level' => 1, 'item_rarity' => 'common', 'sprite_path' => 'characters/stone/clothing/fur_basic.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'stone_fur_decorated', 'item_name' => 'Decorated Hide', 'item_type' => 'clothing', 'age_key' => 'stone', 'item_description' => 'Animal hide with primitive decorations', 'unlock_level' => 5, 'item_rarity' => 'rare', 'sprite_path' => 'characters/stone/clothing/fur_decorated.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'stone_club', 'item_name' => 'Wooden Club', 'item_type' => 'weapon', 'age_key' => 'stone', 'item_description' => 'Simple wooden club', 'unlock_level' => 1, 'item_rarity' => 'common', 'sprite_path' => 'characters/stone/weapons/club.svg', 'sprite_layer' => 20, 'is_default' => true],
            ['item_key' => 'stone_spear', 'item_name' => 'Stone Spear', 'item_type' => 'weapon', 'age_key' => 'stone', 'item_description' => 'Sharpened stone on wooden shaft', 'unlock_level' => 3, 'item_rarity' => 'common', 'sprite_path' => 'characters/stone/weapons/spear.svg', 'sprite_layer' => 20],
            ['item_key' => 'stone_axe', 'item_name' => 'Stone Axe', 'item_type' => 'weapon', 'age_key' => 'stone', 'item_description' => 'Chipped stone hand axe', 'unlock_level' => 6, 'item_rarity' => 'rare', 'sprite_path' => 'characters/stone/weapons/axe.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'stone_bone_necklace', 'item_name' => 'Bone Necklace', 'item_type' => 'accessory', 'age_key' => 'stone', 'item_description' => 'Necklace made from animal bones', 'unlock_level' => 1, 'item_rarity' => 'common', 'sprite_path' => 'characters/stone/accessories/bone_necklace.svg', 'sprite_layer' => 15, 'is_default' => true],
            ['item_key' => 'stone_shell_bracelet', 'item_name' => 'Shell Bracelet', 'item_type' => 'accessory', 'age_key' => 'stone', 'item_description' => 'Bracelet with decorative shells', 'unlock_level' => 4, 'item_rarity' => 'rare', 'sprite_path' => 'characters/stone/accessories/shell_bracelet.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'stone_headband', 'item_name' => 'Leather Headband', 'item_type' => 'headgear', 'age_key' => 'stone', 'item_description' => 'Simple leather headband', 'unlock_level' => 1, 'item_rarity' => 'common', 'sprite_path' => 'characters/stone/headgear/headband.svg', 'sprite_layer' => 30, 'is_default' => true],
            ['item_key' => 'stone_fur_hood', 'item_name' => 'Fur Hood', 'item_type' => 'headgear', 'age_key' => 'stone', 'item_description' => 'Warm animal fur hood', 'unlock_level' => 7, 'item_rarity' => 'epic', 'sprite_path' => 'characters/stone/headgear/fur_hood.svg', 'sprite_layer' => 30],
        ];
    }

    private function getBronzeAgeItems(): array {
        return [
            // Clothing
            ['item_key' => 'bronze_tunic', 'item_name' => 'Bronze Age Tunic', 'item_type' => 'clothing', 'age_key' => 'bronze', 'item_description' => 'Woven cloth tunic', 'unlock_level' => 10, 'item_rarity' => 'common', 'sprite_path' => 'characters/bronze/clothing/tunic.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'bronze_armor', 'item_name' => 'Bronze Breastplate', 'item_type' => 'clothing', 'age_key' => 'bronze', 'item_description' => 'Early bronze armor', 'unlock_level' => 15, 'item_rarity' => 'epic', 'sprite_path' => 'characters/bronze/clothing/armor.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'bronze_sword', 'item_name' => 'Bronze Sword', 'item_type' => 'weapon', 'age_key' => 'bronze', 'item_description' => 'Early bronze blade', 'unlock_level' => 10, 'item_rarity' => 'rare', 'sprite_path' => 'characters/bronze/weapons/sword.svg', 'sprite_layer' => 20],
            ['item_key' => 'bronze_dagger', 'item_name' => 'Bronze Dagger', 'item_type' => 'weapon', 'age_key' => 'bronze', 'item_description' => 'Short bronze blade', 'unlock_level' => 12, 'item_rarity' => 'common', 'sprite_path' => 'characters/bronze/weapons/dagger.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'bronze_ring', 'item_name' => 'Bronze Ring', 'item_type' => 'accessory', 'age_key' => 'bronze', 'item_description' => 'Simple bronze ring', 'unlock_level' => 11, 'item_rarity' => 'common', 'sprite_path' => 'characters/bronze/accessories/ring.svg', 'sprite_layer' => 15],
            ['item_key' => 'bronze_amulet', 'item_name' => 'Bronze Amulet', 'item_type' => 'accessory', 'age_key' => 'bronze', 'item_description' => 'Protective bronze amulet', 'unlock_level' => 16, 'item_rarity' => 'rare', 'sprite_path' => 'characters/bronze/accessories/amulet.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'bronze_cap', 'item_name' => 'Bronze Cap', 'item_type' => 'headgear', 'age_key' => 'bronze', 'item_description' => 'Simple bronze helmet', 'unlock_level' => 13, 'item_rarity' => 'rare', 'sprite_path' => 'characters/bronze/headgear/cap.svg', 'sprite_layer' => 30],
        ];
    }

    private function getIronAgeItems(): array {
        return [
            // Clothing
            ['item_key' => 'iron_chainmail', 'item_name' => 'Chainmail Shirt', 'item_type' => 'clothing', 'age_key' => 'iron', 'item_description' => 'Iron chainmail protection', 'unlock_level' => 20, 'item_rarity' => 'rare', 'sprite_path' => 'characters/iron/clothing/chainmail.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'iron_armor', 'item_name' => 'Iron Plate Armor', 'item_type' => 'clothing', 'age_key' => 'iron', 'item_description' => 'Heavy iron plate armor', 'unlock_level' => 25, 'item_rarity' => 'epic', 'sprite_path' => 'characters/iron/clothing/plate_armor.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'iron_longsword', 'item_name' => 'Iron Longsword', 'item_type' => 'weapon', 'age_key' => 'iron', 'item_description' => 'Strong iron longsword', 'unlock_level' => 20, 'item_rarity' => 'rare', 'sprite_path' => 'characters/iron/weapons/longsword.svg', 'sprite_layer' => 20],
            ['item_key' => 'iron_battle_axe', 'item_name' => 'Battle Axe', 'item_type' => 'weapon', 'age_key' => 'iron', 'item_description' => 'Heavy iron battle axe', 'unlock_level' => 24, 'item_rarity' => 'epic', 'sprite_path' => 'characters/iron/weapons/battle_axe.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'iron_shield', 'item_name' => 'Iron Shield', 'item_type' => 'accessory', 'age_key' => 'iron', 'item_description' => 'Protective iron shield', 'unlock_level' => 22, 'item_rarity' => 'rare', 'sprite_path' => 'characters/iron/accessories/shield.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'iron_helmet', 'item_name' => 'Iron Helmet', 'item_type' => 'headgear', 'age_key' => 'iron', 'item_description' => 'Protective iron helmet', 'unlock_level' => 21, 'item_rarity' => 'rare', 'sprite_path' => 'characters/iron/headgear/helmet.svg', 'sprite_layer' => 30],
            ['item_key' => 'iron_horned_helmet', 'item_name' => 'Horned Helmet', 'item_type' => 'headgear', 'age_key' => 'iron', 'item_description' => 'Intimidating horned helmet', 'unlock_level' => 27, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/iron/headgear/horned_helmet.svg', 'sprite_layer' => 30],
        ];
    }

    private function getMedievalAgeItems(): array {
        return [
            // Clothing
            ['item_key' => 'medieval_knight_armor', 'item_name' => 'Knight Armor', 'item_type' => 'clothing', 'age_key' => 'medieval', 'item_description' => 'Full knight plate armor', 'unlock_level' => 30, 'item_rarity' => 'epic', 'sprite_path' => 'characters/medieval/clothing/knight_armor.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'medieval_royal_robes', 'item_name' => 'Royal Robes', 'item_type' => 'clothing', 'age_key' => 'medieval', 'item_description' => 'Elegant royal robes', 'unlock_level' => 35, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/medieval/clothing/royal_robes.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'medieval_broadsword', 'item_name' => 'Broadsword', 'item_type' => 'weapon', 'age_key' => 'medieval', 'item_description' => 'Classic medieval broadsword', 'unlock_level' => 30, 'item_rarity' => 'rare', 'sprite_path' => 'characters/medieval/weapons/broadsword.svg', 'sprite_layer' => 20],
            ['item_key' => 'medieval_mace', 'item_name' => 'Iron Mace', 'item_type' => 'weapon', 'age_key' => 'medieval', 'item_description' => 'Heavy crushing mace', 'unlock_level' => 33, 'item_rarity' => 'epic', 'sprite_path' => 'characters/medieval/weapons/mace.svg', 'sprite_layer' => 20],
            ['item_key' => 'medieval_excalibur', 'item_name' => 'Legendary Blade', 'item_type' => 'weapon', 'age_key' => 'medieval', 'item_description' => 'A sword of legend', 'unlock_level' => 37, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/medieval/weapons/excalibur.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'medieval_banner', 'item_name' => 'House Banner', 'item_type' => 'accessory', 'age_key' => 'medieval', 'item_description' => 'Your family crest', 'unlock_level' => 32, 'item_rarity' => 'rare', 'sprite_path' => 'characters/medieval/accessories/banner.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'medieval_crown', 'item_name' => 'Royal Crown', 'item_type' => 'headgear', 'age_key' => 'medieval', 'item_description' => 'Crown of royalty', 'unlock_level' => 38, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/medieval/headgear/crown.svg', 'sprite_layer' => 30],
        ];
    }

    private function getRenaissanceItems(): array {
        return [
            // Clothing
            ['item_key' => 'renaissance_doublet', 'item_name' => 'Elegant Doublet', 'item_type' => 'clothing', 'age_key' => 'renaissance', 'item_description' => 'Fashionable Renaissance clothing', 'unlock_level' => 40, 'item_rarity' => 'rare', 'sprite_path' => 'characters/renaissance/clothing/doublet.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'renaissance_scholar_robes', 'item_name' => 'Scholar Robes', 'item_type' => 'clothing', 'age_key' => 'renaissance', 'item_description' => 'Robes of learning', 'unlock_level' => 45, 'item_rarity' => 'epic', 'sprite_path' => 'characters/renaissance/clothing/scholar_robes.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'renaissance_rapier', 'item_name' => 'Rapier', 'item_type' => 'weapon', 'age_key' => 'renaissance', 'item_description' => 'Elegant dueling sword', 'unlock_level' => 40, 'item_rarity' => 'rare', 'sprite_path' => 'characters/renaissance/weapons/rapier.svg', 'sprite_layer' => 20],
            ['item_key' => 'renaissance_musket', 'item_name' => 'Early Musket', 'item_type' => 'weapon', 'age_key' => 'renaissance', 'item_description' => 'Early firearm', 'unlock_level' => 46, 'item_rarity' => 'epic', 'sprite_path' => 'characters/renaissance/weapons/musket.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'renaissance_quill', 'item_name' => 'Golden Quill', 'item_type' => 'accessory', 'age_key' => 'renaissance', 'item_description' => 'Symbol of learning', 'unlock_level' => 42, 'item_rarity' => 'rare', 'sprite_path' => 'characters/renaissance/accessories/quill.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'renaissance_hat', 'item_name' => 'Feathered Hat', 'item_type' => 'headgear', 'age_key' => 'renaissance', 'item_description' => 'Fashionable feathered hat', 'unlock_level' => 43, 'item_rarity' => 'rare', 'sprite_path' => 'characters/renaissance/headgear/feathered_hat.svg', 'sprite_layer' => 30],
        ];
    }

    private function getIndustrialItems(): array {
        return [
            // Clothing
            ['item_key' => 'industrial_suit', 'item_name' => 'Industrial Suit', 'item_type' => 'clothing', 'age_key' => 'industrial', 'item_description' => 'Practical work clothing', 'unlock_level' => 50, 'item_rarity' => 'common', 'sprite_path' => 'characters/industrial/clothing/suit.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'industrial_engineer_coat', 'item_name' => 'Engineer Coat', 'item_type' => 'clothing', 'age_key' => 'industrial', 'item_description' => 'Coat of innovation', 'unlock_level' => 55, 'item_rarity' => 'epic', 'sprite_path' => 'characters/industrial/clothing/engineer_coat.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'industrial_revolver', 'item_name' => 'Revolver', 'item_type' => 'weapon', 'age_key' => 'industrial', 'item_description' => 'Six-shooter handgun', 'unlock_level' => 50, 'item_rarity' => 'rare', 'sprite_path' => 'characters/industrial/weapons/revolver.svg', 'sprite_layer' => 20],
            ['item_key' => 'industrial_rifle', 'item_name' => 'Rifle', 'item_type' => 'weapon', 'age_key' => 'industrial', 'item_description' => 'Precision rifle', 'unlock_level' => 54, 'item_rarity' => 'epic', 'sprite_path' => 'characters/industrial/weapons/rifle.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'industrial_pocket_watch', 'item_name' => 'Pocket Watch', 'item_type' => 'accessory', 'age_key' => 'industrial', 'item_description' => 'Precise timepiece', 'unlock_level' => 52, 'item_rarity' => 'rare', 'sprite_path' => 'characters/industrial/accessories/pocket_watch.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'industrial_top_hat', 'item_name' => 'Top Hat', 'item_type' => 'headgear', 'age_key' => 'industrial', 'item_description' => 'Gentleman\'s top hat', 'unlock_level' => 53, 'item_rarity' => 'rare', 'sprite_path' => 'characters/industrial/headgear/top_hat.svg', 'sprite_layer' => 30],
            ['item_key' => 'industrial_goggles', 'item_name' => 'Steam Goggles', 'item_type' => 'headgear', 'age_key' => 'industrial', 'item_description' => 'Protective goggles', 'unlock_level' => 57, 'item_rarity' => 'epic', 'sprite_path' => 'characters/industrial/headgear/goggles.svg', 'sprite_layer' => 30],
        ];
    }

    private function getModernItems(): array {
        return [
            // Clothing
            ['item_key' => 'modern_business_suit', 'item_name' => 'Business Suit', 'item_type' => 'clothing', 'age_key' => 'modern', 'item_description' => 'Modern professional attire', 'unlock_level' => 60, 'item_rarity' => 'common', 'sprite_path' => 'characters/modern/clothing/suit.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'modern_tactical_gear', 'item_name' => 'Tactical Gear', 'item_type' => 'clothing', 'age_key' => 'modern', 'item_description' => 'Military tactical gear', 'unlock_level' => 67, 'item_rarity' => 'epic', 'sprite_path' => 'characters/modern/clothing/tactical.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'modern_pistol', 'item_name' => 'Modern Pistol', 'item_type' => 'weapon', 'age_key' => 'modern', 'item_description' => 'Semi-automatic pistol', 'unlock_level' => 60, 'item_rarity' => 'rare', 'sprite_path' => 'characters/modern/weapons/pistol.svg', 'sprite_layer' => 20],
            ['item_key' => 'modern_assault_rifle', 'item_name' => 'Assault Rifle', 'item_type' => 'weapon', 'age_key' => 'modern', 'item_description' => 'Modern military rifle', 'unlock_level' => 70, 'item_rarity' => 'epic', 'sprite_path' => 'characters/modern/weapons/rifle.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'modern_smartphone', 'item_name' => 'Smartphone', 'item_type' => 'accessory', 'age_key' => 'modern', 'item_description' => 'Modern communication device', 'unlock_level' => 62, 'item_rarity' => 'rare', 'sprite_path' => 'characters/modern/accessories/smartphone.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'modern_cap', 'item_name' => 'Baseball Cap', 'item_type' => 'headgear', 'age_key' => 'modern', 'item_description' => 'Casual modern cap', 'unlock_level' => 61, 'item_rarity' => 'common', 'sprite_path' => 'characters/modern/headgear/cap.svg', 'sprite_layer' => 30],
            ['item_key' => 'modern_helmet', 'item_name' => 'Tactical Helmet', 'item_type' => 'headgear', 'age_key' => 'modern', 'item_description' => 'Military tactical helmet', 'unlock_level' => 68, 'item_rarity' => 'epic', 'sprite_path' => 'characters/modern/headgear/helmet.svg', 'sprite_layer' => 30],
        ];
    }

    private function getDigitalItems(): array {
        return [
            // Clothing
            ['item_key' => 'digital_smart_suit', 'item_name' => 'Smart Suit', 'item_type' => 'clothing', 'age_key' => 'digital', 'item_description' => 'Networked smart clothing', 'unlock_level' => 75, 'item_rarity' => 'rare', 'sprite_path' => 'characters/digital/clothing/smart_suit.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'digital_cyber_armor', 'item_name' => 'Cyber Armor', 'item_type' => 'clothing', 'age_key' => 'digital', 'item_description' => 'High-tech protective armor', 'unlock_level' => 85, 'item_rarity' => 'epic', 'sprite_path' => 'characters/digital/clothing/cyber_armor.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'digital_plasma_pistol', 'item_name' => 'Plasma Pistol', 'item_type' => 'weapon', 'age_key' => 'digital', 'item_description' => 'Energy weapon', 'unlock_level' => 75, 'item_rarity' => 'epic', 'sprite_path' => 'characters/digital/weapons/plasma_pistol.svg', 'sprite_layer' => 20],
            ['item_key' => 'digital_laser_rifle', 'item_name' => 'Laser Rifle', 'item_type' => 'weapon', 'age_key' => 'digital', 'item_description' => 'Advanced laser weapon', 'unlock_level' => 90, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/digital/weapons/laser_rifle.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'digital_neural_interface', 'item_name' => 'Neural Interface', 'item_type' => 'accessory', 'age_key' => 'digital', 'item_description' => 'Direct brain-computer interface', 'unlock_level' => 80, 'item_rarity' => 'epic', 'sprite_path' => 'characters/digital/accessories/neural.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'digital_vr_headset', 'item_name' => 'VR Headset', 'item_type' => 'headgear', 'age_key' => 'digital', 'item_description' => 'Virtual reality headset', 'unlock_level' => 77, 'item_rarity' => 'rare', 'sprite_path' => 'characters/digital/headgear/vr_headset.svg', 'sprite_layer' => 30],
            ['item_key' => 'digital_ar_visor', 'item_name' => 'AR Visor', 'item_type' => 'headgear', 'age_key' => 'digital', 'item_description' => 'Augmented reality visor', 'unlock_level' => 88, 'item_rarity' => 'epic', 'sprite_path' => 'characters/digital/headgear/ar_visor.svg', 'sprite_layer' => 30],
        ];
    }

    private function getSpaceItems(): array {
        return [
            // Clothing
            ['item_key' => 'space_suit', 'item_name' => 'Space Suit', 'item_type' => 'clothing', 'age_key' => 'space', 'item_description' => 'Advanced space exploration suit', 'unlock_level' => 100, 'item_rarity' => 'epic', 'sprite_path' => 'characters/space/clothing/space_suit.svg', 'sprite_layer' => 10, 'is_default' => true],
            ['item_key' => 'space_exo_armor', 'item_name' => 'Exo Armor', 'item_type' => 'clothing', 'age_key' => 'space', 'item_description' => 'Powered exoskeleton armor', 'unlock_level' => 120, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/space/clothing/exo_armor.svg', 'sprite_layer' => 10],

            // Weapons
            ['item_key' => 'space_ion_blaster', 'item_name' => 'Ion Blaster', 'item_type' => 'weapon', 'age_key' => 'space', 'item_description' => 'Powerful ion weapon', 'unlock_level' => 100, 'item_rarity' => 'epic', 'sprite_path' => 'characters/space/weapons/ion_blaster.svg', 'sprite_layer' => 20],
            ['item_key' => 'space_antimatter_cannon', 'item_name' => 'Antimatter Cannon', 'item_type' => 'weapon', 'age_key' => 'space', 'item_description' => 'Ultimate weapon of destruction', 'unlock_level' => 150, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/space/weapons/antimatter_cannon.svg', 'sprite_layer' => 20],

            // Accessories
            ['item_key' => 'space_jetpack', 'item_name' => 'Jetpack', 'item_type' => 'accessory', 'age_key' => 'space', 'item_description' => 'Personal flight system', 'unlock_level' => 105, 'item_rarity' => 'epic', 'sprite_path' => 'characters/space/accessories/jetpack.svg', 'sprite_layer' => 15],
            ['item_key' => 'space_quantum_field', 'item_name' => 'Quantum Field Generator', 'item_type' => 'accessory', 'age_key' => 'space', 'item_description' => 'Advanced protection field', 'unlock_level' => 130, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/space/accessories/quantum_field.svg', 'sprite_layer' => 15],

            // Headgear
            ['item_key' => 'space_helmet', 'item_name' => 'Space Helmet', 'item_type' => 'headgear', 'age_key' => 'space', 'item_description' => 'Advanced life support helmet', 'unlock_level' => 100, 'item_rarity' => 'rare', 'sprite_path' => 'characters/space/headgear/helmet.svg', 'sprite_layer' => 30],
            ['item_key' => 'space_commander_helm', 'item_name' => 'Commander Helm', 'item_type' => 'headgear', 'age_key' => 'space', 'item_description' => 'Elite space commander helmet', 'unlock_level' => 140, 'item_rarity' => 'legendary', 'sprite_path' => 'characters/space/headgear/commander_helm.svg', 'sprite_layer' => 30],
        ];
    }
}
