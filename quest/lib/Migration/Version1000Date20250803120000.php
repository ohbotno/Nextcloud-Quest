<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\DB\Types;

class Version1000Date20250803120000 extends SimpleMigrationStep {
    
    /**
     * @param IOutput $output
     * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create quest users table
        if (!$schema->hasTable('ncquest_users')) {
            $table = $schema->createTable('ncquest_users');
            
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            
            $table->addColumn('current_xp', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);
            
            $table->addColumn('lifetime_xp', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);
            
            $table->addColumn('level', Types::INTEGER, [
                'notnull' => true,
                'default' => 1,
                'unsigned' => true,
            ]);
            
            $table->addColumn('current_streak', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);
            
            $table->addColumn('longest_streak', Types::INTEGER, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);
            
            $table->addColumn('last_completion_date', Types::DATETIME, [
                'notnull' => false,
            ]);
            
            $table->addColumn('theme_preference', Types::STRING, [
                'notnull' => true,
                'default' => 'game',
                'length' => 16,
            ]);
            
            $table->addColumn('created_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            
            $table->addColumn('updated_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            
            $table->setPrimaryKey(['user_id']);
            $table->addIndex(['level'], 'quest_users_level_idx');
            $table->addIndex(['lifetime_xp'], 'quest_users_xp_idx');
        }

        // Create achievements table
        if (!$schema->hasTable('ncquest_achievements')) {
            $table = $schema->createTable('ncquest_achievements');
            
            $table->addColumn('id', Types::INTEGER, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            
            $table->addColumn('achievement_key', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            
            $table->addColumn('unlocked_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            
            $table->addColumn('notified', Types::SMALLINT, [
                'notnull' => true,
                'default' => 0,
                'unsigned' => true,
            ]);
            
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['user_id', 'achievement_key'], 'quest_achievement_unique');
            $table->addIndex(['user_id'], 'quest_achievement_user_idx');
            $table->addIndex(['unlocked_at'], 'quest_achievement_date_idx');
        }

        // Create history table
        if (!$schema->hasTable('ncquest_history')) {
            $table = $schema->createTable('ncquest_history');
            
            $table->addColumn('id', Types::INTEGER, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            
            $table->addColumn('user_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            
            $table->addColumn('task_id', Types::STRING, [
                'notnull' => true,
                'length' => 64,
            ]);
            
            $table->addColumn('task_title', Types::STRING, [
                'notnull' => true,
                'length' => 255,
            ]);
            
            $table->addColumn('xp_earned', Types::INTEGER, [
                'notnull' => true,
                'unsigned' => true,
            ]);
            
            $table->addColumn('completed_at', Types::DATETIME, [
                'notnull' => true,
            ]);
            
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id', 'completed_at'], 'quest_history_user_date_idx');
            $table->addIndex(['completed_at'], 'quest_history_date_idx');
        }

        return $schema;
    }
}