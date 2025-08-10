<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCA\NextcloudQuest\Service\CharacterService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * Controller for character customization and progression
 */
class CharacterController extends Controller {
    
    /** @var IUserSession */
    private $userSession;
    
    /** @var CharacterService */
    private $characterService;

    public function __construct(
        $appName,
        IRequest $request,
        IUserSession $userSession,
        CharacterService $characterService = null
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->characterService = $characterService;
    }

    /**
     * Get character data for the current user
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getCharacterData() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            if (!$this->characterService) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Character service not available'
                ], 503);
            }

            $userId = $user->getUID();
            $characterData = $this->characterService->getCharacterData($userId);

            return new JSONResponse([
                'status' => 'success',
                'data' => $characterData
            ]);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to get character data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available character items for customization
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAvailableItems() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            if (!$this->characterService) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Character service not available'
                ], 503);
            }

            $userId = $user->getUID();
            $itemsData = $this->characterService->getAvailableItems($userId);

            return new JSONResponse([
                'status' => 'success',
                'data' => $itemsData
            ]);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to get available items: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get character customization interface data
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getCustomizationData() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            if (!$this->characterService) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Character service not available'
                ], 503);
            }

            $userId = $user->getUID();
            $customizationData = $this->characterService->getCustomizationData($userId);

            return new JSONResponse([
                'status' => 'success',
                'data' => $customizationData
            ]);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to get customization data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update character appearance
     *
     * @NoAdminRequired
     * @param string $clothing
     * @param string $weapon
     * @param string $accessory
     * @param string $headgear
     * @return JSONResponse
     */
    public function updateAppearance($clothing = null, $weapon = null, $accessory = null, $headgear = null) {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            if (!$this->characterService) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Character service not available'
                ], 503);
            }

            $userId = $user->getUID();
            
            // Build appearance array from provided parameters
            $appearance = [];
            if ($clothing !== null) $appearance['clothing'] = $clothing;
            if ($weapon !== null) $appearance['weapon'] = $weapon;
            if ($accessory !== null) $appearance['accessory'] = $accessory;
            if ($headgear !== null) $appearance['headgear'] = $headgear;

            if (empty($appearance)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'No appearance data provided'
                ], 400);
            }

            $result = $this->characterService->updateCharacterAppearance($userId, $appearance);

            if ($result['success']) {
                return new JSONResponse([
                    'status' => 'success',
                    'data' => $result
                ]);
            } else {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Failed to update appearance'
                ], 400);
            }

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to update appearance: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Equip a specific item
     *
     * @NoAdminRequired
     * @param string $itemKey
     * @return JSONResponse
     */
    public function equipItem(string $itemKey) {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            if (!$this->characterService) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Character service not available'
                ], 503);
            }

            $userId = $user->getUID();
            
            // First get the item to determine its type
            try {
                $itemMapper = \OC::$server->get(\OCA\NextcloudQuest\Db\CharacterItemMapper::class);
                $item = $itemMapper->findByKey($itemKey);
                $itemType = $item->getItemType();
            } catch (\Exception $e) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Item not found'
                ], 404);
            }
            
            // Update appearance with the item in the correct slot
            $appearance = [$itemType => $itemKey];
            $result = $this->characterService->updateCharacterAppearance($userId, $appearance);

            if ($result['success']) {
                return new JSONResponse([
                    'status' => 'success',
                    'data' => [
                        'item_key' => $itemKey,
                        'item_type' => $itemType,
                        'equipped' => true,
                        'appearance' => $result['appearance']
                    ]
                ]);
            } else {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Failed to equip item'
                ], 400);
            }

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to equip item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Unequip an item from a specific slot
     *
     * @NoAdminRequired
     * @param string $slot
     * @return JSONResponse
     */
    public function unequipItem(string $slot) {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }
            
            if (!$this->characterService) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Character service not available'
                ], 503);
            }

            $validSlots = ['clothing', 'weapon', 'accessory', 'headgear'];
            if (!in_array($slot, $validSlots)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'Invalid equipment slot'
                ], 400);
            }

            $userId = $user->getUID();
            
            // Update appearance to remove the item (set to empty string)
            $appearance = [$slot => ''];
            $result = $this->characterService->updateCharacterAppearance($userId, $appearance);

            if ($result['success']) {
                return new JSONResponse([
                    'status' => 'success',
                    'data' => [
                        'slot' => $slot,
                        'equipped' => false,
                        'appearance' => $result['appearance']
                    ]
                ]);
            } else {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => $result['error'] ?? 'Failed to unequip item'
                ], 400);
            }

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to unequip item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get character ages with progression status
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAges() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }

            $userId = $user->getUID();
            
            // Get age mapper and progression mapper
            $ageMapper = \OC::$server->get(\OCA\NextcloudQuest\Db\CharacterAgeMapper::class);
            $progressionMapper = \OC::$server->get(\OCA\NextcloudQuest\Db\CharacterProgressionMapper::class);
            $questMapper = \OC::$server->get(\OCA\NextcloudQuest\Db\QuestMapper::class);
            
            // Get user's current level
            $quest = $questMapper->findByUserId($userId);
            $userLevel = $quest->getLevel();
            
            // Get all ages
            $allAges = $ageMapper->findAllActive();
            $reachedAges = $progressionMapper->getReachedAgeKeys($userId);
            
            $agesData = [];
            foreach ($allAges as $age) {
                $ageData = $age->jsonSerialize();
                $ageData['is_reached'] = in_array($age->getAgeKey(), $reachedAges);
                $ageData['is_current'] = $age->containsLevel($userLevel);
                $ageData['can_reach'] = $userLevel >= $age->getMinLevel();
                $agesData[] = $ageData;
            }

            return new JSONResponse([
                'status' => 'success',
                'data' => $agesData
            ]);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to get ages: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get character progression statistics
     *
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getProgressionStats() {
        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 401);
            }

            $userId = $user->getUID();
            
            // Get progression mapper and unlock mapper
            $progressionMapper = \OC::$server->get(\OCA\NextcloudQuest\Db\CharacterProgressionMapper::class);
            $unlockMapper = \OC::$server->get(\OCA\NextcloudQuest\Db\CharacterUnlockMapper::class);
            
            $progressionStats = $progressionMapper->getUserProgressionStats($userId);
            $unlockStats = $unlockMapper->getUserUnlockStats($userId);

            return new JSONResponse([
                'status' => 'success',
                'data' => [
                    'progression' => $progressionStats,
                    'unlocks' => $unlockStats
                ]
            ]);

        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => 'Failed to get progression stats: ' . $e->getMessage()
            ], 500);
        }
    }
}