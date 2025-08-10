<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @extends QBMapper<Quest>
 */
class QuestMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ncquest_users', Quest::class);
    }
    
    /**
     * Find quest data for a specific user
     * 
     * @param string $userId
     * @return Quest
     * @throws DoesNotExistException
     */
    public function findByUserId(string $userId): Quest {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        return $this->findEntity($qb);
    }
    
    /**
     * Create or update quest data for a user
     * 
     * @param Quest $quest
     * @return Quest
     */
    public function insertOrUpdate(\OCP\AppFramework\Db\Entity $quest): \OCP\AppFramework\Db\Entity {
        try {
            $existing = $this->findByUserId($quest->getUserId());
            $quest->setId($existing->getId());
            return $this->update($quest);
        } catch (DoesNotExistException $e) {
            return $this->insert($quest);
        }
    }
    
    /**
     * Get leaderboard data
     * 
     * @param int $limit
     * @param int $offset
     * @param string $orderBy 'lifetime_xp' or 'level' or 'current_streak'
     * @return array
     */
    public function getLeaderboard(int $limit = 10, int $offset = 0, string $orderBy = 'lifetime_xp'): array {
        $qb = $this->db->getQueryBuilder();
        
        $validOrderBy = ['lifetime_xp', 'level', 'current_streak'];
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'lifetime_xp';
        }
        
        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy($orderBy, 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        
        return $this->findEntities($qb);
    }
    
    /**
     * Get user's rank position
     * 
     * @param string $userId
     * @param string $orderBy
     * @return int
     */
    public function getUserRank(string $userId, string $orderBy = 'lifetime_xp'): int {
        $qb = $this->db->getQueryBuilder();
        
        $validOrderBy = ['lifetime_xp', 'level', 'current_streak'];
        if (!in_array($orderBy, $validOrderBy)) {
            $orderBy = 'lifetime_xp';
        }
        
        // Get the user's value
        $userQuest = $this->findByUserId($userId);
        $userValue = match($orderBy) {
            'lifetime_xp' => $userQuest->getLifetimeXp(),
            'level' => $userQuest->getLevel(),
            'current_streak' => $userQuest->getCurrentStreak(),
        };
        
        // Count users with higher values
        $qb->select($qb->createFunction('COUNT(*)'))
            ->from($this->getTableName())
            ->where($qb->expr()->gt($orderBy, $qb->createNamedParameter($userValue, IQueryBuilder::PARAM_INT)));
        
        $result = $qb->execute();
        $count = $result->fetchOne();
        $result->closeCursor();
        
        return (int)$count + 1;
    }
}