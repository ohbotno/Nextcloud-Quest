<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// Template variables are passed from the controller
// Define the main content for achievements
ob_start();
?>

<!-- Achievements Header -->
<div class="page-header">
    <h1 class="page-title">🏆 Achievements</h1>
    <p class="page-subtitle">Track your progress and unlock amazing rewards for completing tasks and reaching milestones.</p>
</div>

<!-- Achievement Statistics -->
<section class="content-section">
    <div class="content-grid cols-4">
        <!-- Overall Progress -->
        <div class="stat-card">
            <div class="stat-card-icon">📊</div>
            <div class="stat-card-label">Overall Progress</div>
            <div class="stat-card-value" id="achievement-percentage">0%</div>
            <div class="stat-card-change" id="achievements-unlocked">0 of 0 unlocked</div>
        </div>
        
        <!-- Recent Achievement -->
        <div class="stat-card">
            <div class="stat-card-icon">🆕</div>
            <div class="stat-card-label">Latest Achievement</div>
            <div class="stat-card-value" id="latest-achievement-name">None yet</div>
            <div class="stat-card-change" id="latest-achievement-date">-</div>
        </div>
        
        <!-- Rare Achievements -->
        <div class="stat-card">
            <div class="stat-card-icon">💎</div>
            <div class="stat-card-label">Rare & Above</div>
            <div class="stat-card-value" id="rare-achievements">0</div>
            <div class="stat-card-change">rare achievements</div>
        </div>
        
        <!-- Achievement Points -->
        <div class="stat-card">
            <div class="stat-card-icon">⭐</div>
            <div class="stat-card-label">Achievement Points</div>
            <div class="stat-card-value" id="achievement-points">0</div>
            <div class="stat-card-change">total points earned</div>
        </div>
    </div>
</section>

<!-- Filter and Search Section -->
<section class="content-section">
    <div class="section-header">
        <h2 class="section-title">Your Achievements</h2>
        <div class="section-controls">
            <div class="view-toggle">
                <button class="btn btn-secondary view-toggle-btn active" data-view="grid">
                    <span class="btn-icon">▦</span>
                    <span class="btn-text">Grid</span>
                </button>
                <button class="btn btn-secondary view-toggle-btn" data-view="list">
                    <span class="btn-icon">☰</span>
                    <span class="btn-text">List</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="search-box">
            <input type="text" class="search-input" id="achievement-search" placeholder="Search achievements...">
            <span class="search-icon">🔍</span>
        </div>
        
        <select class="filter-select" id="category-filter">
            <option value="all">All Categories</option>
            <option value="Task Master">Task Master</option>
            <option value="Streak Keeper">Streak Keeper</option>
            <option value="Level Champion">Level Champion</option>
            <option value="Speed Demon">Speed Demon</option>
            <option value="Consistency Master">Consistency Master</option>
            <option value="Time Master">Time Master</option>
            <option value="Priority Master">Priority Master</option>
            <option value="Special Achievements">Special</option>
        </select>
        
        <select class="filter-select" id="status-filter">
            <option value="all">All Status</option>
            <option value="unlocked">Unlocked</option>
            <option value="completed">Completed</option>
            <option value="in-progress">In Progress</option>
            <option value="locked">Locked</option>
        </select>
        
        <select class="filter-select" id="rarity-filter">
            <option value="all">All Rarities</option>
            <option value="common">Common</option>
            <option value="rare">Rare</option>
            <option value="epic">Epic</option>
            <option value="legendary">Legendary</option>
        </select>
        
        <select class="filter-select" id="sort-by">
            <option value="name">Sort by Name</option>
            <option value="rarity">Sort by Rarity</option>
            <option value="progress">Sort by Progress</option>
            <option value="unlocked">Sort by Date Unlocked</option>
        </select>
    </div>
</section>

<!-- Achievements Layout -->
<section class="content-section achievements-main-section">
    <div class="achievements-layout">
        <!-- Main Content -->
        <main class="achievements-content">
            <!-- Loading State -->
            <div class="content-loading" id="achievements-loading">
                <div class="content-spinner">
                    <div class="spinner"></div>
                    <p class="loading-text">Loading achievements...</p>
                </div>
            </div>
            
            <!-- Achievements Grid -->
            <div class="achievements-grid" id="achievements-grid" style="display: none;">
                <!-- Achievement cards will be populated by JavaScript -->
            </div>
            
            <!-- Achievements List View -->
            <div class="achievements-list" id="achievements-list" style="display: none;">
                <!-- Achievement list items will be populated by JavaScript -->
            </div>
            
            <!-- Empty State -->
            <div class="empty-state" id="achievements-empty" style="display: none;">
                <div class="empty-state-icon">🏆</div>
                <div class="empty-state-title">No achievements found</div>
                <div class="empty-state-text">Try adjusting your filters or complete some tasks to start unlocking achievements!</div>
            </div>
        </main>
    </div>
</section>

<!-- Achievement Categories Overview -->
<section class="content-section">
    <div class="section-header">
        <h2 class="section-title">Achievement Categories</h2>
    </div>
    
    <div class="content-grid cols-3" id="category-overview">
        <!-- Categories will be populated by JavaScript -->
    </div>
</section>

<!-- Achievement Detail Modal -->
<div class="modal-overlay" id="achievement-modal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Achievement Details</h3>
            <button class="btn-close" id="close-achievement-modal">×</button>
        </div>
        <div class="modal-body">
            <div class="achievement-detail-content">
                <div class="achievement-detail-icon">
                    <div class="achievement-icon-large" id="modal-achievement-icon">
                        🏆
                    </div>
                    <div class="achievement-rarity-badge" id="modal-achievement-rarity">
                        Common
                    </div>
                </div>
                
                <div class="achievement-detail-info">
                    <h2 class="achievement-detail-name" id="modal-achievement-name">
                        Achievement Name
                    </h2>
                    
                    <p class="achievement-detail-description" id="modal-achievement-description">
                        Achievement description goes here.
                    </p>
                    
                    <div class="achievement-detail-meta">
                        <div class="meta-item">
                            <span class="meta-label">Category:</span>
                            <span class="meta-value" id="modal-achievement-category">Category</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Points:</span>
                            <span class="meta-value" id="modal-achievement-points">0</span>
                        </div>
                        <div class="meta-item" id="modal-unlock-date-item" style="display: none;">
                            <span class="meta-label">Unlocked:</span>
                            <span class="meta-value" id="modal-unlock-date">-</span>
                        </div>
                    </div>
                    
                    <div class="achievement-detail-progress">
                        <div class="progress-label">
                            <span>Progress</span>
                            <span id="modal-progress-text">0 / 0</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" id="modal-progress-bar" style="width: 0%"></div>
                        </div>
                        <div class="progress-percentage" id="modal-progress-percentage">0%</div>
                    </div>
                    
                    <div class="achievement-status" id="modal-achievement-status">
                        <!-- Status content populated by JS -->
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="close-achievement-modal-btn">Close</button>
            <button class="btn btn-primary" id="share-achievement-btn" style="display: none;">
                <span class="btn-icon">📤</span>
                <span class="btn-text">Share</span>
            </button>
        </div>
    </div>
</div>

<style>
/* Achievement Styles - Updated 2025-01-03 - Fixed card heights */
/* Achievement Layout - Full Width */
.achievements-layout {
    display: block;
    min-height: 600px;
}

/* Main Content Area */
.achievements-content {
    width: 100%;
}

/* Achievements Grid - Desktop-First 4-5 Columns with 240x320px Cards */
.achievements-grid {
    display: grid;
    grid-template-columns: repeat(5, 240px);
    gap: 20px;
    justify-content: space-between;
}

/* Achievement Cards - Flexible height to prevent text cutoff */
.achievement-card {
    width: 240px !important;
    min-height: 320px !important;
    height: auto !important;
    background: var(--color-main-background);
    border-radius: var(--radius-large);
    box-shadow: var(--shadow-md);
    overflow: visible !important;
    cursor: pointer;
    transition: all var(--transition-normal);
    position: relative;
    display: flex;
    flex-direction: column;
}

.achievement-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-xl);
}

.achievement-card.locked {
    opacity: 0.7;
    background: var(--color-background-hover);
}

.achievement-card.unlocked {
    border: 2px solid var(--color-success);
}

.achievement-card.completed {
    border: 2px solid #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.05));
    box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
    animation: completedPulse 2s ease-in-out infinite;
}

@keyframes completedPulse {
    0%, 100% { box-shadow: 0 0 15px rgba(245, 158, 11, 0.2); }
    50% { box-shadow: 0 0 25px rgba(245, 158, 11, 0.4); }
}

/* Rarity Borders */
.achievement-card[data-rarity="common"] {
    border: 2px solid #9e9e9e;
}

.achievement-card[data-rarity="rare"] {
    border: 2px solid #2196f3;
    box-shadow: 0 0 20px rgba(33, 150, 243, 0.3);
}

.achievement-card[data-rarity="epic"] {
    border: 2px solid #9c27b0;
    box-shadow: 0 0 20px rgba(156, 39, 176, 0.3);
}

.achievement-card[data-rarity="legendary"] {
    border: 2px solid #ff9800;
    box-shadow: 0 0 20px rgba(255, 152, 0, 0.3);
    animation: legendaryGlow 3s ease-in-out infinite alternate;
}

@keyframes legendaryGlow {
    from { box-shadow: 0 0 20px rgba(255, 152, 0, 0.3); }
    to { box-shadow: 0 0 30px rgba(255, 152, 0, 0.5); }
}

.achievement-card-header {
    padding: 16px;
    text-align: center;
    flex-shrink: 0;
    position: relative;
}

.achievement-icon {
    width: 80px;
    height: 80px;
    background: var(--color-background-hover);
    border-radius: var(--radius-round);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 36px;
    margin: 0 auto 12px;
    position: relative;
    overflow: hidden;
}

.achievement-icon::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
    transform: rotate(45deg);
    transition: all var(--transition-slow);
    opacity: 0;
}

.achievement-card:hover .achievement-icon::before {
    opacity: 1;
    animation: shine 1s ease-in-out;
}

@keyframes shine {
    0% { transform: translateX(-100%) rotate(45deg); }
    100% { transform: translateX(100%) rotate(45deg); }
}

.achievement-rarity {
    position: absolute;
    top: 8px;
    right: 8px;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.achievement-card-body {
    padding: 0 16px 16px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.achievement-name {
    font-size: var(--font-size-normal);
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 8px;
    text-align: center;
    line-height: 1.3;
    min-height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.achievement-description {
    font-size: var(--font-size-small);
    color: var(--color-text-light);
    line-height: 1.4;
    text-align: center;
    margin-bottom: 12px;
    flex: 1;
}

.achievement-progress {
    margin-top: auto;
}

.progress-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    font-size: var(--font-size-small);
}

.progress-text {
    color: var(--color-text-light);
}

.progress-percentage {
    color: var(--color-primary);
    font-weight: 600;
}

.progress-bar {
    height: 8px;
    background: var(--color-background-dark);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-primary), var(--color-success));
    transition: width var(--transition-slow);
}

.achievement-status {
    margin-top: 8px;
    text-align: center;
    font-size: var(--font-size-small);
    font-weight: 600;
    padding: 6px 12px;
    border-radius: 16px;
}

.achievement-status.unlocked {
    background: var(--color-success);
    color: white;
}

.achievement-status.locked {
    background: var(--color-background-dark);
    color: var(--color-text-light);
}

.achievement-status.in-progress {
    background: var(--color-primary-light);
    color: var(--color-primary);
}

.achievement-status.completed {
    background: #f59e0b;
    color: white;
    font-weight: 700;
    animation: completedTextPulse 2s ease-in-out infinite;
}

@keyframes completedTextPulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.9; transform: scale(1.02); }
}

/* Responsive Grid Breakdowns */
@media (max-width: 1600px) {
    .achievements-grid {
        grid-template-columns: repeat(4, 240px);
    }
}

@media (max-width: 1200px) {
    .achievements-grid {
        grid-template-columns: repeat(3, 240px);
        justify-content: center;
    }
}

@media (max-width: 900px) {
    .achievements-grid {
        grid-template-columns: repeat(2, 240px);
    }
}

@media (max-width: 600px) {
    .achievements-grid {
        grid-template-columns: 1fr;
        justify-content: center;
    }
    
    .achievement-card {
        width: 100%;
        max-width: 300px;
        margin: 0 auto;
    }
}

.achievements-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.achievement-list-item {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: var(--color-main-background);
    border-radius: var(--radius-large);
    box-shadow: var(--shadow-sm);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.achievement-list-item:hover {
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.achievement-list-icon {
    width: 60px;
    height: 60px;
    background: var(--color-background-hover);
    border-radius: var(--radius-round);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.achievement-list-content {
    flex: 1;
}

.achievement-list-name {
    font-size: var(--font-size-large);
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 4px;
}

.achievement-list-description {
    font-size: var(--font-size-small);
    color: var(--color-text-light);
    margin-bottom: 8px;
}

.achievement-list-meta {
    display: flex;
    gap: 16px;
    font-size: var(--font-size-small);
    color: var(--color-text-lighter);
}

.achievement-list-progress {
    text-align: right;
    flex-shrink: 0;
}

.view-toggle {
    display: flex;
    gap: 4px;
}

.view-toggle-btn {
    padding: 8px 12px;
}

.view-toggle-btn.active {
    background: var(--color-primary);
    color: white;
}

/* Achievement Detail Modal */
.achievement-detail-content {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

.achievement-detail-icon {
    text-align: center;
    flex-shrink: 0;
}

.achievement-icon-large {
    width: 120px;
    height: 120px;
    background: var(--color-background-hover);
    border-radius: var(--radius-round);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 60px;
    margin-bottom: 12px;
}

.achievement-rarity-badge {
    padding: 4px 12px;
    border-radius: 16px;
    font-size: var(--font-size-small);
    font-weight: 600;
    text-transform: uppercase;
}

/* Rarity Badge Styles */
.rarity-badge,
.achievement-rarity-badge,
.achievement-rarity {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}

.rarity-badge.common,
.achievement-rarity-badge.common,
.achievement-rarity.common {
    background: #9e9e9e;
    color: white;
}

.rarity-badge.rare,
.achievement-rarity-badge.rare,
.achievement-rarity.rare {
    background: #2196f3;
    color: white;
}

.rarity-badge.epic,
.achievement-rarity-badge.epic,
.achievement-rarity.epic {
    background: #9c27b0;
    color: white;
}

.rarity-badge.legendary,
.achievement-rarity-badge.legendary,
.achievement-rarity.legendary {
    background: linear-gradient(45deg, #ff9800, #ffb74d);
    color: white;
    animation: rarityShimmer 2s ease-in-out infinite alternate;
}

@keyframes rarityShimmer {
    from { filter: brightness(1); }
    to { filter: brightness(1.2); }
}

.achievement-detail-info {
    flex: 1;
}

.achievement-detail-name {
    font-size: var(--font-size-xxlarge);
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 12px;
}

.achievement-detail-description {
    font-size: var(--font-size-normal);
    color: var(--color-text-light);
    line-height: 1.6;
    margin-bottom: 24px;
}

.achievement-detail-meta {
    margin-bottom: 24px;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.meta-item:last-child {
    border-bottom: none;
}

.meta-label {
    font-weight: 500;
    color: var(--color-text-light);
}

.meta-value {
    font-weight: 600;
    color: var(--color-main-text);
}

.achievement-detail-progress {
    margin-bottom: 24px;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: var(--font-size-small);
    color: var(--color-text-light);
}

.progress-bar {
    height: 12px;
    background: var(--color-background-dark);
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: var(--color-primary);
    transition: width var(--transition-slow);
}

.progress-percentage {
    text-align: center;
    font-weight: 600;
    color: var(--color-primary);
}

.achievement-status {
    padding: 12px;
    border-radius: var(--radius-medium);
    text-align: center;
    font-weight: 500;
}

.achievement-status.unlocked {
    background: var(--color-success);
    color: white;
}

.achievement-status.locked {
    background: var(--color-background-hover);
    color: var(--color-text-light);
}

.achievement-status.in-progress {
    background: var(--color-primary-light);
    color: var(--color-primary);
}

.achievement-status.completed {
    background: #f59e0b;
    color: white;
    font-weight: 700;
}

/* Category overview cards */
.category-card {
    background: var(--color-main-background);
    border-radius: var(--radius-large);
    padding: 20px;
    box-shadow: var(--shadow-sm);
    text-align: center;
    transition: all var(--transition-normal);
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.category-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.category-name {
    font-size: var(--font-size-large);
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 8px;
}

.category-progress {
    font-size: var(--font-size-small);
    color: var(--color-text-light);
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .achievements-grid {
        grid-template-columns: 1fr;
    }
    
    .achievement-detail-content {
        flex-direction: column;
        text-align: center;
    }
    
    .view-toggle {
        width: 100%;
    }
    
    .view-toggle-btn {
        flex: 1;
    }
}
</style>

<!-- JavaScript initialization moved to achievements.js to avoid CSP violations -->

<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';