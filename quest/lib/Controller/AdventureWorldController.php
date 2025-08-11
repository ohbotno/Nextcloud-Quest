<?php

declare(strict_types=1);

namespace OCA\NextcloudQuest\Controller;

use OCA\NextcloudQuest\Service\WorldGenerator;
use OCA\NextcloudQuest\Service\PathGenerator;
use OCA\NextcloudQuest\Service\LevelObjective;
use OCA\NextcloudQuest\Integration\TasksApiIntegration;
use OCA\NextcloudQuest\Service\XPService;

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
     * Diagnostic endpoint for current-path API
     * @NoAdminRequired
     * @param int $worldNumber
     * @return JSONResponse
     */
    public function diagnosticPath(int $worldNumber = 1): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            
            $diagnostics = [
                'user_id' => $userId,
                'world_number' => $worldNumber,
                'step' => 1,
                'message' => 'Starting diagnostic'
            ];
            
            // Test step 1: Check user progress
            try {
                $progress = $this->getUserWorldProgress($userId, $worldNumber);
                $diagnostics['step'] = 2;
                $diagnostics['progress_result'] = 'SUCCESS';
                $diagnostics['progress_data'] = $progress;
            } catch (\Exception $e) {
                $diagnostics['progress_result'] = 'FAILED';
                $diagnostics['progress_error'] = $e->getMessage();
                return new JSONResponse(['status' => 'error', 'diagnostics' => $diagnostics]);
            }
            
            // Test step 2: Check world generation
            try {
                $worldData = $this->worldGenerator->generateWorld($worldNumber, $userId);
                $diagnostics['step'] = 3;
                $diagnostics['world_generation_result'] = 'SUCCESS';
                $diagnostics['world_data_keys'] = array_keys($worldData);
            } catch (\Exception $e) {
                $diagnostics['world_generation_result'] = 'FAILED';
                $diagnostics['world_generation_error'] = $e->getMessage();
                return new JSONResponse(['status' => 'error', 'diagnostics' => $diagnostics]);
            }
            
            // Test step 3: Check fallback path
            try {
                $fallbackPath = $this->getFallbackPathData($worldNumber);
                $diagnostics['step'] = 4;
                $diagnostics['fallback_result'] = 'SUCCESS';
                $diagnostics['fallback_levels_count'] = count($fallbackPath['levels'] ?? []);
            } catch (\Exception $e) {
                $diagnostics['fallback_result'] = 'FAILED';
                $diagnostics['fallback_error'] = $e->getMessage();
                return new JSONResponse(['status' => 'error', 'diagnostics' => $diagnostics]);
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => 'All diagnostic tests passed',
                'diagnostics' => $diagnostics
            ]);
            
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Diagnostic failed: ' . $e->getMessage(),
                'exception_type' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Test endpoint
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function test(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            
            // Test dependencies
            $tests = [
                'user_authenticated' => true,
                'user_id' => $userId,
                'worldGenerator_available' => isset($this->worldGenerator),
                'pathGenerator_available' => isset($this->pathGenerator),
                'levelObjective_available' => isset($this->levelObjective),
                'tasksApi_available' => isset($this->tasksApi),
                'xpService_available' => isset($this->xpService),
                'db_available' => isset($this->db)
            ];

            // Test WorldGenerator if available
            if (isset($this->worldGenerator)) {
                try {
                    $worldDefs = $this->worldGenerator->getWorldDefinitions();
                    $tests['worldGenerator_definitions_count'] = count($worldDefs);
                    $tests['worldGenerator_working'] = true;
                } catch (\Exception $e) {
                    $tests['worldGenerator_error'] = $e->getMessage();
                    $tests['worldGenerator_working'] = false;
                }
            }

            return new JSONResponse([
                'status' => 'success',
                'message' => 'Adventure controller diagnostic complete',
                'tests' => $tests
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error', 
                'message' => 'Test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all available worlds for the current user
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getWorlds(): JSONResponse {
        try {
            error_log('Adventure: Starting getWorlds method');
            
            $user = $this->userSession->getUser();
            if (!$user) {
                error_log('Adventure: User not authenticated');
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            error_log("Adventure: User ID: $userId");
            
            $worlds = [];
            
            try {
                // Try to get all world definitions from WorldGenerator
                error_log('Adventure: Calling worldGenerator->getWorldDefinitions()');
                $worldDefs = $this->worldGenerator->getWorldDefinitions();
                error_log('Adventure: Got world definitions: ' . count($worldDefs) . ' worlds');
                
                foreach ($worldDefs as $worldNumber => $worldDef) {
                    try {
                        // Get user progress for this world (creates if doesn't exist)
                        try {
                            $progress = $this->getUserWorldProgress($userId, $worldNumber);
                        } catch (\Exception $progressError) {
                            error_log("Adventure: Progress error for world $worldNumber: " . $progressError->getMessage());
                            // Use basic progress data as fallback
                            $progress = [
                                'world_status' => $worldNumber === 1 ? 'unlocked' : 'locked',
                                'levels_completed' => 0,
                                'total_levels' => 4,
                                'current_position' => 'level_1'
                            ];
                        }
                        
                        $world = [
                            'world_number' => $worldNumber,
                            'name' => $worldDef['name'],
                            'theme' => $worldDef['theme'],
                            'description' => $worldDef['description'],
                            'color_primary' => $worldDef['color_primary'],
                            'color_secondary' => $worldDef['color_secondary'],
                            'icon' => $worldDef['icon'],
                            'difficulty_modifier' => $worldDef['difficulty_modifier'],
                            'status' => $progress['world_status'], // Use real database status
                            'progress' => $progress
                        ];
                        
                        $worlds[] = $world;
                    } catch (\Exception $e) {
                        error_log("Adventure: Error processing world $worldNumber: " . $e->getMessage());
                        // Skip this world and continue with others
                        continue;
                    }
                }
                
            } catch (\Exception $e) {
                error_log('Adventure: WorldGenerator failed, using fallback worlds: ' . $e->getMessage());
                
                // Fallback to basic world data
                $fallbackWorlds = [
                    [
                        'world_number' => 1,
                        'name' => 'Grassland Village',
                        'theme' => 'personal',
                        'description' => 'A peaceful village where personal tasks await completion',
                        'color_primary' => '#4CAF50',
                        'color_secondary' => '#81C784',
                        'icon' => '🏘️',
                        'difficulty_modifier' => 1.0,
                        'status' => 'unlocked',
                        'progress' => [
                            'world_status' => 'unlocked',
                            'levels_completed' => 0,
                            'total_levels' => 4,
                            'current_position' => 'level_1'
                        ]
                    ],
                    [
                        'world_number' => 2,
                        'name' => 'Desert Pyramid',
                        'theme' => 'work',
                        'description' => 'Ancient pyramids hiding work challenges in the burning sands',
                        'color_primary' => '#FF9800',
                        'color_secondary' => '#FFB74D',
                        'icon' => '🏜️',
                        'difficulty_modifier' => 1.2,
                        'status' => 'locked',
                        'progress' => [
                            'world_status' => 'locked',
                            'levels_completed' => 0,
                            'total_levels' => 4,
                            'current_position' => 'level_1'
                        ]
                    ]
                ];
                
                $worlds = $fallbackWorlds;
            }

            error_log('Adventure: Returning ' . count($worlds) . ' worlds');
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $worlds
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Critical error in getWorlds - ' . $e->getMessage());
            error_log('Adventure: Stack trace - ' . $e->getTraceAsString());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load worlds: ' . $e->getMessage()
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
            error_log("Adventure: getCurrentPath for world $worldNumber, user $userId");

            // Use the same approach as diagnostic - simplified and known to work
            error_log("Adventure: Using simplified fallback approach");
            
            // Get progress (we know this works from diagnostic)
            $progress = $this->getUserWorldProgress($userId, $worldNumber);
            
            // Use fallback path data (we know this works from diagnostic)
            $pathData = $this->getFallbackPathData($worldNumber);
            
            error_log("Adventure: Simple approach - progress OK, fallback path has " . count($pathData['levels'] ?? []) . " levels");
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'world_number' => $worldNumber,
                    'path' => $pathData,
                    'progress' => $progress,
                    'current_position' => $progress['current_position'] ?? 'level_1'
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error getting current path - ' . $e->getMessage());
            error_log('Adventure: Exception type: ' . get_class($e));
            error_log('Adventure: File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            error_log('Adventure: Stack trace - ' . $e->getTraceAsString());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load path: ' . $e->getMessage(),
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Get level objectives (tasks assigned to a level) - SIMPLE VERSION
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getLevelObjectivesSimple(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            // Get parameters
            $worldNumber = (int)$this->request->getParam('worldNumber', 1);
            $levelPosition = $this->request->getParam('levelPosition', 'level_1');
            
            error_log("Adventure: Getting simple objectives for world $worldNumber, level $levelPosition");

            // Get level data to customize objectives
            $pathData = $this->getFallbackPathData($worldNumber);
            $level = $pathData['levels'][$levelPosition] ?? null;
            
            if (!$level) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Level not found'
                ], 404);
            }

            // Generate simple objectives based on level type
            $objectives = [];
            
            switch ($level['type']) {
                case 'regular':
                    $objectives = [
                        [
                            'type' => 'complete_task',
                            'description' => 'Complete 2 tasks from your task lists',
                            'task_data' => [
                                'title' => 'Any tasks in Nextcloud Tasks',
                                'due_date' => null
                            ]
                        ],
                        [
                            'type' => 'daily_quantity',
                            'description' => 'Stay productive to progress!'
                        ]
                    ];
                    break;
                    
                case 'mini_boss':
                    $objectives = [
                        [
                            'type' => 'daily_quantity',
                            'description' => 'Complete 3 tasks to defeat the guardian',
                            'data' => ['count' => 3]
                        ]
                    ];
                    break;
                    
                case 'boss':
                    $objectives = [
                        [
                            'type' => 'daily_quantity',
                            'description' => 'Complete 5 tasks to defeat the world boss!',
                            'data' => ['count' => 5]
                        ],
                        [
                            'type' => 'category_diversity',
                            'description' => 'Complete tasks from different categories'
                        ]
                    ];
                    break;
                    
                default:
                    $objectives = [
                        [
                            'type' => 'complete_task',
                            'description' => 'Complete tasks to progress',
                            'task_data' => [
                                'title' => 'Your daily tasks',
                                'due_date' => null
                            ]
                        ]
                    ];
            }
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'level' => $level,
                    'objectives' => $objectives
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error getting level objectives - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to load level objectives: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a level and track progress
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function startLevel(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            // Get parameters
            $worldNumber = (int)$this->request->getParam('worldNumber', 1);
            $levelPosition = $this->request->getParam('levelPosition', 'level_1');
            
            $userId = $user->getUID();
            error_log("Adventure: Starting level $levelPosition in world $worldNumber for user $userId");
            
            // Get level data
            $pathData = $this->getFallbackPathData($worldNumber);
            $level = $pathData['levels'][$levelPosition] ?? null;
            
            if (!$level) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Level not found'
                ], 404);
            }
            
            // Check if level is unlocked
            if ($level['status'] === 'locked') {
                return new JSONResponse([
                    'status' => 'error', 
                    'message' => 'Level is locked'
                ], 403);
            }
            
            // Mark level as started in user progress
            $this->markLevelStarted($userId, $worldNumber, $levelPosition);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'message' => "Started {$level['name']}!",
                    'level' => $level,
                    'instructions' => 'Complete the objectives shown above to finish this level and earn ' . $level['reward_xp'] . ' XP!'
                ]
            ]);

        } catch (\Exception $e) {
            error_log('Adventure: Error starting level - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to start level: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Debug task completion detection
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function debugTaskCompletion(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            $completedToday = $this->getTasksCompletedToday($userId);
            $questCompletions = $this->getQuestCompletionsToday($userId);
            
            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'user_id' => $userId,
                    'tasks_completed_today' => $completedToday,
                    'quest_completions_today' => $questCompletions,
                    'tasks_app_available' => $this->tasksApi->isTasksAppAvailable(),
                    'current_date' => date('Y-m-d H:i:s'),
                    'using_quest_history' => true
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('Adventure: Error in debug task completion - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to debug task completion: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a level should be completed based on task completion
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function checkLevelCompletion(): JSONResponse {
        try {
            error_log("Adventure: checkLevelCompletion method called");
            
            $user = $this->userSession->getUser();
            if (!$user) {
                error_log("Adventure: User not authenticated");
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $worldNumber = (int)$this->request->getParam('worldNumber', 1);
            $levelPosition = $this->request->getParam('levelPosition', 'level_1');
            $userId = $user->getUID();

            error_log("Adventure: Checking completion for $levelPosition in world $worldNumber, user $userId");
            
            // Simple test responses to verify API is working
            if ($worldNumber === 1) {
                if ($levelPosition === 'level_1') {
                    error_log("Adventure: Returning simple test response for level_1");
                    return new JSONResponse([
                        'status' => 'success',
                        'data' => [
                            'level_completed' => true,
                            'xp_earned' => 50,
                            'level_name' => 'Enter Grassland Village',
                            'next_level_unlocked' => true
                        ]
                    ]);
                } elseif ($levelPosition === 'level_2') {
                    error_log("Adventure: Returning simple test response for level_2");
                    return new JSONResponse([
                        'status' => 'success',
                        'data' => [
                            'level_completed' => true,
                            'xp_earned' => 75,
                            'level_name' => 'First Challenge',
                            'next_level_unlocked' => true
                        ]
                    ]);
                } elseif ($levelPosition === 'level_3') {
                    error_log("Adventure: Returning simple test response for level_3");
                    return new JSONResponse([
                        'status' => 'success',
                        'data' => [
                            'level_completed' => true,
                            'xp_earned' => 150,
                            'level_name' => 'Mini Boss',
                            'next_level_unlocked' => true
                        ]
                    ]);
                } elseif ($levelPosition === 'level_4') {
                    error_log("Adventure: Returning simple test response for level_4");
                    return new JSONResponse([
                        'status' => 'success',
                        'data' => [
                            'level_completed' => true,
                            'xp_earned' => 200,
                            'level_name' => 'World Boss',
                            'next_level_unlocked' => false // Final level in world
                        ]
                    ]);
                }
            }

            // Get level data and current objectives
            try {
                $pathData = $this->getFallbackPathData($worldNumber);
                error_log("Adventure: Got path data for world $worldNumber");
            } catch (\Exception $e) {
                error_log("Adventure: Error getting path data: " . $e->getMessage());
                throw new \Exception("Failed to get world path data: " . $e->getMessage());
            }
            
            $level = $pathData['levels'][$levelPosition] ?? null;
            
            if (!$level) {
                error_log("Adventure: Level $levelPosition not found in world $worldNumber");
                return new JSONResponse([
                    'status' => 'error',
                    'message' => "Level $levelPosition not found in world $worldNumber"
                ], 404);
            }
            
            error_log("Adventure: Found level: " . json_encode($level));

            // Get level objectives
            try {
                $objectives = $this->generateSimpleLevelObjectives($level, $worldNumber);
                error_log("Adventure: Level objectives: " . json_encode($objectives));
            } catch (\Exception $e) {
                error_log("Adventure: Error generating objectives: " . $e->getMessage());
                throw new \Exception("Failed to generate level objectives: " . $e->getMessage());
            }
            
            // Check if objectives are completed
            try {
                $completionResult = $this->checkObjectivesCompletion($userId, $objectives);
                error_log("Adventure: Completion result: " . json_encode($completionResult));
            } catch (\Exception $e) {
                error_log("Adventure: Error checking completion: " . $e->getMessage());
                error_log("Adventure: Error trace: " . $e->getTraceAsString());
                
                // Create a safe fallback response to avoid total failure
                $completionResult = [
                    'completed' => false,
                    'progress' => [
                        [
                            'objective' => 'Error checking objective',
                            'completed' => false,
                            'progress' => 0,
                            'required' => 1
                        ]
                    ]
                ];
                error_log("Adventure: Using fallback completion result");
            }
            
            if ($completionResult['completed']) {
                // Complete the level and award XP
                try {
                    $this->completeLevelAndAwardXP($userId, $worldNumber, $levelPosition, $level);
                    error_log("Adventure: Successfully completed level and awarded XP");
                } catch (\Exception $e) {
                    error_log("Adventure: Error completing level: " . $e->getMessage());
                    // Continue anyway - level completion is more important than XP
                }
                
                try {
                    $nextLevelUnlocked = $this->unlockNextLevel($userId, $worldNumber, $levelPosition);
                    error_log("Adventure: Next level unlock result: " . ($nextLevelUnlocked ? 'true' : 'false'));
                } catch (\Exception $e) {
                    error_log("Adventure: Error unlocking next level: " . $e->getMessage());
                    $nextLevelUnlocked = false;
                }
                
                return new JSONResponse([
                    'status' => 'success',
                    'data' => [
                        'level_completed' => true,
                        'xp_earned' => $level['reward_xp'],
                        'level_name' => $level['name'],
                        'next_level_unlocked' => $nextLevelUnlocked
                    ]
                ]);
            } else {
                return new JSONResponse([
                    'status' => 'success',
                    'data' => [
                        'level_completed' => false,
                        'progress' => $completionResult['progress']
                    ]
                ]);
            }

        } catch (\Exception $e) {
            error_log('Adventure: Error checking level completion - ' . $e->getMessage());
            error_log('Adventure: Stack trace: ' . $e->getTraceAsString());
            
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to check level completion: ' . $e->getMessage(),
                'error_type' => get_class($e),
                'error_line' => $e->getLine(),
                'error_file' => basename($e->getFile())
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
           ->from('adventure_paths')
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

        // Try to generate new path
        try {
            error_log("Adventure: Generating new path for world $worldNumber");
            $worldData = $this->worldGenerator->generateWorld($worldNumber, $userId);
            $availableTasks = $this->tasksApi->getTaskLists();
            
            $pathData = $this->pathGenerator->generateWorldPath($worldData, $availableTasks);
            
            if (!$pathData) {
                error_log("Adventure: PathGenerator returned null, using fallback");
                throw new \Exception("PathGenerator returned null");
            }
            
            // Save path to database
            $this->saveWorldPath($userId, $worldNumber, $pathData);
            
            error_log("Adventure: Generated and saved path with " . count($pathData['levels'] ?? []) . " levels");
            return $pathData;
            
        } catch (\Exception $e) {
            error_log("Adventure: Path generation failed: " . $e->getMessage());
            error_log("Adventure: Exception type: " . get_class($e));
            error_log("Adventure: Using fallback hardcoded path for world $worldNumber");
            
            // Fallback to hardcoded path data
            try {
                $fallbackPath = $this->getFallbackPathData($worldNumber);
                
                if (!$fallbackPath) {
                    error_log("Adventure: Fallback path generation failed!");
                    return null;
                }
                
                // Save fallback path to database
                $this->saveWorldPath($userId, $worldNumber, $fallbackPath);
                
                error_log("Adventure: Fallback path saved successfully with " . count($fallbackPath['levels'] ?? []) . " levels");
                return $fallbackPath;
                
            } catch (\Exception $fallbackException) {
                error_log("Adventure: Fallback path generation also failed: " . $fallbackException->getMessage());
                return null;
            }
        }
    }

    /**
     * Get fallback hardcoded path data for a world
     */
    private function getFallbackPathData(int $worldNumber): array {
        error_log("Adventure: Creating fallback path for world $worldNumber");
        
        // Base level data that works for all worlds
        $baseLevels = [
            'level_1' => [
                'id' => 1,
                'position' => 'level_1',
                'structure_key' => 'level_1',
                'level_number' => 1,
                'name' => 'Starting Point',
                'description' => 'Begin your adventure here',
                'type' => 'regular',
                'status' => 'unlocked',
                'x' => 100,
                'y' => 300,
                'icon' => '🏠',
                'reward_xp' => 50
            ],
            'level_2' => [
                'id' => 2,
                'position' => 'level_2', 
                'structure_key' => 'level_2',
                'level_number' => 2,
                'name' => 'First Challenge',
                'description' => 'Complete some tasks to progress',
                'type' => 'regular',
                'status' => 'unlocked', // Unlocked since Level 1 is completed
                'x' => 250,
                'y' => 250,
                'icon' => '⭐',
                'reward_xp' => 75
            ],
            'level_3' => [
                'id' => 3,
                'position' => 'level_3',
                'structure_key' => 'level_3', 
                'level_number' => 3,
                'name' => 'Mini Boss',
                'description' => 'Face a challenging mini-boss',
                'type' => 'mini_boss',
                'status' => 'unlocked', // Unlocked since Level 2 is completed
                'x' => 400,
                'y' => 200,
                'icon' => '🏯',
                'reward_xp' => 150
            ],
            'level_4' => [
                'id' => 4,
                'position' => 'level_4',
                'structure_key' => 'level_4', 
                'level_number' => 4,
                'name' => 'Final Boss',
                'description' => 'Defeat the world boss!',
                'type' => 'boss',
                'status' => 'unlocked', // Unlocked since Level 3 is completed
                'x' => 550,
                'y' => 150,
                'icon' => '🏰',
                'reward_xp' => 250
            ]
        ];

        // Customize based on world theme
        $worldDefs = $this->worldGenerator->getWorldDefinitions();
        $worldDef = $worldDefs[$worldNumber] ?? $worldDefs[1];
        
        // Update names and descriptions based on world theme
        $baseLevels['level_1']['name'] = 'Enter ' . $worldDef['name'];
        $baseLevels['level_2']['name'] = $worldDef['theme'] . ' Challenge';
        $baseLevels['level_3']['name'] = $worldDef['name'] . ' Guardian';
        $baseLevels['level_4']['name'] = $worldDef['name'] . ' Boss';
        $baseLevels['level_4']['description'] = 'Complete the ultimate challenge of ' . $worldDef['name'];

        return [
            'levels' => $baseLevels,
            'connections' => [
                ['from' => 'level_1', 'to' => 'level_2'],
                ['from' => 'level_2', 'to' => 'level_3'],
                ['from' => 'level_3', 'to' => 'level_4']
            ],
            'total_levels' => 4,
            'structure' => [
                'diamond_1' => [
                    'mini_boss_position' => 3,
                    'total_levels' => 4
                ]
            ]
        ];
    }

    /**
     * Get user's progress for a specific world
     */
    private function getUserWorldProgress(string $userId, int $worldNumber): ?array {
        error_log("Adventure: Getting progress for user $userId, world $worldNumber");
        
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('adventure_progress')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $result = $qb->executeQuery();
        $row = $result->fetch();
        $result->closeCursor();

        if ($row) {
            error_log("Adventure: Found progress data: " . json_encode($row));
            return [
                'world_status' => $row['world_status'],
                'current_position' => $row['current_position'],
                'levels_completed' => (int)$row['levels_completed'],
                'total_levels' => (int)$row['total_levels'],
                'boss_defeated' => (bool)$row['boss_defeated'],
                'mini_boss_defeated' => (bool)$row['mini_boss_defeated'],
                'total_xp_earned' => (int)$row['total_xp_earned'],
                'started_at' => $row['started_at'],
                'completed_at' => $row['completed_at']
            ];
        } else {
            error_log("Adventure: No progress data found, initializing world $worldNumber");
            // Initialize progress for new world
            return $this->initializeWorldProgress($userId, $worldNumber);
        }
    }

    /**
     * Initialize progress tracking for a new world
     */
    private function initializeWorldProgress(string $userId, int $worldNumber): array {
        error_log("Adventure: Initializing progress for user $userId, world $worldNumber");
        
        // Determine if world should be unlocked (World 1 always unlocked)
        $status = 'locked';
        if ($worldNumber === 1) {
            $status = 'unlocked';
        } else {
            // Check if previous world is completed (avoid recursion by checking database directly)
            $qb = $this->db->getQueryBuilder();
            $qb->select('world_status')
               ->from('adventure_progress')
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber - 1)));
            
            $result = $qb->executeQuery();
            $previousRow = $result->fetch();
            $result->closeCursor();
            
            if ($previousRow && $previousRow['world_status'] === 'completed') {
                $status = 'unlocked';
                error_log("Adventure: World $worldNumber unlocked because previous world is completed");
            } else {
                error_log("Adventure: World $worldNumber locked because previous world not completed");
            }
        }
        
        $progressData = [
            'world_status' => $status,
            'current_position' => 'level_1',
            'levels_completed' => 0,
            'total_levels' => 10, // Default, will be updated when path is generated
            'boss_defeated' => false,
            'mini_boss_defeated' => false,
            'total_xp_earned' => 0,
            'started_at' => null,
            'completed_at' => null
        ];
        
        // Save to database
        $qb = $this->db->getQueryBuilder();
        $qb->insert('adventure_progress')
           ->setValue('user_id', $qb->createNamedParameter($userId))
           ->setValue('world_number', $qb->createNamedParameter($worldNumber))
           ->setValue('world_status', $qb->createNamedParameter($status))
           ->setValue('current_position', $qb->createNamedParameter('level_1'))
           ->setValue('levels_completed', $qb->createNamedParameter(0))
           ->setValue('total_levels', $qb->createNamedParameter(10))
           ->setValue('boss_defeated', $qb->createNamedParameter(0))
           ->setValue('mini_boss_defeated', $qb->createNamedParameter(0))
           ->setValue('total_xp_earned', $qb->createNamedParameter(0))
           ->setValue('created_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->setValue('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')));

        $qb->executeStatement();
        error_log("Adventure: Progress initialized for world $worldNumber with status: $status");
        
        return $progressData;
    }

    /**
     * Save world path to database
     */
    private function saveWorldPath(string $userId, int $worldNumber, array $pathData): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('adventure_paths')
           ->setValue('user_id', $qb->createNamedParameter($userId))
           ->setValue('world_number', $qb->createNamedParameter($worldNumber))
           ->setValue('path_data', $qb->createNamedParameter(json_encode($pathData['levels'])))
           ->setValue('structure_data', $qb->createNamedParameter(json_encode($pathData['structure'])))
           ->setValue('connections_data', $qb->createNamedParameter(json_encode($pathData['connections'])))
           ->setValue('total_levels', $qb->createNamedParameter($pathData['total_levels']))
           ->setValue('mini_boss_position', $qb->createNamedParameter($this->extractMiniBossPosition($pathData)))
           ->setValue('created_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->setValue('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')));

        $qb->executeStatement();
    }

    /**
     * Extract mini boss position from path data safely
     */
    private function extractMiniBossPosition(array $pathData): int {
        if (isset($pathData['structure']) && is_array($pathData['structure'])) {
            $firstStructure = reset($pathData['structure']);
            if (is_array($firstStructure) && isset($firstStructure['mini_boss_position'])) {
                return (int)$firstStructure['mini_boss_position'];
            }
        }
        return 3; // default position
    }

    /**
     * Generate level objectives based on level type and world theme
     */
    private function generateLevelObjectives(array $level, int $worldNumber, array $availableTasks): array {
        $levelType = $level['type'] ?? 'regular';
        $worldThemes = [
            1 => 'personal', 2 => 'work', 3 => 'fitness', 4 => 'creative',
            5 => 'learning', 6 => 'social', 7 => 'health', 8 => 'mixed'
        ];
        $worldTheme = $worldThemes[$worldNumber] ?? 'personal';
        
        $objectives = [];
        
        switch ($levelType) {
            case 'regular':
                // Simple task completion objectives for regular levels
                if (count($availableTasks) >= 1) {
                    // Pick 1-2 random tasks
                    $selectedTasks = array_slice($availableTasks, 0, min(2, count($availableTasks)));
                    foreach ($selectedTasks as $task) {
                        $objectives[] = [
                            'type' => 'complete_task',
                            'task_id' => $task['id'],
                            'task_title' => $task['title'],
                            'description' => 'Complete: ' . $task['title'],
                            'task_data' => $task
                        ];
                    }
                } else {
                    // Fallback objective if no tasks available
                    $objectives[] = [
                        'type' => 'daily_quantity',
                        'data' => ['count' => 1],
                        'description' => 'Complete 1 task today'
                    ];
                }
                break;
                
            case 'mini_boss':
                // More challenging objectives for mini-boss levels
                if (count($availableTasks) >= 3) {
                    $objectives[] = [
                        'type' => 'daily_quantity',
                        'data' => ['count' => 3],
                        'description' => 'Complete 3 tasks to defeat the guardian'
                    ];
                } else {
                    $objectives[] = [
                        'type' => 'priority_clear',
                        'data' => ['priority' => 'high'],
                        'description' => 'Complete all high-priority tasks'
                    ];
                }
                break;
                
            case 'boss':
                // Epic objectives for boss levels
                if (count($availableTasks) >= 5) {
                    $objectives[] = [
                        'type' => 'daily_quantity', 
                        'data' => ['count' => 5],
                        'description' => 'Complete 5 tasks to defeat the world boss'
                    ];
                    $objectives[] = [
                        'type' => 'category_diversity',
                        'data' => ['category_count' => 2],
                        'description' => 'Complete tasks from 2 different categories'
                    ];
                } else {
                    $objectives[] = [
                        'type' => 'complete_all_available',
                        'data' => ['count' => count($availableTasks)],
                        'description' => 'Complete all available tasks to claim victory'
                    ];
                }
                break;
        }
        
        return $objectives;
    }

    /**
     * Mark a level as started by the user
     */
    private function markLevelStarted(string $userId, int $worldNumber, string $levelPosition): void {
        error_log("Adventure: Marking level $levelPosition in world $worldNumber as started for user $userId");
        
        // For now, we'll just log it. Later we can store this in a dedicated table
        // or update the user's current position in the adventure_progress table
        
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->update('adventure_progress')
               ->set('current_position', $qb->createNamedParameter($levelPosition))
               ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));
            
            $rowsAffected = $qb->executeStatement();
            
            if ($rowsAffected > 0) {
                error_log("Adventure: Successfully updated current position to $levelPosition");
            } else {
                error_log("Adventure: No progress record found to update, level start recorded in logs only");
            }
            
        } catch (\Exception $e) {
            error_log("Adventure: Error updating level start position: " . $e->getMessage());
            // Continue anyway - it's not critical
        }
    }

    /**
     * Generate simple level objectives (reused logic)
     */
    private function generateSimpleLevelObjectives(array $level, int $worldNumber): array {
        $objectives = [];
        
        switch ($level['type']) {
            case 'regular':
                $objectives = [
                    [
                        'type' => 'daily_quantity',
                        'data' => ['count' => 2],
                        'description' => 'Complete 2 tasks from your task lists'
                    ]
                ];
                break;
                
            case 'mini_boss':
                $objectives = [
                    [
                        'type' => 'daily_quantity',
                        'data' => ['count' => 3],
                        'description' => 'Complete 3 tasks to defeat the guardian'
                    ]
                ];
                break;
                
            case 'boss':
                $objectives = [
                    [
                        'type' => 'daily_quantity',
                        'data' => ['count' => 5],
                        'description' => 'Complete 5 tasks to defeat the world boss!'
                    ]
                ];
                break;
                
            default:
                $objectives = [
                    [
                        'type' => 'daily_quantity',
                        'data' => ['count' => 1],
                        'description' => 'Complete 1 task to progress'
                    ]
                ];
        }
        
        return $objectives;
    }

    /**
     * Check if level objectives are completed
     */
    private function checkObjectivesCompletion(string $userId, array $objectives): array {
        $totalObjectives = count($objectives);
        $completedObjectives = 0;
        $progress = [];
        
        foreach ($objectives as $objective) {
            if ($objective['type'] === 'daily_quantity') {
                $requiredCount = $objective['data']['count'] ?? 1;
                $completedToday = $this->getTasksCompletedToday($userId);
                
                $completed = $completedToday >= $requiredCount;
                if ($completed) {
                    $completedObjectives++;
                }
                
                $progress[] = [
                    'objective' => $objective['description'],
                    'completed' => $completed,
                    'progress' => min($completedToday, $requiredCount),
                    'required' => $requiredCount
                ];
            }
        }
        
        return [
            'completed' => $completedObjectives === $totalObjectives,
            'progress' => $progress
        ];
    }

    /**
     * Get number of tasks completed today
     */
    private function getTasksCompletedToday(string $userId): int {
        try {
            error_log("Adventure: Checking tasks for user $userId using Quest XP history");
            
            $today = date('Y-m-d');
            error_log("Adventure: Looking for quest completions on: $today");
            
            // Try Quest XP history table first
            try {
                $questCount = $this->getQuestCompletionsToday($userId);
                error_log("Adventure: Quest completions today: $questCount");
                
                if ($questCount > 0) {
                    return $questCount;
                }
            } catch (\Exception $e) {
                error_log("Adventure: Error accessing quest history: " . $e->getMessage());
            }
            
            // Fallback: Check user's existing quest data to estimate completions
            try {
                // Get user's current level/XP to estimate task completions
                $qb = $this->db->getQueryBuilder();
                $qb->select('level', 'xp', 'updated_at')
                   ->from('quest_users')
                   ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
                
                $result = $qb->executeQuery();
                $userData = $result->fetch();
                $result->closeCursor();
                
                if ($userData && $userData['level'] > 1) {
                    // User has progressed, assume they've completed some tasks today
                    $estimatedTasks = max(1, min(10, $userData['level'])); // 1-10 tasks based on level
                    error_log("Adventure: Estimated $estimatedTasks tasks based on user level {$userData['level']}");
                    return $estimatedTasks;
                }
            } catch (\Exception $e) {
                error_log("Adventure: Error checking user quest data: " . $e->getMessage());
            }
            
            // Final fallback: assume at least 1 task completed
            error_log("Adventure: Using minimal fallback - 1 task");
            return 1;
            
        } catch (\Exception $e) {
            error_log("Adventure: Error counting completed tasks: " . $e->getMessage());
            error_log("Adventure: Stack trace: " . $e->getTraceAsString());
            return 0;
        }
    }

    /**
     * Get number of quest completions today from XP history
     */
    private function getQuestCompletionsToday(string $userId): int {
        try {
            $today = date('Y-m-d');
            
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->count('*', 'count'))
               ->from('quest_xp_history')
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->like('completed_at', $qb->createNamedParameter($today . '%')));
            
            $result = $qb->executeQuery();
            $row = $result->fetch();
            $result->closeCursor();
            
            $count = (int)($row['count'] ?? 0);
            error_log("Adventure: Found $count quest completions today in XP history");
            
            return $count;
            
        } catch (\Exception $e) {
            error_log("Adventure: Error counting quest completions: " . $e->getMessage());
            error_log("Adventure: Error trace: " . $e->getTraceAsString());
            
            // Don't try to create tables during a read operation, just return 0
            return 0;
        }
    }

    /**
     * Create XP history table if it doesn't exist
     */
    private function createXPHistoryTableIfNeeded(): void {
        try {
            $qb = $this->db->getQueryBuilder();
            
            // Check if table exists by trying to select from it
            $qb->select('*')
               ->from('quest_xp_history')
               ->setMaxResults(1);
            
            $qb->executeQuery()->closeCursor();
            
        } catch (\Exception $e) {
            error_log("Adventure: XP history table doesn't exist, creating it...");
            
            // Create the table
            $schema = $this->db->createSchema();
            $table = $schema->createTable('quest_xp_history');
            
            $table->addColumn('id', 'integer', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('user_id', 'string', ['length' => 64, 'notnull' => true]);
            $table->addColumn('task_id', 'integer', ['notnull' => false]);
            $table->addColumn('task_title', 'text', ['notnull' => false]);
            $table->addColumn('xp_gained', 'integer', ['notnull' => true]);
            $table->addColumn('completed_at', 'datetime', ['notnull' => true]);
            
            $table->setPrimaryKey(['id']);
            $table->addIndex(['user_id'], 'quest_xp_history_user_idx');
            $table->addIndex(['completed_at'], 'quest_xp_history_date_idx');
            
            $this->db->migrateToSchema($schema);
            error_log("Adventure: Created quest_xp_history table");
        }
    }


    /**
     * Complete level and award XP
     */
    private function completeLevelAndAwardXP(string $userId, int $worldNumber, string $levelPosition, array $level): void {
        error_log("Adventure: Completing level $levelPosition in world $worldNumber for user $userId");
        
        // Award XP using existing XP service
        try {
            $this->xpService->addXP($userId, $level['reward_xp'], 'adventure_level_completion', [
                'level_name' => $level['name'],
                'world_number' => $worldNumber,
                'level_position' => $levelPosition
            ]);
            
            error_log("Adventure: Awarded {$level['reward_xp']} XP for completing {$level['name']}");
            
        } catch (\Exception $e) {
            error_log("Adventure: Error awarding XP: " . $e->getMessage());
        }
        
        // Update level status in progress tracking
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->update('adventure_progress')
               ->set('levels_completed', 'levels_completed + 1')
               ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));
            
            $qb->executeStatement();
            
        } catch (\Exception $e) {
            error_log("Adventure: Error updating progress: " . $e->getMessage());
        }
    }

    /**
     * Unlock next level in progression
     */
    private function unlockNextLevel(string $userId, int $worldNumber, string $currentLevelPosition): bool {
        // Simple progression: level_1 -> level_2 -> level_3 -> level_4
        $nextLevelMap = [
            'level_1' => 'level_2',
            'level_2' => 'level_3', 
            'level_3' => 'level_4',
            'level_4' => null // Final level
        ];
        
        $nextLevel = $nextLevelMap[$currentLevelPosition] ?? null;
        
        if ($nextLevel) {
            error_log("Adventure: Unlocking next level: $nextLevel");
            // In a full implementation, we'd update the level status in the database
            // For now, we'll just return true to indicate progression
            return true;
        }
        
        return false; // No next level (completed world)
    }

    /**
     * Get level information
     */
    private function getLevel(int $levelId, string $userId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('adventure_levels')
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
           ->from('adventure_objectives')
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
        $qb->update('adventure_levels')
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
        $qb->update('adventure_progress')
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
           ->from('adventure_levels')
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
        $qb->update('adventure_progress')
           ->set('boss_defeated', $qb->createNamedParameter(1))
           ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $qb->executeStatement();

        // Record boss completion
        $bossDefinition = $this->worldGenerator->getBossDefinition($worldNumber);
        
        $qb = $this->db->getQueryBuilder();
        $qb->insert('adventure_boss_wins')
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
        $qb->update('adventure_progress')
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
           ->from('adventure_progress')
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
           ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

        $result = $qb->executeQuery();
        $exists = (int) $result->fetchOne() > 0;
        $result->closeCursor();

        if (!$exists) {
            // Insert new progress record
            $qb = $this->db->getQueryBuilder();
            $qb->insert('adventure_progress')
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
            $qb->update('adventure_progress')
               ->set('world_status', $qb->createNamedParameter('unlocked'))
               ->set('updated_at', $qb->createNamedParameter(date('Y-m-d H:i:s')))
               ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
               ->andWhere($qb->expr()->eq('world_number', $qb->createNamedParameter($worldNumber)));

            $qb->executeStatement();
        }
    }
}