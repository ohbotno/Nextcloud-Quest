<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

declare(strict_types=1);

namespace OCA\NextcloudQuest\Service;

use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Health Service - Manages player health system and penalties for overdue tasks
 */
class HealthService {

    /** @var IDBConnection */
    private $db;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(IDBConnection $db, LoggerInterface $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Get current health for a user
     */
    public function getUserHealth(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('current_health', 'max_health')
           ->from('ncquest_users')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if (!$row) {
            // Return default health if user not found
            return [
                'current_health' => 100,
                'max_health' => 100,
                'percentage' => 100.0
            ];
        }

        $currentHealth = (int)($row['current_health'] ?? 100);
        $maxHealth = (int)($row['max_health'] ?? 100);
        $percentage = $maxHealth > 0 ? ($currentHealth / $maxHealth) * 100 : 100;

        return [
            'current_health' => $currentHealth,
            'max_health' => $maxHealth,
            'percentage' => round($percentage, 1)
        ];
    }

    /**
     * Update user health
     */
    public function updateUserHealth(string $userId, int $currentHealth, int $maxHealth = 100): bool {
        // Ensure health values are within valid ranges
        $currentHealth = max(0, min($currentHealth, $maxHealth));
        
        $qb = $this->db->getQueryBuilder();
        $qb->update('ncquest_users')
           ->set('current_health', $qb->createNamedParameter($currentHealth))
           ->set('max_health', $qb->createNamedParameter($maxHealth))
           ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        try {
            $affectedRows = $qb->executeStatement();
            $this->logger->info('Health updated for user', [
                'user_id' => $userId,
                'current_health' => $currentHealth,
                'max_health' => $maxHealth
            ]);
            return $affectedRows > 0;
        } catch (\Exception $e) {
            $this->logger->error('Failed to update user health', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Apply health penalty for overdue tasks
     */
    public function applyHealthPenalty(string $userId, int $overdueTaskCount): int {
        $health = $this->getUserHealth($userId);
        $currentHealth = $health['current_health'];
        
        // Penalty: 10 health points per overdue task
        $penalty = $overdueTaskCount * 10;
        $newHealth = max(0, $currentHealth - $penalty);
        
        if ($penalty > 0) {
            $this->updateUserHealth($userId, $newHealth, $health['max_health']);
            
            $this->logger->info('Health penalty applied', [
                'user_id' => $userId,
                'overdue_tasks' => $overdueTaskCount,
                'penalty' => $penalty,
                'old_health' => $currentHealth,
                'new_health' => $newHealth
            ]);
        }
        
        return $newHealth;
    }

    /**
     * Regenerate health for task completion
     */
    public function regenerateHealth(string $userId, int $amount = 5): int {
        $health = $this->getUserHealth($userId);
        $currentHealth = $health['current_health'];
        $maxHealth = $health['max_health'];
        
        // Don't exceed max health
        $newHealth = min($maxHealth, $currentHealth + $amount);
        
        if ($newHealth > $currentHealth) {
            $this->updateUserHealth($userId, $newHealth, $maxHealth);
            
            $this->logger->info('Health regenerated', [
                'user_id' => $userId,
                'amount' => $amount,
                'old_health' => $currentHealth,
                'new_health' => $newHealth
            ]);
        }
        
        return $newHealth;
    }

    /**
     * Apply XP penalty when health reaches zero
     */
    public function applyXpPenalty(string $userId): array {
        // Get current XP
        $qb = $this->db->getQueryBuilder();
        $qb->select('current_xp', 'lifetime_xp')
           ->from('ncquest_users')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if (!$row) {
            return ['penalty_applied' => false, 'xp_lost' => 0];
        }

        $currentXp = (int)($row['current_xp'] ?? 0);
        $lifetimeXp = (int)($row['lifetime_xp'] ?? 0);
        
        // Lose 10% of current level XP
        $xpPenalty = (int)floor($currentXp * 0.1);
        $newCurrentXp = max(0, $currentXp - $xpPenalty);
        
        if ($xpPenalty > 0) {
            // Update XP and reset health to 50%
            $updateQb = $this->db->getQueryBuilder();
            $updateQb->update('ncquest_users')
                    ->set('current_xp', $updateQb->createNamedParameter($newCurrentXp))
                    ->set('current_health', $updateQb->createNamedParameter(50))
                    ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                    ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)));

            try {
                $updateQb->executeStatement();
                
                $this->logger->warning('XP penalty applied for zero health', [
                    'user_id' => $userId,
                    'xp_lost' => $xpPenalty,
                    'old_xp' => $currentXp,
                    'new_xp' => $newCurrentXp
                ]);
                
                return [
                    'penalty_applied' => true,
                    'xp_lost' => $xpPenalty,
                    'health_restored' => 50
                ];
            } catch (\Exception $e) {
                $this->logger->error('Failed to apply XP penalty', [
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return ['penalty_applied' => false, 'xp_lost' => 0];
    }

    /**
     * Get count of overdue tasks for a user (requires Tasks app integration)
     */
    public function getOverdueTaskCount(string $userId): int {
        // This would integrate with Nextcloud Tasks app
        // For now, return 0 as placeholder
        // TODO: Implement actual overdue task detection
        
        try {
            // Check if Tasks app tables exist
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->count('*'))
               ->from('oc_tasks')
               ->where($qb->expr()->eq('uid', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->isNotNull('due'))
               ->andWhere($qb->expr()->lt('due', $qb->createNamedParameter(time())))
               ->andWhere($qb->expr()->eq('completed', $qb->createNamedParameter(0)));

            $result = $qb->executeQuery();
            $count = (int)$result->fetchOne();
            $result->closeCursor();
            
            return $count;
        } catch (\Exception $e) {
            // Tasks app not installed or different schema
            $this->logger->info('Could not check overdue tasks', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Heal user by spending XP
     */
    public function healWithXp(string $userId, int $healthToRestore): array {
        $health = $this->getUserHealth($userId);
        $currentHealth = $health['current_health'];
        $maxHealth = $health['max_health'];
        
        // Calculate XP cost: 5 XP per health point
        $xpCost = $healthToRestore * 5;
        
        // Get current XP
        $qb = $this->db->getQueryBuilder();
        $qb->select('current_xp')
           ->from('ncquest_users')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if (!$row) {
            return ['success' => false, 'error' => 'User not found'];
        }

        $currentXp = (int)($row['current_xp'] ?? 0);
        
        if ($currentXp < $xpCost) {
            return ['success' => false, 'error' => 'Insufficient XP'];
        }
        
        // Don't exceed max health
        $actualHealthRestore = min($healthToRestore, $maxHealth - $currentHealth);
        $actualXpCost = $actualHealthRestore * 5;
        $newXp = $currentXp - $actualXpCost;
        $newHealth = $currentHealth + $actualHealthRestore;
        
        // Update both XP and health
        $updateQb = $this->db->getQueryBuilder();
        $updateQb->update('ncquest_users')
                ->set('current_xp', $updateQb->createNamedParameter($newXp))
                ->set('current_health', $updateQb->createNamedParameter($newHealth))
                ->set('updated_at', $updateQb->createNamedParameter(date('Y-m-d H:i:s')))
                ->where($updateQb->expr()->eq('user_id', $updateQb->createNamedParameter($userId)));

        try {
            $updateQb->executeStatement();
            
            $this->logger->info('Health restored with XP', [
                'user_id' => $userId,
                'xp_spent' => $actualXpCost,
                'health_restored' => $actualHealthRestore,
                'new_health' => $newHealth
            ]);
            
            return [
                'success' => true,
                'xp_spent' => $actualXpCost,
                'health_restored' => $actualHealthRestore,
                'new_health' => $newHealth,
                'new_xp' => $newXp
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to heal with XP', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return ['success' => false, 'error' => 'Database error'];
        }
    }
}