<?php
/**
 * @copyright Copyright (c) 2025 Nextcloud Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

return [
    'routes' => [
        // Page routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'page#quests', 'url' => '/quests', 'verb' => 'GET'],
        ['name' => 'page#achievements', 'url' => '/achievements', 'verb' => 'GET'],
        ['name' => 'page#progress', 'url' => '/progress', 'verb' => 'GET'],
        ['name' => 'page#settings', 'url' => '/settings', 'verb' => 'GET'],
        
        // Test routes (simplified)
        ['name' => 'test#minimal', 'url' => '/api/minimal', 'verb' => 'GET'],
        ['name' => 'simpleQuest#test', 'url' => '/api/test', 'verb' => 'GET'],
        ['name' => 'simpleQuest#debugDB', 'url' => '/api/debug-db', 'verb' => 'GET'],
        
        // API routes for quest functionality
        ['name' => 'simpleQuest#getUserStats', 'url' => '/api/user/stats', 'verb' => 'GET'],
        ['name' => 'simpleQuest#getUserStats', 'url' => '/api/user-stats', 'verb' => 'GET'],
        ['name' => 'simpleQuest#getQuestLists', 'url' => '/api/quest-lists', 'verb' => 'GET'],
        ['name' => 'simpleQuest#completeTaskFromList', 'url' => '/api/complete-quest', 'verb' => 'POST'],
        ['name' => 'quest#getAchievements', 'url' => '/api/achievements', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementsByCategory', 'url' => '/api/achievements/categories', 'verb' => 'GET'],
        ['name' => 'quest#getRecentAchievements', 'url' => '/api/achievements/recent', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementStats', 'url' => '/api/achievements/stats', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementsByRarity', 'url' => '/api/achievements/rarity/{rarity}', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementProgress', 'url' => '/api/achievements/progress/{achievementKey}', 'verb' => 'GET'],
        ['name' => 'quest#completeTask', 'url' => '/api/complete-task', 'verb' => 'POST'],
        ['name' => 'quest#completeTaskFromList', 'url' => '/api/complete-task-from-list', 'verb' => 'POST'],
        ['name' => 'quest#getHistory', 'url' => '/api/history', 'verb' => 'GET'],
        ['name' => 'quest#getLeaderboard', 'url' => '/api/leaderboard', 'verb' => 'GET'],
        
        // Character system API routes
        ['name' => 'character#getCharacterData', 'url' => '/api/character', 'verb' => 'GET'],
        ['name' => 'character#getAvailableItems', 'url' => '/api/character/items', 'verb' => 'GET'],
        ['name' => 'character#getCustomizationData', 'url' => '/api/character/customization', 'verb' => 'GET'],
        ['name' => 'character#updateAppearance', 'url' => '/api/character/appearance', 'verb' => 'PUT'],
        ['name' => 'character#equipItem', 'url' => '/api/character/equip/{itemKey}', 'verb' => 'POST'],
        ['name' => 'character#unequipItem', 'url' => '/api/character/unequip/{slot}', 'verb' => 'DELETE'],
        ['name' => 'character#getAges', 'url' => '/api/character/ages', 'verb' => 'GET'],
        ['name' => 'character#getProgressionStats', 'url' => '/api/character/progression', 'verb' => 'GET'],
        
        // Progress analytics API routes
        ['name' => 'progressAnalytics#getProgressOverview', 'url' => '/api/progress/overview', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getXPAnalytics', 'url' => '/api/progress/xp-analytics', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getStreakAnalytics', 'url' => '/api/progress/streak-analytics', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getActivityHeatmap', 'url' => '/api/progress/activity-heatmap', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getTaskCompletionTrends', 'url' => '/api/progress/completion-trends', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getProductivityInsights', 'url' => '/api/progress/productivity-insights', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getLevelProgressionData', 'url' => '/api/progress/level-progression', 'verb' => 'GET'],
        ['name' => 'progressAnalytics#getCharacterTimelineData', 'url' => '/api/progress/character-timeline', 'verb' => 'GET'],
        
        // Adventure Path System API routes
        ['name' => 'adventureWorld#getWorlds', 'url' => '/api/adventure/worlds', 'verb' => 'GET'],
        ['name' => 'adventureWorld#getCurrentPath', 'url' => '/api/adventure/current-path/{worldNumber}', 'verb' => 'GET'],
        ['name' => 'adventureWorld#completeLevel', 'url' => '/api/adventure/complete-level/{levelId}', 'verb' => 'POST'],
        ['name' => 'adventureWorld#getBossChallenge', 'url' => '/api/adventure/boss-challenge/{worldNumber}', 'verb' => 'GET'],
        ['name' => 'adventureWorld#completeBoss', 'url' => '/api/adventure/complete-boss/{worldNumber}', 'verb' => 'POST'],
        ['name' => 'adventureWorld#getProgress', 'url' => '/api/adventure/progress', 'verb' => 'GET'],

        // Settings routes
        ['name' => 'settings#get', 'url' => '/api/settings', 'verb' => 'GET'],
        ['name' => 'settings#update', 'url' => '/api/settings', 'verb' => 'PUT'],
        ['name' => 'settings#exportData', 'url' => '/api/settings/export', 'verb' => 'POST'],
        ['name' => 'settings#importData', 'url' => '/api/settings/import', 'verb' => 'POST'],
        ['name' => 'settings#resetData', 'url' => '/api/settings/reset-data', 'verb' => 'POST'],
        ['name' => 'settings#resetToDefaults', 'url' => '/api/settings/reset', 'verb' => 'POST'],
        ['name' => 'settings#resetProgress', 'url' => '/api/settings/reset-progress', 'verb' => 'POST'],
        ['name' => 'settings#getAvailableCalendars', 'url' => '/api/settings/calendars', 'verb' => 'GET'],
        ['name' => 'settings#createBackup', 'url' => '/api/settings/backup', 'verb' => 'POST'],
        ['name' => 'settings#getBackups', 'url' => '/api/settings/backups', 'verb' => 'GET'],
        ['name' => 'settings#restoreBackup', 'url' => '/api/settings/backup/{backupId}/restore', 'verb' => 'POST'],
        ['name' => 'settings#getAuditLog', 'url' => '/api/settings/audit', 'verb' => 'GET'],
    ]
];