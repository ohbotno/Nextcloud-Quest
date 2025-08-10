<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

style('quest', 'settings');
script('quest', 'settings');
?>

<div class="section" id="nextcloud-quest-settings">
    <h2><?php p($l->t('Quest')); ?></h2>
    <p class="settings-hint"><?php p($l->t('Configure your gamification preferences')); ?></p>
    
    <div class="quest-settings-form">
        <div class="quest-setting-item">
            <label for="quest-notifications">
                <input type="checkbox" 
                       id="quest-notifications" 
                       name="notifications_enabled" 
                       <?php if ($_['notifications_enabled'] === 'yes'): ?>checked<?php endif; ?>>
                <?php p($l->t('Enable notifications for achievements and level ups')); ?>
            </label>
        </div>
        
        <div class="quest-setting-item">
            <label for="quest-daily-goal"><?php p($l->t('Daily task completion goal')); ?></label>
            <select id="quest-daily-goal" name="daily_goal">
                <option value="1" <?php if ($_['daily_goal'] === '1'): ?>selected<?php endif; ?>>1 task</option>
                <option value="3" <?php if ($_['daily_goal'] === '3'): ?>selected<?php endif; ?>>3 tasks</option>
                <option value="5" <?php if ($_['daily_goal'] === '5'): ?>selected<?php endif; ?>>5 tasks</option>
                <option value="10" <?php if ($_['daily_goal'] === '10'): ?>selected<?php endif; ?>>10 tasks</option>
            </select>
        </div>
        
        <div class="quest-setting-item">
            <label for="quest-theme"><?php p($l->t('Theme preference')); ?></label>
            <select id="quest-theme" name="theme_preference">
                <option value="auto" <?php if ($_['theme_preference'] === 'auto'): ?>selected<?php endif; ?>><?php p($l->t('Auto (follow system)')); ?></option>
                <option value="light" <?php if ($_['theme_preference'] === 'light'): ?>selected<?php endif; ?>><?php p($l->t('Light theme')); ?></option>
                <option value="dark" <?php if ($_['theme_preference'] === 'dark'): ?>selected<?php endif; ?>><?php p($l->t('Dark theme')); ?></option>
            </select>
        </div>
        
        <button id="quest-save-settings" class="button primary"><?php p($l->t('Save settings')); ?></button>
    </div>
</div>