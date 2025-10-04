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
 * Mapper for character items
 */
class CharacterItemMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ncquest_character_items', CharacterItem::class);
    }

    /**
     * Find all active items
     *
     * @return CharacterItem[]
     */
    public function findAllActive(): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('age_key', 'ASC')
            ->addOrderBy('item_type', 'ASC')
            ->addOrderBy('unlock_level', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find item by key
     *
     * @param string $itemKey
     * @return CharacterItem
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByKey(string $itemKey): CharacterItem {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('item_key', $qb->createNamedParameter($itemKey, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)));

        return $this->findEntity($qb);
    }

    /**
     * Find items by age
     *
     * @param string $ageKey
     * @return CharacterItem[]
     */
    public function findByAge(string $ageKey): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($ageKey, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('item_type', 'ASC')
            ->addOrderBy('unlock_level', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find items by type
     *
     * @param string $itemType
     * @return CharacterItem[]
     */
    public function findByType(string $itemType): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('item_type', $qb->createNamedParameter($itemType, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('age_key', 'ASC')
            ->addOrderBy('unlock_level', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find items by age and type
     *
     * @param string $ageKey
     * @param string $itemType
     * @return CharacterItem[]
     */
    public function findByAgeAndType(string $ageKey, string $itemType): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($ageKey, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('item_type', $qb->createNamedParameter($itemType, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('unlock_level', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find default items for an age
     *
     * @param string $ageKey
     * @return CharacterItem[]
     */
    public function findDefaultItemsForAge(string $ageKey): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('age_key', $qb->createNamedParameter($ageKey, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_default', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('item_type', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find items unlockable at a specific level
     *
     * @param int $level
     * @return CharacterItem[]
     */
    public function findUnlockableAtLevel(int $level): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('unlock_level', $qb->createNamedParameter($level, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('age_key', 'ASC')
            ->addOrderBy('item_type', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Find items by rarity
     *
     * @param string $rarity
     * @return CharacterItem[]
     */
    public function findByRarity(string $rarity): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('item_rarity', $qb->createNamedParameter($rarity, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->orderBy('age_key', 'ASC')
            ->addOrderBy('unlock_level', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Get item statistics
     *
     * @return array
     */
    public function getItemStats(): array {
        $qb = $this->db->getQueryBuilder();

        // Total items by age
        $qb->select('age_key')
            ->selectAlias($qb->createFunction('COUNT(*)'), 'item_count')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->groupBy('age_key')
            ->orderBy('age_key', 'ASC');

        $result = $qb->execute();
        $ageStats = $result->fetchAll();
        $result->closeCursor();

        // Total items by type
        $qb = $this->db->getQueryBuilder();
        $qb->select('item_type')
            ->selectAlias($qb->createFunction('COUNT(*)'), 'item_count')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->groupBy('item_type')
            ->orderBy('item_type', 'ASC');

        $result = $qb->execute();
        $typeStats = $result->fetchAll();
        $result->closeCursor();

        // Total items by rarity
        $qb = $this->db->getQueryBuilder();
        $qb->select('item_rarity')
            ->selectAlias($qb->createFunction('COUNT(*)'), 'item_count')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('is_active', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL)))
            ->groupBy('item_rarity')
            ->orderBy('item_rarity', 'ASC');

        $result = $qb->execute();
        $rarityStats = $result->fetchAll();
        $result->closeCursor();

        return [
            'by_age' => $ageStats,
            'by_type' => $typeStats,
            'by_rarity' => $rarityStats
        ];
    }
}