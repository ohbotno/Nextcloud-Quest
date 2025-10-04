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
 * Character System Database Migration - Clean Version
 */
class Version1003Date20250804140000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Only create tables if they don't exist
        if (!$schema->hasTable('ncquest_character_ages')) {
            $table = $schema->createTable('ncquest_character_ages');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'comment' => 'Primary key'
            ]);
            $table->addColumn('age_key', 'string', [
                'notnull' => true,
                'length' => 20,
                'comment' => 'Unique identifier for the age (stone, bronze, iron, etc.)'
            ]);
            $table->addColumn('age_name', 'string', [
                'notnull' => true,
                'length' => 50,
                'comment' => 'Display name of the age'
            ]);
            $table->addColumn('min_level', 'integer', [
                'notnull' => true,
                'default' => 1,
                'comment' => 'Minimum level to reach this age'
            ]);
            $table->addColumn('is_active', 'integer', [
                'notnull' => true,
                'default' => 1,
                'length' => 1,
                'comment' => 'Whether this age is currently active (1=true, 0=false)'
            ]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['age_key'], 'ncquest_char_ages_key_uniq');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        // Insert basic age data
        $connection = \OC::$server->getDatabaseConnection();
        
        $ages = [
            ['age_key' => 'Stone', 'age_name' => 'Stone Age', 'min_level' => 1],
            ['age_key' => 'Bronze', 'age_name' => 'Bronze Age', 'min_level' => 6],
            ['age_key' => 'Iron', 'age_name' => 'Iron Age', 'min_level' => 11],
            ['age_key' => 'Classical', 'age_name' => 'Classical Age', 'min_level' => 16],
            ['age_key' => 'Medieval', 'age_name' => 'Medieval Age', 'min_level' => 26],
        ];

        foreach ($ages as $age) {
            $age['is_active'] = 1;
            try {
                $connection->insertIfNotExist('ncquest_character_ages', $age, ['age_key']);
            } catch (\Exception $e) {
                // Ignore if already exists
            }
        }
    }
}