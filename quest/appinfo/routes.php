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
        ['name' => 'page#adventure', 'url' => '/adventure', 'verb' => 'GET'],
        ['name' => 'page#quests', 'url' => '/quests', 'verb' => 'GET'],
        ['name' => 'page#achievements', 'url' => '/achievements', 'verb' => 'GET'],
        ['name' => 'page#character', 'url' => '/character', 'verb' => 'GET'],
        ['name' => 'page#settings', 'url' => '/settings', 'verb' => 'GET'],
        
        // API routes for quest functionality
        // Stats endpoints (from QuestStatsController)
        ['name' => 'questStats#getStats', 'url' => '/api/stats', 'verb' => 'GET'],
        ['name' => 'questStats#getUserStats', 'url' => '/api/user/stats', 'verb' => 'GET'],
        ['name' => 'questStats#getUserStats', 'url' => '/api/user-stats', 'verb' => 'GET'],
        // Task list endpoints (using quest controller)
        ['name' => 'quest#getQuestLists', 'url' => '/api/quest-lists', 'verb' => 'GET'],
        // Task completion endpoints (using quest controller)
        ['name' => 'quest#testEndpoint', 'url' => '/api/test-quest', 'verb' => 'GET'],
        ['name' => 'quest#testPost', 'url' => '/api/test-post', 'verb' => 'POST'],
        ['name' => 'quest#completeTaskFromList', 'url' => '/api/complete-quest', 'verb' => 'POST'],
        ['name' => 'quest#testAchievements', 'url' => '/api/achievements/test', 'verb' => 'GET'],
        ['name' => 'quest#triggerAchievementCheck', 'url' => '/api/achievements/trigger-check', 'verb' => 'POST'],
        ['name' => 'quest#getAchievements', 'url' => '/api/achievements', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementsByCategory', 'url' => '/api/achievements/categories', 'verb' => 'GET'],
        ['name' => 'quest#getRecentAchievements', 'url' => '/api/achievements/recent', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementStats', 'url' => '/api/achievements/stats', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementsByRarity', 'url' => '/api/achievements/rarity/{rarity}', 'verb' => 'GET'],
        ['name' => 'quest#getAchievementProgress', 'url' => '/api/achievements/progress/{achievementKey}', 'verb' => 'GET'],
        ['name' => 'quest#completeTask', 'url' => '/api/complete-task', 'verb' => 'POST'],
        ['name' => 'quest#getHistory', 'url' => '/api/history', 'verb' => 'GET'],
        ['name' => 'quest#getLeaderboard', 'url' => '/api/leaderboard', 'verb' => 'GET'],
        
        // Character system API routes
        ['name' => 'character#debugAgeSystem', 'url' => '/api/character/debug-age', 'verb' => 'GET'],
        ['name' => 'character#recalculateAge', 'url' => '/api/character/recalculate-age', 'verb' => 'POST'],
        ['name' => 'character#debugStatus', 'url' => '/api/character/debug', 'verb' => 'GET'],
        ['name' => 'character#getCharacterData', 'url' => '/api/character', 'verb' => 'GET'],
        ['name' => 'character#getCharacterData', 'url' => '/api/character/data', 'verb' => 'GET'],
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

        // New Adventure Grid Map API routes (MUST be before legacy routes for priority)
        ['name' => 'adventure#getMap', 'url' => '/api/adventure-grid/map', 'verb' => 'GET'],
        ['name' => 'adventure#generateArea', 'url' => '/api/adventure-grid/generate', 'verb' => 'POST'],
        ['name' => 'adventure#moveToNode', 'url' => '/api/adventure-grid/move', 'verb' => 'POST'],
        ['name' => 'adventure#getNodeEncounter', 'url' => '/api/adventure-grid/encounter', 'verb' => 'POST'],
        ['name' => 'adventure#completeNode', 'url' => '/api/adventure-grid/complete-node', 'verb' => 'POST'],
        ['name' => 'adventure#completeBoss', 'url' => '/api/adventure-grid/complete-boss-node', 'verb' => 'POST'],
        ['name' => 'adventure#getProgress', 'url' => '/api/adventure-grid/progress', 'verb' => 'GET'],

        // Adventure Path System API routes (Legacy - will be deprecated)
        ['name' => 'adventureWorld#test', 'url' => '/api/adventure/test', 'verb' => 'GET'],
        ['name' => 'adventureWorld#diagnosticPath', 'url' => '/api/adventure/diagnostic-path/{worldNumber}', 'verb' => 'GET'],
        ['name' => 'adventureWorld#debugTaskCompletion', 'url' => '/api/adventure/debug-tasks', 'verb' => 'GET'],
        ['name' => 'adventureWorld#getWorlds', 'url' => '/api/adventure/worlds', 'verb' => 'GET'],
        ['name' => 'adventureWorld#getCurrentPath', 'url' => '/api/adventure/current-path/{worldNumber}', 'verb' => 'GET'],
        ['name' => 'adventureWorld#getInfiniteLevels', 'url' => '/api/adventure/infinite-levels/{worldNumber}', 'verb' => 'GET'],
        ['name' => 'adventureWorld#getLevelObjectivesSimple', 'url' => '/api/adventure/level-objectives', 'verb' => 'GET'],
        ['name' => 'adventureWorld#startLevel', 'url' => '/api/adventure/start-level', 'verb' => 'POST'],
        ['name' => 'adventureWorld#checkLevelCompletion', 'url' => '/api/adventure/check-completion', 'verb' => 'GET'],
        ['name' => 'adventureWorld#completeLevel', 'url' => '/api/adventure/complete-level/{levelId}', 'verb' => 'POST'],
        ['name' => 'adventureWorld#getBossChallenge', 'url' => '/api/adventure/boss-challenge/{worldNumber}', 'verb' => 'GET'],
        ['name' => 'adventureWorld#completeBoss', 'url' => '/api/adventure/complete-boss/{worldNumber}', 'verb' => 'POST'],
        ['name' => 'adventureWorld#getProgress', 'url' => '/api/adventure-old/progress', 'verb' => 'GET'],

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