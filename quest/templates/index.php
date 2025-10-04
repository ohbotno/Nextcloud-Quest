<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// Template variables are passed from the controller
// Define the main content for the dashboard
ob_start();
?>

<!-- Dashboard Header -->
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back! Track your progress and complete tasks to level up.</p>
</div>

<!-- Dashboard Stats Cards -->
<section class="content-section">
    <div class="dashboard-stats primary-stats" id="dashboard-stats">
        <!-- Level Card -->
        <div class="stat-card primary" id="level-card">
            <div class="stat-card-icon">⭐</div>
            <div class="stat-card-label">Level</div>
            <div class="stat-card-value" id="stat-level">1</div>
            <div class="stat-card-change" id="stat-level-change">+0 this week</div>
        </div>

        <!-- Total XP Card -->
        <div class="stat-card primary" id="xp-card">
            <div class="stat-card-icon">✨</div>
            <div class="stat-card-label">Total XP</div>
            <div class="stat-card-value" id="stat-total-xp">0</div>
            <div class="stat-card-change" id="stat-xp-change">+0 today</div>
        </div>

        <!-- Current Streak Card -->
        <div class="stat-card primary" id="streak-card">
            <div class="stat-card-icon">🔥</div>
            <div class="stat-card-label">Current Streak</div>
            <div class="stat-card-value" id="stat-streak">0</div>
            <div class="stat-card-change" id="stat-streak-change">0 days</div>
        </div>

        <!-- Tasks Today Card -->
        <div class="stat-card primary" id="tasks-today-card">
            <div class="stat-card-icon">✅</div>
            <div class="stat-card-label">Tasks Today</div>
            <div class="stat-card-value" id="stat-tasks-today">0</div>
            <div class="stat-card-change" id="stat-tasks-today-target">of 5 target</div>
        </div>

        <!-- Weekly Progress Card -->
        <div class="stat-card primary" id="weekly-card">
            <div class="stat-card-icon">📅</div>
            <div class="stat-card-label">This Week</div>
            <div class="stat-card-value" id="stat-weekly-tasks">0</div>
            <div class="stat-card-change" id="stat-weekly-change">tasks completed</div>
        </div>

        <!-- Achievement Points Card -->
        <div class="stat-card primary" id="achievement-card">
            <div class="stat-card-icon">🏆</div>
            <div class="stat-card-label">Achievements</div>
            <div class="stat-card-value" id="stat-achievements">0</div>
            <div class="stat-card-change" id="stat-achievements-total">of 0 total</div>
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
        
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Stats</h3>
                <p class="card-subtitle">Your performance overview</p>
            </div>
            <div class="card-body">
                <div class="stats-list" id="quick-stats-list">
                    <div class="stat-item">
                        <span class="stat-label">Longest Streak</span>
                        <span class="stat-value" id="longest-streak">0 days</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Tasks Completed</span>
                        <span class="stat-value" id="total-completed">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Average per Day</span>
                        <span class="stat-value" id="average-per-day">0.0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Current Rank</span>
                        <span class="stat-value" id="current-rank">Task Novice</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
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
</style>


<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';