<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Mapper for character ages
 */
class CharacterAgeMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ncquest_character_ages', CharacterAge::class);
    }

    /**
     * Find all active ages ordered by minimum level
     *
     * @return CharacterAge[]
     */
    public function findAllActive(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('min_level', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find age by key
     *
     * @param string $ageKey
     * @return CharacterAge
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByKey(string $ageKey): CharacterAge {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($ageKey, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));

        return $this->findEntity($qb);
    }

    /**
     * Get age for a specific level
     *
     * @param int $level
     * @return CharacterAge|null
     */
    public function getAgeForLevel(int $level): ?CharacterAge {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->lte('min_level', $qb->createNamedParameter($level, IQueryBuilder::PARAM_INT)))
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->isNull('max_level'),
                    $qb->expr()->gte('max_level', $qb->createNamedParameter($level, IQueryBuilder::PARAM_INT))
                )
            )
            ->orderBy('min_level', 'DESC')
            ->setMaxResults(1);

        $result = $this->findEntities($qb);
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * Get next age after current level
     *
     * @param int $currentLevel
     * @return CharacterAge|null
     */
    public function getNextAge(int $currentLevel): ?CharacterAge {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->gt('min_level', $qb->createNamedParameter($currentLevel, IQueryBuilder::PARAM_INT)))
            ->orderBy('min_level', 'ASC')
            ->setMaxResults(1);

        $result = $this->findEntities($qb);
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * Get age progression statistics
     *
     * @return array
     */
    public function getAgeStats(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('age_key', 'age_name', 'min_level', 'max_level')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('min_level', 'ASC');

        $result = $qb->execute();
        $ages = $result->fetchAll();
        $result->closeCursor();

        return $ages;
    }
}