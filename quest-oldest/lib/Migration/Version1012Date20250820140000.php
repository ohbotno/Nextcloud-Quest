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
 * Add missing xp_gained_today field to match other tile storage patterns
 */
class Version1012Date20250820140000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Add missing xp_gained_today field to ncquest_users table
        if ($schema->hasTable('ncquest_users')) {
            $table = $schema->getTable('ncquest_users');
            
            if (!$table->hasColumn('xp_gained_today')) {
                $table->addColumn('xp_gained_today', Types::INTEGER, [
                    'notnull' => true,
                    'default' => 0,
                    'unsigned' => true,
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
        $output->info('Added xp_gained_today field to ncquest_users table');
        $output->info('This matches the storage pattern used by other dashboard tiles');
        $output->info('XP gained today will now be stored and reset daily like other counters');
    }
}