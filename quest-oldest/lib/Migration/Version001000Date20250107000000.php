<?php

declare(strict_types=1);

namespace OCA\NextcloudQuest\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create task list preferences table for storing user customizations
 */
class Version001000Date20250107000000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create task_list_preferences table if it doesn't exist
        if (!$schema->hasTable('quest_tlp')) {
            $table = $schema->createTable('quest_tlp');
            
            // Primary key
            $table->addColumn('id', 'bigint', [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            
            // User ID (foreign key to users table)
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
            ]);
            
            // Task list ID
            $table->addColumn('task_list_id', 'bigint', [
                'notnull' => true,
                'unsigned' => true,
            ]);
            
            // Color preference (hex color code)
            $table->addColumn('color', 'string', [
                'notnull' => false,
                'length' => 7,
                'default' => null,
            ]);
            
            // Visibility preference (boolean stored as string for compatibility)
            $table->addColumn('visible', 'string', [
                'notnull' => true,
                'default' => 'true',
                'length' => 5,
            ]);
            
            // Display order preference
            $table->addColumn('display_order', 'integer', [
                'notnull' => false,
                'default' => null,
            ]);
            
            // Timestamps
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);
            
            $table->addColumn('updated_at', 'datetime', [
                'notnull' => true,
            ]);
            
            // Set primary key
            $table->setPrimaryKey(['id']);
            
            // Add unique constraint on user_id + task_list_id
            $table->addUniqueIndex(['user_id', 'task_list_id'], 'q_tlp_user_list');
            
            // Add index on user_id for faster queries
            $table->addIndex(['user_id'], 'q_tlp_user_idx');
            
            $output->info('Created quest_tlp table');
        }

        return $schema;
    }
}