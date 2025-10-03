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
 * Character Ages Seed Data Migration
 * Creates comprehensive age progression from Stone Age to Space Age
 */
class Version1014Date20250930130000 extends SimpleMigrationStep {

    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        // Create or update character ages table
        if (!$schema->hasTable('ncquest_character_ages')) {
            $table = $schema->createTable('ncquest_character_ages');
            $table->addColumn('id', 'integer', [
                'autoincrement' => true,
                'notnull' => true,
            ]);
            $table->addColumn('age_key', 'string', [
                'notnull' => true,
                'length' => 20,
            ]);
            $table->addColumn('age_name', 'string', [
                'notnull' => true,
                'length' => 50,
            ]);
            $table->addColumn('min_level', 'integer', [
                'notnull' => true,
            ]);
            $table->addColumn('max_level', 'integer', [
                'notnull' => false,
            ]);
            $table->addColumn('age_description', 'text', [
                'notnull' => false,
            ]);
            $table->addColumn('age_color', 'string', [
                'notnull' => false,
                'length' => 7,
            ]);
            $table->addColumn('age_icon', 'string', [
                'notnull' => false,
                'length' => 10,
            ]);
            $table->addColumn('is_active', 'integer', [
                'notnull' => true,
                'default' => 1,
                'length' => 1,
            ]);
            $table->addColumn('created_at', 'datetime', [
                'notnull' => true,
            ]);

            $table->setPrimaryKey(['id'], 'quest_ages_pk');
            $table->addUniqueIndex(['age_key'], 'quest_ages_key');
            $table->addIndex(['min_level'], 'quest_ages_lvl');
        } else {
            // Table exists, ensure all columns are present
            $table = $schema->getTable('ncquest_character_ages');

            if (!$table->hasColumn('max_level')) {
                $table->addColumn('max_level', 'integer', [
                    'notnull' => false,
                ]);
            }

            if (!$table->hasColumn('age_description')) {
                $table->addColumn('age_description', 'text', [
                    'notnull' => false,
                ]);
            }

            if (!$table->hasColumn('age_color')) {
                $table->addColumn('age_color', 'string', [
                    'notnull' => false,
                    'length' => 7,
                ]);
            }

            if (!$table->hasColumn('age_icon')) {
                $table->addColumn('age_icon', 'string', [
                    'notnull' => false,
                    'length' => 10,
                ]);
            }

            if (!$table->hasColumn('created_at')) {
                $table->addColumn('created_at', 'datetime', [
                    'notnull' => false,
                ]);
            }
        }

        return $schema;
    }

    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
        $connection = \OC::$server->getDatabaseConnection();

        // Get the actual schema to check which columns exist
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();
        $table = $schema->getTable('ncquest_character_ages');

        // Check which columns are available
        $hasCreatedAt = $table->hasColumn('created_at');
        $hasAgeDescription = $table->hasColumn('age_description');
        $hasAgeColor = $table->hasColumn('age_color');
        $hasAgeIcon = $table->hasColumn('age_icon');

        // Define character ages from Stone Age to Space Age
        $ages = [
            [
                'age_key' => 'stone',
                'age_name' => 'Stone Age',
                'min_level' => 1,
                'max_level' => 9,
                'age_description' => 'The dawn of civilization. Primitive tools, survival skills, and the beginning of your journey.',
                'age_color' => '#8b7355',
                'age_icon' => '🪨'
            ],
            [
                'age_key' => 'bronze',
                'age_name' => 'Bronze Age',
                'min_level' => 10,
                'max_level' => 19,
                'age_description' => 'Early metalworking emerges. Craft bronze tools and weapons as societies begin to form.',
                'age_color' => '#cd7f32',
                'age_icon' => '⚒️'
            ],
            [
                'age_key' => 'iron',
                'age_name' => 'Iron Age',
                'min_level' => 20,
                'max_level' => 29,
                'age_description' => 'Stronger metals forge stronger warriors. Iron weapons and tools become the standard.',
                'age_color' => '#71706e',
                'age_icon' => '⚔️'
            ],
            [
                'age_key' => 'medieval',
                'age_name' => 'Medieval Age',
                'min_level' => 30,
                'max_level' => 39,
                'age_description' => 'Castles rise, knights ride, and kingdoms expand. The age of chivalry and feudalism.',
                'age_color' => '#8b4513',
                'age_icon' => '🏰'
            ],
            [
                'age_key' => 'renaissance',
                'age_name' => 'Renaissance',
                'min_level' => 40,
                'max_level' => 49,
                'age_description' => 'Art, science, and culture flourish. An age of enlightenment and innovation.',
                'age_color' => '#daa520',
                'age_icon' => '🎨'
            ],
            [
                'age_key' => 'industrial',
                'age_name' => 'Industrial Age',
                'min_level' => 50,
                'max_level' => 59,
                'age_description' => 'Steam power and machinery revolutionize productivity. The age of industry begins.',
                'age_color' => '#696969',
                'age_icon' => '⚙️'
            ],
            [
                'age_key' => 'modern',
                'age_name' => 'Modern Age',
                'min_level' => 60,
                'max_level' => 74,
                'age_description' => 'Electricity, automobiles, and modern conveniences transform daily life.',
                'age_color' => '#4169e1',
                'age_icon' => '💡'
            ],
            [
                'age_key' => 'digital',
                'age_name' => 'Digital Age',
                'min_level' => 75,
                'max_level' => 99,
                'age_description' => 'Computers, internet, and information technology connect the world.',
                'age_color' => '#00ced1',
                'age_icon' => '💻'
            ],
            [
                'age_key' => 'space',
                'age_name' => 'Space Age',
                'min_level' => 100,
                'max_level' => null,
                'age_description' => 'Beyond Earth. Advanced technology, space exploration, and limitless possibilities.',
                'age_color' => '#9370db',
                'age_icon' => '🚀'
            ]
        ];

        $now = new \DateTime();

        // Delete old age entries with incorrect keys (capitalized or wrong levels)
        $oldAgeKeys = ['Stone', 'Bronze', 'Iron', 'Classical', 'Medieval'];
        foreach ($oldAgeKeys as $oldKey) {
            $qb = $connection->getQueryBuilder();
            $qb->delete('ncquest_character_ages')
                ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($oldKey)));
            $qb->execute();
        }

        foreach ($ages as $age) {
            // Check if age already exists (using lowercase key)
            $qb = $connection->getQueryBuilder();
            $qb->select('id')
                ->from('ncquest_character_ages')
                ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($age['age_key'])));

            $result = $qb->execute();
            $exists = $result->fetch();
            $result->closeCursor();

            if (!$exists) {
                // Insert the age
                $qb = $connection->getQueryBuilder();
                // Build values based on available columns
                $values = [
                    'age_key' => $qb->createNamedParameter($age['age_key']),
                    'age_name' => $qb->createNamedParameter($age['age_name']),
                    'min_level' => $qb->createNamedParameter($age['min_level'], \PDO::PARAM_INT),
                    'is_active' => $qb->createNamedParameter(1, \PDO::PARAM_INT),
                ];

                // Add optional columns if they exist
                if ($hasAgeDescription) {
                    $values['age_description'] = $qb->createNamedParameter($age['age_description']);
                }
                if ($hasAgeColor) {
                    $values['age_color'] = $qb->createNamedParameter($age['age_color']);
                }
                if ($hasAgeIcon) {
                    $values['age_icon'] = $qb->createNamedParameter($age['age_icon']);
                }
                if ($hasCreatedAt) {
                    $values['created_at'] = $qb->createNamedParameter($now, 'datetime');
                }

                // Add max_level if not null
                if ($age['max_level'] !== null) {
                    $values['max_level'] = $qb->createNamedParameter($age['max_level'], \PDO::PARAM_INT);
                }

                $qb->insert('ncquest_character_ages')
                    ->values($values);

                $qb->execute();

                $output->info("Created character age: {$age['age_name']}");
            }
        }

        $output->info('Character ages initialized successfully');
    }
}
