<?php

declare(strict_types=1);

namespace OCA\Quest\Controller;

use OCA\Quest\Service\WorldGenerator;
use OCA\Quest\Service\PathGenerator;
use OCA\Quest\Service\LevelObjective;
use OCA\Quest\Integration\TasksApiIntegration;
use OCA\Quest\Service\XPService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Adventure World Controller - Main controller for the Adventure Path System
 * Handles world generation, level progression, and boss challenges
 */
class AdventureWorldController extends Controller {

    /** @var IDBConnection */
    private $db;

    /** @var IUserSession */
    private $userSession;

    /** @var WorldGenerator */
    private $worldGenerator;

    /** @var PathGenerator */
    private $pathGenerator;

    /** @var LevelObjective */
    private $levelObjective;

    /** @var TasksApiIntegration */
    private $tasksApi;

    /** @var XPService */
    private $xpService;

    public function __construct(
        $AppName,
        IRequest $request,
        IDBConnection $db,
        IUserSession $userSession,
        WorldGenerator $worldGenerator,
        PathGenerator $pathGenerator,
        LevelObjective $levelObjective,
        TasksApiIntegration $tasksApi,
        XPService $xpService
    ) {
        parent::__construct($AppName, $request);
        $this->db = $db;
        $this->userSession = $userSession;
        $this->worldGenerator = $worldGenerator;
        $this->pathGenerator = $pathGenerator;
        $this->levelObjective = $levelObjective;
        $this->tasksApi = $tasksApi;
        $this->xpService = $xpService;
    }

    /**
     * Get all available worlds for the current user
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getWorlds(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            $worlds = [];

            // Get all world definitions
            $worldDefs = $this->worldGenerator->getWorldDefinitions();
            
            foreach ($worldDefs as $worldNumber => $worldDef) {
                // Check if world should be unlocked
                $shouldUnlock = $this->worldGenerator->shouldUnlockWorld($worldNumber, $userId);
                
                // Get user progress for this world
                $progress = $this->getUserWorldProgress($userId, $worldNumber);
                
                $world = [
                    'world_number' => $worldNumber,
                    'name' => $worldDef['name'],
                    'theme' => $worldDef['theme'],
                    'description' => $worldDef['description'],
                    'color_primary' => $worldDef['color_primary'],
                    'color_secondary' => $worldDef['color_secondary'],
                    'icon' => $worldDef['icon'],
                    'difficulty_modifier' => $worldDef['difficulty_modifier'],
                    'status' => $shouldUnlock ? ($progress['world_status'] ?? 'unlocked') : 'locked',
                    'progress' => $progress
                ];
                
                $worlds[] = $world;
            }

            return new JSONResponse([
                'status' => 'success',
                'data' => $worlds
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error getting worlds - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load worlds'
            ], 500);
        }
    }

    /**
     * Get current adventure path for a world
     * @NoAdminRequired
     * @param int $worldNumber
     * @return JSONResponse
     */
    public function getCurrentPath(int $worldNumber): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();

            // Check if world is unlocked
            if (!$this->worldGenerator->shouldUnlockWorld($worldNumber, $userId)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'World not unlocked'
                ], 403);
            }

            // Get or generate path for this world
            $pathData = $this->getOrGenerateWorldPath($userId, $worldNumber);
            
            if (!$pathData) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Failed to generate world path'
                ], 500);
            }

            // Get user's current position in this world
            $progress = $this->getUserWorldProgress($userId, $worldNumber);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'world_number' => $worldNumber,
                    'path' => $pathData,
                    'progress' => $progress,
                    'current_position' => $progress['current_position'] ?? 'start'
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error getting current path - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load path'
            ], 500);
        }
    }

    /**
     * Complete a level objective
     * @NoAdminRequired
     * @param int $levelId
     * @return JSONResponse
     */
    public function completeLevel(int $levelId): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();

            // Get level information
            $level = $this->getLevel($levelId, $userId);
            if (!$level) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Level not found'
                ], 404);
            }

            // Check if level is accessible (not locked)
            if ($level['status'] === 'locked') {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Level is locked'
                ], 403);
            }

            // Get level objectives
            $objectives = $this->getLevelObjectives($levelId);
            
            // Get available tasks to check objectives
            $availableTasks = $this->tasksApi->getTaskLists();
            $userStats = $this->xpService->getUserStats($userId);

            // Check if all objectives are completed
            $allCompleted = true;
            foreach ($objectives as $objective) {
                if (!$this->levelObjective->checkCompletion($objective, $availableTasks, $userStats)) {
                    $allCompleted = false;
                    break;
                }
            }

            if (!$allCompleted) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Level objectives not completed'
                ], 400);
            }

            // Mark level as completed
            $this->completeLevelInDatabase($levelId, $userId);
            
            // Award XP
            $xpEarned = $level['reward_xp'];
            $this->xpService->awardXP($userId, $xpEarned, 'Adventure level completed: ' . $level['name']);

            // Update world progress
            $this->updateWorldProgress($userId, $level['world_number'], $levelId);

            // Check if world is completed
            $worldCompleted = $this->checkWorldCompletion($userId, $level['world_number']);
            
            $response = [
                'status' => 'success',
                'data' => [
                    'level_completed' => true,
                    'xp_earned' => $xpEarned,
                    'level_name' => $level['name'],
                    'level_type' => $level['level_type'],
                    'world_completed' => $worldCompleted
                ]
            ];

            // If world completed, unlock next world
            if ($worldCompleted && $level['world_number'] < 8) {
                $this->unlockNextWorld($userId, $level['world_number'] + 1);
                $response['data']['next_world_unlocked'] = $level['world_number'] + 1;
            }

            return new JSONResponse($response);

        } catch (\Exception $e) {
            error_log('Adventure: Error completing level - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to complete level'
            ], 500);
        }
    }

    /**
     * Get current boss challenge for a world
     * @NoAdminRequired
     * @param int $worldNumber
     * @return JSONResponse
     */
    public function getBossChallenge(int $worldNumber): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            
            // Get boss definition for the world
            $bossDefinition = $this->worldGenerator->getBossDefinition($worldNumber);
            
            // Get user's progress to see if boss is accessible
            $progress = $this->getUserWorldProgress($userId, $worldNumber);
            
            // Check if user has reached boss level
            $bossAccessible = $progress && 
                              isset($progress['current_position']) && 
                              $this->isBossAccessible($userId, $worldNumber);

            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'world_number' => $worldNumber,
                    'boss' => $bossDefinition,
                    'accessible' => $bossAccessible,
                    'completed' => $progress['boss_defeated'] ?? false
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error getting boss challenge - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load boss challenge'
            ], 500);
        }
    }

    /**
     * Complete a boss challenge
     * @NoAdminRequired
     * @param int $worldNumber
     * @return JSONResponse
     */
    public function completeBoss(int $worldNumber): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();

            // Check if boss is accessible
            if (!$this->isBossAccessible($userId, $worldNumber)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Boss not accessible'
                ], 403);
            }

            // Get boss definition
            $bossDefinition = $this->worldGenerator->getBossDefinition($worldNumber);
            
            // Get available tasks and user stats
            $availableTasks = $this->tasksApi->getTaskLists();
            $userStats = $this->xpService->getUserStats($userId);

            // Create objective array for checking
            $bossObjective = [
                'type' => $bossDefinition['objective_type'],
                'data' => $bossDefinition['objective_data'],
                'description' => $bossDefinition['description']
            ];

            // Check if boss objective is completed
            if (!$this->levelObjective->checkCompletion($bossObjective, $availableTasks, $userStats)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Boss challenge not completed'
                ], 400);
            }

            // Mark boss as defeated
            $this->defeatBoss($userId, $worldNumber, 'boss');
            
            // Award boss XP
            $xpEarned = $bossDefinition['reward_xp'];
            $this->xpService->awardXP($userId, $xpEarned, 'Boss defeated: ' . $bossDefinition['name']);

            // Complete the world
            $this->completeWorld($userId, $worldNumber);
            
            // Unlock next world if not the final world
            $nextWorldUnlocked = false;
            if ($worldNumber < 8) {
                $this->unlockNextWorld($userId, $worldNumber + 1);
                $nextWorldUnlocked = true;
            }

            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'boss_defeated' => true,
                    'world_completed' => true,
                    'xp_earned' => $xpEarned,
                    'boss_name' => $bossDefinition['name'],
                    'next_world_unlocked' => $nextWorldUnlocked,
                    'next_world_number' => $nextWorldUnlocked ? $worldNumber + 1 : null
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error completing boss - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to complete boss'
            ], 500);
        }
    }

    /**
     * Get adventure progress statistics
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getProgress(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();

            // Get progress for all worlds
            $worldsProgress = [];
            $totalLevelsCompleted = 0;
            $totalBossesDefeated = 0;
            $totalXpFromAdventure = 0;
            
            for ($world = 1; $world <= 8; $world++) {
                $progress = $this->getUserWorldProgress($userId, $world);
                $worldsProgress[$world] = $progress;
                
                if ($progress) {
                    $totalLevelsCompleted += $progress['levels_completed'] ?? 0;
                    $totalXpFromAdventure += $progress['total_xp_earned'] ?? 0;
                    
                    if ($progress['boss_defeated'] ?? false) {
                        $totalBossesDefeated++;
                    }
                }
            }

            // Calculate overall completion percentage
            $unlockedWorlds = 0;
            $completedWorlds = 0;
            
            foreach ($worldsProgress as $progress) {
                if ($progress && $progress['world_status'] !== 'locked') {
                    $unlockedWorlds++;
                    if ($progress['world_status'] === 'completed') {
                        $completedWorlds++;
                    }
                }
            }

            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'worlds_progress' => $worldsProgress,
                    'summary' => [
                        'unlocked_worlds' => $unlockedWorlds,
                        'completed_worlds' => $completedWorlds,
                        'total_levels_completed' => $totalLevelsCompleted,
                        'total_bosses_defeated' => $totalBossesDefeated,
                        'total_xp_from_adventure' => $totalXpFromAdventure,
                        'completion_percentage' => $completedWorlds / 8 * 100
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error getting progress - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load progress'
            ], 500);
        }
    }

    // Private helper methods

    /**
     * Get or generate world path for user
     */
    private function getOrGenerateWorldPath(string $userId, int $worldNumber): ?array {
        // Check if path already exists
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('*PREFIX*adventure_paths')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if ($row) {
            // Return existing path
            return [
                'path_id' => $row['id'],
                'structure' => json_decode($row['structure_data'], true),
                'levels' => json_decode($row['path_data'], true),
                'connections' => json_decode($row['connections_data'], true),
                'total_levels' => $row['total_levels'],
                'mini_boss_position' => $row['mini_boss_position']
            ];
        }

        // Generate new path
        $worldData = $this->worldGenerator->generateWorld($worldNumber, $userId);
        $availableTasks = $this->tasksApi->getTaskLists();
        
        $pathData = $this->pathGenerator->generateWorldPath($worldData, $availableTasks);
        
        // Save path to database
        $this->saveWorldPath($userId, $worldNumber, $pathData);
        
        return $pathData;
    }

    /**
     * Get user's progress for a specific world
     */
    private function getUserWorldProgress(string $userId, int $worldNumber): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('*PREFIX*adventure_player_progress')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? $row : null;
    }

    /**
     * Save world path to database
     */
    private function saveWorldPath(string $userId, int $worldNumber, array $pathData): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('*PREFIX*adventure_paths')
           ->setValue('user_id', $qb->createNamedParameter($userId))
           ->setValue('world_number', $qb->createNamedParameter($worldNumber))
           ->setValue('path_data', $qb->createNamedParameter(json_encode($pathData['levels'])))
           ->setValue('structure_data', $qb->createNamedParameter(json_encode($pathData['structure'])))
           ->setValue('connections_data', $qb->createNamedParameter(json_encode($pathData['connections'])))
           ->setValue('total_levels', $qb->createNamedParameter($pathData['total_levels']))
           ->setValue('mini_boss_position', $qb->createNamedParameter($pathData['structure'][array_key_first($pathData['structure'])]['mini_boss_position'] ?? 5))
           ->setValue('created_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->setValue('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')));

        $qb->executeStatement();
    }

    /**
     * Get level information
     */
    private function getLevel(int $levelId, string $userId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('*PREFIX*adventure_levels')
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($levelId)))
           ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        return $row ? $row : null;
    }

    /**
     * Get level objectives
     */
    private function getLevelObjectives(int $levelId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('*PREFIX*adventure_objectives')
           ->where($qb->expr()->eq('level_id', $qb->createNamedParameter($levelId)));

        $result = $qb->executeQuery();
        $objectives = [];
        
        while ($row = $result->fetch()) {
            $objectives[] = [
                'type' => $row['objective_type'],
                'data' => json_decode($row['objective_data'], true),
                'description' => $row['description'],
                'task_id' => $row['task_id'],
                'task_title' => $row['task_title']
            ];
        }
        
        $result->closeCursor();
        return $objectives;
    }

    /**
     * Mark level as completed in database
     */
    private function completeLevelInDatabase(int $levelId, string $userId): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('*PREFIX*adventure_levels')
           ->set('status', $qb->createNamedParameter('completed'))
           ->set('completed_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($levelId)))
           ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        $qb->executeStatement();
    }

    /**
     * Update world progress
     */
    private function updateWorldProgress(string $userId, int $worldNumber, int $completedLevelId): void {
        // Implementation for updating user's world progress
        $qb = $this->db->getQueryBuilder();
        $qb->update('*PREFIX*adventure_player_progress')
           ->set('levels_completed', 'levels_completed + 1')
           ->set('current_level_id', $qb->createNamedParameter($completedLevelId))
           ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $qb->executeStatement();
    }

    /**
     * Check if world is completed
     */
    private function checkWorldCompletion(string $userId, int $worldNumber): bool {
        // Check if boss level is completed
        $qb = $this->db->getQueryBuilder();
        $qb->select('COUNT(*)')
           ->from('*PREFIX*adventure_levels')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)))
           ->andWhere($qb->expr()->eq('level_type', $qb->createNamedParameter('boss')))
           ->andWhere($qb->expr()->eq('status', $qb->createNamedParameter('completed')));

        $result = $qb->executeQuery();
        $count = (int) $result->fetchOne();
        $result->closeCursor();

        return $count > 0;
    }

    /**
     * Check if boss is accessible
     */
    private function isBossAccessible(string $userId, int $worldNumber): bool {
        // Check if all regular levels and mini-boss are completed
        $progress = $this->getUserWorldProgress($userId, $worldNumber);
        return $progress && 
               ($progress['levels_completed'] ?? 0) >= ($progress['total_levels'] ?? 1) - 1 &&
               ($progress['mini_boss_defeated'] ?? false);
    }

    /**
     * Mark boss as defeated
     */
    private function defeatBoss(string $userId, int $worldNumber, string $bossType): void {
        // Update progress table
        $qb = $this->db->getQueryBuilder();
        $qb->update('*PREFIX*adventure_player_progress')
           ->set('boss_defeated', $qb->createNamedParameter(true))
           ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $qb->executeStatement();

        // Record boss completion
        $bossDefinition = $this->worldGenerator->getBossDefinition($worldNumber);
        
        $qb = $this->db->getQueryBuilder();
        $qb->insert('*PREFIX*adventure_boss_completions')
           ->setValue('user_id', $qb->createNamedParameter($userId))
           ->setValue('world_number', $qb->createNamedParameter($worldNumber))
           ->setValue('boss_level_id', $qb->createNamedParameter(0)) // Will be updated when we have actual boss level IDs
           ->setValue('boss_type', $qb->createNamedParameter($bossType))
           ->setValue('xp_earned', $qb->createNamedParameter($bossDefinition['reward_xp']))
           ->setValue('completed_at', $qb->createNamedParameter(date('Y-m-d H:i:s')));

        $qb->executeStatement();
    }

    /**
     * Complete world
     */
    private function completeWorld(string $userId, int $worldNumber): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('*PREFIX*adventure_player_progress')
           ->set('world_status', $qb->createNamedParameter('completed'))
           ->set('completed_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $qb->executeStatement();
    }

    /**
     * Unlock next world
     */
    private function unlockNextWorld(string $userId, int $worldNumber): void {
        // Insert or update progress for next world
        $qb = $this->db->getQueryBuilder();
        
        // Check if progress row exists
        $qb->select('COUNT(*)')
           ->from('*PREFIX*adventure_player_progress')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $result = $qb->executeQuery();
        $exists = (int) $result->fetchOne() > 0;
        $result->closeCursor();

        if (!$exists) {
            // Insert new progress record
            $qb = $this->db->getQueryBuilder();
            $qb->insert('*PREFIX*adventure_player_progress')
               ->setValue('user_id', $qb->createNamedParameter($userId))
               ->setValue('world_number', $qb->createNamedParameter($worldNumber))
               ->setValue('world_status', $qb->createNamedParameter('unlocked'))
               ->setValue('total_levels', $qb->createNamedParameter(10)) // Default, will be updated
               ->setValue('created_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
               ->setValue('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')));

            $qb->executeStatement();
        } else {
            // Update existing record
            $qb = $this->db->getQueryBuilder();
            $qb->update('*PREFIX*adventure_player_progress')
               ->set('world_status', $qb->createNamedParameter('unlocked'))
               ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

            $qb->executeStatement();
        }
    }
}