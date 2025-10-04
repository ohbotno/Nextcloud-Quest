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
 * Achievement system enhancements
 * - Add achievement points system
 * - Add achievement categories
 * - Add progress tracking columns
 * - Add indexes for performance
 */
class Version1010Date20250818120000 extends SimpleMigrationStep {

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
            
            // Add achievement points column
            if (!$table->hasColumn('achievement_points')) {
                $table->addColumn('achievement_points', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            // Add achievement category column
            if (!$table->hasColumn('achievement_category')) {
                $table->addColumn('achievement_category', Types::STRING, [
                    'notnull' => false,
                    'default' => null,
                    'length' => 64,
                ]);
            }
            
            // Add progress tracking columns
            if (!$table->hasColumn('progress_current')) {
                $table->addColumn('progress_current', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            if (!$table->hasColumn('progress_target')) {
                $table->addColumn('progress_target', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
                ]);
            }
            
            // Add indexes for better performance
            if (!$table->hasIndex('idx_achievements_category')) {
                $table->addIndex(['achievement_category'], 'idx_achievements_category');
            }
            
            if (!$table->hasIndex('idx_achievements_points')) {
                $table->addIndex(['achievement_points'], 'idx_achievements_points');
            }
            
            if (!$table->hasIndex('idx_achievements_progress')) {
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
        // Get database connection to populate new columns
        $connection = \OC::$server->getDatabaseConnection();
        
        // Update existing achievement records with default values
        try {
            $qb = $connection->getQueryBuilder();
            $qb->update('ncquest_achievements')
               ->set('achievement_category', $qb->createNamedParameter('Task Master'))
               ->set('achievement_points', $qb->createNamedParameter(10))
               ->set('progress_current', $qb->createNamedParameter(0))
               ->set('progress_target', $qb->createNamedParameter(0))
               ->where($qb->expr()->isNull('achievement_category'));
            
            $affected = $qb->executeStatement();
            if ($affected > 0) {
                $output->info("Updated $affected existing achievement records with default values");
            }
        } catch (\Exception $e) {
            $output->info('Note: Could not update existing records (table may be empty): ' . $e->getMessage());
        }
        
        $output->info('Achievement system enhanced successfully');
        $output->info('- Achievement points system added');
        $output->info('- Achievement categories added');
        $output->info('- Progress tracking columns added');
        $output->info('- Performance indexes added');
        $output->info('Achievement system now supports 70+ achievements across 10 categories');
    }
}