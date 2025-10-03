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
 * Mapper for character unlocks
 */
class CharacterUnlockMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'quest_char_unlocks', CharacterUnlock::class);
    }

    /**
     * Find all unlocks for a user
     *
     * @param string $userId
     * @return CharacterUnlock[]
     */
    public function findByUserId(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('unlocked_at', 'DESC');

        return $this->findEntities($qb);
    }

    /**
     * Check if user has unlocked a specific item
     *
     * @param string $userId
     * @param string $itemKey
     * @return bool
     */
    public function hasUnlocked(string $userId, string $itemKey): bool {
        $qb = $this->db->getQueryBuilder();

        $qb->select('id')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('item_key', $qb->createNamedParameter($itemKey, IQueryBuilder::PARAM_STR)))
            ->setMaxResults(1);

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row !== false;
    }

    /**
     * Get unlock for specific user and item
     *
     * @param string $userId
     * @param string $itemKey
     * @return CharacterUnlock
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUserAndItem(string $userId, string $itemKey): CharacterUnlock {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('item_key', $qb->createNamedParameter($itemKey, IQueryBuilder::PARAM_STR)));

        return $this->findEntity($qb);
    }

    /**
     * Get unlocked item keys for a user
     *
     * @param string $userId
     * @return array
     */
    public function getUnlockedItemKeys(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('item_key')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

        $result = $qb->execute();
        $itemKeys = [];
        while ($row = $result->fetch()) {
            $itemKeys[] = $row['item_key'];
        }
        $result->closeCursor();

        return $itemKeys;
    }

    /**
     * Get recent unlocks for a user
     *
     * @param string $userId
     * @param int $limit
     * @return CharacterUnlock[]
     */
    public function getRecentUnlocks(string $userId, int $limit = 10): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('unlocked_at', 'DESC')
            ->setMaxResults($limit);

        return $this->findEntities($qb);
    }

    /**
     * Get unlock statistics for a user
     *
     * @param string $userId
     * @return array
     */
    public function getUserUnlockStats(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        // Total unlocks
        $qb->select($qb->createFunction('COUNT(*) as total_unlocks'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

        $result = $qb->execute();
        $totalUnlocks = (int)$result->fetchOne();
        $result->closeCursor();

        // Unlocks by method
        $qb = $this->db->getQueryBuilder();
        $qb->select('unlock_method')
            ->selectAlias($qb->createFunction('COUNT(*)'), 'method_count')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->groupBy('unlock_method');

        $result = $qb->execute();
        $methodStats = $result->fetchAll();
        $result->closeCursor();

        // Recent unlock dates
        $qb = $this->db->getQueryBuilder();
        $qb->select('unlocked_at')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('unlocked_at', 'DESC')
            ->setMaxResults(5);

        $result = $qb->execute();
        $recentDates = [];
        while ($row = $result->fetch()) {
            $recentDates[] = $row['unlocked_at'];
        }
        $result->closeCursor();

        return [
            'total_unlocks' => $totalUnlocks,
            'by_method' => $methodStats,
            'recent_dates' => $recentDates
        ];
    }

    /**
     * Create or update an unlock record
     *
     * @param string $userId
     * @param string $itemKey
     * @param string $unlockMethod
     * @param string|null $unlockReason
     * @return CharacterUnlock
     */
    public function createUnlock(string $userId, string $itemKey, string $unlockMethod = 'level', ?string $unlockReason = null): CharacterUnlock {
        // Check if already unlocked
        if ($this->hasUnlocked($userId, $itemKey)) {
            return $this->findByUserAndItem($userId, $itemKey);
        }

        $unlock = new CharacterUnlock();
        $unlock->setUserId($userId);
        $unlock->setItemKey($itemKey);
        $unlock->setUnlockedAt(new \DateTime());
        $unlock->setUnlockMethod($unlockMethod);
        if ($unlockReason !== null) {
            $unlock->setUnlockReason($unlockReason);
        }

        return $this->insert($unlock);
    }

    /**
     * Bulk unlock items for a user
     *
     * @param string $userId
     * @param array $itemKeys
     * @param string $unlockMethod
     * @param string|null $unlockReason
     * @return int Number of new unlocks created
     */
    public function bulkUnlock(string $userId, array $itemKeys, string $unlockMethod = 'level', ?string $unlockReason = null): int {
        $newUnlocks = 0;
        $alreadyUnlocked = $this->getUnlockedItemKeys($userId);

        foreach ($itemKeys as $itemKey) {
            if (!in_array($itemKey, $alreadyUnlocked)) {
                $this->createUnlock($userId, $itemKey, $unlockMethod, $unlockReason);
                $newUnlocks++;
            }
        }

        return $newUnlocks;
    }
}