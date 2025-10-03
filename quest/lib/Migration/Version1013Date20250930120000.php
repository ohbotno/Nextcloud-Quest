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
 * Character System Enhancement Migration
 * Adds character appearance, equipment, and progression fields
 */
class Version1013Date20250930120000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Extend ncquest_users table with character fields
        if ($schema->hasTable('ncquest_users')) {
            $table = $schema->getTable('ncquest_users');

            // Equipment slot fields
            if (!$table->hasColumn('character_equipped_clothing')) {
                $table->addColumn('character_equipped_clothing', 'string', [
                    'notnull' => false,
                    'length' => 50,
                    'comment' => 'Currently equipped clothing item key'
                ]);
            }

            if (!$table->hasColumn('character_equipped_weapon')) {
                $table->addColumn('character_equipped_weapon', 'string', [
                    'notnull' => false,
                    'length' => 50,
                    'comment' => 'Currently equipped weapon item key'
                ]);
            }

            if (!$table->hasColumn('character_equipped_accessory')) {
                $table->addColumn('character_equipped_accessory', 'string', [
                    'notnull' => false,
                    'length' => 50,
                    'comment' => 'Currently equipped accessory item key'
                ]);
            }

            if (!$table->hasColumn('character_equipped_headgear')) {
                $table->addColumn('character_equipped_headgear', 'string', [
                    'notnull' => false,
                    'length' => 50,
                    'comment' => 'Currently equipped headgear item key'
                ]);
            }

            // Character appearance fields
            if (!$table->hasColumn('character_current_age')) {
                $table->addColumn('character_current_age', 'string', [
                    'notnull' => false,
                    'length' => 20,
                    'default' => 'stone',
                    'comment' => 'Current character age/era (stone, bronze, etc.)'
                ]);
            }

            if (!$table->hasColumn('character_base_sprite')) {
                $table->addColumn('character_base_sprite', 'string', [
                    'notnull' => false,
                    'length' => 50,
                    'default' => 'default',
                    'comment' => 'Base character sprite identifier'
                ]);
            }

            if (!$table->hasColumn('character_appearance_data')) {
                $table->addColumn('character_appearance_data', 'text', [
                    'notnull' => false,
                    'comment' => 'JSON data for character appearance (scars, badges, effects)'
                ]);
            }
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        // Initialize default values for existing users
        $connection = \OC::$server->getDatabaseConnection();

        // Set default age to 'stone' for existing users without one
        try {
            $connection->executeStatement(
                'UPDATE `*PREFIX*ncquest_users` SET `character_current_age` = ? WHERE `character_current_age` IS NULL OR `character_current_age` = ?',
                ['stone', '']
            );
        } catch (\Exception $e) {
            // Ignore if update fails
        }

        // Set default base sprite for existing users
        try {
            $connection->executeStatement(
                'UPDATE `*PREFIX*ncquest_users` SET `character_base_sprite` = ? WHERE `character_base_sprite` IS NULL OR `character_base_sprite` = ?',
                ['default', '']
            );
        } catch (\Exception $e) {
            // Ignore if update fails
        }

        // Initialize empty appearance data for users without it
        $defaultAppearanceData = json_encode([
            'scars' => [],
            'badges' => [],
            'aging_effects' => [],
            'technology_markers' => []
        ]);

        try {
            $connection->executeStatement(
                'UPDATE `*PREFIX*ncquest_users` SET `character_appearance_data` = ? WHERE `character_appearance_data` IS NULL',
                [$defaultAppearanceData]
            );
        } catch (\Exception $e) {
            // Ignore if update fails
        }
    }
}
