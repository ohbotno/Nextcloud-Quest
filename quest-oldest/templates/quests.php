<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// Template variables are passed from the controller
// Define the main content for quests
ob_start();
?>

<!-- Dashboard Header -->
<div class="page-header">
    <h1 class="page-title">⚔️ Quest Management</h1>
    <p class="page-subtitle">Complete tasks, earn XP, and level up your character.</p>
</div>

<div id="nextcloud-quest-app">
    <div class="quest-loading" id="quest-loading">
        <div class="quest-spinner"></div>
        <p><?php p($l->t('Loading your quests...')); ?></p>
    </div>
    
    <!-- Main Quest Interface using Dashboard styling -->
    <div id="quests-main-interface" style="display: none;">
        
        <!-- Quest Management Section -->
        <section class="content-section">
            <div class="dashboard-stats" id="quest-stats">
                <!-- Active Quests Card -->
                <div class="stat-card" id="active-quests-card">
                    <div class="stat-card-icon">⚔️</div>
                    <div class="stat-card-label">Active Quests</div>
                    <div class="stat-card-value" id="active-quests-count">0</div>
                    <div class="stat-card-change" id="active-quests-change">ready to complete</div>
                </div>
                
                <!-- Completed Today Card -->
                <div class="stat-card" id="completed-today-card">
                    <div class="stat-card-icon">✅</div>
                    <div class="stat-card-label">Completed Today</div>
                    <div class="stat-card-value" id="completed-today-count">0</div>
                    <div class="stat-card-change" id="completed-today-change">quests</div>
                </div>
                
                <!-- XP Available Card -->
                <div class="stat-card" id="xp-available-card">
                    <div class="stat-card-icon">💎</div>
                    <div class="stat-card-label">XP Available</div>
                    <div class="stat-card-value" id="total-xp-available">0</div>
                    <div class="stat-card-change" id="xp-available-change">points to earn</div>
                </div>
                
                <!-- Quest Lists Count Card -->
                <div class="stat-card" id="quest-lists-card">
                    <div class="stat-card-icon">📋</div>
                    <div class="stat-card-label">Quest Lists</div>
                    <div class="stat-card-value" id="quest-lists-count">0</div>
                    <div class="stat-card-change" id="quest-lists-change">collections</div>
                </div>
            </div>
        </section>
        
        <!-- Quest Lists Section - Using Dashboard Task Lists Layout -->
        <section class="content-section task-lists-section">
            <div class="section-header">
                <h2 class="section-title">My Quest Lists</h2>
                <div class="section-controls">
                    <button class="btn btn-secondary" id="toggle-quest-view-btn">
                        <span class="btn-icon">⚔️</span>
                        <span class="btn-text">Quest View</span>
                    </button>
                    <button class="btn btn-primary" id="refresh-quests-btn">
                        <span class="btn-icon">🔄</span>
                        <span class="btn-text">Refresh</span>
                    </button>
                </div>
            </div>
            
            <!-- Filter Bar -->
            <div class="filter-bar">
                <div class="search-box">
                    <input type="text" class="search-input" id="quest-search" placeholder="<?php p($l->t('Search quests...')); ?>">
                    <span class="search-icon">🔍</span>
                </div>
                <select class="filter-select" id="priority-filter">
                    <option value="all"><?php p($l->t('All Priorities')); ?></option>
                    <option value="high"><?php p($l->t('High Priority')); ?></option>
                    <option value="medium"><?php p($l->t('Medium Priority')); ?></option>
                    <option value="low"><?php p($l->t('Low Priority')); ?></option>
                </select>
                <select class="filter-select" id="status-filter">
                    <option value="all"><?php p($l->t('All Status')); ?></option>
                    <option value="pending"><?php p($l->t('Pending')); ?></option>
                    <option value="completed"><?php p($l->t('Completed')); ?></option>
                </select>
                <select class="filter-select" id="sort-by">
                    <option value="priority"><?php p($l->t('Sort by Priority')); ?></option>
                    <option value="due-date"><?php p($l->t('Sort by Due Date')); ?></option>
                    <option value="created"><?php p($l->t('Sort by Created')); ?></option>
                    <option value="alphabetical"><?php p($l->t('Sort Alphabetically')); ?></option>
                </select>
            </div>
            
            <!-- Quest Lists Grid - Using Dashboard Task Lists Grid -->
            <div class="task-lists-grid" id="quest-lists-container">
                <!-- Will be populated dynamically by JavaScript -->
                <div class="task-list-placeholder">
                    <div class="empty-state">
                        <div class="empty-state-icon">⚔️</div>
                        <div class="empty-state-title">No quest lists found</div>
                        <div class="empty-state-text">Connect to Nextcloud Tasks app to see your quest lists here.</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Quest Completion Modal -->
    <div id="quest-completion-modal" class="quest-modal" style="display: none;">
        <div class="quest-modal-backdrop"></div>
        <div class="quest-modal-content">
            <div class="quest-completion-celebration">
                <div class="celebration-icon">🎉</div>
                <h2 class="celebration-title"><?php p($l->t('Quest Completed!')); ?></h2>
                <p class="celebration-subtitle" id="completed-quest-title"></p>
                <div class="xp-reward">
                    <span class="xp-label"><?php p($l->t('XP Earned')); ?></span>
                    <span class="xp-amount" id="xp-earned">+0</span>
                </div>
                <div class="level-up-indicator" id="level-up-indicator" style="display: none;">
                    <div class="level-up-text"><?php p($l->t('Level Up!')); ?></div>
                    <div class="new-level" id="new-level-text"></div>
                </div>
            </div>
            <div class="quest-modal-actions">
                <button class="quest-btn quest-btn-primary" id="close-completion-modal">
                    <?php p($l->t('Continue')); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Quest Details Modal -->
    <div id="quest-details-modal" class="quest-modal" style="display: none;">
        <div class="quest-modal-backdrop"></div>
        <div class="quest-modal-content">
            <div class="quest-modal-header">
                <h2 class="quest-modal-title" id="quest-details-title"></h2>
                <button class="quest-modal-close" id="close-details-modal">×</button>
            </div>
            <div class="quest-modal-body">
                <div class="quest-detail-item">
                    <span class="quest-detail-label"><?php p($l->t('Description')); ?></span>
                    <p class="quest-detail-value" id="quest-details-description"></p>
                </div>
                <div class="quest-detail-item">
                    <span class="quest-detail-label"><?php p($l->t('Priority')); ?></span>
                    <span class="quest-detail-value quest-priority" id="quest-details-priority"></span>
                </div>
                <div class="quest-detail-item">
                    <span class="quest-detail-label"><?php p($l->t('Due Date')); ?></span>
                    <span class="quest-detail-value" id="quest-details-due-date"></span>
                </div>
                <div class="quest-detail-item">
                    <span class="quest-detail-label"><?php p($l->t('XP Reward')); ?></span>
                    <span class="quest-detail-value quest-xp-value" id="quest-details-xp"></span>
                </div>
            </div>
            <div class="quest-modal-actions">
                <button class="quest-btn quest-btn-secondary" id="close-details-modal-btn">
                    <?php p($l->t('Close')); ?>
                </button>
                <button class="quest-btn quest-btn-success" id="complete-quest-from-modal" 
                        style="display: none;">
                    <?php p($l->t('Complete Quest')); ?>
                </button>
            </div>
        </div>
    </div>
</div>


<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';