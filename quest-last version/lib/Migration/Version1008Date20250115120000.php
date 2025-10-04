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
use OCP\IDBConnection;

/**
 * Consolidate stats tables and add health/task tracking
 */
class Version1008Date20250115120000 extends SimpleMigrationStep {

    /** @var IDBConnection */
    private $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Add new columns to ncquest_users table
        if ($schema->hasTable('ncquest_users')) {
            $table = $schema->getTable('ncquest_users');
            
            // Add health system columns
            if (!$table->hasColumn('current_health')) {
                $table->addColumn('current_health', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 100,
                    'unsigned' => true,
                ]);
            }
            
            if (!$table->hasColumn('max_health')) {
                $table->addColumn('max_health', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 100,
                    'unsigned' => true,
                ]);
            }
            
            // Add task tracking columns
            if (!$table->hasColumn('tasks_completed_today')) {
                $table->addColumn('tasks_completed_today', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            if (!$table->hasColumn('tasks_completed_this_week')) {
                $table->addColumn('tasks_completed_this_week', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            if (!$table->hasColumn('total_tasks_completed')) {
                $table->addColumn('total_tasks_completed', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            // Add last task completion tracking for daily/weekly resets
            if (!$table->hasColumn('last_task_completion_date')) {
                $table->addColumn('last_task_completion_date', Types::DATETIME, [
                    'notnull' => false,
                ]);
            }
            
            // Add daily/weekly reset tracking
            if (!$table->hasColumn('last_daily_reset')) {
                $table->addColumn('last_daily_reset', Types::DATE, [
                    'notnull' => false,
                ]);
            }
            
            if (!$table->hasColumn('last_weekly_reset')) {
                $table->addColumn('last_weekly_reset', Types::DATE, [
                    'notnull' => false,
                ]);
            }
        }

        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        // Initialize health for existing users
        $this->initializeHealthForExistingUsers($output);
        
        // Note: Data migration and legacy table cleanup is handled by Version1013Date20250828120000
        $output->info('Column additions completed. Data migration handled by later migration.');
    }


    /**
     * Initialize health for existing users
     */
    private function initializeHealthForExistingUsers(IOutput $output): void {
        try {
            $output->info('Initializing health for existing users...');

            // Set health to 100 for all users who don't have health set
            $updateQb = $this->db->getQueryBuilder();
            $updateQb->update('ncquest_users')
                ->set('current_health', $updateQb->createNamedParameter(100))
                ->set('max_health', $updateQb->createNamedParameter(100))
                ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                ->where($updateQb->expr()->orX(
                    $updateQb->expr()->isNull('current_health'),
                    $updateQb->expr()->eq('current_health', $updateQb->createNamedParameter(0))
                ));
            
            $updatedCount = $updateQb->executeStatement();
            $output->info("Initialized health for {$updatedCount} users");

        } catch (\Exception $e) {
            $output->warning('Failed to initialize health: ' . $e->getMessage());
        }
    }

}