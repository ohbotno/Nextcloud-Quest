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
 * Settings Enhancement Database Migration
 */
class Version1004Date20250805140000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Extend ncquest_users table with additional character fields if they don't exist
        if ($schema->hasTable('ncquest_users')) {
            $table = $schema->getTable('ncquest_users');
            
            // Add additional character appearance fields if they don't exist
            if (!$table->hasColumn('character_name')) {
                $table->addColumn('character_name', 'string', [
                    'notnull' => false,
                    'length' => 50,
                    'default' => 'Quest Champion',
                    'comment' => 'Custom character name'
                ]);
            }
            
            if (!$table->hasColumn('character_avatar_url')) {
                $table->addColumn('character_avatar_url', 'string', [
                    'notnull' => false,
                    'length' => 255,
                    'comment' => 'URL to character avatar image'
                ]);
            }
            
            if (!$table->hasColumn('settings_version')) {
                $table->addColumn('settings_version', 'integer', [
                    'notnull' => true,
                    'default' => 1,
                    'comment' => 'Settings schema version for migration tracking'
                ]);
            }
            
            if (!$table->hasColumn('data_retention_days')) {
                $table->addColumn('data_retention_days', 'integer', [
                    'notnull' => true,
                    'default' => 365,
                    'comment' => 'Number of days to retain user data'
                ]);
            }
            
            if (!$table->hasColumn('privacy_settings')) {
                $table->addColumn('privacy_settings', 'text', [
                    'notnull' => false,
                    'comment' => 'JSON string of privacy-related settings'
                ]);
            }
        }

        // Create settings backup table for data export/import functionality
        if (!$schema->hasTable('ncquest_backups')) {
            $table = $schema->createTable('ncquest_backups');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'comment' => 'Primary key'
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
                'comment' => 'User ID who created the backup'
            ]);
            $table->addColumn('backup_name', 'string', [
                'notnull' => true,
                'length' => 100,
                'comment' => 'Name/description of the backup'
            ]);
            $table->addColumn('backup_type', 'string', [
                'notnull' => true,
                'length' => 20,
                'default' => 'manual',
                'comment' => 'Type of backup (manual, automatic, import)'
            ]);
            $table->addColumn('backup_data', 'text', [
                'notnull' => true,
                'comment' => 'JSON string containing the backed up data'
            ]);
            $table->addColumn('data_size', 'integer', [
                'notnull' => true,
                'default' => 0,
                'comment' => 'Size of backup data in bytes'
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
                'comment' => 'When the backup was created'
            ]);
            $table->addColumn('expires_at', 'datetime', [
                'notnull' => false,
                'comment' => 'When the backup expires (null = never)'
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'ncquest_backups_user_idx');
            $table->addIndex(['created_at'], 'ncquest_backups_created_idx');
        }

        // Create settings audit log table for tracking changes
        if (!$schema->hasTable('ncquest_audit')) {
            $table = $schema->createTable('ncquest_audit');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
                'comment' => 'Primary key'
            ]);
            $table->addColumn('user_id', 'string', [
                'notnull' => true,
                'length' => 64,
                'comment' => 'User ID who made the change'
            ]);
            $table->addColumn('action', 'string', [
                'notnull' => true,
                'length' => 30,
                'comment' => 'Type of action (update, reset, import, export)'
            ]);
            $table->addColumn('setting_category', 'string', [
                'notnull' => false,
                'length' => 30,
                'comment' => 'Category of settings changed'
            ]);
            $table->addColumn('setting_key', 'string', [
                'notnull' => false,
                'length' => 50,
                'comment' => 'Specific setting key changed'
            ]);
            $table->addColumn('old_value', 'text', [
                'notnull' => false,
                'comment' => 'Previous value (JSON for complex values)'
            ]);
            $table->addColumn('new_value', 'text', [
                'notnull' => false,
                'comment' => 'New value (JSON for complex values)'
            ]);
            $table->addColumn('ip_address', 'string', [
                'notnull' => false,
                'length' => 45,
                'comment' => 'IP address of the request'
            ]);
            $table->addColumn('user_agent', 'string', [
                'notnull' => false,
                'length' => 255,
                'comment' => 'User agent string'
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
                'comment' => 'When the change was made'
            ]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'ncquest_audit_user_idx');
            $table->addIndex(['action'], 'ncquest_audit_action_idx');
            $table->addIndex(['created_at'], 'ncquest_audit_created_idx');
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        // Update existing users with default values for new fields
        $connection = \OC::$server->getDatabaseConnection();
        
        // Set default privacy settings for existing users
        $defaultPrivacySettings = json_encode([
            'show_on_leaderboard' => true,
            'anonymous_leaderboard' => false,
            'share_achievements' => true,
            'collect_analytics' => true,
            'detailed_logging' => false
        ]);
        
        try {
            $connection->executeStatement(
                'UPDATE `*PREFIX*ncquest_users` SET `privacy_settings` = ? WHERE `privacy_settings` IS NULL',
                [$defaultPrivacySettings]
            );
        } catch (\Exception $e) {
            // Ignore if column doesn't exist or update fails
        }
        
        // Update settings version for existing users
        try {
            $connection->executeStatement(
                'UPDATE `*PREFIX*ncquest_users` SET `settings_version` = 1 WHERE `settings_version` = 0 OR `settings_version` IS NULL'
            );
        } catch (\Exception $e) {
            // Ignore if column doesn't exist or update fails
        }
    }
}