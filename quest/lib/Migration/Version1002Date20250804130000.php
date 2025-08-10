<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Fix Boolean Column Issues
 */
class Version1002Date20250804130000 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Fix character_ages table
        if ($schema->hasTable('ncquest_character_ages')) {
            $table = $schema->getTable('ncquest_character_ages');
            
            if ($table->hasColumn('is_active')) {
                $table->dropColumn('is_active');
            }
            
            $table->addColumn('is_active', 'integer', [
                'notnull' => true,
                'default' => 1,
                'length' => 1,
                'comment' => 'Whether this age is currently active (1=true, 0=false)'
            ]);
        }

        // Fix character_items table
        if ($schema->hasTable('ncquest_character_items')) {
            $table = $schema->getTable('ncquest_character_items');
            
            if ($table->hasColumn('is_active')) {
                $table->dropColumn('is_active');
            }
            
            $table->addColumn('is_active', 'integer', [
                'notnull' => true,
                'default' => 1,
                'length' => 1,
                'comment' => 'Whether this item is currently available (1=true, 0=false)'
            ]);
        }

        return $schema;
    }
}