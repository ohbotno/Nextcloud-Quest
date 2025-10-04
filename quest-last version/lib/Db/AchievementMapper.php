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
 * @extends QBMapper<Achievement>
 */
class AchievementMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'ncquest_achievements', Achievement::class);
    }
    
    /**
     * Find all achievements for a user
     * 
     * @param string $userId
     * @return Achievement[]
     */
    public function findAllByUserId(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('unlocked_at', 'DESC');
        
        return $this->findEntities($qb);
    }

    /**
     * Find recent achievements for a user
     * 
     * @param string $userId
     * @param int $limit
     * @return Achievement[]
     */
    public function findRecentByUserId(string $userId, int $limit = 10): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->orderBy('unlocked_at', 'DESC')
            ->setMaxResults($limit);
        
        return $this->findEntities($qb);
    }
    
    /**
     * Check if user has a specific achievement
     * 
     * @param string $userId
     * @param string $achievementKey
     * @return bool
     */
    public function hasAchievement(string $userId, string $achievementKey): bool {
        try {
            $this->findByUserAndKey($userId, $achievementKey);
            return true;
        } catch (DoesNotExistException $e) {
            return false;
        }
    }
    
    /**
     * Find achievement by user and key
     * 
     * @param string $userId
     * @param string $achievementKey
     * @return Achievement
     * @throws DoesNotExistException
     */
    public function findByUserAndKey(string $userId, string $achievementKey): Achievement {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('achievement_key', $qb->createNamedParameter($achievementKey, IQueryBuilder::PARAM_STR)));
        
        return $this->findEntity($qb);
    }
    
    /**
     * Get unnotified achievements for a user
     * 
     * @param string $userId
     * @return Achievement[]
     */
    public function findUnnotified(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select('*')
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)))
            ->andWhere($qb->expr()->eq('notified', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)))
            ->orderBy('unlocked_at', 'ASC');
        
        return $this->findEntities($qb);
    }
    
    /**
     * Mark achievement as notified
     * 
     * @param int $id
     * @return void
     */
    public function markAsNotified(int $id): void {
        $qb = $this->db->getQueryBuilder();
        
        $qb->update($this->getTableName())
            ->set('notified', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        
        $qb->execute();
    }
    
    /**
     * Get achievement statistics
     * 
     * @param string $userId
     * @return array
     */
    public function getStats(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        
        $qb->select($qb->createFunction('COUNT(*) AS total'))
            ->from($this->getTableName())
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId, IQueryBuilder::PARAM_STR)));
        
        $result = $qb->execute();
        $stats = $result->fetch();
        $result->closeCursor();
        
        return [
            'total' => (int)$stats['total'],
            'unlocked' => (int)$stats['total'], // All records in table are unlocked
        ];
    }
}