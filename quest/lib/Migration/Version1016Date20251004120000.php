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
 * Adventure Map System Migration
 * Creates tables for procedural map generation, area progression, and node-based adventure system
 */
class Version1016Date20251004120000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Table 1: Adventure Areas (completed area history)
        if (!$schema->hasTable('ncquest_adventure_areas')) {
            $table = $schema->createTable('ncquest_adventure_areas');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('area_number', 'integer', [
                'notnull' => true,
                'comment' => 'Sequential area number (1, 2, 3...)',
            ]);
            $table->addColumn('age_key', 'string', [
                'notnull' => true,
                'length' => 20,
                'comment' => 'Age theme (stone, bronze, iron, etc.)',
            ]);
            $table->addColumn('nodes_explored', 'integer', [
                'notnull' => true,
                'default' => 0,
                'comment' => 'Number of nodes completed',
            ]);
            $table->addColumn('total_nodes', 'integer', [
                'notnull' => true,
                'default' => 49,
                'comment' => 'Total nodes in the map',
            ]);
            $table->addColumn('is_completed', 'integer', [
                'notnull' => true,
                'default' => 0,
                'length' => 1,
                'comment' => 'Whether boss was defeated',
            ]);
            $table->addColumn('completed_at', 'datetime', [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id'], 'quest_adv_areas_pk');
            $table->addIndex(['user_id'], 'quest_adv_areas_user');
            $table->addIndex(['user_id', 'area_number'], 'quest_adv_areas_user_num');
            $table->addUniqueIndex(['user_id', 'area_number'], 'quest_adv_areas_unique');
        }

        // Table 2: Adventure Maps (current map state)
        if (!$schema->hasTable('ncquest_adventure_maps')) {
            $table = $schema->createTable('ncquest_adventure_maps');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('area_id', 'integer', [
                'notnull' => true,
                'comment' => 'Reference to ncquest_adventure_areas.id',
            ]);
            $table->addColumn('node_id', 'string', [
                'notnull' => true,
                'length' => 20,
                'comment' => 'Unique node identifier (e.g., "node_3_4")',
            ]);
            $table->addColumn('node_type', 'string', [
                'notnull' => true,
                'length' => 20,
                'comment' => 'START, COMBAT, SHOP, TREASURE, EVENT, BOSS',
            ]);
            $table->addColumn('grid_x', 'integer', [
                'notnull' => true,
                'comment' => 'X coordinate in 7x7 grid (0-6)',
            ]);
            $table->addColumn('grid_y', 'integer', [
                'notnull' => true,
                'comment' => 'Y coordinate in 7x7 grid (0-6)',
            ]);
            $table->addColumn('connections', 'text', [
                'notnull' => true,
                'comment' => 'JSON array of connected node IDs',
            ]);
            $table->addColumn('is_unlocked', 'integer', [
                'notnull' => true,
                'default' => 0,
                'length' => 1,
                'comment' => 'Whether player can access this node',
            ]);
            $table->addColumn('is_completed', 'integer', [
                'notnull' => true,
                'default' => 0,
                'length' => 1,
                'comment' => 'Whether node has been cleared',
            ]);
            $table->addColumn('reward_data', 'text', [
                'notnull' => false,
                'comment' => 'JSON data for node rewards',
            ]);

            $table->setPrimaryKey(['id'], 'quest_adv_maps_pk');
            $table->addIndex(['user_id'], 'quest_adv_maps_user');
            $table->addIndex(['area_id'], 'quest_adv_maps_area');
            $table->addUniqueIndex(['area_id', 'node_id'], 'quest_adv_maps_node_unique');
        }

        // Table 3: Adventure Progress (player position and state)
        if (!$schema->hasTable('ncquest_adventure_progress')) {
            $table = $schema->createTable('ncquest_adventure_progress');

            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('current_area_id', 'integer', [
                'notnull' => false,
                'comment' => 'Reference to current ncquest_adventure_areas.id',
            ]);
            $table->addColumn('current_node_id', 'string', [
                'notnull' => false,
                'length' => 20,
                'comment' => 'Current node player is on',
            ]);
            $table->addColumn('total_areas_completed', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('total_nodes_explored', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('total_bosses_defeated', 'integer', [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id'], 'quest_adv_progress_pk');
            $table->addUniqueIndex(['user_id'], 'quest_adv_progress_user');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        $output->info('Adventure map system tables created successfully');
    }
}
