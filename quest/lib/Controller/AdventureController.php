<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCA\NextcloudQuest\Service\AdventureMapService;
use OCA\NextcloudQuest\Service\AdventureThemeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Adventure Controller
 * Handles map generation, node interaction, and area progression
 */
class AdventureController extends Controller {
    private IUserSession $userSession;
    private AdventureMapService $mapService;
    private AdventureThemeService $themeService;
    private LoggerInterface $logger;
    private IDBConnection $db;

    public function __construct(
        string $appName,
        IRequest $request,
        IUserSession $userSession,
        AdventureMapService $mapService,
        AdventureThemeService $themeService,
        LoggerInterface $logger,
        IDBConnection $db
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->mapService = $mapService;
        $this->themeService = $themeService;
        $this->logger = $logger;
        $this->db = $db;
    }

    /**
     * Get current adventure map
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getMap(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            $map = $this->mapService->getCurrentMap($userId);

            if (!$map) {
                return new JSONResponse([
                    'success' => false,
                    'message' => 'No active adventure area. Generate a new one.',
                ]);
            }

            // Add theme colors
            $ageKey = $map['area']['age_key'];
            $themeColors = $this->themeService->getThemeColors($ageKey);

            return new JSONResponse([
                'success' => true,
                'map' => $map,
                'theme' => $themeColors,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting adventure map: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate new adventure area
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @return JSONResponse
     */
    public function generateArea() {
        $this->logger->info('=== ADVENTURE DEBUG: generateArea method called ===');

        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                $this->logger->error('Adventure: User not found in session');
                throw new \Exception('User not found');
            }

            $userId = $user->getUID();
            $this->logger->info("Adventure: generateArea for user $userId");

            // Get player level from request body
            $params = $this->request->getParams();
            $playerLevel = isset($params['playerLevel']) ? (int)$params['playerLevel'] : 1;

            $this->logger->info("Adventure: Generating area for level $playerLevel");

            // Determine age theme based on player level
            $ageKey = $this->themeService->getAgeKeyForLevel($playerLevel);

            // Generate new area
            $area = $this->mapService->generateNewArea($userId, $ageKey);

            // Add theme colors
            $themeColors = $this->themeService->getThemeColors($ageKey);

            return new JSONResponse([
                'success' => true,
                'area' => $area,
                'theme' => $themeColors,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Adventure: Error generating area - ' . $e->getMessage());
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to generate adventure area: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Move to a node
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function moveToNode(string $nodeId): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            $result = $this->mapService->moveToNode($userId, $nodeId);

            return new JSONResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Error moving to node: ' . $e->getMessage());
            return new JSONResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get node encounter (combat, treasure, event)
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getNodeEncounter(string $nodeId, string $nodeType, string $ageKey): JSONResponse {
        try {
            $encounter = [];

            switch ($nodeType) {
                case 'COMBAT':
                    $encounter = [
                        'type' => 'combat',
                        'enemy' => $this->themeService->getRandomEnemy($ageKey),
                    ];
                    break;

                case 'BOSS':
                    $encounter = [
                        'type' => 'boss',
                        'enemy' => $this->themeService->getBoss($ageKey),
                    ];
                    break;

                case 'TREASURE':
                    $encounter = [
                        'type' => 'treasure',
                        'reward' => $this->themeService->getRandomTreasure($ageKey),
                    ];
                    break;

                case 'EVENT':
                    $encounter = [
                        'type' => 'event',
                        'event' => $this->themeService->getRandomEvent($ageKey),
                    ];
                    break;

                case 'SHOP':
                    $encounter = [
                        'type' => 'shop',
                        'message' => 'Welcome to the shop! Browse available equipment.',
                    ];
                    break;

                case 'START':
                    $encounter = [
                        'type' => 'start',
                        'message' => 'Your adventure begins here.',
                    ];
                    break;

                default:
                    $encounter = [
                        'type' => 'unknown',
                        'message' => 'Nothing happens here.',
                    ];
            }

            return new JSONResponse([
                'success' => true,
                'encounter' => $encounter,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting node encounter: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Complete a node
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function completeNode(string $nodeId): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();
            $this->mapService->completeNode($userId, $nodeId);

            // Get updated map to return unlocked nodes
            $map = $this->mapService->getCurrentMap($userId);

            return new JSONResponse([
                'success' => true,
                'message' => 'Node completed successfully',
                'map' => $map,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error completing node: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Complete boss and finish area
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function completeBoss(string $nodeId): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();

            // Complete the boss node
            $this->mapService->completeNode($userId, $nodeId);

            // Mark area as completed
            $map = $this->mapService->getCurrentMap($userId);
            $areaId = (int)$map['area']['id'];

            // Update area completion
            $qb = $this->db->getQueryBuilder();
            $qb->update('ncquest_adventure_areas')
                ->set('is_completed', $qb->createNamedParameter(1, \PDO::PARAM_INT))
                ->set('completed_at', $qb->createNamedParameter(new \DateTime(), \PDO::PARAM_DATE))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($areaId, \PDO::PARAM_INT)));

            $qb->executeStatement();

            // Update progress stats
            $qb = $this->db->getQueryBuilder();
            $qb->update('ncquest_adventure_progress')
                ->set('total_areas_completed', $qb->createFunction('total_areas_completed + 1'))
                ->set('total_bosses_defeated', $qb->createFunction('total_bosses_defeated + 1'))
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

            $qb->executeStatement();

            return new JSONResponse([
                'success' => true,
                'message' => 'Boss defeated! Area completed!',
                'area_completed' => true,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error completing boss: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get adventure progress stats
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getProgress(): JSONResponse {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse(['error' => 'User not authenticated'], 401);
            }

            $userId = $user->getUID();

            // Get progress data
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
                ->from('ncquest_adventure_progress')
                ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

            $result = $qb->executeQuery();
            $progress = $result->fetch();
            $result->closeCursor();

            if (!$progress) {
                return new JSONResponse([
                    'success' => true,
                    'progress' => [
                        'total_areas_completed' => 0,
                        'total_nodes_explored' => 0,
                        'total_bosses_defeated' => 0,
                    ],
                ]);
            }

            return new JSONResponse([
                'success' => true,
                'progress' => $progress,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error getting adventure progress: ' . $e->getMessage());
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }
    }
}
