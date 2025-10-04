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
        // Migrate data from quest_user_data to ncquest_users if needed
        $this->migrateDataFromQuestUserData($output);
        
        // Initialize task counts from history
        $this->initializeTaskCounts($output);
        
        // Initialize health for existing users
        $this->initializeHealthForExistingUsers($output);
        
        // Drop the redundant quest_user_data table
        $this->dropRedundantTable($output, $schemaClosure);
    }

    /**
     * Migrate data from quest_user_data to ncquest_users
     */
    private function migrateDataFromQuestUserData(IOutput $output): void {
        try {
            // Check if quest_user_data table exists
            $qb = $this->db->getQueryBuilder();
            $qb->select('1')->from('quest_user_data')->setMaxResults(1);
            $result = $qb->executeQuery();
            $tableExists = $result->fetchOne() !== false;
            $result->closeCursor();
            
            if (!$tableExists) {
                $output->info('quest_user_data table does not exist, skipping migration');
                return;
            }

            $output->info('Migrating data from quest_user_data to ncquest_users...');

            // Get all data from quest_user_data
            $selectQb = $this->db->getQueryBuilder();
            $selectQb->select('user_id', 'total_xp', 'level')
                ->from('quest_user_data');
            
            $result = $selectQb->executeQuery();
            $userData = $result->fetchAll();
            $result->closeCursor();

            foreach ($userData as $row) {
                $userId = $row['user_id'];
                $totalXp = $row['total_xp'] ?? 0;
                $level = $row['level'] ?? 1;

                // Check if user already exists in ncquest_users
                $checkQb = $this->db->getQueryBuilder();
                $checkQb->select('user_id')
                    ->from('ncquest_users')
                    ->where($checkQb->expr()->eq('user_id', $checkQb->createNamedParameter($userId)));
                
                $checkResult = $checkQb->executeQuery();
                $userExists = $checkResult->fetchOne() !== false;
                $checkResult->closeCursor();

                if ($userExists) {
                    // Update existing user with missing data
                    $updateQb = $this->db->getQueryBuilder();
                    $updateQb->update('ncquest_users')
                        ->set('lifetime_xp', $updateQb->createNamedParameter(max($totalXp, 0)))
                        ->set('level', $updateQb->createNamedParameter(max($level, 1)))
                        ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                        ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)));
                    
                    $updateQb->executeStatement();
                } else {
                    // Insert new user
                    $insertQb = $this->db->getQueryBuilder();
                    $insertQb->insert('ncquest_users')
                        ->values([
                            'user_id' => $insertQb->createNamedParameter($userId),
                            'current_xp' => $insertQb->createNamedParameter(0),
                            'lifetime_xp' => $insertQb->createNamedParameter(max($totalXp, 0)),
                            'level' => $insertQb->createNamedParameter(max($level, 1)),
                            'current_streak' => $insertQb->createNamedParameter(0),
                            'longest_streak' => $insertQb->createNamedParameter(0),
                            'current_health' => $insertQb->createNamedParameter(100),
                            'max_health' => $insertQb->createNamedParameter(100),
                            'tasks_completed_today' => $insertQb->createNamedParameter(0),
                            'tasks_completed_this_week' => $insertQb->createNamedParameter(0),
                            'total_tasks_completed' => $insertQb->createNamedParameter(0),
                            'theme_preference' => $insertQb->createNamedParameter('game'),
                            'created_at' => $insertQb->createNamedParameter(date('Y-m-d H:i:s')),
                            'updated_at' => $insertQb->createNamedParameter(date('Y-m-d H:i:s'))
                        ]);
                    
                    $insertQb->executeStatement();
                }
            }

            $output->info('Data migration completed successfully');

        } catch (\Exception $e) {
            $output->warning('Failed to migrate data from quest_user_data: ' . $e->getMessage());
        }
    }

    /**
     * Initialize task counts from history
     */
    private function initializeTaskCounts(IOutput $output): void {
        try {
            $output->info('Initializing task counts from history...');

            // Get all users from ncquest_users
            $usersQb = $this->db->getQueryBuilder();
            $usersQb->select('user_id')->from('ncquest_users');
            $usersResult = $usersQb->executeQuery();
            $users = $usersResult->fetchAll();
            $usersResult->closeCursor();

            foreach ($users as $user) {
                $userId = $user['user_id'];

                // Calculate total tasks from history
                $totalQb = $this->db->getQueryBuilder();
                $totalQb->select($totalQb->expr()->count('*', 'total'))
                    ->from('ncquest_history')
                    ->where($totalQb->expr()->eq('user_id', $totalQb->createNamedParameter($userId)));
                
                $totalResult = $totalQb->executeQuery();
                $totalTasks = (int)($totalResult->fetchOne() ?: 0);
                $totalResult->closeCursor();

                // Calculate tasks completed today
                $todayQb = $this->db->getQueryBuilder();
                $todayQb->select($todayQb->expr()->count('*', 'today'))
                    ->from('ncquest_history')
                    ->where($todayQb->expr()->eq('user_id', $todayQb->createNamedParameter($userId)))
                    ->andWhere($todayQb->expr()->gte('completed_at', $todayQb->createNamedParameter(date('Y-m-d 00:00:00'))));
                
                $todayResult = $todayQb->executeQuery();
                $todayTasks = (int)($todayResult->fetchOne() ?: 0);
                $todayResult->closeCursor();

                // Calculate tasks completed this week
                $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $weekQb = $this->db->getQueryBuilder();
                $weekQb->select($weekQb->expr()->count('*', 'week'))
                    ->from('ncquest_history')
                    ->where($weekQb->expr()->eq('user_id', $weekQb->createNamedParameter($userId)))
                    ->andWhere($weekQb->expr()->gte('completed_at', $weekQb->createNamedParameter($weekStart)));
                
                $weekResult = $weekQb->executeQuery();
                $weekTasks = (int)($weekResult->fetchOne() ?: 0);
                $weekResult->closeCursor();

                // Update user with task counts
                $updateQb = $this->db->getQueryBuilder();
                $updateQb->update('ncquest_users')
                    ->set('total_tasks_completed', $updateQb->createNamedParameter($totalTasks))
                    ->set('tasks_completed_today', $updateQb->createNamedParameter($todayTasks))
                    ->set('tasks_completed_this_week', $updateQb->createNamedParameter($weekTasks))
                    ->set('last_daily_reset', $updateQb->createNamedParameter(date('Y-m-d')))
                    ->set('last_weekly_reset', $updateQb->createNamedParameter(date('Y-m-d', strtotime('monday this week'))))
                    ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)));
                
                $updateQb->executeStatement();
            }

            $output->info('Task counts initialized successfully');

        } catch (\Exception $e) {
            $output->warning('Failed to initialize task counts: ' . $e->getMessage());
        }
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

    /**
     * Drop the redundant quest_user_data table
     */
    private function dropRedundantTable(IOutput $output, Closure $schemaClosure): void {
        try {
            /** @var ISchemaWrapper $schema */
            $schema = $schemaClosure();
            
            if ($schema->hasTable('quest_user_data')) {
                $output->info('Dropping redundant quest_user_data table...');
                $schema->dropTable('quest_user_data');
                $output->info('quest_user_data table dropped successfully');
            }

        } catch (\Exception $e) {
            $output->warning('Failed to drop quest_user_data table: ' . $e->getMessage());
        }
    }
}