<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

namespace OCA\NextcloudQuest\Controller;

use OCA\NextcloudQuest\Db\QuestMapper;
use OCA\NextcloudQuest\Db\AchievementMapper;
use OCA\NextcloudQuest\Db\HistoryMapper;
use OCA\NextcloudQuest\Db\CharacterProgressionMapper;
use OCA\NextcloudQuest\Db\CharacterItemMapper;
use OCA\NextcloudQuest\Db\CharacterUnlockMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IConfig;
use OCP\IL10N;

class SettingsController extends Controller {
    private IUserSession $userSession;
    private IConfig $config;
    private QuestMapper $questMapper;
    private AchievementMapper $achievementMapper;
    private HistoryMapper $historyMapper;
    private CharacterProgressionMapper $characterProgressionMapper;
    private CharacterItemMapper $characterItemMapper;
    private CharacterUnlockMapper $characterUnlockMapper;
    private IL10N $l;
    
    public function __construct(
        string $appName,
        IRequest $request,
        IUserSession $userSession,
        IConfig $config,
        QuestMapper $questMapper,
        AchievementMapper $achievementMapper,
        HistoryMapper $historyMapper,
        CharacterProgressionMapper $characterProgressionMapper,
        CharacterItemMapper $characterItemMapper,
        CharacterUnlockMapper $characterUnlockMapper,
        IL10N $l
    ) {
        parent::__construct($appName, $request);
        $this->userSession = $userSession;
        $this->config = $config;
        $this->questMapper = $questMapper;
        $this->achievementMapper = $achievementMapper;
        $this->historyMapper = $historyMapper;
        $this->characterProgressionMapper = $characterProgressionMapper;
        $this->characterItemMapper = $characterItemMapper;
        $this->characterUnlockMapper = $characterUnlockMapper;
        $this->l = $l;
    }
    
    /**
     * Get comprehensive user settings
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function get(): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            // Get theme preference from quest data
            $quest = $this->questMapper->findByUserId($userId);
            $themePreference = $quest->getThemePreference();
        } catch (\Exception $e) {
            $themePreference = 'game';
        }
        
        // Comprehensive settings structure
        $settings = [
            // General settings
            'general' => [
                'show_xp_popup' => $this->config->getUserValue($userId, 'nextcloudquest', 'show_xp_popup', 'true') === 'true',
                'show_streak_counter' => $this->config->getUserValue($userId, 'nextcloudquest', 'show_streak_counter', 'true') === 'true',
                'show_level_progress' => $this->config->getUserValue($userId, 'nextcloudquest', 'show_level_progress', 'true') === 'true',
                'compact_view' => $this->config->getUserValue($userId, 'nextcloudquest', 'compact_view', 'false') === 'true',
                'language' => $this->config->getUserValue($userId, 'nextcloudquest', 'language', 'en'),
                'date_format' => $this->config->getUserValue($userId, 'nextcloudquest', 'date_format', 'auto'),
                'time_format' => $this->config->getUserValue($userId, 'nextcloudquest', 'time_format', 'auto')
            ],
            
            // Theme settings
            'themes' => [
                'theme_preference' => $themePreference,
                'color_scheme' => $this->config->getUserValue($userId, 'nextcloudquest', 'color_scheme', 'auto'),
                'accent_color' => $this->config->getUserValue($userId, 'nextcloudquest', 'accent_color', 'default'),
                'enable_animations' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_animations', 'true') === 'true',
                'enable_particles' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_particles', 'true') === 'true',
                'reduce_motion' => $this->config->getUserValue($userId, 'nextcloudquest', 'reduce_motion', 'false') === 'true'
            ],
            
            // Notification settings
            'notifications' => [
                'notify_achievements' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_achievements', 'true') === 'true',
                'notify_rare_achievements' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_rare_achievements', 'true') === 'true',
                'notify_level_up' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_level_up', 'true') === 'true',
                'notify_streak_milestones' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_streak_milestones', 'true') === 'true',
                'notify_character_unlock' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_character_unlock', 'true') === 'true',
                'notify_streak_reminder' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_streak_reminder', 'true') === 'true',
                'notify_daily_summary' => $this->config->getUserValue($userId, 'nextcloudquest', 'notify_daily_summary', 'false') === 'true',
                'reminder_time' => $this->config->getUserValue($userId, 'nextcloudquest', 'reminder_time', '18:00')
            ],
            
            // Gameplay settings
            'gameplay' => [
                'xp_multiplier' => (float)$this->config->getUserValue($userId, 'nextcloudquest', 'xp_multiplier', '1.0'),
                'difficulty_level' => $this->config->getUserValue($userId, 'nextcloudquest', 'difficulty_level', 'normal'),
                'streak_grace_period' => (int)$this->config->getUserValue($userId, 'nextcloudquest', 'streak_grace_period', '12'),
                'weekend_streak_bonus' => $this->config->getUserValue($userId, 'nextcloudquest', 'weekend_streak_bonus', 'false') === 'true',
                'holiday_streak_protection' => $this->config->getUserValue($userId, 'nextcloudquest', 'holiday_streak_protection', 'false') === 'true',
                'track_time_achievements' => $this->config->getUserValue($userId, 'nextcloudquest', 'track_time_achievements', 'true') === 'true',
                'track_special_dates' => $this->config->getUserValue($userId, 'nextcloudquest', 'track_special_dates', 'true') === 'true',
                'competitive_achievements' => $this->config->getUserValue($userId, 'nextcloudquest', 'competitive_achievements', 'true') === 'true'
            ],
            
            // Character settings
            'character' => [
                'auto_equip_unlocks' => $this->config->getUserValue($userId, 'nextcloudquest', 'auto_equip_unlocks', 'false') === 'true',
                'show_character_in_sidebar' => $this->config->getUserValue($userId, 'nextcloudquest', 'show_character_in_sidebar', 'true') === 'true',
                'age_progression_notifications' => $this->config->getUserValue($userId, 'nextcloudquest', 'age_progression_notifications', 'true') === 'true'
            ],
            
            // Integration settings
            'integration' => [
                'enable_tasks_sync' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_tasks_sync', 'true') === 'true',
                'sync_interval' => $this->config->getUserValue($userId, 'nextcloudquest', 'sync_interval', '5'),
                'bidirectional_sync' => $this->config->getUserValue($userId, 'nextcloudquest', 'bidirectional_sync', 'false') === 'true',
                'enable_calendar_sync' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_calendar_sync', 'false') === 'true',
                'default_calendar' => $this->config->getUserValue($userId, 'nextcloudquest', 'default_calendar', ''),
                'enable_webhooks' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_webhooks', 'false') === 'true',
                'api_rate_limit' => $this->config->getUserValue($userId, 'nextcloudquest', 'api_rate_limit', '30')
            ],
            
            // Privacy settings
            'privacy' => [
                'show_on_leaderboard' => $this->config->getUserValue($userId, 'nextcloudquest', 'show_on_leaderboard', 'true') === 'true',
                'anonymous_leaderboard' => $this->config->getUserValue($userId, 'nextcloudquest', 'anonymous_leaderboard', 'false') === 'true',
                'share_achievements' => $this->config->getUserValue($userId, 'nextcloudquest', 'share_achievements', 'true') === 'true',
                'collect_analytics' => $this->config->getUserValue($userId, 'nextcloudquest', 'collect_analytics', 'true') === 'true',
                'detailed_logging' => $this->config->getUserValue($userId, 'nextcloudquest', 'detailed_logging', 'false') === 'true',
                'data_retention' => $this->config->getUserValue($userId, 'nextcloudquest', 'data_retention', '365'),
                'require_password_confirmation' => $this->config->getUserValue($userId, 'nextcloudquest', 'require_password_confirmation', 'true') === 'true',
                'enable_2fa_backup' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_2fa_backup', 'false') === 'true'
            ],
            
            // Advanced settings
            'advanced' => [
                'cache_duration' => (int)$this->config->getUserValue($userId, 'nextcloudquest', 'cache_duration', '900'),
                'preload_resources' => $this->config->getUserValue($userId, 'nextcloudquest', 'preload_resources', 'true') === 'true',
                'lazy_load_images' => $this->config->getUserValue($userId, 'nextcloudquest', 'lazy_load_images', 'true') === 'true',
                'enable_debug_mode' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_debug_mode', 'false') === 'true',
                'log_api_calls' => $this->config->getUserValue($userId, 'nextcloudquest', 'log_api_calls', 'false') === 'true',
                'log_level' => $this->config->getUserValue($userId, 'nextcloudquest', 'log_level', 'warning'),
                'enable_beta_features' => $this->config->getUserValue($userId, 'nextcloudquest', 'enable_beta_features', 'false') === 'true',
                'ai_suggestions' => $this->config->getUserValue($userId, 'nextcloudquest', 'ai_suggestions', 'false') === 'true',
                'smart_scheduling' => $this->config->getUserValue($userId, 'nextcloudquest', 'smart_scheduling', 'false') === 'true'
            ]
        ];
        
        return new JSONResponse([
            'status' => 'success',
            'data' => $settings
        ]);
    }
    
    /**
     * Update comprehensive user settings
     * 
     * @NoAdminRequired
     * @param array $settings
     * @return JSONResponse
     */
    public function update(array $settings): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            // Validate settings
            $validationErrors = $this->validateSettings($settings);
            if (!empty($validationErrors)) {
                return new JSONResponse([
                    'status' => 'error',
                    'message' => $this->l->t('Invalid settings'),
                    'errors' => $validationErrors
                ], 400);
            }
            
            // Get current settings for audit logging
            $currentSettingsResponse = $this->get();
            $currentSettingsData = json_decode($currentSettingsResponse->render(), true);
            $currentSettings = $currentSettingsData['data'] ?? [];
            
            // Update theme preference in quest data if provided
            if (isset($settings['themes']['theme_preference'])) {
                $quest = $this->questMapper->findByUserId($userId);
                $oldTheme = $quest->getThemePreference();
                $newTheme = $settings['themes']['theme_preference'];
                
                $quest->setThemePreference($newTheme);
                $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
                $this->questMapper->update($quest);
                
                // Log theme change
                $this->logSettingsChange('update', 'themes', 'theme_preference', $oldTheme, $newTheme);
            }
            
            // Process all settings categories
            $settingsCategories = ['general', 'themes', 'notifications', 'gameplay', 'character', 'integration', 'privacy', 'advanced'];
            
            foreach ($settingsCategories as $category) {
                if (isset($settings[$category])) {
                    foreach ($settings[$category] as $key => $value) {
                        // Get old value for audit logging
                        $oldValue = $currentSettings[$category][$key] ?? null;
                        
                        // Skip if value hasn't changed
                        if ($oldValue === $value) {
                            continue;
                        }
                        
                        // Special handling for different data types
                        if (is_bool($value)) {
                            $this->config->setUserValue($userId, 'nextcloudquest', $key, $value ? 'true' : 'false');
                        } elseif (is_numeric($value)) {
                            $this->config->setUserValue($userId, 'nextcloudquest', $key, (string)$value);
                        } else {
                            $this->config->setUserValue($userId, 'nextcloudquest', $key, $value);
                        }
                        
                        // Log setting change
                        $this->logSettingsChange('update', $category, $key, $oldValue, $value);
                    }
                }
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => $this->l->t('Settings updated successfully')
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to update settings: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Export comprehensive user data
     * 
     * @NoAdminRequired
     * @param string $format Export format (json, csv, pdf)
     * @param string $type Type of data to export (all, achievements, progress, character)
     * @return JSONResponse|DataResponse
     */
    public function exportData(string $format = 'json', string $type = 'all'): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $exportData = [
                'export_date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'user_id' => $userId,
                'export_type' => $type,
                'export_format' => $format
            ];
            
            // Export quest progress data
            if ($type === 'all' || $type === 'progress') {
                $quest = $this->questMapper->findByUserId($userId);
                $exportData['quest_data'] = [
                    'current_xp' => $quest->getCurrentXp(),
                    'lifetime_xp' => $quest->getLifetimeXp(),
                    'level' => $quest->getLevel(),
                    'current_streak' => $quest->getCurrentStreak(),
                    'longest_streak' => $quest->getLongestStreak(),
                    'last_completion_date' => $quest->getLastCompletionDate(),
                    'theme_preference' => $quest->getThemePreference(),
                    'created_at' => $quest->getCreatedAt(),
                    'updated_at' => $quest->getUpdatedAt()
                ];
                
                $history = $this->historyMapper->findByUserId($userId, 10000, 0);
                $exportData['history'] = array_map(function($entry) {
                    return [
                        'task_id' => $entry->getTaskId(),
                        'task_title' => $entry->getTaskTitle(),
                        'xp_earned' => $entry->getXpEarned(),
                        'completed_at' => $entry->getCompletedAt()
                    ];
                }, $history);
            }
            
            // Export achievements data
            if ($type === 'all' || $type === 'achievements') {
                $achievements = $this->achievementMapper->findAllByUserId($userId);
                $exportData['achievements'] = array_map(function($achievement) {
                    return [
                        'achievement_key' => $achievement->getAchievementKey(),
                        'unlocked_at' => $achievement->getUnlockedAt()
                    ];
                }, $achievements);
            }
            
            // Export character data
            if ($type === 'all' || $type === 'character') {
                try {
                    $characterProgression = $this->characterProgressionMapper->findByUserId($userId);
                    $exportData['character_progression'] = [
                        'character_name' => $characterProgression->getCharacterName(),
                        'current_age' => $characterProgression->getCurrentAge(),
                        'equipped_clothing' => $characterProgression->getEquippedClothing(),
                        'equipped_weapon' => $characterProgression->getEquippedWeapon(),
                        'equipped_accessory' => $characterProgression->getEquippedAccessory(),
                        'equipped_headgear' => $characterProgression->getEquippedHeadgear(),
                        'unlocked_ages' => $characterProgression->getUnlockedAges(),
                        'created_at' => $characterProgression->getCreatedAt(),
                        'updated_at' => $characterProgression->getUpdatedAt()
                    ];
                } catch (\Exception $e) {
                    $exportData['character_progression'] = null;
                }
                
                $characterUnlocks = $this->characterUnlockMapper->findByUserId($userId);
                $exportData['character_unlocks'] = array_map(function($unlock) {
                    return [
                        'item_key' => $unlock->getItemKey(),
                        'unlocked_at' => $unlock->getUnlockedAt()
                    ];
                }, $characterUnlocks);
            }
            
            // Export settings
            if ($type === 'all') {
                $settingsResponse = $this->get();
                $settingsData = json_decode($settingsResponse->render(), true);
                $exportData['settings'] = $settingsData['data'] ?? [];
            }
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $exportData,
                'filename' => 'nextcloud-quest-export-' . $type . '-' . date('Y-m-d-H-i-s') . '.' . $format
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to export data: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Import user data from backup
     * 
     * @NoAdminRequired
     * @param array $data
     * @param bool $merge
     * @param bool $backup
     * @return JSONResponse
     */
    public function importData(array $data, bool $merge = false, bool $backup = true): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            // Validate import data structure
            if (!isset($data['export_date']) || !isset($data['user_id'])) {
                throw new \InvalidArgumentException($this->l->t('Invalid import data format'));
            }
            
            // Import quest data
            if (isset($data['quest_data'])) {
                $quest = $this->questMapper->findByUserId($userId);
                
                if (!$merge) {
                    // Full replace
                    $quest->setCurrentXp($data['quest_data']['current_xp'] ?? 0);
                    $quest->setLifetimeXp($data['quest_data']['lifetime_xp'] ?? 0);
                    $quest->setLevel($data['quest_data']['level'] ?? 1);
                    $quest->setCurrentStreak($data['quest_data']['current_streak'] ?? 0);
                    $quest->setLongestStreak($data['quest_data']['longest_streak'] ?? 0);
                } else {
                    // Merge mode - only update if imported values are higher
                    $quest->setCurrentXp(max($quest->getCurrentXp(), $data['quest_data']['current_xp'] ?? 0));
                    $quest->setLifetimeXp(max($quest->getLifetimeXp(), $data['quest_data']['lifetime_xp'] ?? 0));
                    $quest->setLevel(max($quest->getLevel(), $data['quest_data']['level'] ?? 1));
                    $quest->setLongestStreak(max($quest->getLongestStreak(), $data['quest_data']['longest_streak'] ?? 0));
                }
                
                $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
                $this->questMapper->update($quest);
            }
            
            // Import settings
            if (isset($data['settings'])) {
                $this->update($data['settings']);
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => $this->l->t('Data imported successfully')
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to import data: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Reset specific data types
     * 
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     * @param string $type Type to reset (progress, achievements, character, all)
     * @param string $confirmationText
     * @return JSONResponse
     */
    public function resetData(string $type, string $confirmationText): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        $requiredConfirmation = 'DELETE MY ' . strtoupper($type);
        if ($confirmationText !== $requiredConfirmation) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Invalid confirmation text')
            ], 400);
        }
        
        try {
            switch ($type) {
                case 'progress':
                    $quest = $this->questMapper->findByUserId($userId);
                    $quest->setCurrentXp(0);
                    $quest->setLifetimeXp(0);
                    $quest->setLevel(1);
                    $quest->setCurrentStreak(0);
                    $quest->setLongestStreak(0);
                    $quest->setLastCompletionDate(null);
                    $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
                    $this->questMapper->update($quest);
                    
                    $history = $this->historyMapper->findByUserId($userId, 10000, 0);
                    foreach ($history as $entry) {
                        $this->historyMapper->delete($entry);
                    }
                    break;
                    
                case 'achievements':
                    $achievements = $this->achievementMapper->findAllByUserId($userId);
                    foreach ($achievements as $achievement) {
                        $this->achievementMapper->delete($achievement);
                    }
                    break;
                    
                case 'character':
                    try {
                        $characterProgression = $this->characterProgressionMapper->findByUserId($userId);
                        $this->characterProgressionMapper->delete($characterProgression);
                    } catch (\Exception $e) {
                        // Character doesn't exist, ignore
                    }
                    
                    $characterUnlocks = $this->characterUnlockMapper->findByUserId($userId);
                    foreach ($characterUnlocks as $unlock) {
                        $this->characterUnlockMapper->delete($unlock);
                    }
                    break;
                    
                case 'all':
                    return $this->resetProgress($confirmationText);
                    
                default:
                    throw new \InvalidArgumentException($this->l->t('Invalid reset type'));
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => $this->l->t('%s data reset successfully', [ucfirst($type)])
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to reset %s: %s', [$type, $e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Reset settings to defaults
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function resetToDefaults(): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            // Get all nextcloudquest config keys for the user
            $keys = $this->config->getUserKeys($userId, 'nextcloudquest');
            
            // Delete all user settings
            foreach ($keys as $key) {
                $this->config->deleteUserValue($userId, 'nextcloudquest', $key);
            }
            
            // Reset theme preference in quest data
            try {
                $quest = $this->questMapper->findByUserId($userId);
                $quest->setThemePreference('game');
                $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
                $this->questMapper->update($quest);
            } catch (\Exception $e) {
                // Quest doesn't exist, ignore
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => $this->l->t('Settings reset to defaults successfully')
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to reset settings: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Reset user progress (dangerous operation)
     * 
     * @NoAdminRequired
     * @PasswordConfirmationRequired
     * @param string $confirmationText
     * @return JSONResponse
     */
    public function resetProgress(string $confirmationText): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        if ($confirmationText !== 'RESET MY PROGRESS') {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Invalid confirmation text')
            ], 400);
        }
        
        try {
            // Reset quest data
            $quest = $this->questMapper->findByUserId($userId);
            $quest->setCurrentXp(0);
            $quest->setLifetimeXp(0);
            $quest->setLevel(1);
            $quest->setCurrentStreak(0);
            $quest->setLongestStreak(0);
            $quest->setLastCompletionDate(null);
            $quest->setUpdatedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $this->questMapper->update($quest);
            
            // Delete achievements
            $achievements = $this->achievementMapper->findAllByUserId($userId);
            foreach ($achievements as $achievement) {
                $this->achievementMapper->delete($achievement);
            }
            
            // Delete history
            $history = $this->historyMapper->findByUserId($userId, 10000, 0);
            foreach ($history as $entry) {
                $this->historyMapper->delete($entry);
            }
            
            return new JSONResponse([
                'status' => 'success',
                'message' => $this->l->t('Progress reset successfully')
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to reset progress: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Get available calendars for integration
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getAvailableCalendars(): JSONResponse {
        try {
            // This would integrate with Nextcloud's calendar app
            // For now, return mock data
            $calendars = [
                ['id' => 'personal', 'name' => 'Personal Calendar', 'color' => '#1976d2'],
                ['id' => 'work', 'name' => 'Work Calendar', 'color' => '#388e3c'],
                ['id' => 'shared', 'name' => 'Shared Calendar', 'color' => '#f57c00']
            ];
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $calendars
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to get calendars: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Create a settings backup before major operations
     * 
     * @NoAdminRequired
     * @param string $backupName
     * @param string $backupType
     * @return JSONResponse
     */
    public function createBackup(string $backupName = '', string $backupType = 'manual'): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            // Export all data
            $exportResponse = $this->exportData('json', 'all');
            $exportData = json_decode($exportResponse->render(), true);
            
            if ($exportData['status'] !== 'success') {
                throw new \Exception('Failed to export data for backup');
            }
            
            // Store backup in database
            $connection = \OC::$server->getDatabaseConnection();
            $backupData = json_encode($exportData['data']);
            
            if (empty($backupName)) {
                $backupName = 'Backup ' . date('Y-m-d H:i:s');
            }
            
            $connection->executeStatement(
                'INSERT INTO `*PREFIX*ncquest_backups` (`user_id`, `backup_name`, `backup_type`, `backup_data`, `data_size`, `created_at`) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $backupName,
                    $backupType,
                    $backupData,
                    strlen($backupData),
                    (new \DateTime())->format('Y-m-d H:i:s')
                ]
            );
            
            return new JSONResponse([
                'status' => 'success',
                'message' => $this->l->t('Backup created successfully'),
                'backup_name' => $backupName
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to create backup: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Get list of available backups for the user
     * 
     * @NoAdminRequired
     * @return JSONResponse
     */
    public function getBackups(): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $connection = \OC::$server->getDatabaseConnection();
            $result = $connection->executeQuery(
                'SELECT `id`, `backup_name`, `backup_type`, `data_size`, `created_at`, `expires_at` FROM `*PREFIX*ncquest_backups` WHERE `user_id` = ? ORDER BY `created_at` DESC',
                [$userId]
            );
            
            $backups = [];
            while ($row = $result->fetch()) {
                $backups[] = [
                    'id' => $row['id'],
                    'name' => $row['backup_name'],
                    'type' => $row['backup_type'],
                    'size' => $row['data_size'],
                    'created_at' => $row['created_at'],
                    'expires_at' => $row['expires_at']
                ];
            }
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $backups
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to get backups: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Restore from a specific backup
     * 
     * @NoAdminRequired
     * @param int $backupId
     * @param bool $merge
     * @return JSONResponse
     */
    public function restoreBackup(int $backupId, bool $merge = false): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $connection = \OC::$server->getDatabaseConnection();
            $result = $connection->executeQuery(
                'SELECT `backup_data` FROM `*PREFIX*ncquest_backups` WHERE `id` = ? AND `user_id` = ?',
                [$backupId, $userId]
            );
            
            $row = $result->fetch();
            if (!$row) {
                throw new \Exception('Backup not found');
            }
            
            $backupData = json_decode($row['backup_data'], true);
            if (!$backupData) {
                throw new \Exception('Invalid backup data');
            }
            
            // Import the backup data
            return $this->importData($backupData, $merge, false);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to restore backup: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Log settings changes for audit purposes
     * 
     * @param string $action
     * @param string $category
     * @param string $key
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    private function logSettingsChange(string $action, string $category = null, string $key = null, $oldValue = null, $newValue = null): void {
        try {
            $userId = $this->userSession->getUser()->getUID();
            $request = \OC::$server->getRequest();
            
            $connection = \OC::$server->getDatabaseConnection();
            $connection->executeStatement(
                'INSERT INTO `*PREFIX*ncquest_audit` (`user_id`, `action`, `setting_category`, `setting_key`, `old_value`, `new_value`, `ip_address`, `user_agent`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId,
                    $action,
                    $category,
                    $key,
                    is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue) : $oldValue,
                    is_array($newValue) || is_object($newValue) ? json_encode($newValue) : $newValue,
                    $request->getRemoteAddress(),
                    substr($request->getHeader('User-Agent'), 0, 255),
                    (new \DateTime())->format('Y-m-d H:i:s')
                ]
            );
        } catch (\Exception $e) {
            // Don't fail the main operation if audit logging fails
        }
    }
    
    /**
     * Get user settings audit log
     * 
     * @NoAdminRequired
     * @param int $limit
     * @param int $offset
     * @return JSONResponse
     */
    public function getAuditLog(int $limit = 50, int $offset = 0): JSONResponse {
        $userId = $this->userSession->getUser()->getUID();
        
        try {
            $connection = \OC::$server->getDatabaseConnection();
            $result = $connection->executeQuery(
                'SELECT `action`, `setting_category`, `setting_key`, `old_value`, `new_value`, `created_at` FROM `*PREFIX*ncquest_audit` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT ? OFFSET ?',
                [$userId, $limit, $offset]
            );
            
            $auditLog = [];
            while ($row = $result->fetch()) {
                $auditLog[] = [
                    'action' => $row['action'],
                    'category' => $row['setting_category'],
                    'key' => $row['setting_key'],
                    'old_value' => $row['old_value'],
                    'new_value' => $row['new_value'],
                    'created_at' => $row['created_at']
                ];
            }
            
            return new JSONResponse([
                'status' => 'success',
                'data' => $auditLog
            ]);
        } catch (\Exception $e) {
            return new JSONResponse([
                'status' => 'error',
                'message' => $this->l->t('Failed to get audit log: %s', [$e->getMessage()])
            ], 500);
        }
    }
    
    /**
     * Validate settings data structure and values
     * 
     * @param array $settings
     * @return array Array of validation errors (empty if valid)
     */
    private function validateSettings(array $settings): array {
        $errors = [];
        
        // Define validation rules
        $validationRules = [
            'gameplay' => [
                'xp_multiplier' => ['type' => 'float', 'min' => 0.1, 'max' => 10.0],
                'difficulty_level' => ['type' => 'string', 'values' => ['easy', 'normal', 'hard', 'expert']],
                'streak_grace_period' => ['type' => 'int', 'min' => 0, 'max' => 48],
            ],
            'privacy' => [
                'data_retention' => ['type' => 'string', 'values' => ['30', '90', '365', 'unlimited']],
            ],
            'advanced' => [
                'cache_duration' => ['type' => 'int', 'min' => 60, 'max' => 86400],
                'log_level' => ['type' => 'string', 'values' => ['error', 'warning', 'info', 'debug']],
            ],
            'integration' => [
                'sync_interval' => ['type' => 'string', 'values' => ['realtime', '5', '15', '60']],
                'api_rate_limit' => ['type' => 'string', 'values' => ['10', '30', '60', 'unlimited']],
            ]
        ];
        
        // Validate each category
        foreach ($validationRules as $category => $rules) {
            if (!isset($settings[$category])) continue;
            
            foreach ($rules as $key => $rule) {
                if (!isset($settings[$category][$key])) continue;
                
                $value = $settings[$category][$key];
                
                // Type validation
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'float':
                            if (!is_numeric($value)) {
                                $errors[] = "$category.$key must be a number";
                                continue 2;
                            }
                            $value = (float)$value;
                            break;
                        case 'int':
                            if (!is_numeric($value) || (int)$value != $value) {
                                $errors[] = "$category.$key must be an integer";
                                continue 2;
                            }
                            $value = (int)$value;
                            break;
                        case 'string':
                            if (!is_string($value)) {
                                $errors[] = "$category.$key must be a string";
                                continue 2;
                            }
                            break;
                    }
                }
                
                // Range validation
                if (isset($rule['min']) && $value < $rule['min']) {
                    $errors[] = "$category.$key must be at least {$rule['min']}";
                }
                if (isset($rule['max']) && $value > $rule['max']) {
                    $errors[] = "$category.$key must be at most {$rule['max']}";
                }
                
                // Value list validation
                if (isset($rule['values']) && !in_array($value, $rule['values'])) {
                    $validValues = implode(', ', $rule['values']);
                    $errors[] = "$category.$key must be one of: $validValues";
                }
            }
        }
        
        return $errors;
    }
}