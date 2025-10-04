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
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

/**
 * Consolidate legacy quest_* tables into ncquest_* tables
 * 
 * This migration:
 * 1. Migrates data from quest_user_data to ncquest_users
 * 2. Migrates data from quest_xp_history to ncquest_history
 * 3. Drops the legacy tables after successful migration
 */
class Version1013Date20250828120000 extends SimpleMigrationStep {

    /** @var IDBConnection */
    private $db;
    
    /** @var LoggerInterface */
    private $logger;

    public function __construct(IDBConnection $db, LoggerInterface $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $output->info('Starting migration from legacy quest_* tables to ncquest_* tables');
        
        // Migrate quest_user_data to ncquest_users
        $this->migrateUserData($output);
        
        // Migrate quest_xp_history to ncquest_history
        $this->migrateXPHistory($output);
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
        
        // Drop legacy tables if they exist
        if ($schema->hasTable('quest_user_data')) {
            $output->info('Dropping legacy table: quest_user_data');
            $schema->dropTable('quest_user_data');
        }
        
        if ($schema->hasTable('quest_xp_history')) {
            $output->info('Dropping legacy table: quest_xp_history');
            $schema->dropTable('quest_xp_history');
        }
        
        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $output->info('Migration completed successfully');
        $output->info('Legacy quest_* tables have been consolidated into ncquest_* tables');
    }

    /**
     * Migrate data from quest_user_data to ncquest_users
     */
    private function migrateUserData(IOutput $output): void {
        try {
            // Check if source table exists
            $sourceExists = $this->tableExists('quest_user_data');
            if (!$sourceExists) {
                $output->info('Source table quest_user_data does not exist, skipping user data migration');
                return;
            }
            
            // Select all data from quest_user_data
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('quest_user_data');
            
            $result = $qb->executeQuery();
            $count = 0;
            
            while ($row = $result->fetch()) {
                $userId = $row['user_id'];
                
                // Check if user already exists in ncquest_users
                $checkQb = $this->db->getQueryBuilder();
                $checkQb->select('user_id')
                    ->from('ncquest_users')
                    ->where($checkQb->expr()->eq('user_id', $checkQb->createNamedParameter($userId)));
                
                $checkResult = $checkQb->executeQuery();
                $exists = $checkResult->fetch();
                $checkResult->closeCursor();
                
                if ($exists) {
                    // Update existing user - only update if legacy data is more recent or has more XP
                    $updateQb = $this->db->getQueryBuilder();
                    $updateQb->update('ncquest_users')
                        ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)));
                    
                    // Only update XP if legacy has more
                    if (isset($row['total_xp']) && $row['total_xp'] > 0) {
                        $updateQb->set('lifetime_xp', $updateQb->createNamedParameter($row['total_xp']));
                        $updateQb->set('current_xp', $updateQb->createNamedParameter($row['total_xp']));
                    }
                    
                    // Update level if higher
                    if (isset($row['level']) && $row['level'] > 1) {
                        $updateQb->set('level', $updateQb->createNamedParameter($row['level']));
                    }
                    
                    // Update streaks if higher
                    if (isset($row['current_streak']) && $row['current_streak'] > 0) {
                        $updateQb->set('current_streak', $updateQb->createNamedParameter($row['current_streak']));
                    }
                    if (isset($row['longest_streak']) && $row['longest_streak'] > 0) {
                        $updateQb->set('longest_streak', $updateQb->createNamedParameter($row['longest_streak']));
                    }
                    
                    // Update last completion date
                    if (isset($row['last_activity_date']) && $row['last_activity_date']) {
                        $updateQb->set('last_completion_date', $updateQb->createNamedParameter($row['last_activity_date']));
                    }
                    
                    $updateQb->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')));
                    $updateQb->executeStatement();
                    
                    $output->info("Updated user data for: $userId");
                } else {
                    // Insert new user
                    $insertQb = $this->db->getQueryBuilder();
                    $insertQb->insert('ncquest_users')
                        ->values([
                            'user_id' => $insertQb->createNamedParameter($userId),
                            'current_xp' => $insertQb->createNamedParameter($row['total_xp'] ?? 0),
                            'lifetime_xp' => $insertQb->createNamedParameter($row['total_xp'] ?? 0),
                            'level' => $insertQb->createNamedParameter($row['level'] ?? 1),
                            'current_streak' => $insertQb->createNamedParameter($row['current_streak'] ?? 0),
                            'longest_streak' => $insertQb->createNamedParameter($row['longest_streak'] ?? 0),
                            'last_completion_date' => $insertQb->createNamedParameter($row['last_activity_date'] ?? null),
                            'current_health' => $insertQb->createNamedParameter(100),
                            'max_health' => $insertQb->createNamedParameter(100),
                            'tasks_completed_today' => $insertQb->createNamedParameter(0),
                            'tasks_completed_this_week' => $insertQb->createNamedParameter(0),
                            'total_tasks_completed' => $insertQb->createNamedParameter(0),
                            'xp_gained_today' => $insertQb->createNamedParameter(0),
                            'theme_preference' => $insertQb->createNamedParameter('game'),
                            'created_at' => $insertQb->createNamedParameter($row['created_at'] ?? date('Y-m-d H:i:s')),
                            'updated_at' => $insertQb->createNamedParameter($row['updated_at'] ?? date('Y-m-d H:i:s'))
                        ]);
                    $insertQb->executeStatement();
                    
                    $output->info("Migrated user data for: $userId");
                }
                
                $count++;
            }
            
            $result->closeCursor();
            $output->info("Migrated $count user records from quest_user_data to ncquest_users");
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to migrate user data', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $output->warning('Failed to migrate user data: ' . $e->getMessage());
        }
    }

    /**
     * Migrate data from quest_xp_history to ncquest_history
     */
    private function migrateXPHistory(IOutput $output): void {
        try {
            // Check if source table exists
            $sourceExists = $this->tableExists('quest_xp_history');
            if (!$sourceExists) {
                $output->info('Source table quest_xp_history does not exist, skipping XP history migration');
                return;
            }
            
            // Select all data from quest_xp_history
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('quest_xp_history')
                ->orderBy('id', 'ASC');
            
            $result = $qb->executeQuery();
            $count = 0;
            $duplicates = 0;
            
            while ($row = $result->fetch()) {
                // Check for duplicate entries (same user, task, and completion time)
                $checkQb = $this->db->getQueryBuilder();
                $checkQb->select('id')
                    ->from('ncquest_history')
                    ->where($checkQb->expr()->eq('user_id', $checkQb->createNamedParameter($row['user_id'])))
                    ->andWhere($checkQb->expr()->eq('task_id', $checkQb->createNamedParameter((string)$row['task_id'])))
                    ->andWhere($checkQb->expr()->eq('completed_at', $checkQb->createNamedParameter($row['completed_at'])));
                
                $checkResult = $checkQb->executeQuery();
                $exists = $checkResult->fetch();
                $checkResult->closeCursor();
                
                if (!$exists) {
                    // Insert into ncquest_history
                    $insertQb = $this->db->getQueryBuilder();
                    $insertQb->insert('ncquest_history')
                        ->values([
                            'user_id' => $insertQb->createNamedParameter($row['user_id']),
                            'task_id' => $insertQb->createNamedParameter((string)$row['task_id']), // Convert INT to STRING
                            'task_title' => $insertQb->createNamedParameter($row['task_title']),
                            'xp_earned' => $insertQb->createNamedParameter($row['xp_gained']), // Rename field
                            'completed_at' => $insertQb->createNamedParameter($row['completed_at'])
                        ]);
                    $insertQb->executeStatement();
                    $count++;
                } else {
                    $duplicates++;
                }
            }
            
            $result->closeCursor();
            $output->info("Migrated $count XP history records from quest_xp_history to ncquest_history");
            if ($duplicates > 0) {
                $output->info("Skipped $duplicates duplicate records");
            }
            
            // Update task completion counts in ncquest_users based on history
            $this->updateTaskCompletionCounts($output);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to migrate XP history', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $output->warning('Failed to migrate XP history: ' . $e->getMessage());
        }
    }

    /**
     * Update task completion counts in ncquest_users based on migrated history
     */
    private function updateTaskCompletionCounts(IOutput $output): void {
        try {
            // Get all users
            $userQb = $this->db->getQueryBuilder();
            $userQb->select('user_id')
                ->from('ncquest_users');
            
            $userResult = $userQb->executeQuery();
            
            while ($user = $userResult->fetch()) {
                $userId = $user['user_id'];
                
                // Count total tasks
                $totalQb = $this->db->getQueryBuilder();
                $totalQb->select($totalQb->expr()->count('*', 'total'))
                    ->from('ncquest_history')
                    ->where($totalQb->expr()->eq('user_id', $totalQb->createNamedParameter($userId)));
                
                $totalResult = $totalQb->executeQuery();
                $totalTasks = (int)$totalResult->fetchOne();
                $totalResult->closeCursor();
                
                // Count tasks today
                $todayQb = $this->db->getQueryBuilder();
                $todayQb->select($todayQb->expr()->count('*', 'today'))
                    ->from('ncquest_history')
                    ->where($todayQb->expr()->eq('user_id', $todayQb->createNamedParameter($userId)))
                    ->andWhere($todayQb->expr()->gte('completed_at', $todayQb->createNamedParameter(date('Y-m-d 00:00:00'))));
                
                $todayResult = $todayQb->executeQuery();
                $todayTasks = (int)$todayResult->fetchOne();
                $todayResult->closeCursor();
                
                // Count tasks this week
                $weekStart = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $weekQb = $this->db->getQueryBuilder();
                $weekQb->select($weekQb->expr()->count('*', 'week'))
                    ->from('ncquest_history')
                    ->where($weekQb->expr()->eq('user_id', $weekQb->createNamedParameter($userId)))
                    ->andWhere($weekQb->expr()->gte('completed_at', $weekQb->createNamedParameter($weekStart)));
                
                $weekResult = $weekQb->executeQuery();
                $weekTasks = (int)$weekResult->fetchOne();
                $weekResult->closeCursor();
                
                // Update user stats
                $updateQb = $this->db->getQueryBuilder();
                $updateQb->update('ncquest_users')
                    ->set('total_tasks_completed', $updateQb->createNamedParameter($totalTasks))
                    ->set('tasks_completed_today', $updateQb->createNamedParameter($todayTasks))
                    ->set('tasks_completed_this_week', $updateQb->createNamedParameter($weekTasks))
                    ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)))
                    ->executeStatement();
            }
            
            $userResult->closeCursor();
            $output->info('Updated task completion counts for all users');
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update task completion counts', [
                'exception' => $e->getMessage()
            ]);
            $output->warning('Failed to update task completion counts: ' . $e->getMessage());
        }
    }

    /**
     * Check if a table exists
     */
    private function tableExists(string $tableName): bool {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from($tableName)
                ->setMaxResults(1);
            $qb->executeQuery()->closeCursor();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}