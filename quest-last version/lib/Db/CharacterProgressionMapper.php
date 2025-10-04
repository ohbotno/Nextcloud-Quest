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
 * Mapper for character progression
 */
class CharacterProgressionMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ncquest_character_progression', CharacterProgression::class);
    }

    /**
     * Find all progression records for a user
     *
     * @param string $userId
     * @return CharacterProgression[]
     */
    public function findByUserId(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('reached_at', 'ASC');

        return $this->findEntities($qb);
    }

    /**
     * Check if user has reached a specific age
     *
     * @param string $userId
     * @param string $ageKey
     * @return bool
     */
    public function hasReachedAge(string $userId, string $ageKey): bool {
        $qb = $this->db->getQueryBuilder();

        $qb->select('id')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('age_key', $qb->createNamedParameter($ageKey, IQueryBuilder::PARAM_STR)))
            ->setMaxResults(1);

        $result = $qb->execute();
        $row = $result->fetch();
        $result->closeCursor();

        return $row !== false;
    }

    /**
     * Get progression for specific user and age
     *
     * @param string $userId
     * @param string $ageKey
     * @return CharacterProgression
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException
     */
    public function findByUserAndAge(string $userId, string $ageKey): CharacterProgression {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('age_key', $qb->createNamedParameter($ageKey, IQueryBuilder::PARAM_STR)));

        return $this->findEntity($qb);
    }

    /**
     * Get reached age keys for a user
     *
     * @param string $userId
     * @return array
     */
    public function getReachedAgeKeys(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('age_key')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('reached_at', 'ASC');

        $result = $qb->execute();
        $ageKeys = [];
        while ($row = $result->fetch()) {
            $ageKeys[] = $row['age_key'];
        }
        $result->closeCursor();

        return $ageKeys;
    }

    /**
     * Get latest reached age for a user
     *
     * @param string $userId
     * @return CharacterProgression|null
     */
    public function getLatestAge(string $userId): ?CharacterProgression {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('reached_at', 'DESC')
            ->setMaxResults(1);

        $result = $this->findEntities($qb);
        return count($result) > 0 ? $result[0] : null;
    }

    /**
     * Get progression statistics for a user
     *
     * @param string $userId
     * @return array
     */
    public function getUserProgressionStats(string $userId): array {
        $qb = $this->db->getQueryBuilder();

        // Total ages reached
        $qb->select($qb->createFunction('COUNT(*) as total_ages'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

        $result = $qb->execute();
        $totalAges = (int)$result->fetchOne();
        $result->closeCursor();

        // First and latest progression
        $qb = $this->db->getQueryBuilder();
        $qb->select('MIN(reached_at) as first_progression', 'MAX(reached_at) as latest_progression')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));

        $result = $qb->execute();
        $dates = $result->fetch();
        $result->closeCursor();

        // Level and XP progression
        $qb = $this->db->getQueryBuilder();
        $qb->select('reached_at_level', 'reached_with_xp', 'age_key')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('reached_at', 'ASC');

        $result = $qb->execute();
        $progressionData = $result->fetchAll();
        $result->closeCursor();

        return [
            'total_ages_reached' => $totalAges,
            'first_progression' => $dates['first_progression'] ?? null,
            'latest_progression' => $dates['latest_progression'] ?? null,
            'progression_timeline' => $progressionData
        ];
    }

    /**
     * Record age progression for a user
     *
     * @param string $userId
     * @param string $ageKey
     * @param int $level
     * @param int $xp
     * @return CharacterProgression
     */
    public function recordAgeProgression(string $userId, string $ageKey, int $level, int $xp): CharacterProgression {
        // Check if already recorded
        if ($this->hasReachedAge($userId, $ageKey)) {
            return $this->findByUserAndAge($userId, $ageKey);
        }

        $progression = new CharacterProgression();
        $progression->setUserId($userId);
        $progression->setAgeKey($ageKey);
        $progression->setReachedAt(new \DateTime());
        $progression->setReachedAtLevel($level);
        $progression->setReachedWithXp($xp);

        return $this->insert($progression);
    }

    /**
     * Get global progression statistics
     *
     * @return array
     */
    public function getGlobalProgressionStats(): array {
        $qb = $this->db->getQueryBuilder();

        // Users by age progression
        $qb->select('age_key')
            ->selectAlias($qb->createFunction('COUNT(DISTINCT user_id)'), 'user_count')
            ->from($this->getTableName())
            ->groupBy('age_key')
            ->orderBy('age_key', 'ASC');

        $result = $qb->execute();
        $ageStats = $result->fetchAll();
        $result->closeCursor();

        // Average level by age
        $qb = $this->db->getQueryBuilder();
        $qb->select('age_key')
            ->selectAlias($qb->createFunction('AVG(reached_at_level)'), 'avg_level')
            ->selectAlias($qb->createFunction('MIN(reached_at_level)'), 'min_level')
            ->selectAlias($qb->createFunction('MAX(reached_at_level)'), 'max_level')
            ->from($this->getTableName())
            ->groupBy('age_key')
            ->orderBy('age_key', 'ASC');

        $result = $qb->execute();
        $levelStats = $result->fetchAll();
        $result->closeCursor();

        // Recent progressions
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy('reached_at', 'DESC')
            ->setMaxResults(10);

        $result = $qb->execute();
        $recentProgressions = $result->fetchAll();
        $result->closeCursor();

        return [
            'users_by_age' => $ageStats,
            'level_stats_by_age' => $levelStats,
            'recent_progressions' => $recentProgressions
        ];
    }
}