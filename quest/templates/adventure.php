<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// Template variables are passed from the controller
// Define the main content for the adventure map
ob_start();
?>

<!-- Adventure Map Container -->
<div id="adventure-map-container" class="adventure-grid-container" style="display: block;">
    <!-- Area Progress Header -->
    <div class="area-progress-header">
        <div class="area-info">
            <h3 id="area-name">Loading...</h3>
            <p id="area-description">Initializing adventure...</p>
        </div>
        <div class="area-stats">
            <div class="stat-badge">
                <span class="stat-label">Nodes Explored:</span>
                <span class="stat-value" id="nodes-explored">0/49</span>
            </div>
            <div class="stat-badge">
                <span class="stat-label">Areas Completed:</span>
                <span class="stat-value" id="areas-completed">0</span>
            </div>
        </div>
    </div>

    <!-- Canvas Map -->
    <div class="map-canvas-wrapper">
        <canvas id="adventure-grid-canvas"></canvas>
    </div>

    <!-- Map Legend -->
    <div class="map-legend">
        <div class="legend-item">
            <span class="legend-icon" style="background: #4CAF50;">▶</span>
            <span class="legend-label">Start</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon" style="background: #FF5722;">⚔</span>
            <span class="legend-label">Combat</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon" style="background: #2196F3;">🏪</span>
            <span class="legend-label">Shop</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon" style="background: #FFD700;">💎</span>
            <span class="legend-label">Treasure</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon" style="background: #9C27B0;">?</span>
            <span class="legend-label">Event</span>
        </div>
        <div class="legend-item">
            <span class="legend-icon" style="background: #B71C1C;">👑</span>
            <span class="legend-label">Boss</span>
        </div>
    </div>

    <!-- Controls -->
    <div class="map-controls">
        <button class="btn btn-secondary" id="btn-reset-camera">Reset View</button>
        <button class="btn btn-primary" id="btn-new-area">New Area</button>
    </div>
</div>

<!-- Dashboard Content (fallback when Adventure API fails) -->
<div id="dashboard-content" style="display: none;">
    <!-- Dashboard Header -->
    <div class="page-header">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Welcome back! Track your progress and complete tasks to level up.</p>
    </div>

    <!-- Dashboard Stats Cards -->
    <section class="content-section">
        <div class="dashboard-stats" id="dashboard-stats">
            <!-- Level Card -->
            <div class="stat-card" id="level-card">
                <div class="stat-card-icon">⭐</div>
                <div class="stat-card-label">Level</div>
                <div class="stat-card-value" id="stat-level">1</div>
                <div class="stat-card-change" id="stat-level-change">+0 this week</div>
            </div>
            
            <!-- Total XP Card -->
            <div class="stat-card" id="xp-card">
                <div class="stat-card-icon">✨</div>
                <div class="stat-card-label">Total XP</div>
                <div class="stat-card-value" id="stat-total-xp">0</div>
                <div class="stat-card-change" id="stat-xp-change">+0 today</div>
            </div>
            
            <!-- Current Streak Card -->
            <div class="stat-card" id="streak-card">
                <div class="stat-card-icon">🔥</div>
                <div class="stat-card-label">Current Streak</div>
                <div class="stat-card-value" id="stat-streak">0</div>
                <div class="stat-card-change" id="stat-streak-change">0 days</div>
            </div>
            
            <!-- Tasks Today Card -->
            <div class="stat-card" id="tasks-today-card">
                <div class="stat-card-icon">✅</div>
                <div class="stat-card-label">Tasks Today</div>
                <div class="stat-card-value" id="stat-tasks-today">0</div>
                <div class="stat-card-change" id="stat-tasks-today-target">of 5 target</div>
            </div>
            
            <!-- Weekly Progress Card -->
            <div class="stat-card" id="weekly-card">
                <div class="stat-card-icon">📅</div>
                <div class="stat-card-label">This Week</div>
                <div class="stat-card-value" id="stat-weekly-tasks">0</div>
                <div class="stat-card-change" id="stat-weekly-change">tasks completed</div>
            </div>
            
            <!-- Achievement Points Card -->
            <div class="stat-card" id="achievement-card">
                <div class="stat-card-icon">🏆</div>
                <div class="stat-card-label">Achievements</div>
                <div class="stat-card-value" id="stat-achievements">0</div>
                <div class="stat-card-change" id="stat-achievements-total">of 0 total</div>
            </div>

            <!-- Adventure Progress Card (new) -->
            <div class="stat-card" id="adventure-card">
                <div class="stat-card-icon">🗺️</div>
                <div class="stat-card-label">Adventure</div>
                <div class="stat-card-value" id="stat-worlds-completed">0</div>
                <div class="stat-card-change" id="stat-worlds-total">of 8 worlds</div>
            </div>
        </div>
    </section>

    <!-- Task Lists Section -->
    <section class="content-section task-lists-section">
        <div class="section-header">
            <h2 class="section-title">My Task Lists</h2>
            <div class="section-controls">
                <button class="btn btn-secondary" id="toggle-visibility-btn">
                    <span class="btn-icon">👁️</span>
                    <span class="btn-text">Toggle Lists</span>
                </button>
                <button class="btn btn-primary" id="add-manual-task-btn">
                    <span class="btn-icon">➕</span>
                    <span class="btn-text">Add Task</span>
                </button>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="search-box">
                <input type="text" class="search-input" id="task-search" placeholder="Search tasks...">
                <span class="search-icon">🔍</span>
            </div>
            <select class="filter-select" id="priority-filter">
                <option value="all">All Priorities</option>
                <option value="high">High Priority</option>
                <option value="medium">Medium Priority</option>
                <option value="low">Low Priority</option>
            </select>
            <select class="filter-select" id="status-filter">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        
        <!-- Task Lists Grid -->
        <div class="task-lists-grid" id="task-lists-grid">
            <!-- Will be populated dynamically by JavaScript -->
            <div class="task-list-placeholder">
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <div class="empty-state-title">No task lists found</div>
                    <div class="empty-state-text">Connect to Nextcloud Tasks app to see your lists here.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Activity Section -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">Recent Activity</h2>
        </div>
        
        <div class="content-grid cols-2">
            <!-- Recent Achievements -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Achievements</h3>
                    <p class="card-subtitle">Your latest unlocked achievements</p>
                </div>
                <div class="card-body">
                    <div id="recent-achievements-list">
                        <div class="empty-state">
                            <div class="empty-state-icon">🏆</div>
                            <div class="empty-state-title">No achievements yet</div>
                            <div class="empty-state-text">Complete some tasks to start unlocking achievements!</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Adventure Progress -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Adventure Progress</h3>
                    <p class="card-subtitle">Your world completion status</p>
                </div>
                <div class="card-body">
                    <div class="stats-list" id="adventure-stats-list">
                        <div class="stat-item">
                            <span class="stat-label">Current World</span>
                            <span class="stat-value" id="current-world-stat">World 1</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Levels Completed</span>
                            <span class="stat-value" id="levels-completed-stat">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Bosses Defeated</span>
                            <span class="stat-value" id="bosses-defeated-stat">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Adventure XP</span>
                            <span class="stat-value" id="adventure-xp-stat">0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
/* Adventure Grid Map Styles */
.adventure-grid-container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.area-progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background: var(--color-background-hover);
    border-radius: var(--border-radius-large);
}

.area-info h3 {
    margin: 0 0 5px 0;
    font-size: 24px;
    color: var(--color-main-text);
}

.area-info p {
    margin: 0;
    color: var(--color-text-maxcontrast);
    font-size: 14px;
}

.area-stats {
    display: flex;
    gap: 20px;
}

.stat-badge {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.stat-badge .stat-label {
    font-size: 12px;
    color: var(--color-text-maxcontrast);
    margin-bottom: 5px;
}

.stat-badge .stat-value {
    font-size: 18px;
    font-weight: bold;
    color: var(--color-primary);
}

.map-canvas-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    background: var(--color-background-dark);
    border-radius: var(--border-radius-large);
    padding: 20px;
    margin-bottom: 20px;
    min-height: 600px;
}

#adventure-grid-canvas {
    border: 2px solid var(--color-border);
    border-radius: var(--border-radius);
    background: #1a1a1a;
    cursor: pointer;
}

.map-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 15px;
    background: var(--color-background-hover);
    border-radius: var(--border-radius);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--border-radius);
    font-size: 20px;
    color: white;
    border: 2px solid white;
}

.legend-label {
    font-size: 14px;
    color: var(--color-main-text);
    font-weight: 500;
}

.map-controls {
    display: flex !important;
    justify-content: center;
    gap: 10px;
    margin-top: 20px;
    padding: 20px;
    background: var(--color-main-background);
    border-top: 2px solid var(--color-border);
}

.map-controls .btn {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: var(--border-radius);
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.map-controls .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.map-controls .btn-primary {
    background: var(--color-primary);
    color: white;
}

.map-controls .btn-secondary {
    background: var(--color-background-dark);
    color: var(--color-main-text);
    border: 2px solid var(--color-border);
}

.adventure-generate-prompt {
    text-align: center;
    padding: 60px 20px;
    background: var(--color-background-hover);
    border-radius: var(--border-radius-large);
}

.adventure-generate-prompt h2 {
    margin-bottom: 10px;
    color: var(--color-main-text);
}

.adventure-generate-prompt p {
    margin-bottom: 20px;
    color: var(--color-text-maxcontrast);
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-item .stat-label {
    font-size: var(--font-size-small);
    color: var(--color-text-light);
}

.stat-item .stat-value {
    font-weight: 600;
    color: var(--color-primary);
}

.achievement-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--color-border);
}

.achievement-item:last-child {
    border-bottom: none;
}

.achievement-item .achievement-icon {
    width: 40px;
    height: 40px;
    background: var(--color-background-hover);
    border-radius: var(--radius-round);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.achievement-item .achievement-info {
    flex: 1;
}

.achievement-item .achievement-name {
    font-weight: 500;
    color: var(--color-main-text);
    margin-bottom: 2px;
}

.achievement-item .achievement-date {
    font-size: var(--font-size-small);
    color: var(--color-text-light);
}

.world-info-name {
    font-size: 16px;
    font-weight: bold;
    color: #FFD700;
    margin-bottom: 8px;
}

#adventure-map-container.active + #dashboard-content {
    display: none !important;
}
</style>

<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';