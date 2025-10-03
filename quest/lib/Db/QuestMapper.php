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
use Psr\Log\LoggerInterface;

/**
 * @extends QBMapper<Quest>
 */
class QuestMapper extends QBMapper {

    private $logger;

    public function __construct(IDBConnection $db, LoggerInterface $logger) {
        parent::__construct($db, 'ncquest_users', Quest::class);
        $this->logger = $logger;
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
            // Try to find existing record
            $existing = $this->findByUserId($quest->getUserId());

            $this->logger->info('Found existing quest', [
                'userId' => $quest->getUserId(),
                'existingId' => $existing->getId(),
                'questIdBefore' => $quest->getId()
            ]);

            // If found, update the existing entity with new values and save
            // Copy all modified fields from $quest to $existing
            $quest->setId($existing->getId());

            $this->logger->info('Set ID on quest', [
                'userId' => $quest->getUserId(),
                'questIdAfter' => $quest->getId()
            ]);

            return $this->update($quest);
        } catch (DoesNotExistException $e) {
            // No existing record, insert new one
            $this->logger->info('No existing quest, inserting', [
                'userId' => $quest->getUserId()
            ]);
            return $this->insert($quest);
        }
    }

    /**
     * Update equipment fields for a quest
     * Custom method because ncquest_users table uses user_id as primary key, not id
     *
     * @param Quest $quest
     * @return Quest
     */
    public function updateEquipment(Quest $quest): Quest {
        $qb = $this->db->getQueryBuilder();

        $qb->update($this->getTableName())
            ->set('character_equipped_clothing', $qb->createNamedParameter($quest->getCharacterEquippedClothing()))
            ->set('character_equipped_weapon', $qb->createNamedParameter($quest->getCharacterEquippedWeapon()))
            ->set('character_equipped_accessory', $qb->createNamedParameter($quest->getCharacterEquippedAccessory()))
            ->set('character_equipped_headgear', $qb->createNamedParameter($quest->getCharacterEquippedHeadgear()))
            ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($quest->getUserId())))
            ->executeStatement();

        return $quest;
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