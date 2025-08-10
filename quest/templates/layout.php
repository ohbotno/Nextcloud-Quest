<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// CSS and JS are loaded by the controller using Util::addStyle and Util::addScript
?>

<div id="nextcloud-quest-wrapper" class="quest-wrapper">
        <!-- Fixed Left Sidebar -->
        <aside class="quest-sidebar" id="quest-sidebar">
            <!-- Sidebar Toggle -->
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                <svg class="toggle-icon" width="20" height="20" viewBox="0 0 20 20">
                    <path d="M3 5h14M3 10h14M3 15h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </button>
            
            <!-- Logo Section -->
            <div class="sidebar-header">
                <div class="quest-logo">
                    <img src="<?php echo \OC::$server->getURLGenerator()->imagePath('quest', 'app.svg'); ?>" alt="Quest" class="logo-icon">
                    <span class="logo-text">Quest</span>
                </div>
            </div>
            
            <!-- Character Section -->
            <div class="character-section" id="character-section">
                <div class="character-avatar-container">
                    <div class="character-avatar" id="character-avatar">
                        <!-- Dynamic avatar with level indicator -->
                        <div class="avatar-background"></div>
                        <div class="avatar-content">
                            <div class="avatar-initials" id="avatar-initials">
                                <?php 
                                $displayName = $_['user_displayname'] ?? 'Adventurer';
                                $initials = '';
                                $words = explode(' ', trim($displayName));
                                if (count($words) >= 2) {
                                    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
                                } else {
                                    $initials = strtoupper(substr($displayName, 0, 2));
                                }
                                p($initials);
                                ?>
                            </div>
                        </div>
                        <div class="avatar-border"></div>
                        <div class="avatar-level-badge" id="avatar-level-badge">
                            <span id="avatar-level-number">1</span>
                        </div>
                        <div class="avatar-status-indicator online" id="avatar-status"></div>
                        <div class="avatar-xp-ring" id="avatar-xp-ring">
                            <svg class="xp-progress-ring" width="140" height="140">
                                <circle class="xp-ring-bg" cx="70" cy="70" r="64" stroke="currentColor" stroke-width="3" fill="none" opacity="0.2"/>
                                <circle class="xp-ring-progress" id="xp-ring-progress" cx="70" cy="70" r="64" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="402" stroke-dashoffset="402"/>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <div class="character-name-section">
                    <div class="character-name">
                        <span id="character-name-display"><?php p($_['user_displayname'] ?? 'Adventurer'); ?></span>
                    </div>
                    <div class="character-title" id="character-title">
                        <span id="character-rank">Novice Adventurer</span>
                    </div>
                </div>
                
                <!-- Character Gauges - Condensed Layout -->
                <div class="character-gauges">
                    <!-- Level Gauge -->
                    <div class="character-gauge level-gauge">
                        <div class="gauge-header">
                            <div class="gauge-label">
                                <span class="gauge-icon">⭐</span>
                                <span class="gauge-text">Level</span>
                            </div>
                            <div class="gauge-value" id="character-level">1</div>
                        </div>
                        <div class="level-bar">
                            <div class="level-progress" id="level-progress-bar" style="width: 0%"></div>
                            <div class="level-text" id="level-progress-text">0%</div>
                        </div>
                    </div>
                    
                    <!-- XP Gauge -->
                    <div class="character-gauge xp-gauge">
                        <div class="gauge-header">
                            <div class="gauge-label">
                                <span class="gauge-icon">✨</span>
                                <span class="gauge-text">Experience</span>
                            </div>
                            <div class="gauge-value">
                                <span id="current-xp">0</span> / <span id="next-level-xp">100</span>
                            </div>
                        </div>
                        <div class="xp-bar">
                            <div class="xp-progress" id="xp-progress-bar" style="width: 0%"></div>
                            <div class="xp-text">XP</div>
                        </div>
                    </div>
                    
                    <!-- Health Gauge -->
                    <div class="character-gauge health-gauge">
                        <div class="gauge-header">
                            <div class="gauge-label">
                                <span class="gauge-icon">❤️</span>
                                <span class="gauge-text">Health</span>
                            </div>
                            <div class="gauge-value">
                                <span id="current-health">100</span>%
                            </div>
                        </div>
                        <div class="health-bar">
                            <div class="health-progress" id="health-progress-bar" style="width: 100%"></div>
                            <div class="health-text">HP</div>
                        </div>
                    </div>
                </div>
                
                <!-- Streak Display -->
                <div class="character-streak">
                    <div class="streak-icon">🔥</div>
                    <div class="streak-info">
                        <div class="streak-number" id="streak-days">0</div>
                        <div class="streak-label">Day Streak</div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <nav class="sidebar-nav" id="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('quest.page.index')); ?>" 
                           class="nav-link <?php p($_['active_page'] === 'dashboard' ? 'active' : ''); ?>"
                           data-page="dashboard">
                            <span class="nav-icon">🏠</span>
                            <span class="nav-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('quest.page.quests')); ?>" 
                           class="nav-link <?php p($_['active_page'] === 'quests' ? 'active' : ''); ?>"
                           data-page="quests">
                            <span class="nav-icon">⚔️</span>
                            <span class="nav-text">Quests</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('quest.page.achievements')); ?>" 
                           class="nav-link <?php p($_['active_page'] === 'achievements' ? 'active' : ''); ?>"
                           data-page="achievements">
                            <span class="nav-icon">🏆</span>
                            <span class="nav-text">Achievements</span>
                            <span class="nav-badge" id="achievements-badge" style="display: none;">0</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('quest.page.progress')); ?>" 
                           class="nav-link <?php p($_['active_page'] === 'progress' ? 'active' : ''); ?>"
                           data-page="progress">
                            <span class="nav-icon">📊</span>
                            <span class="nav-text">Progress</span>
                        </a>
                    </li>
                    <li class="nav-item nav-separator"></li>
                    <li class="nav-item">
                        <a href="<?php print_unescaped(\OC::$server->getURLGenerator()->linkToRoute('quest.page.settings')); ?>" 
                           class="nav-link <?php p($_['active_page'] === 'settings' ? 'active' : ''); ?>"
                           data-page="settings">
                            <span class="nav-icon">⚙️</span>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            
            <!-- Footer Info -->
            <div class="sidebar-footer">
                <div class="footer-stats">
                    <div class="stat-item">
                        <span class="stat-label">Today</span>
                        <span class="stat-value" id="tasks-today">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">This Week</span>
                        <span class="stat-value" id="tasks-week">0</span>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content Area -->
        <main class="quest-main" id="quest-main">
            <div class="main-container">
                <?php print_unescaped($_['content']); ?>
            </div>
        </main>
        
        <!-- Overlay for mobile sidebar -->
        <div class="sidebar-overlay" id="sidebar-overlay"></div>
    </div>
    
    <!-- Global Modals -->
    <!-- Task Completion Modal -->
    <div class="quest-modal" id="task-completion-modal" style="display: none;">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Complete a Task</h2>
                <button class="modal-close" id="close-task-modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="task-completion-form">
                    <div class="form-group">
                        <label for="task-title">Task Title</label>
                        <input type="text" id="task-title" name="title" required 
                               placeholder="Enter task description...">
                    </div>
                    <div class="form-group">
                        <label for="task-priority">Priority</label>
                        <select id="task-priority" name="priority">
                            <option value="low">Low Priority</option>
                            <option value="medium" selected>Medium Priority</option>
                            <option value="high">High Priority</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="task-list">Task List</label>
                        <select id="task-list" name="list_id">
                            <!-- Will be populated dynamically -->
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" id="cancel-task-btn">Cancel</button>
                <button class="btn btn-primary" id="complete-task-btn">Complete Task</button>
            </div>
        </div>
    </div>
    
    <!-- Level Up Notification -->
    <div class="notification-popup level-up-notification" id="level-up-notification" style="display: none;">
        <div class="notification-content">
            <div class="notification-icon">🎉</div>
            <h3 class="notification-title">Level Up!</h3>
            <p class="notification-message">
                You've reached <strong>Level <span id="new-level">1</span></strong>!
            </p>
            <p class="notification-subtitle" id="new-rank">Task Novice</p>
        </div>
    </div>
    
    <!-- Achievement Notification -->
    <div class="notification-popup achievement-notification" id="achievement-notification" style="display: none;">
        <div class="notification-content">
            <div class="notification-icon">🏆</div>
            <h3 class="notification-title">Achievement Unlocked!</h3>
            <p class="notification-message" id="achievement-name">Achievement Name</p>
            <p class="notification-subtitle" id="achievement-description">Achievement description</p>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="global-loading" style="display: none;">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p class="loading-text">Loading...</p>
    </div>
</div>