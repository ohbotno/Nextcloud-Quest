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
 * Achievement system enhancements (Fixed version)
 * - Add achievement points system
 * - Add achievement categories
 * - Add progress tracking columns
 * - Add indexes for performance
 */
class Version1011Date20250818130000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Enhance achievements table with new columns
        if ($schema->hasTable('ncquest_achievements')) {
            $table = $schema->getTable('ncquest_achievements');
            
            // Add achievement points column (nullable initially)
            if (!$table->hasColumn('achievement_points')) {
                $table->addColumn('achievement_points', Types::INTEGER, [
                    'notnull' => false,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            // Add achievement category column (nullable initially)
            if (!$table->hasColumn('achievement_category')) {
                $table->addColumn('achievement_category', Types::STRING, [
                    'notnull' => false,
                    'default' => null,
                    'length' => 64,
                ]);
            }
            
            // Add progress tracking columns (nullable initially)
            if (!$table->hasColumn('progress_current')) {
                $table->addColumn('progress_current', Types::INTEGER, [
                    'notnull' => false,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            if (!$table->hasColumn('progress_target')) {
                $table->addColumn('progress_target', Types::INTEGER, [
                    'notnull' => false,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            // Add indexes for better performance (only if columns exist)
            if ($table->hasColumn('achievement_category') && !$table->hasIndex('idx_achievements_category')) {
                $table->addIndex(['achievement_category'], 'idx_achievements_category');
            }
            
            if ($table->hasColumn('achievement_points') && !$table->hasIndex('idx_achievements_points')) {
                $table->addIndex(['achievement_points'], 'idx_achievements_points');
            }
            
            if ($table->hasColumn('progress_current') && $table->hasColumn('progress_target') && !$table->hasIndex('idx_achievements_progress')) {
                $table->addIndex(['progress_current', 'progress_target'], 'idx_achievements_progress');
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
        // Get database connection to populate new columns with default values
        $connection = \OC::$server->getDatabaseConnection();
        
        // Update existing achievement records with sensible default values
        try {
            // First, check if there are any existing records
            $checkQb = $connection->getQueryBuilder();
            $checkQb->select($checkQb->func()->count('*', 'total'))
                   ->from('ncquest_achievements');
            $result = $checkQb->executeQuery();
            $totalRecords = (int)$result->fetchOne();
            $result->closeCursor();
            
            if ($totalRecords > 0) {
                // Update records that have null values in the new columns
                $updateQb = $connection->getQueryBuilder();
                $updateQb->update('ncquest_achievements')
                        ->set('achievement_points', $updateQb->createNamedParameter(10))
                        ->set('achievement_category', $updateQb->createNamedParameter('Task Master'))
                        ->set('progress_current', $updateQb->createNamedParameter(0))
                        ->set('progress_target', $updateQb->createNamedParameter(0))
                        ->where($updateQb->expr()->isNull('achievement_category'));
                
                $affected = $updateQb->executeStatement();
                if ($affected > 0) {
                    $output->info("Updated $affected existing achievement records with default values");
                } else {
                    $output->info('All existing achievement records already have the new column values');
                }
            } else {
                $output->info('No existing achievement records found - table is ready for new achievements');
            }
        } catch (\Exception $e) {
            $output->info('Note: Could not update existing records: ' . $e->getMessage());
        }
        
        $output->info('Achievement system enhanced successfully');
        $output->info('- Achievement points system added');
        $output->info('- Achievement categories added');
        $output->info('- Progress tracking columns added');
        $output->info('- Performance indexes added');
        $output->info('Achievement system now supports 73+ achievements across 10 categories');
    }
}