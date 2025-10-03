<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Service;

use OCA\NextcloudQuest\Db\CharacterAge;
use OCA\NextcloudQuest\Db\CharacterAgeMapper;
use OCA\NextcloudQuest\Db\CharacterItem;
use OCA\NextcloudQuest\Db\CharacterItemMapper;
use OCA\NextcloudQuest\Db\CharacterUnlock;
use OCA\NextcloudQuest\Db\CharacterUnlockMapper;
use OCA\NextcloudQuest\Db\CharacterProgression;
use OCA\NextcloudQuest\Db\CharacterProgressionMapper;
use OCA\NextcloudQuest\Db\Quest;
use OCA\NextcloudQuest\Db\QuestMapper;
use OCP\EventDispatcher\IEventDispatcher;
use OCA\NextcloudQuest\Event\CharacterAgeReachedEvent;
use OCA\NextcloudQuest\Event\CharacterItemUnlockedEvent;
use Psr\Log\LoggerInterface;

/**
 * Service for managing character progression and customization
 */
class CharacterService {
    
    /** @var CharacterAgeMapper */
    private $ageMapper;
    
    /** @var CharacterItemMapper */
    private $itemMapper;
    
    /** @var CharacterUnlockMapper */
    private $unlockMapper;
    
    /** @var CharacterProgressionMapper */
    private $progressionMapper;
    
    /** @var QuestMapper */
    private $questMapper;
    
    /** @var IEventDispatcher */
    private $eventDispatcher;
    
    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        CharacterAgeMapper $ageMapper,
        CharacterItemMapper $itemMapper,
        CharacterUnlockMapper $unlockMapper,
        CharacterProgressionMapper $progressionMapper,
        QuestMapper $questMapper,
        IEventDispatcher $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->ageMapper = $ageMapper;
        $this->itemMapper = $itemMapper;
        $this->unlockMapper = $unlockMapper;
        $this->progressionMapper = $progressionMapper;
        $this->questMapper = $questMapper;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * Get character data for a user
     *
     * @param string $userId
     * @return array
     */
    public function getCharacterData(string $userId): array {
        try {
            // Get user quest data
            $quest = $this->questMapper->findByUserId($userId);
            $userLevel = $quest->getLevel();

            // Get current age
            $currentAge = $this->ageMapper->getAgeForLevel($userLevel);
            if (!$currentAge) {
                // Try default to Stone Age if no age found
                try {
                    $currentAge = $this->ageMapper->findByKey('stone');
                } catch (\Exception $e) {
                    // If stone age doesn't exist, return default data
                    $this->logger->warning('Character ages table is empty, returning default data', [
                        'userId' => $userId,
                        'error' => $e->getMessage()
                    ]);
                    return $this->getDefaultCharacterData();
                }
            }
            
            // Get next age for progression tracking
            $nextAge = $this->ageMapper->getNextAge($userLevel);
            
            // Get equipped items
            $equippedItems = $this->getEquippedItems($quest);
            
            // Get unlocked items count
            $unlockedItems = $this->unlockMapper->getUnlockedItemKeys($userId);
            $totalItems = count($this->itemMapper->findAllActive());
            
            // Get age progression history
            $progressionHistory = $this->progressionMapper->findByUserId($userId);
            
            return [
                'current_age' => [
                    'key' => $currentAge->getAgeKey(),
                    'name' => $currentAge->getAgeName(),
                    'description' => $currentAge->getAgeDescription(),
                    'color' => $currentAge->getAgeColor(),
                    'icon' => $currentAge->getAgeIcon()
                ],
                'next_age' => $nextAge ? [
                    'key' => $nextAge->getAgeKey(),
                    'name' => $nextAge->getAgeName(),
                    'levels_until' => $nextAge->getMinLevel() - $userLevel,
                    'xp_needed' => $this->calculateXpToLevel($userLevel, $nextAge->getMinLevel())
                ] : null,
                'equipped_items' => $equippedItems,
                'customization_stats' => [
                    'unlocked_items' => count($unlockedItems),
                    'total_items' => $totalItems,
                    'unlock_percentage' => $totalItems > 0
                        ? round((count($unlockedItems) / $totalItems) * 100, 1)
                        : 0,
                    'ages_reached' => count($progressionHistory),
                    'total_ages' => count($this->ageMapper->findAllActive())
                ],
                'appearance' => [
                    'clothing' => $quest->getCharacterEquippedClothing(),
                    'weapon' => $quest->getCharacterEquippedWeapon(),
                    'accessory' => $quest->getCharacterEquippedAccessory(),
                    'headgear' => $quest->getCharacterEquippedHeadgear()
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get character data', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            // Return default character data
            return $this->getDefaultCharacterData();
        }
    }

    /**
     * Get available items for a user
     *
     * @param string $userId
     * @return array
     */
    public function getAvailableItems(string $userId): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
            $userLevel = $quest->getLevel();
            
            // Get all active items
            $allItems = $this->itemMapper->findAllActive();
            
            // Get user's unlocked items
            $unlockedItemKeys = $this->unlockMapper->getUnlockedItemKeys($userId);
            
            // Get user's achievements (for achievement-locked items)
            $userAchievements = []; // TODO: Get from achievement service
            
            $availableItems = [];
            $lockedItems = [];
            
            foreach ($allItems as $item) {
                $itemData = $item->jsonSerialize();
                
                // Check if item is unlocked
                if (in_array($item->getItemKey(), $unlockedItemKeys)) {
                    $itemData['is_unlocked'] = true;
                    $itemData['unlock_status'] = 'unlocked';
                    $availableItems[] = $itemData;
                } else {
                    $itemData['is_unlocked'] = false;
                    
                    // Check unlock requirements
                    $canUnlock = true;
                    $reasons = [];
                    
                    // Check level requirement
                    if ($item->getUnlockLevel() && $userLevel < $item->getUnlockLevel()) {
                        $canUnlock = false;
                        $reasons[] = "Requires level {$item->getUnlockLevel()} (current: {$userLevel})";
                    }
                    
                    // Check achievement requirement
                    if ($item->getUnlockAchievement() && !in_array($item->getUnlockAchievement(), $userAchievements)) {
                        $canUnlock = false;
                        $reasons[] = "Requires achievement: {$item->getUnlockAchievement()}";
                    }
                    
                    $itemData['can_unlock'] = $canUnlock;
                    $itemData['unlock_status'] = $canUnlock ? 'available' : 'locked';
                    $itemData['lock_reasons'] = $reasons;
                    
                    $lockedItems[] = $itemData;
                }
            }
            
            // Merge available and locked items for frontend
            $allItemsData = array_merge($availableItems, $lockedItems);

            return [
                'items' => $allItemsData,
                'available_items' => $availableItems,
                'locked_items' => $lockedItems,
                'statistics' => [
                    'total_items' => count($allItems),
                    'unlocked_count' => count($availableItems),
                    'locked_count' => count($lockedItems),
                    'unlock_percentage' => count($allItems) > 0
                        ? round((count($availableItems) / count($allItems)) * 100, 1)
                        : 0
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get available items', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'available_items' => [],
                'locked_items' => [],
                'statistics' => [
                    'total_items' => 0,
                    'unlocked_count' => 0,
                    'locked_count' => 0,
                    'unlock_percentage' => 0
                ]
            ];
        }
    }

    /**
     * Update character appearance
     *
     * @param string $userId
     * @param array $appearance
     * @return array
     */
    public function updateCharacterAppearance(string $userId, array $appearance): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);

            $this->logger->info('Quest entity loaded', [
                'userId' => $userId,
                'questId' => $quest->getId(),
                'hasId' => $quest->getId() !== null
            ]);

            // Validate that user has unlocked the items
            $unlockedItems = $this->unlockMapper->getUnlockedItemKeys($userId);

            // Update each equipment slot if provided and unlocked
            $updated = [];

            if (isset($appearance['clothing'])) {
                if (empty($appearance['clothing']) || in_array($appearance['clothing'], $unlockedItems)) {
                    $quest->setCharacterEquippedClothing($appearance['clothing']);
                    $updated[] = 'clothing';
                }
            }

            if (isset($appearance['weapon'])) {
                if (empty($appearance['weapon']) || in_array($appearance['weapon'], $unlockedItems)) {
                    $quest->setCharacterEquippedWeapon($appearance['weapon']);
                    $updated[] = 'weapon';
                }
            }

            if (isset($appearance['accessory'])) {
                if (empty($appearance['accessory']) || in_array($appearance['accessory'], $unlockedItems)) {
                    $quest->setCharacterEquippedAccessory($appearance['accessory']);
                    $updated[] = 'accessory';
                }
            }

            if (isset($appearance['headgear'])) {
                if (empty($appearance['headgear']) || in_array($appearance['headgear'], $unlockedItems)) {
                    $quest->setCharacterEquippedHeadgear($appearance['headgear']);
                    $updated[] = 'headgear';
                }
            }

            // Save changes using custom method (table uses user_id as primary key, not id)
            $this->questMapper->updateEquipment($quest);
            
            $this->logger->info('Character appearance updated', [
                'userId' => $userId,
                'updated_slots' => $updated
            ]);
            
            return [
                'success' => true,
                'updated_slots' => $updated,
                'appearance' => [
                    'clothing' => $quest->getCharacterEquippedClothing(),
                    'weapon' => $quest->getCharacterEquippedWeapon(),
                    'accessory' => $quest->getCharacterEquippedAccessory(),
                    'headgear' => $quest->getCharacterEquippedHeadgear()
                ]
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to update character appearance', [
                'userId' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check and unlock items based on user level
     *
     * @param string $userId
     * @param int $newLevel
     * @return array
     */
    public function checkLevelUnlocks(string $userId, int $newLevel): array {
        try {
            // Get items unlockable at this level
            $unlockableItems = $this->itemMapper->findUnlockableAtLevel($newLevel);
            
            if (empty($unlockableItems)) {
                return [];
            }
            
            $newUnlocks = [];
            $itemKeys = [];
            
            foreach ($unlockableItems as $item) {
                $itemKeys[] = $item->getItemKey();
            }
            
            // Bulk unlock items
            $unlockedCount = $this->unlockMapper->bulkUnlock(
                $userId,
                $itemKeys,
                CharacterUnlock::METHOD_LEVEL,
                "Level {$newLevel} reached"
            );
            
            if ($unlockedCount > 0) {
                // Get the newly unlocked items for return
                foreach ($unlockableItems as $item) {
                    if ($this->unlockMapper->hasUnlocked($userId, $item->getItemKey())) {
                        $newUnlocks[] = $item->jsonSerialize();
                        
                        // Dispatch unlock event
                        $event = new CharacterItemUnlockedEvent($userId, $item);
                        $this->eventDispatcher->dispatch(CharacterItemUnlockedEvent::class, $event);
                    }
                }
                
                $this->logger->info('Character items unlocked at level', [
                    'userId' => $userId,
                    'level' => $newLevel,
                    'unlocked_count' => $unlockedCount
                ]);
            }
            
            return $newUnlocks;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to check level unlocks', [
                'userId' => $userId,
                'level' => $newLevel,
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Check and record age progression
     *
     * @param string $userId
     * @param int $newLevel
     * @param int $totalXp
     * @return CharacterAge|null
     */
    public function checkAgeProgression(string $userId, int $newLevel, int $totalXp): ?CharacterAge {
        try {
            // Get the age for the new level
            $newAge = $this->ageMapper->getAgeForLevel($newLevel);
            if (!$newAge) {
                return null;
            }
            
            // Check if user has already reached this age
            if ($this->progressionMapper->hasReachedAge($userId, $newAge->getAgeKey())) {
                return null;
            }
            
            // Record the age progression
            $progression = $this->progressionMapper->recordAgeProgression(
                $userId,
                $newAge->getAgeKey(),
                $newLevel,
                $totalXp
            );
            
            // Unlock default items for this age
            $defaultItems = $this->itemMapper->findDefaultItemsForAge($newAge->getAgeKey());
            $defaultItemKeys = array_map(function($item) {
                return $item->getItemKey();
            }, $defaultItems);
            
            $this->unlockMapper->bulkUnlock(
                $userId,
                $defaultItemKeys,
                CharacterUnlock::METHOD_LEVEL,
                "Reached {$newAge->getAgeName()}"
            );
            
            // Update user's current age
            $quest = $this->questMapper->findByUserId($userId);
            $quest->setCharacterCurrentAge($newAge->getAgeKey());
            $this->questMapper->update($quest);
            
            // Dispatch age reached event
            $event = new CharacterAgeReachedEvent($userId, $newAge, $progression);
            $this->eventDispatcher->dispatch(CharacterAgeReachedEvent::class, $event);
            
            $this->logger->info('Character age progression recorded', [
                'userId' => $userId,
                'age' => $newAge->getAgeKey(),
                'level' => $newLevel
            ]);
            
            return $newAge;
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to check age progression', [
                'userId' => $userId,
                'level' => $newLevel,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Get character customization interface data
     *
     * @param string $userId
     * @return array
     */
    public function getCustomizationData(string $userId): array {
        try {
            $quest = $this->questMapper->findByUserId($userId);
            $currentAge = $this->ageMapper->getAgeForLevel($quest->getLevel());

            // If no age found, return empty customization data
            if (!$currentAge) {
                $this->logger->warning('No age found for user level, returning empty customization data', [
                    'userId' => $userId,
                    'level' => $quest->getLevel()
                ]);

                return [
                    'level' => $quest->getLevel(),
                    'current_age' => [
                        'key' => 'stone',
                        'name' => 'Stone Age',
                        'color' => '#8b7355',
                        'icon' => '🪨'
                    ],
                    'ages' => [],
                    'items_by_type' => [],
                    'equipped_items' => [],
                    'current_appearance' => [],
                    'customization_stats' => [
                        'unlocked_items' => 0,
                        'total_items' => 0
                    ],
                    'rarities' => [],
                    'recent_unlocks' => []
                ];
            }
            
            // Get all ages with progression status
            $allAges = $this->ageMapper->findAllActive();
            $reachedAges = $this->progressionMapper->getReachedAgeKeys($userId);
            
            $agesData = [];
            foreach ($allAges as $age) {
                $ageData = $age->jsonSerialize();
                $ageData['is_reached'] = in_array($age->getAgeKey(), $reachedAges);
                $ageData['is_current'] = $age->getAgeKey() === $currentAge->getAgeKey();
                
                // Get items for this age
                $ageItems = $this->itemMapper->findByAge($age->getAgeKey());
                $ageData['item_count'] = count($ageItems);
                
                $agesData[] = $ageData;
            }
            
            // Get items grouped by type
            $itemsByType = [];
            foreach (CharacterItem::getValidTypes() as $type) {
                $items = $this->itemMapper->findByType($type);
                $itemsByType[$type] = $this->categorizeItemsForUser($userId, $items);
            }
            
            // Get current equipped items with full data
            $equippedData = $this->getEquippedItems($quest);
            
            return [
                'level' => $quest->getLevel(),
                'current_age' => [
                    'key' => $currentAge->getAgeKey(),
                    'name' => $currentAge->getAgeName(),
                    'color' => $currentAge->getAgeColor(),
                    'icon' => $currentAge->getAgeIcon()
                ],
                'ages' => $agesData,
                'items_by_type' => $itemsByType,
                'equipped_items' => $equippedData,
                'current_appearance' => [
                    'clothing' => $quest->getCharacterEquippedClothing(),
                    'weapon' => $quest->getCharacterEquippedWeapon(),
                    'accessory' => $quest->getCharacterEquippedAccessory(),
                    'headgear' => $quest->getCharacterEquippedHeadgear()
                ],
                'customization_stats' => [
                    'unlocked_items' => count($this->unlockMapper->getUnlockedItemKeys($userId)),
                    'total_items' => count($this->itemMapper->findAllActive())
                ],
                'rarities' => $this->getRarityStats($userId),
                'recent_unlocks' => $this->getRecentUnlocks($userId, 5)
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to get customization data', [
                'userId' => $userId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'ages' => [],
                'items_by_type' => [],
                'equipped_items' => [],
                'current_appearance' => [],
                'rarities' => [],
                'recent_unlocks' => []
            ];
        }
    }

    /**
     * Get equipped items with full data
     *
     * @param Quest $quest
     * @return array
     */
    private function getEquippedItems(Quest $quest): array {
        $equipped = [];
        
        $slots = [
            'clothing' => $quest->getCharacterEquippedClothing(),
            'weapon' => $quest->getCharacterEquippedWeapon(),
            'accessory' => $quest->getCharacterEquippedAccessory(),
            'headgear' => $quest->getCharacterEquippedHeadgear()
        ];
        
        foreach ($slots as $type => $itemKey) {
            if ($itemKey) {
                try {
                    $item = $this->itemMapper->findByKey($itemKey);
                    $equipped[$type] = $item->jsonSerialize();
                } catch (\Exception $e) {
                    $equipped[$type] = null;
                }
            } else {
                $equipped[$type] = null;
            }
        }
        
        return $equipped;
    }

    /**
     * Categorize items for user (unlocked/locked)
     *
     * @param string $userId
     * @param array $items
     * @return array
     */
    private function categorizeItemsForUser(string $userId, array $items): array {
        $unlockedKeys = $this->unlockMapper->getUnlockedItemKeys($userId);
        $quest = $this->questMapper->findByUserId($userId);
        $userLevel = $quest->getLevel();
        
        $categorized = [
            'unlocked' => [],
            'available' => [],
            'locked' => []
        ];
        
        foreach ($items as $item) {
            $itemData = $item->jsonSerialize();
            
            if (in_array($item->getItemKey(), $unlockedKeys)) {
                $categorized['unlocked'][] = $itemData;
            } elseif ($item->canUnlockAtLevel($userLevel) && !$item->requiresAchievement()) {
                $categorized['available'][] = $itemData;
            } else {
                $categorized['locked'][] = $itemData;
            }
        }
        
        return $categorized;
    }

    /**
     * Get rarity statistics for user
     *
     * @param string $userId
     * @return array
     */
    private function getRarityStats(string $userId): array {
        $unlockedKeys = $this->unlockMapper->getUnlockedItemKeys($userId);
        $stats = [];
        
        foreach (CharacterItem::getValidRarities() as $rarity) {
            $rarityItems = $this->itemMapper->findByRarity($rarity);
            $unlockedCount = 0;
            
            foreach ($rarityItems as $item) {
                if (in_array($item->getItemKey(), $unlockedKeys)) {
                    $unlockedCount++;
                }
            }
            
            $stats[$rarity] = [
                'total' => count($rarityItems),
                'unlocked' => $unlockedCount,
                'percentage' => count($rarityItems) > 0 
                    ? round(($unlockedCount / count($rarityItems)) * 100, 1) 
                    : 0
            ];
        }
        
        return $stats;
    }

    /**
     * Get recent unlocks for user
     *
     * @param string $userId
     * @param int $limit
     * @return array
     */
    private function getRecentUnlocks(string $userId, int $limit = 5): array {
        $recentUnlocks = $this->unlockMapper->getRecentUnlocks($userId, $limit);
        $unlockData = [];
        
        foreach ($recentUnlocks as $unlock) {
            try {
                $item = $this->itemMapper->findByKey($unlock->getItemKey());
                $data = $unlock->jsonSerialize();
                $data['item'] = $item->jsonSerialize();
                $unlockData[] = $data;
            } catch (\Exception $e) {
                // Skip if item not found
            }
        }
        
        return $unlockData;
    }

    /**
     * Calculate XP needed to reach a target level
     *
     * @param int $currentLevel
     * @param int $targetLevel
     * @return int
     */
    private function calculateXpToLevel(int $currentLevel, int $targetLevel): int {
        $totalXp = 0;
        
        for ($level = $currentLevel; $level < $targetLevel; $level++) {
            // Using the exponential formula: 100 * 1.5^(level-1)
            $xpForLevel = (int)(100 * pow(1.5, $level - 1));
            $totalXp += $xpForLevel;
        }
        
        return $totalXp;
    }

    /**
     * Get default character data for new users
     *
     * @return array
     */
    private function getDefaultCharacterData(): array {
        return [
            'current_age' => [
                'key' => 'stone',
                'name' => 'Stone Age',
                'description' => 'The dawn of civilization. Use basic tools and survive in the wilderness.',
                'color' => '#8b7355',
                'icon' => '🪨'
            ],
            'next_age' => [
                'key' => 'bronze',
                'name' => 'Bronze Age',
                'levels_until' => 5,
                'xp_needed' => 525
            ],
            'equipped_items' => [],
            'customization_stats' => [
                'unlocked_items' => 0,
                'total_items' => 0,
                'unlock_percentage' => 0,
                'ages_reached' => 1,
                'total_ages' => 10
            ],
            'appearance' => [
                'clothing' => null,
                'weapon' => null,
                'accessory' => null,
                'headgear' => null
            ]
        ];
    }
}