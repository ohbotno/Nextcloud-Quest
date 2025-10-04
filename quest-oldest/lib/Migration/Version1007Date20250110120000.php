<?php

declare(strict_types=1);

namespace OCA\NextcloudQuest\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration for Adventure Path System tables
 * Creates all necessary tables for the Mario-style world map feature
 */
class Version1007Date20250110120000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Adventure Worlds Table - Defines the 8 themed worlds
        if (!$schema->hasTable('quest_adv_worlds')) {
            $table = $schema->createTable('quest_adv_worlds');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('world_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 100,
            ]);
            $table->addColumn('theme', Types::STRING, [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('description', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('color_primary', Types::STRING, [
                'notnull' => true,
                'length' => 7,
            ]);
            $table->addColumn('color_secondary', Types::STRING, [
                'notnull' => true,
                'length' => 7,
            ]);
            $table->addColumn('task_focus', Types::STRING, [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('difficulty_modifier', Types::DECIMAL, [
                'notnull' => true,
                'precision' => 3,
                'scale' => 1,
            ]);
            $table->addColumn('icon', Types::STRING, [
                'notnull' => true,
                'length' => 10,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['world_number'], 'adv_world_num_uniq');
        }

        // Adventure Boss Levels Table - Global boss challenges same for all players
        if (!$schema->hasTable('quest_adv_bosses')) {
            $table = $schema->createTable('quest_adv_bosses');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('world_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('boss_type', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'default' => 'boss'
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 100,
            ]);
            $table->addColumn('description', Types::TEXT, [
                'notnull' => true,
            ]);
            $table->addColumn('objective_type', Types::STRING, [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('objective_data', Types::JSON, [
                'notnull' => true,
            ]);
            $table->addColumn('reward_xp', Types::INTEGER, [
                'notnull' => true,
                'length' => 8,
            ]);
            $table->addColumn('icon', Types::STRING, [
                'notnull' => true,
                'length' => 10,
            ]);
            $table->addColumn('is_active', Types::BOOLEAN, [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['world_number'], 'adv_boss_world');
            $table->addIndex(['boss_type'], 'adv_boss_type');
        }

        // Adventure Paths Table - Generated paths with world references
        if (!$schema->hasTable('quest_adv_paths')) {
            $table = $schema->createTable('quest_adv_paths');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('world_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('path_data', Types::JSON, [
                'notnull' => true,
            ]);
            $table->addColumn('structure_data', Types::JSON, [
                'notnull' => true,
            ]);
            $table->addColumn('connections_data', Types::JSON, [
                'notnull' => true,
            ]);
            $table->addColumn('total_levels', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('mini_boss_position', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('status', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'default' => 'active'
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'adv_paths_user');
            $table->addIndex(['world_number'], 'adv_paths_world');
            $table->addUniqueIndex(['user_id', 'world_number'], 'adv_paths_uniq');
        }

        // Adventure Levels Table - Individual level data with boss_type field
        if (!$schema->hasTable('quest_adv_levels')) {
            $table = $schema->createTable('quest_adv_levels');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('path_id', Types::BIGINT, [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('world_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('level_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('structure_key', Types::STRING, [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('name', Types::STRING, [
                'notnull' => true,
                'length' => 100,
            ]);
            $table->addColumn('description', Types::TEXT, [
                'notnull' => false,
            ]);
            $table->addColumn('level_type', Types::STRING, [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('boss_type', Types::STRING, [
                'notnull' => false,
                'length' => 20,
            ]);
            $table->addColumn('theme', Types::STRING, [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('position_x', Types::INTEGER, [
                'notnull' => true,
                'length' => 6,
            ]);
            $table->addColumn('position_y', Types::INTEGER, [
                'notnull' => true,
                'length' => 6,
            ]);
            $table->addColumn('reward_xp', Types::INTEGER, [
                'notnull' => true,
                'length' => 8,
            ]);
            $table->addColumn('status', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'default' => 'locked'
            ]);
            $table->addColumn('icon', Types::STRING, [
                'notnull' => true,
                'length' => 10,
            ]);
            $table->addColumn('is_global', Types::BOOLEAN, [
                'notnull' => false,
            ]);
            $table->addColumn('completed_at', Types::DATETIME, [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['path_id'], 'adv_levels_path');
            $table->addIndex(['user_id'], 'adv_levels_user');
            $table->addIndex(['world_number'], 'adv_levels_world');
            $table->addIndex(['level_type'], 'adv_levels_type');
            $table->addIndex(['status'], 'adv_levels_status');
        }

        // Adventure Objectives Table - Level goals and requirements
        if (!$schema->hasTable('quest_adv_objectives')) {
            $table = $schema->createTable('quest_adv_objectives');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('level_id', Types::BIGINT, [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('objective_type', Types::STRING, [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('objective_data', Types::JSON, [
                'notnull' => true,
            ]);
            $table->addColumn('description', Types::TEXT, [
                'notnull' => true,
            ]);
            $table->addColumn('task_id', Types::STRING, [
                'notnull' => false,
                'length' => 64,
            ]);
            $table->addColumn('task_title', Types::STRING, [
                'notnull' => false,
                'length' => 200,
            ]);
            $table->addColumn('is_completed', Types::BOOLEAN, [
                'notnull' => false,
            ]);
            $table->addColumn('completed_at', Types::DATETIME, [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['level_id'], 'adv_obj_level');
            $table->addIndex(['objective_type'], 'adv_obj_type');
            $table->addIndex(['task_id'], 'adv_obj_task');
            $table->addIndex(['is_completed'], 'adv_obj_completed');
        }

        // Adventure Player Progress Table - World progression tracking
        if (!$schema->hasTable('quest_adv_progress')) {
            $table = $schema->createTable('quest_adv_progress');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('world_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('current_level_id', Types::BIGINT, [
                'notnull' => false,
                'length' => 20,
            ]);
            $table->addColumn('current_position', Types::STRING, [
                'notnull' => false,
                'length' => 50,
            ]);
            $table->addColumn('levels_completed', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'length' => 4,
            ]);
            $table->addColumn('total_levels', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('world_status', Types::STRING, [
                'notnull' => true,
                'length' => 20,
                'default' => 'locked'
            ]);
            $table->addColumn('boss_defeated', Types::BOOLEAN, [
                'notnull' => false,
            ]);
            $table->addColumn('mini_boss_defeated', Types::BOOLEAN, [
                'notnull' => false,
            ]);
            $table->addColumn('total_xp_earned', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'length' => 8,
            ]);
            $table->addColumn('started_at', Types::DATETIME, [
                'notnull' => false,
            ]);
            $table->addColumn('completed_at', Types::DATETIME, [
                'notnull' => false,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'adv_prog_user');
            $table->addIndex(['world_number'], 'adv_prog_world');
            $table->addIndex(['world_status'], 'adv_prog_status');
            $table->addUniqueIndex(['user_id', 'world_number'], 'adv_prog_uniq');
        }

        // Adventure Boss Completions Table - Global boss completion stats
        if (!$schema->hasTable('quest_adv_wins')) {
            $table = $schema->createTable('quest_adv_wins');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('world_number', Types::INTEGER, [
                'notnull' => true,
                'length' => 4,
            ]);
            $table->addColumn('boss_level_id', Types::BIGINT, [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('boss_type', Types::STRING, [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('completion_time_seconds', Types::INTEGER, [
                'notnull' => false,
                'length' => 8,
            ]);
            $table->addColumn('attempts_count', Types::INTEGER, [
                'notnull' => true,
                'default' => 1,
                'length' => 4,
            ]);
            $table->addColumn('xp_earned', Types::INTEGER, [
                'notnull' => true,
                'length' => 8,
            ]);
            $table->addColumn('completed_at', Types::DATETIME, [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'adv_boss_user');
            $table->addIndex(['world_number'], 'adv_boss_world2');
            $table->addIndex(['boss_type'], 'adv_boss_type2');
            $table->addIndex(['completed_at'], 'adv_boss_date');
            $table->addUniqueIndex(['user_id', 'world_number', 'boss_type'], 'adv_boss_uniq');
        }

        return $schema;
    }
}