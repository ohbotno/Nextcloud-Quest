<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUserId()
 * @method void setUserId(string $userId)
 * @method int getCurrentXp()
 * @method void setCurrentXp(int $currentXp)
 * @method int getLifetimeXp()
 * @method void setLifetimeXp(int $lifetimeXp)
 * @method int getLevel()
 * @method void setLevel(int $level)
 * @method int getCurrentStreak()
 * @method void setCurrentStreak(int $currentStreak)
 * @method int getLongestStreak()
 * @method void setLongestStreak(int $longestStreak)
 * @method string|null getLastCompletionDate()
 * @method void setLastCompletionDate(?string $lastCompletionDate)
 * @method string getThemePreference()
 * @method void setThemePreference(string $themePreference)
 * @method string getCreatedAt()
 * @method void setCreatedAt(string $createdAt)
 * @method string getUpdatedAt()
 * @method void setUpdatedAt(string $updatedAt)
 * @method string|null getCharacterEquippedClothing()
 * @method void setCharacterEquippedClothing(?string $characterEquippedClothing)
 * @method string|null getCharacterEquippedWeapon()
 * @method void setCharacterEquippedWeapon(?string $characterEquippedWeapon)
 * @method string|null getCharacterEquippedAccessory()
 * @method void setCharacterEquippedAccessory(?string $characterEquippedAccessory)
 * @method string|null getCharacterEquippedHeadgear()
 * @method void setCharacterEquippedHeadgear(?string $characterEquippedHeadgear)
 * @method string|null getCharacterCurrentAge()
 * @method void setCharacterCurrentAge(?string $characterCurrentAge)
 * @method string|null getCharacterBaseSprite()
 * @method void setCharacterBaseSprite(?string $characterBaseSprite)
 * @method string|null getCharacterAppearanceData()
 * @method void setCharacterAppearanceData(?string $characterAppearanceData)
 * @method string|null getCharacterName()
 * @method void setCharacterName(?string $characterName)
 * @method string|null getCharacterAvatarUrl()
 * @method void setCharacterAvatarUrl(?string $characterAvatarUrl)
 * @method int|null getCurrentHealth()
 * @method void setCurrentHealth(?int $currentHealth)
 * @method int|null getMaxHealth()
 * @method void setMaxHealth(?int $maxHealth)
 * @method int|null getDataRetentionDays()
 * @method void setDataRetentionDays(?int $dataRetentionDays)
 * @method string|null getPrivacySettings()
 * @method void setPrivacySettings(?string $privacySettings)
 * @method int|null getSettingsVersion()
 * @method void setSettingsVersion(?int $settingsVersion)
 * @method int|null getTasksCompletedToday()
 * @method void setTasksCompletedToday(?int $tasksCompletedToday)
 * @method int|null getTasksCompletedThisWeek()
 * @method void setTasksCompletedThisWeek(?int $tasksCompletedThisWeek)
 * @method int|null getTotalTasksCompleted()
 * @method void setTotalTasksCompleted(?int $totalTasksCompleted)
 * @method int|null getXpGainedToday()
 * @method void setXpGainedToday(?int $xpGainedToday)
 * @method \DateTime|null getLastTaskCompletionDate()
 * @method void setLastTaskCompletionDate(?\DateTime $lastTaskCompletionDate)
 * @method \DateTime|null getLastDailyReset()
 * @method void setLastDailyReset(?\DateTime $lastDailyReset)
 * @method \DateTime|null getLastWeeklyReset()
 * @method void setLastWeeklyReset(?\DateTime $lastWeeklyReset)
 */
class Quest extends Entity {
    protected string $userId = '';
    protected int $currentXp = 0;
    protected int $lifetimeXp = 0;
    protected int $level = 1;
    protected int $currentStreak = 0;
    protected int $longestStreak = 0;
    protected $lastCompletionDate = null;
    protected string $themePreference = 'game';
    protected $createdAt = '';
    protected $updatedAt = '';
    protected ?int $currentHealth = null;
    protected ?int $maxHealth = null;
    protected ?int $dataRetentionDays = null;
    protected ?string $privacySettings = null;
    protected ?int $settingsVersion = null;
    protected ?int $tasksCompletedToday = null;
    protected ?int $tasksCompletedThisWeek = null;
    protected ?int $totalTasksCompleted = null;
    protected ?int $xpGainedToday = null;
    protected $lastTaskCompletionDate = null;
    protected $lastDailyReset = null;
    protected $lastWeeklyReset = null;

    // Character appearance fields
    protected ?string $characterEquippedClothing = null;
    protected ?string $characterEquippedWeapon = null;
    protected ?string $characterEquippedAccessory = null;
    protected ?string $characterEquippedHeadgear = null;
    protected ?string $characterCurrentAge = 'stone';
    protected ?string $characterBaseSprite = 'default';
    protected ?string $characterAppearanceData = null;
    protected ?string $characterName = null;
    protected ?string $characterAvatarUrl = null;
    
    public function __construct() {
        $this->addType('userId', 'string');
        $this->addType('currentXp', 'integer');
        $this->addType('lifetimeXp', 'integer');
        $this->addType('level', 'integer');
        $this->addType('currentStreak', 'integer');
        $this->addType('longestStreak', 'integer');
        $this->addType('lastCompletionDate', 'datetime');
        $this->addType('themePreference', 'string');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
        $this->addType('characterEquippedClothing', 'string');
        $this->addType('characterEquippedWeapon', 'string');
        $this->addType('characterEquippedAccessory', 'string');
        $this->addType('characterEquippedHeadgear', 'string');
        $this->addType('characterCurrentAge', 'string');
        $this->addType('characterBaseSprite', 'string');
        $this->addType('characterAppearanceData', 'string');
        $this->addType('characterName', 'string');
        $this->addType('characterAvatarUrl', 'string');
        $this->addType('currentHealth', 'integer');
        $this->addType('maxHealth', 'integer');
        $this->addType('dataRetentionDays', 'integer');
        $this->addType('privacySettings', 'string');
        $this->addType('settingsVersion', 'integer');
        $this->addType('tasksCompletedToday', 'integer');
        $this->addType('tasksCompletedThisWeek', 'integer');
        $this->addType('totalTasksCompleted', 'integer');
        $this->addType('xpGainedToday', 'integer');
        $this->addType('lastTaskCompletionDate', 'datetime');
        $this->addType('lastDailyReset', 'datetime');
        $this->addType('lastWeeklyReset', 'datetime');
    }

    /**
     * Get character appearance data as array
     *
     * @return array
     */
    public function getCharacterAppearanceArray(): array {
        if (empty($this->characterAppearanceData)) {
            return [
                'scars' => [],
                'badges' => [],
                'aging_effects' => [],
                'technology_markers' => []
            ];
        }

        $data = json_decode($this->characterAppearanceData, true);
        return is_array($data) ? $data : [
            'scars' => [],
            'badges' => [],
            'aging_effects' => [],
            'technology_markers' => []
        ];
    }

    /**
     * Set character appearance data from array
     *
     * @param array $data
     */
    public function setCharacterAppearanceArray(array $data): void {
        $this->setCharacterAppearanceData(json_encode($data));
    }

    /**
     * Add a badge to character appearance
     *
     * @param string $badgeKey
     */
    public function addCharacterBadge(string $badgeKey): void {
        $appearance = $this->getCharacterAppearanceArray();
        if (!in_array($badgeKey, $appearance['badges'])) {
            $appearance['badges'][] = $badgeKey;
            $this->setCharacterAppearanceArray($appearance);
        }
    }

    /**
     * Add a scar to character appearance
     *
     * @param string $scarKey
     */
    public function addCharacterScar(string $scarKey): void {
        $appearance = $this->getCharacterAppearanceArray();
        if (!in_array($scarKey, $appearance['scars'])) {
            $appearance['scars'][] = $scarKey;
            $this->setCharacterAppearanceArray($appearance);
        }
    }

    /**
     * Add aging effect to character appearance
     *
     * @param string $effectKey
     */
    public function addAgingEffect(string $effectKey): void {
        $appearance = $this->getCharacterAppearanceArray();
        if (!in_array($effectKey, $appearance['aging_effects'])) {
            $appearance['aging_effects'][] = $effectKey;
            $this->setCharacterAppearanceArray($appearance);
        }
    }

    /**
     * Add technology marker to character appearance
     *
     * @param string $markerKey
     */
    public function addTechnologyMarker(string $markerKey): void {
        $appearance = $this->getCharacterAppearanceArray();
        if (!in_array($markerKey, $appearance['technology_markers'])) {
            $appearance['technology_markers'][] = $markerKey;
            $this->setCharacterAppearanceArray($appearance);
        }
    }
}