/**
 * Enhanced Task List Manager - Desktop-First Design
 * Comprehensive task list customization, drag-and-drop, and live preview
 * 
 * Features:
 * - Advanced drag-and-drop with visual feedback
 * - Real-time color customization with CSS variables
 * - Keyboard navigation support
 * - Desktop interactions (double-click, right-click)
 * - Live preview system with animations
 * - Auto-save with localStorage and server sync
 * - Performance optimized for desktop
 * 
 * @copyright Copyright (c) 2025 Quest Team
 * @license GNU AGPL version 3 or any later version
 */

(function() {
    'use strict';
    
    const QuestTaskListManager = {
        // Configuration
        config: {
            debounceDelay: 300,
            animationDelay: 100,
            maxTaskLists: 12,
            storageKey: 'quest-tasklist-preferences',
            autoSaveDelay: 1000
        },
        
        // State
        taskLists: new Map(),
        userPreferences: {},
        initialized: false,
        keyboardNavEnabled: false,
        dragState: {
            isDragging: false,
            draggedElement: null,
            startPosition: null
        },
        
        // Initialize the enhanced task list manager
        init: function() {
            if (this.initialized) return;
            
            console.log('🚀 Initializing Enhanced Task List Manager...');
            
            this.loadUserPreferences();
            this.bindEvents();
            this.initializeTaskLists();
            this.setupKeyboardNavigation();
            this.startAutoSave();
            this.applyDesktopEnhancements();
            
            this.initialized = true;
            console.log('✅ Task List Manager initialized successfully');
            
            // Dispatch initialization event
            document.dispatchEvent(new CustomEvent('taskListManagerReady', {
                detail: { manager: this }
            }));
        },
        
        // Load user preferences with fallback handling
        loadUserPreferences: function() {
            try {
                // Load from localStorage first
                const localPrefs = localStorage.getItem(this.config.storageKey);
                if (localPrefs) {
                    this.userPreferences = JSON.parse(localPrefs);
                }
            } catch (error) {
                console.warn('Failed to load local preferences:', error);
                this.userPreferences = this.getDefaultPreferences();
            }
            
            // Sync with server asynchronously
            this.syncWithServer();
        },
        
        // Get default preferences structure
        getDefaultPreferences: function() {
            return {
                version: '1.0',
                taskLists: {},
                globalSettings: {
                    theme: 'auto',
                    animations: true,
                    keyboardNav: false,
                    autoHideEmpty: false
                }
            };
        },
        
        // Sync preferences with server
        syncWithServer: function() {
            if (typeof OC === 'undefined' || !OC.generateUrl) {
                console.warn('Nextcloud API not available, skipping server sync');
                return;
            }
            
            // For now, skip server sync as the endpoint doesn't exist yet
            console.log('📡 Server sync skipped - endpoint not implemented yet');
        },
        
        // Merge server preferences with local preferences
        mergePreferences: function(serverPrefs) {
            // Merge task list preferences
            if (serverPrefs.taskLists) {
                Object.keys(serverPrefs.taskLists).forEach(listId => {
                    if (!this.userPreferences.taskLists[listId]) {
                        this.userPreferences.taskLists[listId] = {};
                    }
                    Object.assign(this.userPreferences.taskLists[listId], serverPrefs.taskLists[listId]);
                });
            }
            
            // Merge global settings
            if (serverPrefs.globalSettings) {
                Object.assign(this.userPreferences.globalSettings, serverPrefs.globalSettings);
            }
        },
        
        // Apply desktop-specific enhancements
        applyDesktopEnhancements: function() {
            // Add custom scrollbars to main containers
            document.querySelectorAll('.task-lists-grid, .achievements-grid').forEach(element => {
                element.classList.add('custom-scrollbar');
            });
            
            // Enable hardware acceleration for animations
            document.querySelectorAll('.task-list-card, .achievement-card').forEach(element => {
                element.style.willChange = 'transform';
            });
            
            // Add context menu support
            this.setupContextMenus();
            
            // Enable hover delays for better UX
            this.setupHoverDelays();
        },
        
        // Setup context menu support for desktop
        setupContextMenus: function() {
            document.addEventListener('contextmenu', (e) => {
                if (e.target.closest('.task-list-card')) {
                    e.preventDefault();
                    this.showTaskListContextMenu(e);
                }
            });
        },
        
        // Show context menu for task lists
        showTaskListContextMenu: function(e) {
            const taskListCard = e.target.closest('.task-list-card');
            const listId = taskListCard.dataset.listId;
            
            // Remove existing context menu
            const existingMenu = document.querySelector('.task-list-context-menu');
            if (existingMenu) existingMenu.remove();
            
            // Create context menu
            const menu = document.createElement('div');
            menu.className = 'task-list-context-menu';
            menu.innerHTML = `
                <div class="context-menu-item" data-action="customize">
                    <span class="context-menu-icon">🎨</span>
                    <span class="context-menu-text">Customize</span>
                </div>
                <div class="context-menu-item" data-action="hide">
                    <span class="context-menu-icon">👁️</span>
                    <span class="context-menu-text">Hide</span>
                </div>
                <div class="context-menu-item" data-action="clone">
                    <span class="context-menu-icon">📋</span>
                    <span class="context-menu-text">Clone Settings</span>
                </div>
                <div class="context-menu-separator"></div>
                <div class="context-menu-item" data-action="reset">
                    <span class="context-menu-icon">🔄</span>
                    <span class="context-menu-text">Reset to Default</span>
                </div>
            `;
            
            // Position and show menu
            menu.style.left = e.pageX + 'px';
            menu.style.top = e.pageY + 'px';
            document.body.appendChild(menu);
            
            // Bind menu events
            menu.addEventListener('click', (event) => {
                const action = event.target.closest('.context-menu-item')?.dataset.action;
                if (action) {
                    this.handleContextMenuAction(action, listId);
                }
                menu.remove();
            });
            
            // Remove menu on outside click
            document.addEventListener('click', () => menu.remove(), { once: true });
        },
        
        // Handle context menu actions
        handleContextMenuAction: function(action, listId) {
            switch (action) {
                case 'customize':
                    this.openCustomizationDialog(listId);
                    break;
                case 'hide':
                    this.toggleTaskListVisibility(listId, false);
                    break;
                case 'clone':
                    this.cloneTaskListSettings(listId);
                    break;
                case 'reset':
                    this.resetTaskListToDefault(listId);
                    break;
            }
        },
        
        // Setup hover delays for better desktop UX
        setupHoverDelays: function() {
            let hoverTimeout;
            
            document.addEventListener('mouseenter', (e) => {
                if (e.target && e.target.matches && e.target.matches('.task-list-card, .achievement-card')) {
                    clearTimeout(hoverTimeout);
                    hoverTimeout = setTimeout(() => {
                        e.target.classList.add('hover-delayed');
                    }, 150);
                }
            }, true);
            
            document.addEventListener('mouseleave', (e) => {
                if (e.target && e.target.matches && e.target.matches('.task-list-card, .achievement-card')) {
                    clearTimeout(hoverTimeout);
                    e.target.classList.remove('hover-delayed');
                }
            }, true);
        },
        
        // Setup comprehensive keyboard navigation
        setupKeyboardNavigation: function() {
            // Global keyboard shortcuts
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey) {
                    switch (e.key) {
                        case 'k':
                            e.preventDefault();
                            this.focusSearch();
                            break;
                        case 'n':
                            e.preventDefault();
                            this.createNewTaskList();
                            break;
                        case 's':
                            e.preventDefault();
                            this.saveAllPreferences();
                            break;
                        case 'r':
                            e.preventDefault();
                            this.randomizeAllColors();
                            break;
                    }
                }
                
                // Navigation shortcuts
                if (e.key === 'Tab' && !e.shiftKey) {
                    this.handleTabNavigation(e);
                }
                
                // Escape to close modals/menus
                if (e.key === 'Escape') {
                    this.closeAllModals();
                }
            });
            
            // Arrow key navigation for task lists
            this.setupArrowKeyNavigation();
        },
        
        // Setup arrow key navigation
        setupArrowKeyNavigation: function() {
            let currentFocus = -1;
            const taskListCards = () => document.querySelectorAll('.task-list-card');
            
            document.addEventListener('keydown', (e) => {
                const cards = taskListCards();
                if (cards.length === 0) return;
                
                switch (e.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        e.preventDefault();
                        currentFocus = Math.min(currentFocus + 1, cards.length - 1);
                        this.focusTaskList(cards[currentFocus]);
                        break;
                        
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        e.preventDefault();
                        currentFocus = Math.max(currentFocus - 1, 0);
                        this.focusTaskList(cards[currentFocus]);
                        break;
                        
                    case 'Enter':
                        if (currentFocus >= 0 && cards[currentFocus]) {
                            this.activateTaskList(cards[currentFocus]);
                        }
                        break;
                        
                    case 'Space':
                        e.preventDefault();
                        if (currentFocus >= 0 && cards[currentFocus]) {
                            this.toggleTaskListSelection(cards[currentFocus]);
                        }
                        break;
                }
            });
        },
        
        // Focus a specific task list
        focusTaskList: function(card) {
            // Remove previous focus
            document.querySelectorAll('.task-list-card.keyboard-focused').forEach(c => {
                c.classList.remove('keyboard-focused');
            });
            
            // Add focus to target card
            card.classList.add('keyboard-focused');
            card.focus();
            
            // Scroll into view if needed
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        },
        
        // Start auto-save system
        startAutoSave: function() {
            setInterval(() => {
                if (this.hasUnsavedChanges()) {
                    this.saveAllPreferences();
                }
            }, this.config.autoSaveDelay);
        },
        
        // Check if there are unsaved changes
        hasUnsavedChanges: function() {
            const currentPrefs = JSON.stringify(this.userPreferences);
            const savedPrefs = localStorage.getItem(this.config.storageKey);
            return currentPrefs !== savedPrefs;
        },
        
        // Apply all preferences
        applyAllPreferences: function() {
            this.applyColorPreferences();
            this.applyVisibilityPreferences();
            this.applyOrderPreferences();
            this.applyGlobalSettings();
        },
        
        // Apply color preferences with CSS variables
        applyColorPreferences: function() {
            Object.keys(this.userPreferences.taskLists).forEach(listId => {
                const prefs = this.userPreferences.taskLists[listId];
                if (prefs.color) {
                    this.setTaskListColor(listId, prefs.color);
                }
            });
        },
        
        // Set task list color using CSS variables
        setTaskListColor: function(listId, color) {
            // Update CSS custom property
            document.documentElement.style.setProperty(`--task-list-color-${listId}`, color);
            
            // Update any existing UI elements
            const cards = document.querySelectorAll(`[data-list-id="${listId}"]`);
            cards.forEach(card => {
                card.style.setProperty('--list-accent', color);
            });
            
            // Save preference
            if (!this.userPreferences.taskLists[listId]) {
                this.userPreferences.taskLists[listId] = {};
            }
            this.userPreferences.taskLists[listId].color = color;
            
            // Trigger color change event
            this.triggerEvent('taskListColorChanged', { listId, color });
        },
        
        // Randomize all colors with animation
        randomizeAllColors: function() {
            const colors = [
                '#0082c9', '#46ba61', '#e9322d', '#8b5cf6', 
                '#f6a502', '#ec407a', '#00bcd4', '#795548',
                '#9c27b0', '#ff5722', '#4caf50', '#ff9800'
            ];
            
            const taskLists = document.querySelectorAll('.task-list-card');
            taskLists.forEach((card, index) => {
                setTimeout(() => {
                    const randomColor = colors[Math.floor(Math.random() * colors.length)];
                    const listId = card.dataset.listId;
                    
                    // Add animation class
                    card.classList.add('color-changing');
                    
                    // Change color
                    this.setTaskListColor(listId, randomColor);
                    
                    // Remove animation class
                    setTimeout(() => {
                        card.classList.remove('color-changing');
                    }, 300);
                    
                }, index * 100);
            });
        },
        
        // Save all preferences
        saveAllPreferences: function() {
            this.saveToLocalStorage();
            this.saveToServer();
            this.showSaveNotification();
        },
        
        // Save to localStorage
        saveToLocalStorage: function() {
            try {
                localStorage.setItem(this.config.storageKey, JSON.stringify(this.userPreferences));
            } catch (error) {
                console.error('Failed to save to localStorage:', error);
            }
        },
        
        // Save to server
        saveToServer: function() {
            if (typeof OC === 'undefined' || !OC.generateUrl) return;
            
            fetch(OC.generateUrl('/apps/quest/api/preferences/tasklists'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': OC.requestToken
                },
                body: JSON.stringify(this.userPreferences)
            }).catch(error => {
                console.warn('Failed to save to server:', error);
            });
        },
        
        // Show save notification
        showSaveNotification: function() {
            const notification = document.createElement('div');
            notification.className = 'save-notification';
            notification.textContent = '✅ Settings saved';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => notification.remove(), 300);
            }, 2000);
        },
        
        // Trigger custom event
        triggerEvent: function(eventName, detail) {
            document.dispatchEvent(new CustomEvent(eventName, { detail }));
        },
        
        // Initialize task lists
        initializeTaskLists: function() {
            // This will be called when actual task list data is available
            if (!this.userPreferences.taskLists) {
                this.userPreferences.taskLists = {};
            }
        },
        
        // Bind all event listeners
        bindEvents: function() {
            // Color change events
            document.addEventListener('change', (e) => {
                if (e.target && e.target.matches && e.target.matches('.task-list-color-picker')) {
                    this.handleColorChange(e.target);
                }
            });
            
            // Visibility toggle events
            document.addEventListener('click', (e) => {
                if (e.target && e.target.matches && e.target.matches('.visibility-toggle')) {
                    this.handleVisibilityToggle(e.target);
                }
            });
            
            // Double-click events for quick actions
            document.addEventListener('dblclick', (e) => {
                if (e.target.closest('.task-list-card')) {
                    this.handleTaskListDoubleClick(e.target.closest('.task-list-card'));
                }
            });
        },
        
        // Handle color changes with debouncing
        handleColorChange: function(colorInput) {
            const listId = colorInput.dataset.listId;
            const newColor = colorInput.value;
            
            // Debounce color changes
            clearTimeout(this.colorChangeTimeout);
            this.colorChangeTimeout = setTimeout(() => {
                this.setTaskListColor(listId, newColor);
            }, this.config.debounceDelay);
        },
        
        // Handle task list double-click
        handleTaskListDoubleClick: function(card) {
            const listId = card.dataset.listId;
            this.openCustomizationDialog(listId);
        },
        
        // Open customization dialog
        openCustomizationDialog: function(listId) {
            console.log(`Opening customization dialog for list ${listId}`);
            // This would integrate with a modal system
        },
        
        // Cleanup and destroy
        destroy: function() {
            this.initialized = false;
            // Remove event listeners and clean up
        },

        updateIncludeStatus: function(listId, isIncluded) {
            
            // Update the main quest settings (includedLists array)
            let questSettings = this.loadSavedSettings();
            if (!questSettings.includedLists) {
                questSettings.includedLists = [];
            }
            
            const listIdString = String(listId);
            
            if (isIncluded) {
                // Add to included lists if not already there
                if (!questSettings.includedLists.includes(listIdString)) {
                    questSettings.includedLists.push(listIdString);
                }
            } else {
                // Remove from included lists
                questSettings.includedLists = questSettings.includedLists.filter(id => id !== listIdString);
            }
            
            // Save the updated quest settings
            localStorage.setItem('questSettings', JSON.stringify(questSettings));
            
            // Also update the old format for compatibility
            let settings = JSON.parse(localStorage.getItem('questTaskListSettings') || '{}');
            if (!settings[listId]) {
                settings[listId] = {};
            }
            settings[listId].includeInQuest = isIncluded;
            localStorage.setItem('questTaskListSettings', JSON.stringify(settings));
            
        },

        autoSaveSettings: function() {
            
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }
            
            this.autoSaveTimeout = setTimeout(() => {
                this.saveSettings();
                this.showAutoSaveIndicator();
            }, 500);
        },

        showAutoSaveIndicator: function() {
            const indicator = document.createElement('div');
            indicator.className = 'auto-save-indicator';
            indicator.textContent = '';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: var(--color-success);
                color: white;
                padding: 8px 16px;
                border-radius: 4px;
                z-index: 10000;
                font-size: 14px;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;
            
            document.body.appendChild(indicator);
            
            // Fade in
            setTimeout(() => {
                indicator.style.opacity = '1';
            }, 10);
            
            // Fade out and remove
            setTimeout(() => {
                indicator.style.opacity = '0';
                setTimeout(() => {
                    if (indicator.parentNode) {
                        indicator.parentNode.removeChild(indicator);
                    }
                }, 300);
            }, 2000);
        },


        setupTableEventHandlers: function() {
            console.log('🔧 Setting up table event handlers');
            
            // Handle include checkbox changes
            document.querySelectorAll('.include-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const listId = this.dataset.listId;
                    const isIncluded = this.checked;
                    
                    // Update label
                    const label = this.nextElementSibling;
                    if (label) {
                        label.textContent = isIncluded ? 'Included' : 'Excluded';
                    }
                    
                    window.QuestTaskListManager.updateIncludeStatus(listId, isIncluded);
                    window.QuestTaskListManager.autoSaveSettings();
                });
            });
            
            // Handle priority changes
            document.querySelectorAll('.priority-select').forEach(select => {
                select.addEventListener('change', function() {
                    const listId = this.dataset.listId;
                    const priority = this.value;
                    
                    window.QuestTaskListManager.updateListPriority(listId, priority);
                    window.QuestTaskListManager.autoSaveSettings();
                });
            });
            
            // Handle color changes
            document.querySelectorAll('.color-picker').forEach(picker => {
                picker.addEventListener('change', function() {
                    const listId = this.dataset.listId;
                    const color = this.value;
                    
                    window.QuestTaskListManager.updateListColor(listId, color);
                    window.QuestTaskListManager.autoSaveSettings();
                });
            });
            
            // Handle visibility changes
            document.querySelectorAll('.visibility-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const listId = this.dataset.listId;
                    const isVisible = this.checked;
                    
                    // Update label
                    const label = this.nextElementSibling;
                    if (label) {
                        label.textContent = isVisible ? 'Visible' : 'Hidden';
                    }
                    
                    window.QuestTaskListManager.updateListVisibility(listId, isVisible);
                    window.QuestTaskListManager.autoSaveSettings();
                });
            });
            
            console.log('✅ Table event handlers setup complete');
        },

        updateListPriority: function(listId, priority) {
            
            let settings = JSON.parse(localStorage.getItem('questTaskListSettings') || '{}');
            if (!settings[listId]) {
                settings[listId] = {};
            }
            settings[listId].priority = priority;
            localStorage.setItem('questTaskListSettings', JSON.stringify(settings));
            
        },

        updateListColor: function(listId, color) {
            
            let settings = JSON.parse(localStorage.getItem('questTaskListSettings') || '{}');
            if (!settings[listId]) {
                settings[listId] = {};
            }
            settings[listId].color = color;
            localStorage.setItem('questTaskListSettings', JSON.stringify(settings));
            
            // Update the icon color immediately
            const icon = document.querySelector(`tr [data-list-id="${listId}"]`)?.closest('tr')?.querySelector('.list-icon');
            if (icon) {
                icon.style.color = color;
            }
            
        },

        updateListVisibility: function(listId, isVisible) {
            
            let settings = JSON.parse(localStorage.getItem('questTaskListSettings') || '{}');
            if (!settings[listId]) {
                settings[listId] = {};
            }
            settings[listId].visibility = isVisible;
            localStorage.setItem('questTaskListSettings', JSON.stringify(settings));
            
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            QuestTaskListManager.init();
        });
    } else {
        QuestTaskListManager.init();
    }
    
    // Expose to global scope
    window.QuestTaskListManager = QuestTaskListManager;
    
    // Debug: Log all methods immediately after object creation
    console.log('🔍 QuestTaskListManager methods at creation:', Object.getOwnPropertyNames(QuestTaskListManager));
    console.log('🔍 Methods containing "Include":', Object.getOwnPropertyNames(QuestTaskListManager).filter(m => m.toLowerCase().includes('include')));
    
})();

/**
 * Dashboard functionality
 */
(function() {
    'use strict';
    
    const QuestDashboard = {
        initialized: false,
        taskLists: [],
        
        init: function() {
            if (this.initialized) return;
            
            // Detect current page with debugging
            const questStatsElement = document.getElementById('quest-stats');
            const taskListsGridElement = document.getElementById('task-lists-grid');
            const questListsContainerElement = document.getElementById('quest-lists-container');
            const progressStatsElement = document.getElementById('progress-stats');
            const progressListsGridElement = document.getElementById('progress-lists-grid');
            const adventureMapContainer = document.getElementById('adventure-map-container');
            
            console.log('🔍 Page detection debug:');
            console.log('  - quest-stats element:', questStatsElement);
            console.log('  - task-lists-grid element:', taskListsGridElement);
            console.log('  - quest-lists-container element:', questListsContainerElement);
            console.log('  - progress-stats element:', progressStatsElement);
            console.log('  - progress-lists-grid element:', progressListsGridElement);
            console.log('  - adventure-map-container element:', adventureMapContainer);
            
            const isQuestsPage = questStatsElement !== null;
            const isAdventurePage = adventureMapContainer !== null;
            const isDashboardPage = taskListsGridElement !== null && !isQuestsPage && !progressStatsElement && !isAdventurePage;
            const isProgressPage = progressStatsElement !== null;
            const isSettingsPage = document.getElementById('settings-loading') !== null;
            
            console.log('🔍 Page type determined:');
            console.log('  - isQuestsPage:', isQuestsPage);
            console.log('  - isAdventurePage:', isAdventurePage);
            console.log('  - isDashboardPage:', isDashboardPage);
            console.log('  - isProgressPage:', isProgressPage);
            console.log('  - isSettingsPage:', isSettingsPage);
            console.log('🔍 Element detection details:');
            console.log('  - taskListsGridElement:', !!taskListsGridElement, taskListsGridElement ? 'found' : 'not found');
            console.log('  - questStatsElement:', !!questStatsElement, questStatsElement ? 'found' : 'not found');
            console.log('  - progressStatsElement:', !!progressStatsElement, progressStatsElement ? 'found' : 'not found');
            console.log('  - adventureMapContainer:', !!adventureMapContainer, adventureMapContainer ? 'found' : 'not found');
            
            // Store page types as instance properties
            this.isQuestsPage = isQuestsPage;
            this.isAdventurePage = isAdventurePage;
            this.isDashboardPage = isDashboardPage;
            this.isProgressPage = isProgressPage;
            this.isSettingsPage = isSettingsPage;
            
            if (isQuestsPage) {
                console.log('⚔️ Initializing Quest Manager (Quests Page)...');
                this.initializeQuestsPage();
            } else if (isAdventurePage) {
                console.log('🗺️ Adventure page detected - skipping task manager initialization');
                // Don't initialize task manager for adventure page - let adventure-map.js handle it
                return;
            } else if (isDashboardPage) {
                console.log('🏠 Initializing Quest Dashboard...');
                console.log('🔍 Dashboard initialization details:', {
                    taskListsGridElement: !!taskListsGridElement,
                    isQuestsPage: isQuestsPage,
                    progressStatsElement: !!progressStatsElement,
                    isAdventurePage: isAdventurePage
                });
                this.loadPlayerStats(); // Load current user stats for dashboard
                
                // Add a small delay to ensure DOM is fully ready
                setTimeout(() => {
                    console.log('🕐 Loading task lists after DOM ready delay...');
                    
                    // Double-check grid element exists at this point
                    const gridCheck = document.getElementById('task-lists-grid');
                    console.log('🔍 Final grid check before loading:', {
                        exists: !!gridCheck,
                        innerHTML: gridCheck ? gridCheck.innerHTML.length : 'N/A'
                    });
                    
                    this.loadTaskLists();
                }, 100);
                
                this.setupEventHandlers();
                
                // Register with QuestApp for dashboard stats updates
                this.registerWithQuestApp('dashboard-page');
            } else if (isProgressPage) {
                console.log('📊 Initializing Progress Dashboard...');
                this.initializeProgressPage();
                
                // Register with new QuestApp for progress page stats
                this.registerWithQuestApp('progress-page');
            } else if (isSettingsPage) {
                console.log('⚙️ Initializing Settings Page...');
                this.initializeSettingsPage();
            } else {
                console.log('📄 Quest system initialized for other page');
            }
            
            this.initialized = true;
        },
        
        initializeQuestsPage: function() {
            console.log('⚔️ Setting up Quests page functionality...');
            
            // Load user stats and populate player avatar
            this.loadPlayerStats();
            
            // Load task lists for the quests page
            this.loadQuestLists();
            
            // Setup any quests-specific event handlers
            this.setupQuestsEventHandlers();
        },
        
        loadPlayerStats: function() {
            console.log('👤 Loading player stats via QuestApp...');
            
            // Use QuestApp stats service instead of direct API calls
            this.registerWithQuestApp('task-manager');
        },
        
        // Register with the new QuestApp architecture
        registerWithQuestApp: function(consumerId) {
            console.log(`📊 TaskManager: Registering with QuestApp as '${consumerId}'`);
            
            // Capture the correct 'this' context
            const self = this;
            
            // Wait for QuestApp to be ready
            const connectToQuestApp = function() {
                if (typeof window !== 'undefined' && window.QuestApp && window.QuestApp.initialized) {
                    console.log('📊 TaskManager: QuestApp is ready, registering as consumer');
                    
                    window.QuestApp.registerStatsConsumer(consumerId, {
                        onUpdate: function(stats) {
                            console.log('📊 TaskManager: Received stats update from QuestApp:', stats);
                            console.log('🔍 Debug - XP from QuestApp:', stats?.level?.xp || 'undefined');
                            console.log('🔍 Debug - Full level data:', stats?.level || 'undefined');
                            
                            // Call debug endpoint to check database state
                            self.callDebugEndpoint();
                            
                            // Update the player avatar with the new stats
                            self.updatePlayerAvatar(stats);
                        },
                        onError: function(error) {
                            console.error('📊 TaskManager: Stats error from QuestApp:', error);
                            // Set default values if loading fails
                            self.updatePlayerAvatar({
                                level: { level: 1, rank_title: 'Novice', xp: 0, xp_to_next: 100, progress_percentage: 0 }
                            });
                        },
                        onLoading: function(isLoading) {
                            console.log('📊 TaskManager: Stats loading state:', isLoading);
                            // Could show loading UI here if needed
                        }
                    });
                    
                    // Get current stats immediately if available
                    const currentStats = window.QuestApp.getCurrentStats();
                    if (currentStats) {
                        console.log('📊 TaskManager: Getting current stats immediately from QuestApp');
                        self.updatePlayerAvatar(currentStats);
                    }
                    
                } else {
                    console.log('📊 TaskManager: QuestApp not ready yet, retrying...');
                    setTimeout(connectToQuestApp, 500);
                }
            };
            
            // Try to connect immediately or wait for QuestApp ready event
            if (typeof window !== 'undefined' && window.QuestApp && window.QuestApp.initialized) {
                connectToQuestApp();
            } else {
                document.addEventListener('questAppReady', connectToQuestApp);
                setTimeout(connectToQuestApp, 1000); // Fallback
            }
        },
        
        callDebugEndpoint: function() {
            fetch(OC.generateUrl('/apps/quest/api/debug-db'), {
                method: 'GET',
                headers: {
                    'requesttoken': OC.requestToken
                }
            })
            .then(response => response.json())
            .then(data => {
                console.log('🔍 Debug DB response:', data);
                if (data.xp_history) {
                    console.log('🔍 XP History entries:', data.xp_history.length);
                    console.log('🔍 Recent XP History (full details):', data.xp_history.slice(0, 3));
                    // Show just the dates for easier debugging
                    const dates = data.xp_history.slice(0, 5).map(record => ({
                        completed_at: record.completed_at,
                        date_only: record.completed_at ? record.completed_at.split(' ')[0] : 'no date',
                        task_title: record.task_title
                    }));
                    console.log('🔍 Completion dates:', dates);
                }
                if (data.test_streak_data) {
                    console.log('🔍 Test Streak Data:', data.test_streak_data);
                }
                if (data.test_task_counts) {
                    console.log('🔍 Test Task Counts:', data.test_task_counts);
                }
            })
            .catch(error => {
                console.error('❌ Error calling debug endpoint:', error);
            });
        },
        
        updatePlayerAvatar: function(playerData) {
            console.log('🔍 updatePlayerAvatar called with:', playerData);
            
            // Handle both getUserStats format (data.level) and task completion format (data.user_stats)
            const level = playerData.level || playerData.user_stats || {};
            const streak = playerData.streak || {};
            const stats = playerData.stats || {};
            
            console.log('🔍 Extracted data - level:', level, 'streak:', streak, 'stats:', stats);
            
            // Update stat cards - use different IDs based on page type
            console.log('🔍 Page type check in updatePlayerAvatar - isQuestsPage:', this.isQuestsPage);
            console.log('🔍 Available page flags:', {
                isQuestsPage: this.isQuestsPage,
                isAdventurePage: this.isAdventurePage,
                isDashboardPage: this.isDashboardPage,
                isProgressPage: this.isProgressPage,
                isSettingsPage: this.isSettingsPage
            });
            
            if (this.isQuestsPage) {
                // Quests page specific IDs
                this.updateStatCard('player-level-display', level.level || 1);
                this.updateStatCard('player-rank-display', level.rank_title || 'Novice');
                this.updateStatCard('player-xp-display', level.xp || 0);
                
                const progressPercentage = Math.round(level.progress_percentage || 0);
                this.updateStatCard('player-xp-progress', `${progressPercentage}% to next level`);
                
                console.log('✅ Updated quests page player stats:', {
                    level: level.level,
                    rank: level.rank_title,
                    xp: level.xp,
                    progress: progressPercentage
                });
            } else {
                // Dashboard page IDs (fallback)
                this.updateStatCard('stat-level', level.level || 1);
                this.updateStatCard('stat-level-change', level.rank_title || 'Novice');
                this.updateStatCard('stat-total-xp', level.xp || 0);
                
                const progressPercentage = Math.round(level.progress_percentage || 0);
                this.updateStatCard('stat-xp-change', `${progressPercentage}% to next level`);
                
                // Update streak stats
                this.updateStatCard('stat-streak', streak.current_streak || 0);
                this.updateStatCard('stat-streak-change', `${streak.current_streak || 0} days`);
                
                // Update task count stats
                this.updateStatCard('stat-tasks-today', stats.tasks_today || 0);
                this.updateStatCard('stat-tasks-today-target', `of 5 target`);
                this.updateStatCard('stat-weekly-tasks', stats.tasks_this_week || 0);
                this.updateStatCard('stat-weekly-change', 'tasks completed');
            }
            
            // Update sidebar avatar
            this.updateSidebarAvatar(level);
            
            // Store player data for later use
            this.playerData = playerData;
        },
        
        updateSidebarAvatar: function(level) {
            // Update level badge
            const levelBadge = document.getElementById('avatar-level-number');
            if (levelBadge) {
                levelBadge.textContent = level.level || 1;
            }
            
            // Update character rank/title
            const characterRank = document.getElementById('character-rank');
            if (characterRank) {
                const rankTitle = this.getRankTitle(level.level || 1);
                characterRank.textContent = rankTitle;
            }
            
            // Update XP ring progress
            const xpRing = document.getElementById('xp-ring-progress');
            if (xpRing) {
                const percentage = level.progress_percentage || 0;
                const circumference = 2 * Math.PI * 64; // radius is 64
                const offset = circumference - (percentage / 100) * circumference;
                xpRing.style.strokeDashoffset = offset;
            }
            
            // Update all level displays
            const characterLevel = document.getElementById('character-level');
            if (characterLevel) {
                characterLevel.textContent = level.level || 1;
            }
            
            const currentXP = document.getElementById('current-xp');
            const nextLevelXP = document.getElementById('next-level-xp');
            if (currentXP && nextLevelXP) {
                currentXP.textContent = level.xp || 0;
                nextLevelXP.textContent = (level.xp || 0) + (level.xp_to_next || 100);
            }
            
            // Update level progress bar
            const levelProgress = document.getElementById('level-progress-bar');
            const levelProgressText = document.getElementById('level-progress-text');
            if (levelProgress && levelProgressText) {
                const percentage = level.progress_percentage || 0;
                levelProgress.style.width = `${percentage}%`;
                levelProgressText.textContent = `${Math.round(percentage)}%`;
            }
            
            // Update XP progress bar
            const xpProgress = document.getElementById('xp-progress-bar');
            if (xpProgress) {
                const percentage = level.progress_percentage || 0;
                xpProgress.style.width = `${percentage}%`;
            }
        },
        
        getRankTitle: function(level) {
            if (level >= 50) return 'Legendary Hero';
            if (level >= 40) return 'Master Adventurer';
            if (level >= 30) return 'Elite Warrior';
            if (level >= 25) return 'Seasoned Fighter';
            if (level >= 20) return 'Veteran Explorer';
            if (level >= 15) return 'Skilled Hunter';
            if (level >= 10) return 'Experienced Ranger';
            if (level >= 5) return 'Apprentice Warrior';
            return 'Novice Adventurer';
        },
        
        updateSidebarStats: function(playerData) {
            // This will be updated when quest lists are loaded
            // For now, we'll update it in displayQuestLists
        },
        
        updateQuestStats: function(questLists) {
            let totalActiveQuests = 0;
            let completedToday = 0; // This would need to be calculated based on completion dates
            let totalXPAvailable = 0;
            let totalLists = questLists.length;
            
            questLists.forEach(questList => {
                if (questList.tasks) {
                    const pendingTasks = questList.tasks.filter(task => !task.completed);
                    totalActiveQuests += pendingTasks.length;
                    totalXPAvailable += pendingTasks.reduce((sum, task) => sum + this.calculateTaskXP(task.priority), 0);
                }
            });
            
            // Update quest stats cards using dashboard format
            this.updateStatCard('active-quests-count', totalActiveQuests);
            this.updateStatCard('active-quests-change', totalActiveQuests > 0 ? 'ready to complete' : 'all done!');
            
            this.updateStatCard('completed-today-count', completedToday);
            this.updateStatCard('completed-today-change', 'quests');
            
            this.updateStatCard('total-xp-available', totalXPAvailable);
            this.updateStatCard('xp-available-change', totalXPAvailable > 0 ? 'points to earn' : 'all earned!');
            
            this.updateStatCard('quest-lists-count', totalLists);
            this.updateStatCard('quest-lists-change', totalLists === 1 ? 'collection' : 'collections');
        },
        
        updateStatCard: function(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                console.log(`📊 Updating stat card ${elementId} with value:`, value);
                element.textContent = value;
            } else {
                console.warn(`⚠️ Stat card element not found: ${elementId}`);
            }
        },
        
        loadQuestLists: function() {
            console.log('⚔️ Loading quest lists for quests page...');
            console.log('🔍 Quest lists container check:', document.getElementById('quest-lists-container'));
            
            fetch(OC.generateUrl('/apps/quest/api/quest-lists'), {
                method: 'GET',
                headers: {
                    'requesttoken': OC.requestToken
                }
            })
                .then(response => {
                    console.log('📡 Response status:', response.status, response.statusText);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Quest lists response:', data);
                    
                    if (data.status === 'success') {
                        this.taskLists = data.data;
                        this.displayQuestLists(data.data);
                        this.showMainInterface();
                    } else {
                        throw new Error(data.message || 'Failed to load quest lists');
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading quest lists:', error);
                    this.showQuestListError('Failed to load quest lists: ' + error.message);
                });
        },
        
        displayQuestLists: function(questLists) {
            const container = document.getElementById('quest-lists-container');
            if (!container) {
                console.warn('⚠️ Quest lists container not found');
                return;
            }
            
            if (!questLists || questLists.length === 0) {
                container.innerHTML = `
                    <div class="task-list-placeholder">
                        <div class="empty-state">
                            <div class="empty-state-icon">⚔️</div>
                            <div class="empty-state-title">No quest lists found</div>
                            <div class="empty-state-text">Install and configure the Nextcloud Tasks app to see your quest lists here.</div>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Clear the container
            container.innerHTML = '';
            
            // Create quest list cards using dashboard task-list-card format
            questLists.forEach((questList, index) => {
                const listCard = this.createQuestListCard(questList, index);
                container.appendChild(listCard);
            });
            
            // Update quest stats
            this.updateQuestStats(questLists);
            
            console.log(`⚔️ Displayed ${questLists.length} quest lists`);
        },
        
        createQuestListCard: function(questList, index) {
            const card = document.createElement('div');
            card.className = 'task-list-card';
            card.dataset.listId = questList.id;
            card.dataset.questMode = 'true'; // Mark as quest mode for special styling
            
            const pendingTasks = questList.tasks ? questList.tasks.filter(task => !task.completed) : [];
            const completedTasks = questList.tasks ? questList.tasks.filter(task => task.completed) : [];
            const totalXP = pendingTasks.reduce((sum, task) => sum + this.calculateTaskXP(task.priority), 0);
            
            card.innerHTML = `
                <div class="task-list-header" style="--list-color: ${questList.color || '#0082c9'}">
                    <div class="task-list-title">⚔️ ${questList.name || 'Untitled Quest List'}</div>
                    <div class="task-list-count">
                        <span>${pendingTasks.length} active</span>
                        <span>•</span>
                        <span>${completedTasks.length} completed</span>
                        <span>•</span>
                        <span class="xp-highlight">${totalXP} XP</span>
                    </div>
                </div>
                <div class="task-list-body">
                    <div class="task-items">
                        ${pendingTasks.slice(0, 6).map(task => `
                            <div class="task-item quest-task-item" data-task-id="${task.id}">
                                <input type="checkbox" class="task-checkbox" data-task-id="${task.id}" data-list-id="${questList.id}">
                                <div class="task-content">
                                    <div class="task-title">
                                        <span class="task-priority ${task.priority || 'medium'}">${(task.priority || 'medium').toUpperCase()}</span>
                                        ${task.title || 'Untitled Quest'}
                                    </div>
                                    <div class="task-meta">
                                        <span class="task-xp">+${this.calculateTaskXP(task.priority)} XP</span>
                                        ${task.due_date ? `<span class="task-due">Due: ${task.due_date}</span>` : ''}
                                    </div>
                                </div>
                                <div class="task-actions">
                                    <button class="task-action-btn complete-quest-btn" data-task-id="${task.id}" data-list-id="${questList.id}" title="Complete Quest">
                                        ⚔️
                                    </button>
                                </div>
                            </div>
                        `).join('')}
                        ${pendingTasks.length === 0 ? `
                            <div class="empty-task-list">
                                <div class="empty-state-icon">🎉</div>
                                <div class="empty-state-title">All quests completed!</div>
                                <div class="empty-state-text">Great job, adventurer!</div>
                            </div>
                        ` : ''}
                        ${pendingTasks.length > 6 ? `
                            <div class="task-item more-tasks-indicator">
                                <span class="more-tasks-text">...and ${pendingTasks.length - 6} more quests</span>
                                <button class="btn btn-secondary show-all-btn" data-list-id="${questList.id}">Show All</button>
                            </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            return card;
        },
        
        calculateTaskXP: function(priority) {
            const xpMap = {
                'high': 50,
                'medium': 25, 
                'low': 10
            };
            return xpMap[priority] || 25;
        },
        
        showMainInterface: function() {
            // Hide loading screen
            const loading = document.getElementById('quest-loading');
            if (loading) {
                loading.style.display = 'none';
            }
            
            // Show main interface
            const mainInterface = document.getElementById('quests-main-interface');
            if (mainInterface) {
                mainInterface.style.display = 'block';
            }
            
            console.log('✅ Quest interface displayed');
        },
        
        showQuestListError: function(message) {
            const container = document.getElementById('quest-lists-container');
            if (!container) return;
            
            container.innerHTML = `
                <div class="quest-error-state">
                    <div class="error-state-icon">⚠️</div>
                    <div class="error-state-title">Error Loading Quests</div>
                    <div class="error-state-text">${message}</div>
                    <button class="btn btn-primary" data-action="retry-quest-load">Try Again</button>
                </div>
            `;
            
            this.showMainInterface();
        },
        
        setupQuestsEventHandlers: function() {
            // Add any quests-specific event handlers here
            console.log('🎯 Setting up quests event handlers...');
            
            // Handle quest-specific actions
            document.addEventListener('click', (e) => {
                if (e.target && e.target.matches && e.target.matches('.complete-quest-btn')) {
                    this.handleQuestCompletion(e.target);
                } else if (e.target.classList.contains('task-checkbox') && e.target.closest('.quest-task-item')) {
                    e.preventDefault(); // Prevent default checkbox behavior
                    const taskId = e.target.dataset.taskId;
                    const listId = e.target.dataset.listId;
                    this.completeQuest(taskId, listId);
                } else if (e.target.classList.contains('show-all-btn')) {
                    const listId = e.target.dataset.listId;
                    this.showAllQuestsInList(listId);
                }
                
                if (e.target.dataset.action === 'retry-quest-load') {
                    this.loadQuestLists();
                }
            });
            
            // Setup refresh button
            const refreshBtn = document.getElementById('refresh-quests-btn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.loadQuestLists();
                });
            }
        },
        
        initializeProgressPage: function() {
            console.log('📊 Setting up Progress page functionality...');
            
            // Hide loading screen and show content
            setTimeout(() => {
                const loadingElement = document.getElementById('progress-loading');
                const mainInterface = document.getElementById('progress-main-interface');
                
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                if (mainInterface) {
                    mainInterface.style.display = 'block';
                }
                
                console.log('✅ Progress page content displayed');
            }, 500);
            
            // Load progress data
            this.loadProgressData();
            
            // Setup progress-specific event handlers
            console.log('🔥 ABOUT TO CALL setupProgressEventHandlers');
            try {
                this.setupProgressEventHandlers();
                console.log('🔥 setupProgressEventHandlers COMPLETED');
            } catch (error) {
                console.error('🔥 ERROR IN setupProgressEventHandlers:', error);
                console.error('🔥 ERROR STACK:', error.stack);
            }
            
            // Now handled by registerWithQuestApp call above
        },
        
        loadProgressData: function() {
            console.log('📈 Loading progress data...');
            
            // Load basic stats first
            this.loadProgressStats();
            
            // Initialize timeline
            this.initializeTimeline();
            
            // Setup report tabs
            this.setupReportTabs();
            
            console.log('✅ Progress data loading initiated');
        },
        
        // Legacy function removed - now using QuestApp architecture
        
        loadProgressStats: function() {
            // Mock data for now - in real implementation, this would fetch from API
            const mockStats = {
                level: 1,
                rank: 'Task Novice',
                totalXP: 0,
                xpToNext: 100,
                currentStreak: 0,
                longestStreak: 0,
                totalTasks: 0,
                tasksThisWeek: 0
            };
            
            // Update stat cards
            this.updateElement('current-level-display', mockStats.level);
            this.updateElement('current-rank-display', mockStats.rank);
            this.updateElement('total-xp-display', mockStats.totalXP);
            this.updateElement('xp-to-next-display', `${mockStats.xpToNext} to next level`);
            this.updateElement('current-streak-display', mockStats.currentStreak);
            this.updateElement('longest-streak-display', `Best: ${mockStats.longestStreak} days`);
            this.updateElement('total-tasks-display', mockStats.totalTasks);
            this.updateElement('tasks-this-week-display', `${mockStats.tasksThisWeek} this week`);
            
            console.log('📊 Progress stats updated');
        },
        
        initializeTimeline: function() {
            // Initialize character timeline
            const timelineLoading = document.querySelector('.timeline-loading');
            const timelineDisplay = document.getElementById('timeline-display');
            
            setTimeout(() => {
                if (timelineLoading) timelineLoading.style.display = 'none';
                if (timelineDisplay) timelineDisplay.style.display = 'block';
                console.log('🏛️ Timeline initialized');
            }, 800);
        },
        
        setupReportTabs: function() {
            // Setup report tab switching
            const reportTabs = document.querySelectorAll('.report-tabs .btn');
            const reportPanels = document.querySelectorAll('.report-panel');
            
            reportTabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const targetTab = tab.dataset.tab;
                    
                    // Update active tab
                    reportTabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');
                    
                    // Update active panel
                    reportPanels.forEach(panel => {
                        panel.classList.remove('active');
                        if (panel.id === `${targetTab}-report`) {
                            panel.classList.add('active');
                        }
                    });
                    
                    console.log(`📋 Switched to ${targetTab} report`);
                });
            });
        },
        
        setupProgressEventHandlers: function() {
            console.log('🎯 Setting up progress event handlers...');
            
            // Time range selector
            const timeRangeSelect = document.getElementById('time-range-select');
            if (timeRangeSelect) {
                timeRangeSelect.addEventListener('change', (e) => {
                    console.log(`📅 Time range changed to: ${e.target.value}`);
                    this.updateTimeRange(e.target.value);
                });
            }
            
            // Export button
            const exportBtn = document.getElementById('export-progress');
            if (exportBtn) {
                exportBtn.addEventListener('click', () => {
                    console.log('📥 Exporting progress data...');
                    this.exportProgressData();
                });
            }
            
            // Calendar navigation
            const prevMonthBtn = document.getElementById('prev-month');
            const nextMonthBtn = document.getElementById('next-month');
            
            if (prevMonthBtn) {
                prevMonthBtn.addEventListener('click', () => {
                    this.navigateCalendar('prev');
                });
            }
            
            if (nextMonthBtn) {
                nextMonthBtn.addEventListener('click', () => {
                    this.navigateCalendar('next');
                });
            }
            
            // Analytics controls
            document.addEventListener('click', (e) => {
                if (e.target && e.target.matches && (e.target.matches('.analytics-controls .btn') || e.target.matches('.heatmap-controls .btn'))) {
                    // Update active state
                    const parent = e.target.parentElement;
                    parent.querySelectorAll('.btn').forEach(btn => btn.classList.remove('active'));
                    e.target.classList.add('active');
                    
                    console.log(`📊 Analytics view changed to: ${e.target.textContent}`);
                }
            });
        },
        
        updateTimeRange: function(range) {
            // Update progress data based on time range
            console.log(`📅 Updating data for time range: ${range}`);
            // Implementation would fetch new data based on range
        },
        
        exportProgressData: function() {
            // Export functionality
            console.log('📥 Exporting progress data...');
            // Implementation would generate and download data
        },
        
        navigateCalendar: function(direction) {
            console.log(`📅 Navigating calendar: ${direction}`);
            // Implementation would update calendar display
        },
        
        updateElement: function(id, content) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = content;
            }
        },
        
        initializeSettingsPage: function() {
            console.log('⚙️ Setting up Settings page functionality...');
            
            // Hide loading screen and show content
            setTimeout(() => {
                const loadingElement = document.getElementById('settings-loading');
                const mainInterface = document.getElementById('settings-main-interface');
                
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
                if (mainInterface) {
                    mainInterface.style.display = 'block';
                }
                
                console.log('✅ Settings page content displayed');
            }, 500);
            
            // Load task lists for settings
            this.loadTaskListsForSettings();
            
            // Setup settings event handlers
            this.setupSettingsEventHandlers();
            
            // Initialize color presets
            this.initializeColorPresets();
        },
        
        loadTaskListsForSettings: function() {
            console.log('📋 Loading task lists for settings...');
            
            // Fetch task lists from the API
            fetch(OC.generateUrl('/apps/quest/api/quest-lists'), {
                method: 'GET',
                headers: {
                    'requesttoken': OC.requestToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    console.log('✅ Task lists response for settings:', data);
                    
                    if (data.status === 'success') {
                        this.displayTaskListsSettings(data.data);
                        this.updateSettingsStats(data.data);
                    } else {
                        this.handleSettingsError(data.message || 'Failed to load task lists');
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading task lists for settings:', error);
                    this.handleSettingsError('Failed to load task lists');
                });
        },
        
        displayTaskListsSettings: function(taskLists) {
            console.log('🚨🚨🚨 NEW TABLE VERSION LOADED - Version 2.0 🚨🚨🚨');
            const container = document.getElementById('settings-task-lists-grid');
            if (!container) return;
            
            // Clear existing content
            container.innerHTML = '';
            
            if (!taskLists || taskLists.length === 0) {
                container.innerHTML = `
                    <div class="task-list-placeholder">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-title">No task lists found</div>
                            <div class="empty-state-text">Connect to Nextcloud Tasks app to see your lists here.</div>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Load saved settings
            const savedSettings = this.loadSavedSettings();
            console.log('🚨 CRITICAL DEBUG - savedSettings:', JSON.stringify(savedSettings, null, 2));
            console.log('🚨 CRITICAL DEBUG - savedSettings.includedLists:', savedSettings.includedLists);
            console.log('🚨 CRITICAL DEBUG - taskLists length:', taskLists.length);
            
            // Apply hideCompletedTasks setting to checkbox
            const hideCompletedCheckbox = document.getElementById('hide-completed-tasks');
            if (hideCompletedCheckbox) {
                hideCompletedCheckbox.checked = savedSettings.hideCompletedTasks ?? true;
            }
            
            // Create table
            const table = document.createElement('table');
            table.className = 'task-lists-table';
            
            // Create table header
            const thead = document.createElement('thead');
            thead.innerHTML = `
                <tr>
                    <th class="col-task-list">Task List</th>
                    <th class="col-priority">Priority</th>
                    <th class="col-color">Color</th>
                    <th class="col-visibility">Visibility</th>
                    <th class="col-task-count">Tasks</th>
                    <th class="col-include">Include in Quest</th>
                </tr>
            `;
            table.appendChild(thead);
            
            // Create table body
            const tbody = document.createElement('tbody');
            
            console.log('🚨 STARTING FOREACH LOOP');
            taskLists.forEach((list, index) => {
                try {
                    console.log(`🔍 Processing list ${index + 1}:`, list); // Debug log
                    console.log(`🔍 List ${index + 1} ID type:`, typeof list.id, 'Value:', list.id); // Debug log
                    
                    // Fix the logic: if includedLists doesn't exist or is null/undefined, default to true (all included)
                    // if includedLists exists as an array, only include if the list ID is in the array
                    // if includedLists is an empty array, exclude all lists
                    let isIncluded;
                    if (!savedSettings.includedLists || !Array.isArray(savedSettings.includedLists)) {
                        isIncluded = true; // Default: include all if no array exists
                    } else {
                        isIncluded = savedSettings.includedLists.includes(String(list.id));
                    }
                    
                    // Debug the checkbox state - CRITICAL DEBUG
                    console.log(`🚨 CRITICAL - List "${list.name}" checkbox state:`, {
                        listId: list.id,
                        listIdString: String(list.id),
                        includedLists: savedSettings.includedLists,
                        includedListsExists: !!savedSettings.includedLists,
                        includedListsType: Array.isArray(savedSettings.includedLists) ? 'array' : typeof savedSettings.includedLists,
                        includesTest: savedSettings.includedLists ? savedSettings.includedLists.includes(String(list.id)) : 'no includedLists',
                        isIncluded: isIncluded
                    });
                    const color = savedSettings.listColors ? savedSettings.listColors[list.id] : this.getDefaultColor(list.id);
                    const priority = savedSettings.listPriorities ? savedSettings.listPriorities[list.id] || 'normal' : 'normal';
                    
                    console.log(`🔍 Creating row ${index + 1} for list: ${list.name}`);
                    console.log(`🚨 CHECKBOX DEBUG - About to create HTML with isIncluded=${isIncluded}`);
                    
                    const row = document.createElement('tr');
                    row.className = isIncluded ? 'included' : 'excluded';
                    row.dataset.listId = list.id;
                    
                    row.innerHTML = `
                        <td class="col-task-list">
                            <span class="task-list-name">${this.escapeHtml(list.name || 'Unnamed List')}</span>
                        </td>
                        <td class="col-priority">
                            <select id="priority-${list.id}" class="priority-select">
                                <option value="high" ${priority === 'high' ? 'selected' : ''}>High</option>
                                <option value="normal" ${priority === 'normal' ? 'selected' : ''}>Normal</option>
                                <option value="low" ${priority === 'low' ? 'selected' : ''}>Low</option>
                            </select>
                        </td>
                        <td class="col-color">
                            <div class="table-color-preview" style="background-color: ${color}">
                                <input type="color" id="color-${list.id}" class="table-color-picker color-picker" value="${color}" title="Choose list color">
                            </div>
                        </td>
                        <td class="col-visibility">
                            <span class="visibility-badge ${isIncluded ? 'included' : 'excluded'}">
                                ${isIncluded ? 'Included' : 'Excluded'}
                            </span>
                        </td>
                        <td class="col-task-count">${list.total_tasks || 0}</td>
                        <td class="col-include">
                            <input type="checkbox" id="include-${list.id}" class="table-checkbox include-checkbox" ${isIncluded ? 'checked' : ''}>
                        </td>
                    `;
                    
                    tbody.appendChild(row);
                    console.log(`✅ Row ${index + 1} created successfully`);
                } catch (error) {
                    console.error(`❌ Error creating row for list ${list.name}:`, error);
                    console.error('❌ Error stack:', error.stack);
                }
            });
            
            table.appendChild(tbody);
            container.appendChild(table);
            
            // Set up event handlers for the newly created table
            this.setupTableEventHandlers();
            
            console.log('✅ Task lists displayed for settings in table format');
            console.log('🔍 Table created with', taskLists.length, 'rows');
            console.log('🔍 Container now contains:', container.innerHTML.substring(0, 200) + '...');
        },
        
        setupTableEventHandlers: function() {
            console.log('🎯 Setting up table-specific event handlers...');
            
            const self = this; // Preserve context
            
            // Add direct event listeners to all checkboxes
            const checkboxes = document.querySelectorAll('.task-lists-table .include-checkbox');
            console.log('🔍 Found checkboxes:', checkboxes.length);
            
            checkboxes.forEach(function(checkbox, index) {
                console.log(`🔍 Setting up checkbox ${index + 1}:`, checkbox.id);
                checkbox.addEventListener('change', function(e) {
                    console.log('🔄 CHECKBOX CLICKED! Include checkbox changed:', e.target.checked);
                    console.log('🔍 Checkbox ID:', e.target.id);
                    // Use the correct object reference - this method is part of QuestDashboard
                    window.QuestDashboard.updateIncludeStatus(e.target);
                });
            });
            
            // Add direct event listeners to all color pickers
            const colorPickers = document.querySelectorAll('.task-lists-table .color-picker');
            console.log('🔍 Found color pickers:', colorPickers.length);
            
            colorPickers.forEach(function(colorPicker, index) {
                console.log(`🔍 Setting up color picker ${index + 1}:`, colorPicker.id);
                colorPicker.addEventListener('change', function(e) {
                    console.log('🔄 COLOR CHANGED! Color changed:', e.target.value);
                    window.QuestDashboard.updateListColor(e.target);
                });
            });
            
            // Add direct event listeners to all priority selects
            const prioritySelects = document.querySelectorAll('.task-lists-table .priority-select');
            console.log('🔍 Found priority selects:', prioritySelects.length);
            
            prioritySelects.forEach(function(select, index) {
                console.log(`🔍 Setting up priority select ${index + 1}:`, select.id);
                select.addEventListener('change', function(e) {
                    console.log('🔄 PRIORITY CHANGED! Priority changed:', e.target.value);
                    window.QuestDashboard.updateSettingsStatsFromUI();
                    window.QuestDashboard.autoSaveSettings();
                });
            });
            
            console.log('✅ Table event handlers set up for', checkboxes.length, 'checkboxes,', colorPickers.length, 'color pickers,', prioritySelects.length, 'priority selects');
        },
        
        setupSettingsEventHandlers: function() {
            console.log('🎯 Setting up settings event handlers...');
            
            // Save settings button
            const saveBtn = document.getElementById('save-settings-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', () => {
                    this.saveSettings();
                });
            }
            
            // Refresh lists button
            const refreshBtn = document.getElementById('refresh-lists-btn');
            if (refreshBtn) {
                refreshBtn.addEventListener('click', () => {
                    this.loadTaskListsForSettings();
                });
            }
            
            // Reset colors button
            const resetBtn = document.getElementById('reset-colors-btn');
            if (resetBtn) {
                resetBtn.addEventListener('click', () => {
                    this.resetColors();
                });
            }
            
            // Search functionality
            const searchInput = document.getElementById('list-search');
            if (searchInput) {
                searchInput.addEventListener('input', (e) => {
                    this.filterTaskLists(e.target.value);
                });
            }
            
            // Hide completed tasks checkbox (handled in global change listener above)
            
            // Status filter
            const statusFilter = document.getElementById('status-filter');
            if (statusFilter) {
                statusFilter.addEventListener('change', (e) => {
                    this.filterByStatus(e.target.value);
                });
            }
            
            // Color preset selection
            document.addEventListener('click', (e) => {
                if (e.target.closest('.preset-option')) {
                    const preset = e.target.closest('.preset-option').dataset.preset;
                    this.applyColorPreset(preset);
                }
                
            });
            
            // Handle global settings changes (non-table elements only)
            document.addEventListener('change', (e) => {
                // Handle hide completed tasks checkbox (not in table)
                if (e.target.id === 'hide-completed-tasks') {
                    console.log('🔄 Hide completed changed:', e.target.checked);
                    this.updateHideCompletedSetting(e.target.checked);
                    this.autoSaveSettings();
                    return;
                }
                
                // Note: Table-specific handlers are set up in setupTableEventHandlers()
            });
        },
        
        initializeColorPresets: function() {
            this.colorPresets = {
                default: ['#0082c9', '#46ba61', '#f59e0b', '#e53e3e', '#8b5cf6', '#f97316'],
                warm: ['#f97316', '#dc2626', '#fbbf24', '#fb7185', '#e11d48', '#f59e0b'],
                cool: ['#3b82f6', '#06b6d4', '#8b5cf6', '#10b981', '#0891b2', '#6366f1'],
                earth: ['#92400e', '#059669', '#7c2d12', '#365314', '#a16207', '#166534']
            };
        },
        
        applyColorPreset: function(preset) {
            console.log(`🎨 Applying color preset: ${preset}`);
            
            const colors = this.colorPresets[preset];
            if (!colors) return;
            
            const colorPickers = document.querySelectorAll('.color-picker');
            colorPickers.forEach((picker, index) => {
                const color = colors[index % colors.length];
                picker.value = color;
                
                // Update preview
                const listId = picker.id.replace('color-', '');
                const preview = document.querySelector(`[data-list-id="${listId}"] .list-color-preview`);
                if (preview) {
                    preview.style.backgroundColor = color;
                }
            });
            
            // Mark active preset
            document.querySelectorAll('.preset-option').forEach(option => {
                option.classList.remove('active');
            });
            document.querySelector(`[data-preset="${preset}"]`).classList.add('active');
        },
        
        updateIncludeStatus: function(checkbox) {
            console.log('🚨🚨🚨 VERSION 3.0 - updateIncludeStatus called!', {
                checkboxId: checkbox.id,
                checked: checkbox.checked,
                timestamp: new Date().toISOString(),
                stackTrace: new Error().stack
            });
            
            const listId = checkbox.id.replace('include-', '');
            const row = checkbox.closest('tr');
            const visibilityBadge = row.querySelector('.visibility-badge');
            
            if (checkbox.checked) {
                row.classList.remove('excluded');
                row.classList.add('included');
                visibilityBadge.textContent = 'Included';
                visibilityBadge.className = 'visibility-badge included';
            } else {
                row.classList.remove('included');
                row.classList.add('excluded');
                visibilityBadge.textContent = 'Excluded';
                visibilityBadge.className = 'visibility-badge excluded';
            }
            
            this.updateSettingsStatsFromUI();
            
            console.log('🚨 About to call autoSaveSettings...');
            // Auto-save settings
            this.autoSaveSettings();
        },
        
        updateListColor: function(colorPicker) {
            const listId = colorPicker.id.replace('color-', '');
            const color = colorPicker.value;
            
            // Update preview in table
            const preview = colorPicker.parentElement;
            if (preview && preview.classList.contains('table-color-preview')) {
                preview.style.backgroundColor = color;
            }
            
            this.updateSettingsStatsFromUI();
            
            // Auto-save settings
            this.autoSaveSettings();
        },
        
        saveSettings: function() {
            console.log('💾 Saving settings...');
            
            const settings = {
                includedLists: [],
                listColors: {},
                listPriorities: {},
                hideCompletedTasks: document.getElementById('hide-completed-tasks')?.checked ?? true,
                lastSaved: new Date().toISOString()
            };
            
            // Collect settings from UI table
            document.querySelectorAll('.task-lists-table tbody tr').forEach(row => {
                const listId = row.dataset.listId;
                
                // Include status
                const checkbox = row.querySelector('.include-checkbox');
                if (checkbox && checkbox.checked) {
                    // Ensure consistent string type for list IDs
                    settings.includedLists.push(String(listId));
                }
                
                // Color
                const colorPicker = row.querySelector('.color-picker');
                if (colorPicker) {
                    settings.listColors[listId] = colorPicker.value;
                }
                
                // Priority
                const prioritySelect = row.querySelector('.priority-select');
                if (prioritySelect) {
                    settings.listPriorities[listId] = prioritySelect.value;
                }
            });
            
            // Save to localStorage
            localStorage.setItem('questSettings', JSON.stringify(settings));
            
            // Debug what was saved
            console.log('💾 Settings saved:', settings);
            console.log('💾 Included lists saved:', settings.includedLists);
            
            // Update UI
            this.updateElement('last-saved-time', this.formatTime(new Date()));
            
            // Show success message
            this.showSettingsSaved();
            
        },
        
        autoSaveSettings: function() {
            // Debounced auto-save to prevent excessive saves
            if (this.autoSaveTimeout) {
                clearTimeout(this.autoSaveTimeout);
            }
            
            this.autoSaveTimeout = setTimeout(() => {
                console.log('🔄 Auto-saving settings...');
                this.saveSettings();
                
                // Show subtle auto-save indicator
                this.showAutoSaveIndicator();
                
                // Trigger refresh of other pages that might be affected
                this.notifySettingsChanged();
            }, 500); // Wait 500ms after last change before saving
        },
        
        notifySettingsChanged: function() {
            console.log('📢 Notifying other components that settings changed...');
            
            // If there are other instances of task list displays, refresh them
            if (typeof window.QuestDashboard !== 'undefined' && window.QuestDashboard.loadTaskLists) {
                console.log('🔄 Refreshing dashboard task lists...');
                window.QuestDashboard.loadTaskLists();
            }
            
            // Trigger a custom event for other components to listen to
            window.dispatchEvent(new CustomEvent('questSettingsChanged', {
                detail: { timestamp: Date.now() }
            }));
        },
        
        showAutoSaveIndicator: function() {
            // Create or update auto-save indicator
            let indicator = document.getElementById('auto-save-indicator');
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'auto-save-indicator';
                indicator.className = 'auto-save-indicator';
                indicator.innerHTML = '✅ Auto-saved';
                document.body.appendChild(indicator);
            }
            
            // Show the indicator
            indicator.style.display = 'block';
            indicator.classList.remove('fade-out');
            
            // Hide it after 2 seconds
            setTimeout(() => {
                indicator.classList.add('fade-out');
                setTimeout(() => {
                    indicator.style.display = 'none';
                }, 300);
            }, 2000);
        },
        
        updateHideCompletedSetting: function(hideCompleted) {
            console.log('🔄 Updating hide completed tasks setting:', hideCompleted);
            
            // Save to localStorage immediately
            const currentSettings = this.loadSavedSettings();
            currentSettings.hideCompletedTasks = hideCompleted;
            localStorage.setItem('questSettings', JSON.stringify(currentSettings));
            
            // Re-render all task lists to apply the change
            if (this.currentTaskLists) {
                this.displayTaskLists(this.currentTaskLists);
            }
        },
        
        loadSavedSettings: function() {
            try {
                const saved = localStorage.getItem('questSettings');
                const settings = saved ? JSON.parse(saved) : {};
                console.log('📋 Loading saved settings:', settings);
                return settings;
            } catch (error) {
                console.error('❌ Error loading saved settings:', error);
                return {};
            }
        },
        
        
        getDefaultColor: function(listId) {
            // Generate a consistent color based on list ID
            const colors = this.colorPresets.default;
            // Convert listId to string to ensure it works with both string and number IDs
            const idString = String(listId);
            const hash = idString.split('').reduce((a, b) => {
                a = ((a << 5) - a) + b.charCodeAt(0);
                return a & a;
            }, 0);
            return colors[Math.abs(hash) % colors.length];
        },
        
        updateSettingsStats: function(taskLists) {
            const total = taskLists.length;
            const savedSettings = this.loadSavedSettings();
            const included = savedSettings.includedLists ? savedSettings.includedLists.length : total;
            const colored = Object.keys(savedSettings.listColors || {}).length;
            const lastSaved = savedSettings.lastSaved ? this.formatTime(new Date(savedSettings.lastSaved)) : 'Never';
            
            this.updateElement('total-lists-count', total);
            this.updateElement('included-lists-count', included);
            this.updateElement('colored-lists-count', colored);
            this.updateElement('last-saved-time', lastSaved);
        },
        
        updateSettingsStatsFromUI: function() {
            const total = document.querySelectorAll('.task-lists-table tbody tr').length;
            const included = document.querySelectorAll('.task-lists-table .include-checkbox:checked').length;
            const colored = document.querySelectorAll('.task-lists-table .color-picker').length;
            
            this.updateElement('total-lists-count', total);
            this.updateElement('included-lists-count', included);
            this.updateElement('colored-lists-count', colored);
        },
        
        handleSettingsError: function(message) {
            const grid = document.getElementById('settings-task-lists-grid');
            if (grid) {
                grid.innerHTML = `
                    <div class="task-list-placeholder">
                        <div class="empty-state">
                            <div class="empty-state-icon">❌</div>
                            <div class="empty-state-title">Error loading task lists</div>
                            <div class="empty-state-text">${this.escapeHtml(message)}</div>
                            <button class="btn btn-primary" id="retry-settings-load">Retry</button>
                        </div>
                    </div>
                `;
                
                // Add event listener for retry button
                const retryBtn = grid.querySelector('#retry-settings-load');
                if (retryBtn) {
                    retryBtn.addEventListener('click', () => {
                        this.loadTaskListsForSettings();
                    });
                }
            }
        },
        
        showSettingsSaved: function() {
            // Create and show a temporary notification
            const notification = document.createElement('div');
            notification.className = 'settings-notification';
            // notification.textContent = '✅ Settings saved successfully!';
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 2000);
        },
        
        formatTime: function(date) {
            return date.toLocaleString();
        },
        
        filterTaskLists: function(query) {
            // Implement search filtering for table rows
            const rows = document.querySelectorAll('.task-lists-table tbody tr');
            rows.forEach(row => {
                const name = row.querySelector('.task-list-name').textContent.toLowerCase();
                const matches = name.includes(query.toLowerCase());
                row.style.display = matches ? '' : 'none';
            });
        },
        
        filterByStatus: function(status) {
            const rows = document.querySelectorAll('.task-lists-table tbody tr');
            rows.forEach(row => {
                const checkbox = row.querySelector('.include-checkbox');
                const isIncluded = checkbox.checked;
                
                let show = true;
                if (status === 'included') {
                    show = isIncluded;
                } else if (status === 'excluded') {
                    show = !isIncluded;
                }
                
                row.style.display = show ? '' : 'none';
            });
        },
        
        resetColors: function() {
            console.log('🔄 Resetting colors to defaults...');
            
            const colorPickers = document.querySelectorAll('.color-picker');
            colorPickers.forEach((picker, index) => {
                const listId = picker.id.replace('color-', '');
                const defaultColor = this.getDefaultColor(listId);
                picker.value = defaultColor;
                
                // Update preview
                const preview = document.querySelector(`[data-list-id="${listId}"] .list-color-preview`);
                if (preview) {
                    preview.style.backgroundColor = defaultColor;
                }
            });
            
            this.updateSettingsStatsFromUI();
        },
        
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },
        
        handleQuestCompletion: function(button) {
            const taskId = button.dataset.taskId;
            const listId = button.dataset.listId;
            if (taskId && listId) {
                this.completeQuest(taskId, listId);
            }
        },
        
        showAllQuestsInList: function(listId) {
            // This would open a detailed view of all quests in the list
            console.log(`👁️ Show all quests in list ${listId}`);
            // Implementation would go here for a modal or expanded view
        },
        
        completeQuest: function(taskId, listId) {
            console.log(`🏆 Completing quest: task ${taskId} in list ${listId}`);
            console.log('Debug - taskId type:', typeof taskId, 'value:', taskId);
            console.log('Debug - listId type:', typeof listId, 'value:', listId);
            
            // Show loading state on the button/checkbox
            const button = document.querySelector(`[data-task-id="${taskId}"].complete-quest-btn`);
            const checkbox = document.querySelector(`[data-task-id="${taskId}"].task-checkbox`);
            
            if (button) {
                button.disabled = true;
                button.innerHTML = '🔄';
            }
            if (checkbox) {
                checkbox.disabled = true;
            }
            
            const requestData = {
                task_id: parseInt(taskId),
                list_id: parseInt(listId)
            };
            
            console.log('📤 Sending request to:', OC.generateUrl('/apps/quest/api/complete-quest'));
            console.log('📤 Request data:', requestData);
            console.log('📤 Request token:', OC.requestToken ? 'Present' : 'Missing');
            
            // Call the API to complete the task (using the correct endpoint)
            fetch(OC.generateUrl('/apps/quest/api/complete-quest'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': OC.requestToken
                },
                body: JSON.stringify(requestData)
            })
                .then(response => {
                    console.log('📥 Response received');
                    console.log('📥 Response status:', response.status);
                    console.log('📥 Response statusText:', response.statusText);
                    console.log('📥 Response type:', response.type);
                    console.log('📥 Response URL:', response.url);
                    
                    if (!response.ok) {
                        console.error('❌ Response not OK:', response.status, response.statusText);
                        return response.text().then(text => {
                            console.error('❌ Response body:', text);
                            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                        });
                    }
                    
                    return response.text().then(text => {
                        console.log('📥 Raw response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('❌ Failed to parse JSON:', e);
                            console.error('❌ Raw text was:', text);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    console.log('✅ Quest completion response:', data);
                    
                    if (data.status === 'success') {
                        // Update the task as completed in our local data
                        if (this.taskLists) {
                            const taskList = this.taskLists.find(list => list.id == listId);
                            if (taskList && taskList.tasks) {
                                const task = taskList.tasks.find(t => t.id == taskId);
                                if (task) {
                                    task.completed = true;
                                }
                            }
                        }
                        
                        // Immediately mark the task as completed in the UI
                        if (checkbox) {
                            checkbox.checked = true;
                            checkbox.disabled = true;
                        }
                        
                        // Mark the task item as completed
                        const taskItem = document.querySelector(`[data-task-id="${taskId}"].task-item`);
                        if (taskItem) {
                            taskItem.classList.add('completed');
                            
                            // If hide completed tasks is enabled, fade out and remove the task
                            const savedSettings = this.loadSavedSettings();
                            const hideCompletedTasks = savedSettings.hideCompletedTasks ?? true;
                            
                            if (hideCompletedTasks) {
                                taskItem.style.transition = 'opacity 0.5s ease-out';
                                taskItem.style.opacity = '0';
                                setTimeout(() => {
                                    taskItem.remove();
                                    // After removing, load next task if available
                                    this.loadNextTaskForList(listId);
                                }, 500);
                            } else {
                                // Even if not hiding completed tasks, update the counter
                                this.updateTaskListCounter(listId);
                            }
                        }
                        
                        // Show completion celebration
                        this.showQuestCompletionCelebration(data.data);
                        
                        // Refresh the quest lists to show updated state
                        setTimeout(() => {
                            this.loadQuestLists();
                        }, 2000); // Wait 2 seconds to show celebration
                        
                        // Update player stats if provided
                        if (data.data.user_stats) {
                            this.updatePlayerAvatar(data.data);
                        }
                        
                        // Also refresh player stats from server to ensure consistency
                        setTimeout(() => {
                            this.loadPlayerStats();
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Failed to complete quest');
                    }
                })
                .catch(error => {
                    console.error('❌ Error completing quest:', error);
                    console.error('❌ Error type:', error.name);
                    console.error('❌ Error message:', error.message);
                    console.error('❌ Error stack:', error.stack);
                    
                    // Reset button state
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = '⚔️';
                    }
                    if (checkbox) {
                        checkbox.disabled = false;
                        checkbox.checked = false;
                    }
                    
                    // Show error notification
                    this.showNotification('error', 'Failed to complete quest', error.message);
                });
        },
        
        updateTaskListCounter: function(listId) {
            console.log(`🔢 Updating counter for list ${listId}`);
            
            // Find the task list card
            const taskListCard = document.querySelector(`.task-list-card[data-list-id="${listId}"]`);
            if (!taskListCard) return;
            
            const taskItemsContainer = taskListCard.querySelector('.task-items');
            if (!taskItemsContainer) return;
            
            // Find the corresponding task list data
            const taskList = this.taskLists?.find(list => list.id == listId);
            if (!taskList || !taskList.tasks) return;
            
            // Get hide completed tasks setting
            const savedSettings = this.loadSavedSettings();
            const hideCompletedTasks = savedSettings.hideCompletedTasks ?? true;
            
            // Filter tasks based on setting
            const availableTasks = hideCompletedTasks 
                ? taskList.tasks.filter(task => !task.completed)
                : taskList.tasks;
            
            // Remove the old "more" counter
            let moreElement = taskItemsContainer.querySelector('.task-item-more');
            if (moreElement) {
                moreElement.remove();
            }
            
            // Count currently displayed tasks
            const displayedCount = taskItemsContainer.querySelectorAll('.task-item:not(.completed)').length;
            const remainingCount = availableTasks.length - displayedCount;
            
            // Add updated counter if there are remaining tasks
            if (remainingCount > 0) {
                const newMoreElement = document.createElement('div');
                newMoreElement.className = 'task-item-more';
                newMoreElement.textContent = `...and ${remainingCount} more`;
                taskItemsContainer.appendChild(newMoreElement);
            }
        },
        
        loadNextTaskForList: function(listId) {
            console.log(`📋 Loading next task for list ${listId}`);
            
            // Find the task list card
            const taskListCard = document.querySelector(`.task-list-card[data-list-id="${listId}"]`);
            if (!taskListCard) return;
            
            const taskItemsContainer = taskListCard.querySelector('.task-items');
            if (!taskItemsContainer) return;
            
            // Remove the old "more" counter temporarily
            let moreElement = taskItemsContainer.querySelector('.task-item-more');
            if (moreElement) {
                moreElement.remove();
            }
            
            // Count visible tasks (only actual task items)
            const visibleTasks = taskItemsContainer.querySelectorAll('.task-item:not(.completed)').length;
            
            // Find the corresponding task list data
            const taskList = this.taskLists?.find(list => list.id == listId);
            if (!taskList || !taskList.tasks) return;
            
            // Get hide completed tasks setting
            const savedSettings = this.loadSavedSettings();
            const hideCompletedTasks = savedSettings.hideCompletedTasks ?? true;
            
            // Filter tasks based on setting
            const availableTasks = hideCompletedTasks 
                ? taskList.tasks.filter(task => !task.completed)
                : taskList.tasks;
            
            // Check if there are more tasks to show (we show max 5 at a time)
            if (visibleTasks < 5 && availableTasks.length > visibleTasks) {
                // Get the next task that isn't already displayed
                const displayedTaskIds = Array.from(taskItemsContainer.querySelectorAll('.task-item'))
                    .map(item => item.dataset.taskId)
                    .filter(id => id); // Filter out undefined ids
                
                const nextTask = availableTasks.find(task => 
                    !displayedTaskIds.includes(task.id.toString())
                );
                
                if (nextTask) {
                    // Create and append the new task element
                    const newTaskElement = document.createElement('div');
                    newTaskElement.className = `task-item ${nextTask.completed ? 'completed' : ''}`;
                    newTaskElement.dataset.taskId = nextTask.id;
                    newTaskElement.innerHTML = `
                        <input type="checkbox" class="task-checkbox" data-task-id="${nextTask.id}" data-list-id="${listId}" ${nextTask.completed ? 'checked disabled' : ''}>
                        <div class="task-content">
                            <div class="task-title">
                                <span class="task-priority ${nextTask.priority || 'medium'}">${(nextTask.priority || 'medium').toUpperCase()}</span>
                                ${nextTask.title || 'Untitled Task'}
                            </div>
                            <div class="task-meta">
                                ${nextTask.due_date ? `<span class="task-due">Due: ${nextTask.due_date}</span>` : ''}
                            </div>
                        </div>
                    `;
                    
                    // Add with fade-in animation
                    newTaskElement.style.opacity = '0';
                    taskItemsContainer.appendChild(newTaskElement);
                    
                    // Trigger fade-in
                    setTimeout(() => {
                        newTaskElement.style.transition = 'opacity 0.5s ease-in';
                        newTaskElement.style.opacity = '1';
                    }, 10);
                    
                    console.log(`✅ Loaded next task: ${nextTask.title}`);
                }
            }
            
            // Now recalculate and add the "more" counter at the bottom if needed
            const currentDisplayedCount = taskItemsContainer.querySelectorAll('.task-item:not(.completed)').length;
            const remainingCount = availableTasks.length - currentDisplayedCount;
            
            if (remainingCount > 0) {
                // Create new "more" element and append at the end
                const newMoreElement = document.createElement('div');
                newMoreElement.className = 'task-item-more';
                newMoreElement.textContent = `...and ${remainingCount} more`;
                taskItemsContainer.appendChild(newMoreElement);
            }
        },
        
        showQuestCompletionCelebration: function(completionData) {
            console.log('🎉 Showing quest completion celebration');
            
            // For now, just show a notification
            const xpEarned = completionData.xp_earned || 0;
            const levelUp = completionData.level_up || false;
            
            let message = `Quest completed! +${xpEarned} XP earned`;
            if (levelUp) {
                message += ` • Level up to ${completionData.new_level}!`;
            }
            
            this.showNotification('success', 'Quest Completed! 🎉', message);
        },
        
        showNotification: function(type, title, message) {
            // Simple notification system
            const notification = document.createElement('div');
            notification.className = `notification-popup ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <div class="notification-icon">${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}</div>
                    <div class="notification-body">
                        <div class="notification-title">${title}</div>
                        <div class="notification-message">${message}</div>
                    </div>
                    <button class="notification-close">×</button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
            
            // Handle close button
            notification.querySelector('.notification-close').addEventListener('click', () => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            });
        },
        
        handleQuestCompletion: function(button) {
            const taskId = button.dataset.taskId;
            const listId = button.dataset.listId;
            
            console.log('🎯 Quest completion triggered for task:', taskId, 'in list:', listId);
            
            // Disable button and show loading state
            button.disabled = true;
            button.textContent = 'Completing...';
            
            // Call the completion API
            fetch(OC.generateUrl('/apps/quest/api/complete-quest'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'requesttoken': OC.requestToken
                },
                body: JSON.stringify({
                    task_id: taskId,
                    list_id: listId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('✅ Quest completed successfully!', data);
                    
                    // Show success and refresh the quest lists
                    this.showQuestCompletionSuccess(data);
                    setTimeout(() => this.loadQuestLists(), 1500);
                    
                } else {
                    throw new Error(data.message || 'Failed to complete quest');
                }
            })
            .catch(error => {
                console.error('❌ Error completing quest:', error);
                
                // Reset button state
                button.disabled = false;
                button.textContent = 'Complete Quest';
                
                // Show error
                if (window.OC && window.OC.Notification) {
                    window.OC.Notification.showTemporary('Failed to complete quest: ' + error.message);
                }
            });
        },
        
        showQuestCompletionSuccess: function(data) {
            // Show celebration notification
            if (window.OC && window.OC.Notification) {
                window.OC.Notification.showTemporary(`🎉 Quest completed! +${data.data.xp_earned || 25} XP earned!`, { type: 'success' });
            }
            
            console.log('🎉 Quest completion celebration!');
        },
        
        setupEventHandlers: function() {
            // Initialize search and filter functionality
            this.initializeTaskListFilters();
            this.initializeVisibilityToggle();
            this.initializeManualTaskHandlers();
        },
        
        initializeTaskListFilters: function() {
            const searchInput = document.getElementById('task-search');
            const priorityFilter = document.getElementById('priority-filter');
            const statusFilter = document.getElementById('status-filter');
            
            if (!searchInput || !priorityFilter || !statusFilter) return;
            
            let filterTimeout;
            
            // Search input handler
            searchInput.addEventListener('input', (e) => {
                clearTimeout(filterTimeout);
                filterTimeout = setTimeout(() => {
                    this.filterTaskLists();
                }, 300);
            });
            
            // Filter handlers
            priorityFilter.addEventListener('change', () => this.filterTaskLists());
            statusFilter.addEventListener('change', () => this.filterTaskLists());
        },
        
        filterTaskLists: function() {
            const searchInput = document.getElementById('task-search');
            const priorityFilter = document.getElementById('priority-filter');
            const statusFilter = document.getElementById('status-filter');
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            const priorityValue = priorityFilter.value;
            const statusValue = statusFilter.value;
            
            const taskListCards = document.querySelectorAll('.task-list-card');
            let visibleCount = 0;
            
            taskListCards.forEach(card => {
                const title = card.querySelector('.task-list-title')?.textContent.toLowerCase() || '';
                const tasks = card.querySelectorAll('.task-item');
                
                let hasMatchingTask = false;
                
                // Filter individual tasks within the card
                tasks.forEach(taskItem => {
                    const taskTitle = taskItem.querySelector('.task-title')?.textContent.toLowerCase() || '';
                    const taskPriority = taskItem.querySelector('.task-priority')?.textContent.toLowerCase() || '';
                    const isCompleted = taskItem.classList.contains('completed');
                    
                    let matches = true;
                    
                    // Search filter
                    if (searchTerm && !taskTitle.includes(searchTerm) && !title.includes(searchTerm)) {
                        matches = false;
                    }
                    
                    // Priority filter
                    if (priorityValue !== 'all' && !taskPriority.includes(priorityValue)) {
                        matches = false;
                    }
                    
                    // Status filter
                    if (statusValue === 'pending' && isCompleted) {
                        matches = false;
                    } else if (statusValue === 'completed' && !isCompleted) {
                        matches = false;
                    }
                    
                    if (matches) {
                        hasMatchingTask = true;
                        taskItem.style.display = '';
                    } else {
                        taskItem.style.display = 'none';
                    }
                });
                
                // Show/hide the entire card based on matches
                if (hasMatchingTask || (searchTerm && title.includes(searchTerm))) {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update empty state
            this.updateEmptyState(visibleCount, searchTerm, priorityValue, statusValue);
        },
        
        updateEmptyState: function(visibleCount, searchTerm, priorityValue, statusValue) {
            const grid = document.getElementById('task-lists-grid');
            let emptyState = grid.querySelector('.filter-empty-state');
            
            if (visibleCount === 0) {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'filter-empty-state';
                    grid.appendChild(emptyState);
                }
                
                let message = 'No task lists match your filters.';
                if (searchTerm) message = `No results found for "${searchTerm}".`;
                if (priorityValue !== 'all') message += ` Priority: ${priorityValue}.`;
                if (statusValue !== 'all') message += ` Status: ${statusValue}.`;
                
                emptyState.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <div class="empty-state-title">No Results Found</div>
                        <div class="empty-state-text">${message}</div>
                        <button class="btn btn-secondary" data-action="clear-filters">Clear Filters</button>
                    </div>
                `;
            } else if (emptyState) {
                emptyState.remove();
            }
        },
        
        clearAllFilters: function() {
            document.getElementById('task-search').value = '';
            document.getElementById('priority-filter').value = 'all';
            document.getElementById('status-filter').value = 'all';
            
            // Show all cards and tasks
            document.querySelectorAll('.task-list-card').forEach(card => {
                card.style.display = '';
            });
            document.querySelectorAll('.task-item').forEach(item => {
                item.style.display = '';
            });
            
            // Remove empty state
            const emptyState = document.querySelector('.filter-empty-state');
            if (emptyState) emptyState.remove();
        },
        
        initializeVisibilityToggle: function() {
            const toggleBtn = document.getElementById('toggle-visibility-btn');
            if (!toggleBtn) return;
            
            let allHidden = false;
            
            toggleBtn.addEventListener('click', () => {
                const taskListCards = document.querySelectorAll('.task-list-card');
                
                if (allHidden) {
                    // Show all cards
                    taskListCards.forEach(card => {
                        card.classList.remove('hidden');
                        card.style.opacity = '';
                        card.style.transform = '';
                    });
                    toggleBtn.querySelector('.btn-text').textContent = 'Toggle Lists';
                    allHidden = false;
                } else {
                    // Hide all cards
                    taskListCards.forEach(card => {
                        card.classList.add('hidden');
                    });
                    toggleBtn.querySelector('.btn-text').textContent = 'Show All';
                    allHidden = true;
                }
            });
        },
        
        initializeManualTaskHandlers: function() {
            document.addEventListener('click', (e) => {
                if (e.target.id === 'add-manual-task-btn') {
                    const modal = document.getElementById('task-completion-modal');
                    if (modal) {
                        modal.style.display = 'block';
                    }
                }
                
                if (e.target.id === 'close-task-modal' || e.target.classList.contains('modal-backdrop')) {
                    const modal = document.getElementById('task-completion-modal');
                    if (modal) {
                        modal.style.display = 'none';
                    }
                }
                
                if (e.target.id === 'complete-task-btn') {
                    // Handle manual task completion
                    const form = document.getElementById('task-completion-form');
                    if (form) {
                        const formData = new FormData(form);
                        const taskData = {
                            title: formData.get('title'),
                            priority: formData.get('priority'),
                            list_id: formData.get('list_id')
                        };
                        
                        if (taskData.title.trim()) {
                            this.completeManualTask(taskData);
                        }
                    }
                }
                
                // Handle retry button
                if (e.target.dataset.action === 'retry-load') {
                    this.loadTaskLists();
                }
                
                // Handle clear filters button
                if (e.target.dataset.action === 'clear-filters') {
                    this.clearAllFilters();
                }
                
                // Handle task checkbox clicks on dashboard
                if (e.target.classList.contains('task-checkbox') && !e.target.closest('.quest-task-item')) {
                    const taskId = e.target.dataset.taskId;
                    const listId = e.target.dataset.listId;
                    
                    if (taskId && listId && !e.target.disabled) {
                        e.preventDefault(); // Prevent default checkbox behavior
                        console.log('📋 Dashboard task clicked - taskId:', taskId, 'listId:', listId);
                        this.completeQuest(taskId, listId);
                    }
                }
            });
        },
        
        completeManualTask: function(taskData) {
            console.log('Completing manual task:', taskData);
            
            // Close modal
            const modal = document.getElementById('task-completion-modal');
            if (modal) {
                modal.style.display = 'none';
            }
            
            // Show success notification
            if (window.OC && window.OC.Notification) {
                window.OC.Notification.showTemporary(`Task "${taskData.title}" completed! +15 XP earned!`);
            }
            
            // Clear the form
            const form = document.getElementById('task-completion-form');
            if (form) form.reset();
        },
        
        loadTaskLists: function() {
            console.log('📋 Loading task lists...');
            console.log('🔍 Current page context:', {
                isDashboardPage: this.isDashboardPage,
                isQuestsPage: this.isQuestsPage,
                isAdventurePage: this.isAdventurePage,
                url: window.location.href
            });
            
            // Check if we have the grid element before making API call
            const grid = document.getElementById('task-lists-grid');
            console.log('🎯 Grid element check before API call:', {
                exists: !!grid,
                id: grid ? grid.id : 'N/A',
                innerHTML: grid ? grid.innerHTML.substring(0, 50) + '...' : 'N/A'
            });
            
            const apiUrl = OC.generateUrl('/apps/quest/api/quest-lists');
            console.log('🌐 API URL:', apiUrl);
            
            fetch(apiUrl, {
                method: 'GET',
                headers: {
                    'requesttoken': OC.requestToken
                }
            })
                .then(response => {
                    console.log('📡 Response status:', response.status, response.statusText);
                    console.log('📡 Response headers:', response.headers);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('✅ Task lists response:', data);
                    console.log('✅ Data type:', typeof data);
                    console.log('✅ Data structure:', {
                        status: data.status,
                        dataLength: data.data ? data.data.length : 'undefined',
                        message: data.message
                    });
                    
                    if (data.status === 'success') {
                        this.taskLists = data.data;
                        console.log('📝 Stored task lists:', this.taskLists);
                        this.displayTaskLists(data.data);
                    } else {
                        console.error('❌ API returned error status:', data);
                        throw new Error(data.message || 'Failed to load task lists');
                    }
                })
                .catch(error => {
                    console.error('❌ Error loading task lists:', error);
                    console.error('❌ Error stack:', error.stack);
                    this.showTaskListError('Failed to load task lists: ' + error.message);
                });
        },
        
        displayTaskLists: function(taskLists) {
            console.log('🎨 displayTaskLists called with:', taskLists);
            
            const grid = document.getElementById('task-lists-grid');
            console.log('🎯 task-lists-grid element:', grid);
            console.log('🎯 grid exists:', !!grid);
            console.log('🎯 grid innerHTML before:', grid ? grid.innerHTML.substring(0, 100) + '...' : 'N/A');
            
            if (!grid) {
                console.error('❌ task-lists-grid element not found!');
                console.log('🔍 Available elements with "grid" in ID:', 
                    Array.from(document.querySelectorAll('[id*="grid"]')).map(el => el.id)
                );
                console.log('🔍 All elements with "task" in ID:', 
                    Array.from(document.querySelectorAll('[id*="task"]')).map(el => el.id)
                );
                return;
            }
            
            // Store task lists for later use (e.g., dynamic loading)
            this.taskLists = taskLists;
            
            console.log('📊 Task lists analysis:', {
                isArray: Array.isArray(taskLists),
                length: taskLists ? taskLists.length : 'undefined',
                type: typeof taskLists,
                firstItem: taskLists && taskLists.length > 0 ? taskLists[0] : 'none'
            });
            
            if (!taskLists || taskLists.length === 0) {
                console.log('📋 No task lists to display, showing placeholder');
                grid.innerHTML = `
                    <div class="task-list-placeholder">
                        <div class="empty-state">
                            <div class="empty-state-icon">📋</div>
                            <div class="empty-state-title">No task lists found</div>
                            <div class="empty-state-text">Install and configure the Nextcloud Tasks app to see your task lists here.</div>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Apply saved settings to filter and style task lists
            const savedSettings = this.loadSavedSettings();
            const filteredTaskLists = this.applyTaskListSettings(taskLists, savedSettings);
            
            if (filteredTaskLists.length === 0) {
                grid.innerHTML = `
                    <div class="task-list-placeholder">
                        <div class="empty-state">
                            <div class="empty-state-icon">⚙️</div>
                            <div class="empty-state-title">No task lists selected</div>
                            <div class="empty-state-text">Go to Settings to select which task lists to include in your quest.</div>
                            <a href="${OC.generateUrl('/apps/quest/settings')}" class="btn btn-primary">Open Settings</a>
                        </div>
                    </div>
                `;
                return;
            }
            
            // Clear the grid
            grid.innerHTML = '';
            
            // Create task list cards for filtered lists
            filteredTaskLists.forEach((taskList, index) => {
                const card = this.createTaskListCard(taskList, index);
                grid.appendChild(card);
            });
            
            console.log(`📊 Displayed ${filteredTaskLists.length} of ${taskLists.length} task lists (filtered by settings)`);
        },
        
        applyTaskListSettings: function(taskLists, savedSettings) {
            console.log('🎯 Applying task list settings...', savedSettings);
            
            if (!savedSettings || !taskLists) {
                return taskLists || [];
            }
            
            // Filter task lists based on included lists setting
            let filteredLists = taskLists;
            
            console.log('🔍 Checking includedLists filter:', {
                hasIncludedLists: !!savedSettings.includedLists,
                includedListsLength: savedSettings.includedLists ? savedSettings.includedLists.length : 'N/A',
                includedLists: savedSettings.includedLists
            });
            
            if (savedSettings.includedLists && savedSettings.includedLists.length > 0) {
                console.log('🔍 Applying includedLists filter...');
                console.log('🔍 Saved includedLists:', savedSettings.includedLists);
                filteredLists = taskLists.filter(list => {
                    const listId = String(list.id);
                    const isIncluded = savedSettings.includedLists.includes(listId);
                    console.log(`  List "${list.name}" (ID: ${listId}, type: ${typeof listId}): ${isIncluded ? 'Included' : 'Excluded'}`);
                    return isIncluded;
                });
                console.log(`🔍 Filter result: ${filteredLists.length} of ${taskLists.length} lists included`);
            } else if (savedSettings.includedLists && savedSettings.includedLists.length === 0) {
                console.log('⚠️ includedLists is empty array - this will exclude ALL lists!');
                console.log('💡 Treating empty includedLists as "include all" to prevent total exclusion');
                // Don't filter if includedLists is explicitly empty - treat as "show all"
                filteredLists = taskLists;
            } else {
                console.log('🔍 No includedLists filter set - showing all lists');
            }
            
            // Apply custom colors to the filtered lists
            filteredLists.forEach(list => {
                const listId = String(list.id);
                
                // Apply custom color if set
                if (savedSettings.listColors && savedSettings.listColors[listId]) {
                    list.customColor = savedSettings.listColors[listId];
                    console.log(`  Applied custom color ${list.customColor} to "${list.name}"`);
                }
                
                // Apply priority if set
                if (savedSettings.listPriorities && savedSettings.listPriorities[listId]) {
                    list.priority = savedSettings.listPriorities[listId];
                    console.log(`  Applied priority ${list.priority} to "${list.name}"`);
                }
            });
            
            // Sort by priority if priorities are set
            if (savedSettings.listPriorities) {
                const priorityOrder = { 'high': 0, 'normal': 1, 'low': 2 };
                filteredLists.sort((a, b) => {
                    const aPriority = priorityOrder[a.priority] !== undefined ? priorityOrder[a.priority] : 1;
                    const bPriority = priorityOrder[b.priority] !== undefined ? priorityOrder[b.priority] : 1;
                    return aPriority - bPriority;
                });
            }
            
            console.log(`✅ Applied settings: ${filteredLists.length} lists after filtering and styling`);
            return filteredLists;
        },
        
        createTaskListCard: function(taskList, index) {
            const card = document.createElement('div');
            card.className = 'task-list-card';
            card.dataset.listId = taskList.id;
            
            const pendingTasks = taskList.tasks ? taskList.tasks.filter(task => !task.completed) : [];
            const completedTasks = taskList.tasks ? taskList.tasks.filter(task => task.completed) : [];
            
            // Get hide completed tasks setting
            const savedSettings = this.loadSavedSettings();
            const hideCompletedTasks = savedSettings.hideCompletedTasks ?? true;
            
            // Filter tasks based on setting
            const tasksToShow = hideCompletedTasks ? pendingTasks : (taskList.tasks || []);
            
            card.innerHTML = `
                <div class="task-list-header" style="background: ${taskList.customColor || taskList.color || '#0082c9'}">
                    <div class="task-list-title">${taskList.name || 'Untitled List'}</div>
                    <div class="task-list-count">
                        <span>${pendingTasks.length} pending</span>
                        <span>${completedTasks.length} completed</span>
                    </div>
                </div>
                <div class="task-list-body">
                    <div class="task-items">
                        ${tasksToShow && tasksToShow.length > 0 ? 
                            tasksToShow.slice(0, 5).map(task => `
                                <div class="task-item ${task.completed ? 'completed' : ''}" data-task-id="${task.id}">
                                    <input type="checkbox" class="task-checkbox" data-task-id="${task.id}" data-list-id="${taskList.id}" ${task.completed ? 'checked disabled' : ''}>
                                    <div class="task-content">
                                        <div class="task-title">
                                            <span class="task-priority ${task.priority || 'medium'}">${(task.priority || 'medium').toUpperCase()}</span>
                                            ${task.title || 'Untitled Task'}
                                        </div>
                                        <div class="task-meta">
                                            ${task.due_date ? `<span class="task-due">Due: ${task.due_date}</span>` : ''}
                                        </div>
                                    </div>
                                </div>
                            `).join('') : '<div class="empty-task-list">No tasks</div>'
                        }
                        ${tasksToShow && tasksToShow.length > 5 ? 
                            `<div class="task-item-more">...and ${tasksToShow.length - 5} more</div>` : ''
                        }
                    </div>
                </div>
            `;
            
            return card;
        },
        
        showTaskListError: function(message) {
            console.log('🚨 showTaskListError called with message:', message);
            
            const grid = document.getElementById('task-lists-grid');
            console.log('🎯 Error display - grid element:', grid);
            
            if (!grid) {
                console.error('❌ Cannot show error - task-lists-grid element not found!');
                return;
            }
            
            console.log('📝 Setting error HTML in grid');
            grid.innerHTML = `
                <div class="task-list-error">
                    <div class="empty-state">
                        <div class="empty-state-icon">⚠️</div>
                        <div class="empty-state-title">Error Loading Task Lists</div>
                        <div class="empty-state-text">${message}</div>
                        <button class="btn btn-primary" data-action="retry-load">Try Again</button>
                    </div>
                </div>
            `;
            console.log('✅ Error HTML set successfully');
        },
        
        refresh: function() {
            console.log('🔄 Refreshing dashboard...');
            this.loadTaskLists();
        }
    };
    
    // Initialize when DOM is ready and CSS has loaded
    function initializeWhenReady() {
        // Ensure CSS has loaded by waiting for next frame
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                requestAnimationFrame(() => {
                    setTimeout(() => QuestDashboard.init(), 50);
                });
            });
        } else {
            // If DOM is already ready, still wait a bit for CSS
            requestAnimationFrame(() => {
                setTimeout(() => QuestDashboard.init(), 50);
            });
        }
    }
    
    initializeWhenReady();
    
    // Expose to global scope
    window.QuestDashboard = QuestDashboard;
    
    // Create alias for backward compatibility
    window.QuestManager = QuestDashboard;
    
    // Add debugging helpers
    window.debugTaskLoading = function() {
        console.log('🔧 Debug Task Loading Helper');
        console.log('🔍 Current page detection:', {
            isDashboardPage: QuestDashboard.isDashboardPage,
            isQuestsPage: QuestDashboard.isQuestsPage,
            isAdventurePage: QuestDashboard.isAdventurePage,
            initialized: QuestDashboard.initialized
        });
        
        console.log('🔍 DOM Element Analysis:');
        const allGrids = document.querySelectorAll('[id*="grid"]');
        const allTasks = document.querySelectorAll('[id*="task"]');
        console.log('  - Elements with "grid" in ID:', Array.from(allGrids).map(el => el.id));
        console.log('  - Elements with "task" in ID:', Array.from(allTasks).map(el => el.id));
        
        const grid = document.getElementById('task-lists-grid');
        console.log('🎯 task-lists-grid element:', grid);
        console.log('🎯 grid innerHTML:', grid ? grid.innerHTML.substring(0, 200) + '...' : 'N/A');
        
        console.log('🔍 Current URL:', window.location.href);
        console.log('🔍 Current page:', window.location.pathname);
        
        if (QuestDashboard.isDashboardPage) {
            console.log('🔧 Manually triggering loadTaskLists...');
            QuestDashboard.loadTaskLists();
        } else {
            console.log('❌ Not on dashboard page, cannot load task lists');
            console.log('❌ Reason:', {
                hasTaskGrid: !!document.getElementById('task-lists-grid'),
                isQuests: !!document.getElementById('quest-stats'),
                isProgress: !!document.getElementById('progress-stats'),
                isAdventure: !!document.getElementById('adventure-map-container')
            });
        }
    };
    
    window.testTaskListAPI = function() {
        console.log('🔧 Testing API directly...');
        const apiUrl = OC.generateUrl('/apps/quest/api/quest-lists');
        console.log('🌐 API URL:', apiUrl);
        
        fetch(apiUrl, {
            method: 'GET',
            headers: {
                'requesttoken': OC.requestToken
            }
        })
        .then(response => {
            console.log('📡 API Response status:', response.status);
            console.log('📡 API Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ API Response data:', data);
            
            if (data.status === 'success') {
                console.log('✅ API Success! Found', data.data.length, 'task lists');
                data.data.forEach((list, index) => {
                    console.log(`  ${index + 1}. ${list.name} (${list.total_tasks} tasks)`);
                });
                
                // Test displayTaskLists with this data
                if (typeof QuestDashboard !== 'undefined' && QuestDashboard.displayTaskLists) {
                    console.log('🎨 Testing displayTaskLists with API data...');
                    QuestDashboard.displayTaskLists(data.data);
                }
            } else {
                console.error('❌ API Error:', data.message);
            }
        })
        .catch(error => {
            console.error('❌ API Call failed:', error);
        });
    };
    
    window.resetQuestSettings = function() {
        console.log('🔧 Resetting Quest Settings...');
        
        try {
            const currentSettings = localStorage.getItem('questSettings');
            console.log('Current settings:', currentSettings);
            
            localStorage.removeItem('questSettings');
            console.log('✅ Settings cleared from localStorage');
            
            // Reload task lists if on dashboard
            if (typeof QuestDashboard !== 'undefined' && QuestDashboard.isDashboardPage) {
                console.log('🔄 Reloading task lists...');
                QuestDashboard.loadTaskLists();
            }
            
            console.log('💡 Settings reset complete! Task lists should now appear.');
        } catch (error) {
            console.error('❌ Error resetting settings:', error);
        }
    };
    
    window.debugQuestSettings = function() {
        console.log('🔧 Debug Quest Settings...');
        
        try {
            const saved = localStorage.getItem('questSettings');
            const settings = saved ? JSON.parse(saved) : {};
            
            console.log('📋 Current settings:', settings);
            console.log('🔍 Settings analysis:', {
                hasIncludedLists: !!settings.includedLists,
                includedListsLength: settings.includedLists ? settings.includedLists.length : 'N/A',
                includedLists: settings.includedLists,
                hasListColors: !!settings.listColors,
                hasListPriorities: !!settings.listPriorities,
                hideCompletedTasks: settings.hideCompletedTasks
            });
            
            if (settings.includedLists && settings.includedLists.length === 0) {
                console.warn('⚠️ includedLists is empty array - this will hide all task lists!');
                console.log('💡 Run resetQuestSettings() to fix this issue');
            }
            
        } catch (error) {
            console.error('❌ Error reading settings:', error);
        }
    };
    
})();

/**
 * CSS for enhanced features (to be included in the main stylesheet)
 */
const enhancedStyles = `
/* Context Menu Styles */
.task-list-context-menu {
    position: absolute;
    background: var(--color-main-background);
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-floating);
    padding: 8px 0;
    z-index: 10000;
    min-width: 180px;
    border: 1px solid var(--color-border);
}

.context-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    cursor: pointer;
    transition: background-color var(--animation-duration-fast);
}

.context-menu-item:hover {
    background: var(--color-background-hover);
}

.context-menu-separator {
    height: 1px;
    background: var(--color-border);
    margin: 4px 0;
}

/* Keyboard Focus Styles */
.task-list-card.keyboard-focused {
    outline: 2px solid var(--color-primary);
    outline-offset: 2px;
}

/* Color Change Animation */
.task-list-card.color-changing {
    animation: pulse 0.3s ease-in-out;
}

/* Hover Delayed Effects */
.task-list-card.hover-delayed::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, var(--color-primary), var(--color-success));
    border-radius: inherit;
    z-index: -1;
    opacity: 0.5;
}

/* Save Notification */
.save-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: var(--color-success);
    color: white;
    padding: 12px 20px;
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-lg);
    z-index: 10000;
    animation: slideInRight 0.3s ease-out;
}

.save-notification.fade-out {
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease-in;
}
`;

// Inject enhanced styles if not already present
if (!document.querySelector('#quest-enhanced-styles')) {
    // Apply enhanced styles via CSS custom properties instead of dynamic styles
    const root = document.documentElement;
    root.classList.add('quest-enhanced-mode');
    
    // Set CSS custom properties for dynamic styling
    root.style.setProperty('--quest-enhanced-shadow', '0 4px 12px rgba(0, 0, 0, 0.15)');
    root.style.setProperty('--quest-enhanced-hover-transform', 'translateY(-2px)');
    root.style.setProperty('--quest-enhanced-transition', 'all 0.2s ease');
}