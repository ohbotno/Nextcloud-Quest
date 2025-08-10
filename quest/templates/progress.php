<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// Template variables are passed from the controller
// Define the main content for the progress page
ob_start();
?>

<!-- Dashboard Header -->
<div class="page-header">
    <h1 class="page-title">📊 Progress Dashboard</h1>
    <p class="page-subtitle">Track your journey through time and productivity achievements.</p>
</div>

<div class="quest-loading" id="progress-loading">
    <div class="quest-spinner"></div>
    <p>Loading your progress data...</p>
</div>

<!-- Main Progress Interface using Dashboard styling -->
<div id="progress-main-interface" style="display: none;">
        
        <!-- Progress Stats Cards - Using Dashboard Stats Layout -->
        <section class="content-section">
            <div class="dashboard-stats" id="progress-stats">
                <!-- Current Level Card -->
                <div class="stat-card" id="current-level-card">
                    <div class="stat-card-icon">⭐</div>
                    <div class="stat-card-label">Current Level</div>
                    <div class="stat-card-value" id="current-level-display">1</div>
                    <div class="stat-card-change" id="current-rank-display">Task Novice</div>
                </div>
                
                <!-- Total XP Card -->
                <div class="stat-card" id="total-xp-card">
                    <div class="stat-card-icon">✨</div>
                    <div class="stat-card-label">Total XP</div>
                    <div class="stat-card-value" id="total-xp-display">0</div>
                    <div class="stat-card-change" id="xp-to-next-display">0 to next level</div>
                </div>
                
                <!-- Current Streak Card -->
                <div class="stat-card" id="current-streak-card">
                    <div class="stat-card-icon">🔥</div>
                    <div class="stat-card-label">Current Streak</div>
                    <div class="stat-card-value" id="current-streak-display">0</div>
                    <div class="stat-card-change" id="longest-streak-display">Best: 0 days</div>
                </div>
                
                <!-- Tasks Completed Card -->
                <div class="stat-card" id="tasks-completed-card">
                    <div class="stat-card-icon">✅</div>
                    <div class="stat-card-label">Tasks Completed</div>
                    <div class="stat-card-value" id="total-tasks-display">0</div>
                    <div class="stat-card-change" id="tasks-this-week-display">0 this week</div>
                </div>
            </div>
        </section>

        <!-- Filter Bar (matching dashboard pattern) -->
        <div class="filter-bar">
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="time-range-select">📅 Time Range:</label>
                    <select id="time-range-select" class="filter-select">
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="all">All Time</option>
                    </select>
                </div>
                <button class="btn btn-secondary" id="export-progress">
                    📥 Export Data
                </button>
            </div>
        </div>

        <!-- Progress Analysis Section -->
        <section class="content-section task-lists-section">
            <div class="section-header">
                <h2 class="section-title">Progress Analysis</h2>
                <div class="section-controls">
                    <button class="btn btn-secondary" id="view-analytics-btn">
                        <span class="btn-icon">📊</span>
                        <span class="btn-text">Analytics</span>
                    </button>
                </div>
            </div>
            
            <!-- Progress Lists Grid (matching task-lists-grid pattern) -->
            <div class="task-lists-grid" id="progress-lists-grid">
                
                <!-- Character Timeline Card -->
                <div class="task-list-card">
                    <div class="task-list-header">
                        <div class="task-list-title">
                            <span class="task-list-icon">🏛️</span>
                            <span class="task-list-name">Character Evolution Timeline</span>
                        </div>
                        <div class="task-list-meta">
                            <span class="task-count" id="timeline-progress">Stone Age</span>
                        </div>
                    </div>
                    
                    <div class="task-list-content" id="character-timeline-content">
                        <div class="timeline-loading">
                            <div class="quest-spinner"></div>
                            <p>Loading character timeline...</p>
                        </div>
                        
                        <div class="timeline-display" id="timeline-display" style="display: none;">
                            <div class="current-age-info">
                                <div class="age-icon">🗿</div>
                                <div class="age-details">
                                    <h4 id="current-age-name">Stone Age</h4>
                                    <p id="current-age-description">The beginning of your productivity journey</p>
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="age-progress-fill" style="width: 0%;"></div>
                                    </div>
                                    <span class="progress-text" id="age-progress-text">0% to next age</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- XP Analytics Card -->
                <div class="task-list-card">
                    <div class="task-list-header">
                        <div class="task-list-title">
                            <span class="task-list-icon">📈</span>
                            <span class="task-list-name">XP Analytics</span>
                        </div>
                        <div class="task-list-meta">
                            <span class="task-count">Trends</span>
                        </div>
                    </div>
                    
                    <div class="task-list-content">
                        <div class="analytics-chart-container">
                            <canvas id="xp-trends-chart" class="progress-chart"></canvas>
                        </div>
                        
                        <div class="analytics-controls">
                            <button class="btn btn-small active" data-period="daily">Daily</button>
                            <button class="btn btn-small" data-period="weekly">Weekly</button>
                            <button class="btn btn-small" data-period="monthly">Monthly</button>
                        </div>
                    </div>
                </div>

                <!-- Level Progression Card -->
                <div class="task-list-card">
                    <div class="task-list-header">
                        <div class="task-list-title">
                            <span class="task-list-icon">📊</span>
                            <span class="task-list-name">Level Progression</span>
                        </div>
                        <div class="task-list-meta">
                            <span class="task-count" id="level-progress-meta">Level 1</span>
                        </div>
                    </div>
                    
                    <div class="task-list-content">
                        <div class="analytics-chart-container">
                            <canvas id="level-progression-chart" class="progress-chart"></canvas>
                        </div>
                        
                        <div class="level-predictions">
                            <div class="prediction-row">
                                <span>Next Level:</span>
                                <span id="next-level-eta">-- days</span>
                            </div>
                            <div class="prediction-row">
                                <span>Level 25:</span>
                                <span id="level-25-eta">-- days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Streak Calendar Card -->
                <div class="task-list-card">
                    <div class="task-list-header">
                        <div class="task-list-title">
                            <span class="task-list-icon">🔥</span>
                            <span class="task-list-name">Streak Calendar</span>
                        </div>
                        <div class="task-list-meta">
                            <span class="task-count" id="streak-percentage">0% this month</span>
                        </div>
                    </div>
                    
                    <div class="task-list-content">
                        <div class="calendar-header">
                            <button class="btn btn-small" id="prev-month">‹</button>
                            <h4 class="calendar-month" id="calendar-month-title">Loading...</h4>
                            <button class="btn btn-small" id="next-month">›</button>
                        </div>
                        
                        <div class="calendar-grid" id="streak-calendar-grid">
                            <!-- Calendar will be populated dynamically -->
                        </div>
                        
                        <div class="calendar-legend">
                            <div class="legend-item"><div class="legend-dot no-activity"></div><span>No Activity</span></div>
                            <div class="legend-item"><div class="legend-dot low-activity"></div><span>1-2 Tasks</span></div>
                            <div class="legend-item"><div class="legend-dot medium-activity"></div><span>3-5 Tasks</span></div>
                            <div class="legend-item"><div class="legend-dot high-activity"></div><span>6+ Tasks</span></div>
                        </div>
                    </div>
                </div>

                <!-- Activity Heatmap Card -->
                <div class="task-list-card">
                    <div class="task-list-header">
                        <div class="task-list-title">
                            <span class="task-list-icon">📊</span>
                            <span class="task-list-name">Activity Overview</span>
                        </div>
                        <div class="task-list-meta">
                            <span class="task-count">Patterns</span>
                        </div>
                    </div>
                    
                    <div class="task-list-content">
                        <div class="heatmap-controls">
                            <button class="btn btn-small active" data-view="yearly">Year</button>
                            <button class="btn btn-small" data-view="monthly">Month</button>
                            <button class="btn btn-small" data-view="weekly">Week</button>
                        </div>
                        
                        <div class="heatmap-container">
                            <canvas id="activity-heatmap-canvas" class="activity-heatmap"></canvas>
                        </div>
                        
                        <div class="activity-insights">
                            <div class="insight-row">
                                <span>Most Productive Day:</span>
                                <span id="most-productive-day">--</span>
                            </div>
                            <div class="insight-row">
                                <span>Peak Hour:</span>
                                <span id="peak-hour">--:--</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Achievement Progress Card -->
                <div class="task-list-card">
                    <div class="task-list-header">
                        <div class="task-list-title">
                            <span class="task-list-icon">🏆</span>
                            <span class="task-list-name">Achievement Progress</span>
                        </div>
                        <div class="task-list-meta">
                            <span class="task-count" id="achievement-percentage">0% Complete</span>
                        </div>
                    </div>
                    
                    <div class="task-list-content">
                        <div class="achievement-overview">
                            <div class="progress-circle" data-percentage="0">
                                <div class="circle-content">
                                    <div class="percentage-text">0%</div>
                                    <div class="percentage-label">Complete</div>
                                </div>
                            </div>
                            
                            <div class="achievement-stats-mini">
                                <div class="stat-mini">
                                    <span id="unlocked-achievements">0</span>
                                    <small>Unlocked</small>
                                </div>
                                <div class="stat-mini">
                                    <span id="total-achievements">0</span>
                                    <small>Total</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="recent-achievements">
                            <h5>🎉 Recent Unlocks</h5>
                            <div class="recent-list" id="recent-achievements-list">
                                <!-- Recent achievements will be populated -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- Progress Reports Section -->
        <section class="content-section">
            <div class="section-header">
                <h2 class="section-title">📋 Progress Reports</h2>
                <div class="section-controls">
                    <div class="report-tabs">
                        <button class="btn btn-small active" data-tab="weekly">📅 Weekly</button>
                        <button class="btn btn-small" data-tab="monthly">🗓️ Monthly</button>
                        <button class="btn btn-small" data-tab="quarterly">📆 Quarterly</button>
                    </div>
                </div>
            </div>
            
            <div class="reports-container">
                <!-- Weekly Report -->
                <div class="report-panel active" id="weekly-report">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="stat-card-icon">✅</div>
                            <div class="stat-card-label">Tasks</div>
                            <div class="stat-card-value" id="week-tasks-completed">0</div>
                            <div class="stat-card-change">This Week</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-icon">✨</div>
                            <div class="stat-card-label">XP Gained</div>
                            <div class="stat-card-value" id="week-xp-gained">0</div>
                            <div class="stat-card-change">This Week</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-icon">🔥</div>
                            <div class="stat-card-label">Streak Days</div>
                            <div class="stat-card-value" id="week-streak-days">0</div>
                            <div class="stat-card-change">This Week</div>
                        </div>
                    </div>
                    
                    <div class="report-chart-container">
                        <canvas id="weekly-activity-chart" class="report-chart"></canvas>
                    </div>
                </div>
                
                <!-- Monthly Report -->
                <div class="report-panel" id="monthly-report">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="stat-card-icon">✅</div>
                            <div class="stat-card-label">Tasks</div>
                            <div class="stat-card-value" id="month-tasks-completed">0</div>
                            <div class="stat-card-change">This Month</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-icon">✨</div>
                            <div class="stat-card-label">XP Gained</div>
                            <div class="stat-card-value" id="month-xp-gained">0</div>
                            <div class="stat-card-change">This Month</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-icon">📈</div>
                            <div class="stat-card-label">Levels</div>
                            <div class="stat-card-value" id="month-levels-gained">0</div>
                            <div class="stat-card-change">This Month</div>
                        </div>
                    </div>
                    
                    <div class="report-chart-container">
                        <canvas id="monthly-trends-chart" class="report-chart"></canvas>
                    </div>
                </div>
                
                <!-- Quarterly Report -->
                <div class="report-panel" id="quarterly-report">
                    <div class="dashboard-stats">
                        <div class="stat-card">
                            <div class="stat-card-icon">✅</div>
                            <div class="stat-card-label">Tasks</div>
                            <div class="stat-card-value" id="quarter-tasks-completed">0</div>
                            <div class="stat-card-change">This Quarter</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-icon">🏆</div>
                            <div class="stat-card-label">Achievements</div>
                            <div class="stat-card-value" id="quarter-achievements-unlocked">0</div>
                            <div class="stat-card-change">This Quarter</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-card-icon">🏛️</div>
                            <div class="stat-card-label">Ages</div>
                            <div class="stat-card-value" id="quarter-character-ages">0</div>
                            <div class="stat-card-change">This Quarter</div>
                        </div>
                    </div>
                    
                    <div class="report-chart-container">
                        <canvas id="quarterly-growth-chart" class="report-chart"></canvas>
                    </div>
                </div>
            </div>
        </section>

</div>

<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';