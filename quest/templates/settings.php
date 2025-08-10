<?php
/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

// Template variables are passed from the controller  
// Define the main content for settings
ob_start();
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">⚙️ Quest Settings</h1>
    <p class="page-subtitle">Customize which task lists to include in your quest and assign colors</p>
</div>

<div class="quest-loading" id="settings-loading">
    <div class="quest-spinner"></div>
    <p>Loading your task lists...</p>
</div>

<!-- Main Settings Interface -->
<div id="settings-main-interface" style="display: none;">
    
    <!-- Task List Selection Section -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">📋 Task List Selection</h2>
            <div class="section-controls">
                <button class="btn btn-secondary" id="refresh-lists-btn">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Refresh Lists</span>
                </button>
                <button class="btn btn-primary" id="save-settings-btn">
                    <span class="btn-icon">💾</span>
                    <span class="btn-text">Save Settings</span>
                </button>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="search-box">
                <input type="text" class="search-input" id="list-search" placeholder="Search task lists...">
                <span class="search-icon">🔍</span>
            </div>
            <select class="filter-select" id="status-filter">
                <option value="all">All Lists</option>
                <option value="included">Included in Quest</option>
                <option value="excluded">Excluded from Quest</option>
            </select>
        </div>
        
        <!-- Task Lists Grid -->
        <div class="task-lists-grid" id="settings-task-lists-grid">
            <!-- Will be populated dynamically -->
            <div class="task-list-placeholder">
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <div class="empty-state-title">No task lists found</div>
                    <div class="empty-state-text">Connect to Nextcloud Tasks app to see your lists here.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Display Preferences Section -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">🎨 Display Preferences</h2>
            <p class="section-subtitle">Customize how tasks are displayed</p>
        </div>
        
        <div class="settings-controls">
            <div class="setting-item">
                <label class="toggle-label" for="hide-completed-tasks">
                    <input type="checkbox" id="hide-completed-tasks" class="include-checkbox" checked>
                    Hide Completed Tasks
                </label>
                <p class="setting-description">Hide completed tasks from task lists to focus on active tasks</p>
            </div>
        </div>
    </section>

    <!-- Color Palette Section -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">🎨 Color Management</h2>
            <div class="section-controls">
                <button class="btn btn-secondary" id="reset-colors-btn">
                    <span class="btn-icon">🔄</span>
                    <span class="btn-text">Reset Colors</span>
                </button>
            </div>
        </div>
        
        <!-- Color Presets -->
        <div class="color-presets">
            <div class="presets-header">
                <h3>Color Presets</h3>
                <p>Quick color schemes for your task lists</p>
            </div>
            <div class="presets-grid">
                <div class="preset-option" data-preset="default">
                    <div class="preset-preview">
                        <div class="color-dot" style="background: #0082c9"></div>
                        <div class="color-dot" style="background: #46ba61"></div>
                        <div class="color-dot" style="background: #f59e0b"></div>
                        <div class="color-dot" style="background: #e53e3e"></div>
                    </div>
                    <span class="preset-name">Default</span>
                </div>
                <div class="preset-option" data-preset="warm">
                    <div class="preset-preview">
                        <div class="color-dot" style="background: #f97316"></div>
                        <div class="color-dot" style="background: #dc2626"></div>
                        <div class="color-dot" style="background: #fbbf24"></div>
                        <div class="color-dot" style="background: #fb7185"></div>
                    </div>
                    <span class="preset-name">Warm</span>
                </div>
                <div class="preset-option" data-preset="cool">
                    <div class="preset-preview">
                        <div class="color-dot" style="background: #3b82f6"></div>
                        <div class="color-dot" style="background: #06b6d4"></div>
                        <div class="color-dot" style="background: #8b5cf6"></div>
                        <div class="color-dot" style="background: #10b981"></div>
                    </div>
                    <span class="preset-name">Cool</span>
                </div>
                <div class="preset-option" data-preset="earth">
                    <div class="preset-preview">
                        <div class="color-dot" style="background: #92400e"></div>
                        <div class="color-dot" style="background: #059669"></div>
                        <div class="color-dot" style="background: #7c2d12"></div>
                        <div class="color-dot" style="background: #365314"></div>
                    </div>
                    <span class="preset-name">Earth</span>
                </div>
            </div>
        </div>
        
        <!-- Custom Color Picker -->
        <div class="color-picker-section">
            <div class="picker-header">
                <h3>Custom Colors</h3>
                <p>Create your own color palette</p>
            </div>
            <div class="color-picker-grid" id="color-picker-grid">
                <!-- Will be populated dynamically based on selected lists -->
            </div>
        </div>
    </section>
    
    <!-- Settings Summary Section -->
    <section class="content-section">
        <div class="section-header">
            <h2 class="section-title">📊 Settings Summary</h2>
        </div>
        
        <div class="dashboard-stats" id="settings-stats">
            <!-- Total Lists Card -->
            <div class="stat-card">
                <div class="stat-card-icon">📋</div>
                <div class="stat-card-label">Total Lists</div>
                <div class="stat-card-value" id="total-lists-count">0</div>
                <div class="stat-card-change">available</div>
            </div>
            
            <!-- Included Lists Card -->
            <div class="stat-card">
                <div class="stat-card-icon">✅</div>
                <div class="stat-card-label">Included Lists</div>
                <div class="stat-card-value" id="included-lists-count">0</div>
                <div class="stat-card-change">in quest</div>
            </div>
            
            <!-- Colored Lists Card -->
            <div class="stat-card">
                <div class="stat-card-icon">🎨</div>
                <div class="stat-card-label">Colored Lists</div>
                <div class="stat-card-value" id="colored-lists-count">0</div>
                <div class="stat-card-change">customized</div>
            </div>
            
            <!-- Last Saved Card -->
            <div class="stat-card">
                <div class="stat-card-icon">💾</div>
                <div class="stat-card-label">Last Saved</div>
                <div class="stat-card-value" id="last-saved-time">Never</div>
                <div class="stat-card-change">settings</div>
            </div>
        </div>
    </section>

</div>

<?php
// Capture the content and pass it to the layout
$_['content'] = ob_get_clean();

// Include the unified layout template
include_once __DIR__ . '/layout.php';