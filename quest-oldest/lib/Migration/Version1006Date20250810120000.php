<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

declare(strict_types=1);

namespace OCA\NextcloudQuest\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create XP tracking tables for Quest system
 */
class Version1006Date20250810120000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create quest_user_data table if it doesn't exist
        if (!$schema->hasTable('quest_user_data')) {
            $table = $schema->createTable('quest_user_data');
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('total_xp', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
            ]);
            $table->addColumn('level', Types::INTEGER, [
                'notnull' => true,
                'default' => 1,
            ]);
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['user_id']);
            $table->addIndex(['user_id'], 'quest_user_data_user_id');
        }

        // Create quest_xp_history table if it doesn't exist
        if (!$schema->hasTable('quest_xp_history')) {
            $table = $schema->createTable('quest_xp_history');
            $table->addColumn('id', Types::BIGINT, [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            $table->addColumn('task_id', Types::INTEGER, [
                'notnull' => true,
            ]);
            $table->addColumn('task_title', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);
            $table->addColumn('xp_gained', Types::INTEGER, [
                'notnull' => true,
            ]);
            $table->addColumn('completed_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'quest_xp_history_user_id');
            $table->addIndex(['completed_at'], 'quest_xp_history_completed_at');
        }

        return $schema;
    }
}