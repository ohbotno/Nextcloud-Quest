/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

/**
 * Achievement System JavaScript
 * Handles achievement display, filtering, animations, and interactions
 * Updated: Fixed API endpoints to use correct app ID
 * Cache bust: Force new version hash generation
 */

console.log('🏆 achievements.js file loaded!');

(function() {
    'use strict';

    console.log('🏆 achievements.js IIFE executing!');

    // QuestAchievements global object
    window.QuestAchievements = {
        // Application state
        state: {
            achievements: [],
            categories: {},
            filteredAchievements: [],
            currentView: 'grid',
            searchQuery: '',
            selectedCategories: [],
            selectedRarities: [],
            selectedStatuses: [],
            isLoading: true,
            statistics: {}
        },

        // DOM elements cache
        elements: {},

        // API endpoints
        endpoints: {
            achievements: OC.generateUrl('/apps/quest/api/achievements'),
            categories: OC.generateUrl('/apps/quest/api/achievements/categories'),
            recent: OC.generateUrl('/apps/quest/api/achievements/recent'),
            stats: OC.generateUrl('/apps/quest/api/achievements/stats'),
            rarity: (rarity) => OC.generateUrl('/apps/quest/api/achievements/rarity/' + rarity)
        },

        // Initialize the application
        init() {
            console.log('Initializing QuestAchievements...');
            this.bindElements();
            this.bindEvents();
            this.loadAchievements();
        },

        // Bind DOM elements
        bindElements() {
            this.elements = {
                // Loading and content containers
                loading: document.getElementById('achievements-loading'),
                grid: document.getElementById('achievements-grid'),
                list: document.getElementById('achievements-list'),
                empty: document.getElementById('achievements-empty'),
                categoryOverview: document.getElementById('category-overview'),
                
                // Statistics elements
                achievementPercentage: document.getElementById('achievement-percentage'),
                achievementsUnlocked: document.getElementById('achievements-unlocked'),
                latestAchievementName: document.getElementById('latest-achievement-name'),
                latestAchievementDate: document.getElementById('latest-achievement-date'),
                rareAchievements: document.getElementById('rare-achievements'),
                achievementPoints: document.getElementById('achievement-points'),
                
                // Filter and search
                searchInput: document.getElementById('achievement-search'),
                categoryFilter: document.getElementById('category-filter'),
                statusFilter: document.getElementById('status-filter'),
                rarityFilter: document.getElementById('rarity-filter'),
                sortBy: document.getElementById('sort-by'),

                // View toggles
                viewToggleBtns: document.querySelectorAll('.view-toggle-btn'),
                
                // Modal
                modal: document.getElementById('achievement-modal'),
                modalOverlay: document.querySelector('.modal-overlay'),
                closeModalBtns: document.querySelectorAll('.btn-close, #close-achievement-modal-btn'),
                shareBtn: document.getElementById('share-achievement-btn'),
                
                // Modal content
                modalIcon: document.getElementById('modal-achievement-icon'),
                modalRarity: document.getElementById('modal-achievement-rarity'),
                modalName: document.getElementById('modal-achievement-name'),
                modalDescription: document.getElementById('modal-achievement-description'),
                modalCategory: document.getElementById('modal-achievement-category'),
                modalPoints: document.getElementById('modal-achievement-points'),
                modalUnlockDate: document.getElementById('modal-unlock-date'),
                modalUnlockDateItem: document.getElementById('modal-unlock-date-item'),
                modalProgressText: document.getElementById('modal-progress-text'),
                modalProgressBar: document.getElementById('modal-progress-bar'),
                modalProgressPercentage: document.getElementById('modal-progress-percentage'),
                modalStatus: document.getElementById('modal-achievement-status')
            };
        },

        // Bind event listeners
        bindEvents() {
            // Search functionality
            if (this.elements.searchInput) {
                this.elements.searchInput.addEventListener('input', (e) => {
                    this.state.searchQuery = e.target.value.toLowerCase().trim();
                    this.filterAndRenderAchievements();
                });
            }

            // Filter dropdowns
            if (this.elements.categoryFilter) {
                this.elements.categoryFilter.addEventListener('change', (e) => {
                    this.updateCategoryFilter(e.target.value);
                });
            }

            if (this.elements.statusFilter) {
                this.elements.statusFilter.addEventListener('change', (e) => {
                    this.updateStatusFilter(e.target.value);
                });
            }

            if (this.elements.rarityFilter) {
                this.elements.rarityFilter.addEventListener('change', (e) => {
                    this.updateRarityFilter(e.target.value);
                });
            }

            if (this.elements.sortBy) {
                this.elements.sortBy.addEventListener('change', () => {
                    this.filterAndRenderAchievements();
                });
            }

            // View toggle buttons
            this.elements.viewToggleBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const view = e.currentTarget.dataset.view;
                    this.switchView(view);
                });
            });

            // Modal events
            this.elements.closeModalBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    this.closeModal();
                });
            });

            if (this.elements.modalOverlay) {
                this.elements.modalOverlay.addEventListener('click', (e) => {
                    if (e.target === this.elements.modalOverlay) {
                        this.closeModal();
                    }
                });
            }

            if (this.elements.shareBtn) {
                this.elements.shareBtn.addEventListener('click', () => {
                    this.shareAchievement();
                });
            }

            // Escape key to close modal
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.elements.modal && this.elements.modal.style.display !== 'none') {
                    this.closeModal();
                }
            });
        },

        // Load achievements from API
        async loadAchievements() {
            try {
                this.showLoading(true);
                
                // Load all achievements and statistics in parallel
                console.log('🔍 Loading from endpoints:', this.endpoints);
                
                const [achievementsResponse, categoriesResponse, statsResponse] = await Promise.all([
                    fetch(this.endpoints.achievements, {
                        method: 'GET',
                        headers: {
                            'requesttoken': OC.requestToken
                        }
                    }),
                    fetch(this.endpoints.categories, {
                        method: 'GET',
                        headers: {
                            'requesttoken': OC.requestToken
                        }
                    }),
                    fetch(this.endpoints.stats, {
                        method: 'GET',
                        headers: {
                            'requesttoken': OC.requestToken
                        }
                    })
                ]);

                console.log('🔍 Response status:', {
                    achievements: achievementsResponse.status,
                    categories: categoriesResponse.status,
                    stats: statsResponse.status
                });

                if (!achievementsResponse.ok) {
                    const errorText = await achievementsResponse.text();
                    console.error('❌ Achievements endpoint failed:', achievementsResponse.status, achievementsResponse.statusText);
                    console.error('❌ Response body:', errorText);
                    throw new Error(`Failed to load achievements: ${achievementsResponse.status} ${achievementsResponse.statusText}`);
                }
                if (!categoriesResponse.ok) {
                    console.error('❌ Categories endpoint failed:', categoriesResponse.status, categoriesResponse.statusText);
                    throw new Error(`Failed to load categories: ${categoriesResponse.status} ${categoriesResponse.statusText}`);
                }
                if (!statsResponse.ok) {
                    console.error('❌ Stats endpoint failed:', statsResponse.status, statsResponse.statusText);
                    throw new Error(`Failed to load stats: ${statsResponse.status} ${statsResponse.statusText}`);
                }

                const achievementsData = await achievementsResponse.json();
                const categoriesData = await categoriesResponse.json();
                const statsData = await statsResponse.json();

                console.log('🔍 Response data:', {
                    achievements: achievementsData,
                    categories: categoriesData,
                    stats: statsData
                });

                // Store data in state
                this.state.achievements = achievementsData.achievements || [];
                this.state.categories = categoriesData.categories || {};
                this.state.statistics = statsData.data || {};
                this.state.filteredAchievements = [...this.state.achievements];
                this.state.isLoading = false;

                // Update UI
                this.updateStatistics();
                this.renderCategoryOverview();
                this.filterAndRenderAchievements();
                this.showLoading(false);

                console.log('Achievements loaded:', this.state.achievements.length);
            } catch (error) {
                console.error('Error loading achievements:', error);
                this.showError('Failed to load achievements');
                this.showLoading(false);
            }
        },

        // Show/hide loading state
        showLoading(show) {
            if (this.elements.loading) {
                this.elements.loading.style.display = show ? 'block' : 'none';
            }
            if (this.elements.grid) {
                this.elements.grid.style.display = show ? 'none' : (this.state.currentView === 'grid' ? 'grid' : 'none');
            }
            if (this.elements.list) {
                this.elements.list.style.display = show ? 'none' : (this.state.currentView === 'list' ? 'flex' : 'none');
            }
        },

        // Update statistics display
        updateStatistics() {
            console.log('📊 Updating statistics with data:', this.state.statistics);
            console.log('📊 Total achievements loaded:', this.state.achievements.length);

            // Calculate stats from achievements if API stats are missing or incomplete
            const unlockedAchievements = this.state.achievements.filter(a => a.unlocked);
            const totalAchievements = this.state.achievements.length;
            const unlockedCount = unlockedAchievements.length;
            const percentage = totalAchievements > 0 ? Math.round((unlockedCount / totalAchievements) * 100) : 0;

            // Update overall progress
            if (this.elements.achievementPercentage) {
                this.elements.achievementPercentage.textContent = percentage + '%';
                console.log('📊 Set percentage to:', percentage + '%');
            }

            if (this.elements.achievementsUnlocked) {
                this.elements.achievementsUnlocked.textContent = `${unlockedCount} of ${totalAchievements} unlocked`;
                console.log('📊 Set unlocked to:', `${unlockedCount} of ${totalAchievements} unlocked`);
            }

            // Find latest achievement
            if (unlockedAchievements.length > 0) {
                const latest = unlockedAchievements.sort((a, b) => new Date(b.unlocked_at) - new Date(a.unlocked_at))[0];
                if (this.elements.latestAchievementName) {
                    this.elements.latestAchievementName.textContent = latest.name;
                }
                if (this.elements.latestAchievementDate) {
                    this.elements.latestAchievementDate.textContent = this.formatDate(latest.unlocked_at);
                }
            }

            // Count rare achievements
            const rareCount = unlockedAchievements.filter(a =>
                ['rare', 'epic', 'legendary', 'mythic'].includes(a.rarity.toLowerCase())
            ).length;

            if (this.elements.rareAchievements) {
                this.elements.rareAchievements.textContent = rareCount.toString();
                console.log('📊 Set rare count to:', rareCount);
            }

            // Calculate total achievement points
            const totalPoints = unlockedAchievements
                .reduce((sum, a) => sum + (a.achievement_points || 0), 0);

            if (this.elements.achievementPoints) {
                this.elements.achievementPoints.textContent = totalPoints.toString();
                console.log('📊 Set points to:', totalPoints);
            }
        },

        // Render category overview
        renderCategoryOverview() {
            if (!this.elements.categoryOverview) return;
            
            const categories = this.state.categories;
            let html = '';
            
            Object.keys(categories).forEach(categoryName => {
                const category = categories[categoryName];
                const icon = this.getCategoryIcon(categoryName);
                
                html += `
                    <div class="category-card" data-category="${categoryName}">
                        <div class="category-icon">${icon}</div>
                        <div class="category-name">${categoryName}</div>
                        <div class="category-progress">
                            ${category.unlocked}/${category.total} (${category.percentage}%)
                        </div>
                    </div>
                `;
            });
            
            this.elements.categoryOverview.innerHTML = html;
            
            // Add click handlers for category cards
            this.elements.categoryOverview.querySelectorAll('.category-card').forEach(card => {
                card.addEventListener('click', () => {
                    const category = card.dataset.category;
                    this.filterByCategory(category);
                });
            });
        },

        // Get category icon
        getCategoryIcon(categoryName) {
            const icons = {
                'Task Master': '📋',
                'Streak Keeper': '🔥',
                'Level Champion': '⭐',
                'Speed Demon': '⚡',
                'Consistency Master': '🎯',
                'Time Master': '⏰',
                'Priority Master': '🎖️',
                'Special Achievements': '🏆',
                'Endurance Titan': '💪',
                'World Conqueror': '🌍',
                'Category Specialist': '🎓',
                'Time Lord': '⏳',
                'Extreme Challenges': '💥',
                'Health Master': '❤️',
                'XP Legends': '✨',
                'Statistical Marvels': '📊',
                'Rare & Secret': '🔮',
                'Community & Social': '👥'
            };
            return icons[categoryName] || '🏆';
        },

        // Filter and render achievements
        filterAndRenderAchievements() {
            this.filterAchievements();
            this.sortAchievements();
            this.renderAchievements();
        },

        // Filter achievements based on current filters
        filterAchievements() {
            this.state.filteredAchievements = this.state.achievements.filter(achievement => {
                // Search filter
                if (this.state.searchQuery) {
                    const searchMatch = achievement.name.toLowerCase().includes(this.state.searchQuery) ||
                                      achievement.description.toLowerCase().includes(this.state.searchQuery) ||
                                      achievement.category.toLowerCase().includes(this.state.searchQuery);
                    if (!searchMatch) return false;
                }
                
                // Category filter
                if (this.state.selectedCategories.length > 0 && !this.state.selectedCategories.includes('all')) {
                    if (!this.state.selectedCategories.includes(achievement.category)) return false;
                }
                
                // Rarity filter
                if (this.state.selectedRarities.length > 0) {
                    if (!this.state.selectedRarities.includes(achievement.rarity.toLowerCase())) return false;
                }
                
                // Status filter
                if (this.state.selectedStatuses.length > 0) {
                    // Use backend status field if available, otherwise calculate
                    const status = achievement.status || (achievement.unlocked ? 'unlocked' : (achievement.progress_percentage > 0 ? 'in-progress' : 'locked'));
                    if (!this.state.selectedStatuses.includes(status)) return false;
                }

                return true;
            });
        },

        // Sort achievements
        sortAchievements() {
            const sortBy = this.elements.sortBy ? this.elements.sortBy.value : 'name';
            
            this.state.filteredAchievements.sort((a, b) => {
                switch (sortBy) {
                    case 'rarity':
                        const rarityOrder = { 'common': 1, 'rare': 2, 'epic': 3, 'legendary': 4, 'mythic': 5 };
                        return (rarityOrder[b.rarity.toLowerCase()] || 0) - (rarityOrder[a.rarity.toLowerCase()] || 0);
                    
                    case 'progress':
                        return (b.progress_percentage || 0) - (a.progress_percentage || 0);
                    
                    case 'unlocked':
                        if (a.unlocked && !b.unlocked) return -1;
                        if (!a.unlocked && b.unlocked) return 1;
                        if (a.unlocked && b.unlocked) {
                            return new Date(b.unlocked_at) - new Date(a.unlocked_at);
                        }
                        return 0;
                    
                    case 'category':
                        const categoryCompare = a.category.localeCompare(b.category);
                        if (categoryCompare !== 0) return categoryCompare;
                        return a.name.localeCompare(b.name);
                    
                    case 'name':
                    default:
                        return a.name.localeCompare(b.name);
                }
            });
        },

        // Render achievements
        renderAchievements() {
            if (this.state.currentView === 'grid') {
                this.renderAchievementGrid();
            } else {
                this.renderAchievementList();
            }
            
            // Show empty state if no achievements
            if (this.elements.empty) {
                this.elements.empty.style.display = this.state.filteredAchievements.length === 0 ? 'block' : 'none';
            }
        },

        // Render achievement grid
        renderAchievementGrid() {
            if (!this.elements.grid) return;
            
            let html = '';
            
            this.state.filteredAchievements.forEach(achievement => {
                // Use backend status field if available, otherwise calculate
                const status = achievement.status || (achievement.unlocked ? 'unlocked' : (achievement.progress_percentage > 0 ? 'in-progress' : 'locked'));
                const rarityClass = achievement.rarity.toLowerCase();
                const progressPercentage = achievement.progress_percentage || 0;
                
                html += `
                    <div class="achievement-card ${status}" data-rarity="${rarityClass}" data-achievement="${achievement.key}">
                        <div class="achievement-card-header">
                            <div class="achievement-icon">${this.getAchievementIcon(achievement.icon)}</div>
                            <div class="achievement-rarity ${rarityClass}">${achievement.rarity}</div>
                        </div>
                        <div class="achievement-card-body">
                            <h3 class="achievement-name">${achievement.name}</h3>
                            <p class="achievement-description">${achievement.description}</p>
                            <div class="achievement-progress">
                                <div class="progress-info">
                                    <span class="progress-text">${progressPercentage.toFixed(1)}%</span>
                                    <span class="progress-percentage">${achievement.milestone || ''}</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                                </div>
                            </div>
                            <div class="achievement-status ${status}">
                                ${this.getStatusText(status, achievement)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            this.elements.grid.innerHTML = html;
            
            // Add click handlers
            this.elements.grid.querySelectorAll('.achievement-card').forEach(card => {
                card.addEventListener('click', () => {
                    const achievementKey = card.dataset.achievement;
                    const achievement = this.state.achievements.find(a => a.key === achievementKey);
                    if (achievement) {
                        this.showAchievementModal(achievement);
                    }
                });
            });
        },

        // Render achievement list
        renderAchievementList() {
            if (!this.elements.list) return;
            
            let html = '';
            
            this.state.filteredAchievements.forEach(achievement => {
                // Use backend status field if available, otherwise calculate
                const status = achievement.status || (achievement.unlocked ? 'unlocked' : (achievement.progress_percentage > 0 ? 'in-progress' : 'locked'));
                const progressPercentage = achievement.progress_percentage || 0;
                
                html += `
                    <div class="achievement-list-item ${status}" data-achievement="${achievement.key}">
                        <div class="achievement-list-icon">${this.getAchievementIcon(achievement.icon)}</div>
                        <div class="achievement-list-content">
                            <h3 class="achievement-list-name">${achievement.name}</h3>
                            <p class="achievement-list-description">${achievement.description}</p>
                            <div class="achievement-list-meta">
                                <span>Category: ${achievement.category}</span>
                                <span>Rarity: ${achievement.rarity}</span>
                                ${achievement.unlocked ? `<span>Unlocked: ${this.formatDate(achievement.unlocked_at)}</span>` : ''}
                            </div>
                        </div>
                        <div class="achievement-list-progress">
                            <div class="progress-percentage">${progressPercentage.toFixed(1)}%</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${progressPercentage}%"></div>
                            </div>
                            <div class="achievement-status ${status}">
                                ${this.getStatusText(status, achievement)}
                            </div>
                        </div>
                    </div>
                `;
            });
            
            this.elements.list.innerHTML = html;
            
            // Add click handlers
            this.elements.list.querySelectorAll('.achievement-list-item').forEach(item => {
                item.addEventListener('click', () => {
                    const achievementKey = item.dataset.achievement;
                    const achievement = this.state.achievements.find(a => a.key === achievementKey);
                    if (achievement) {
                        this.showAchievementModal(achievement);
                    }
                });
            });
        },

        // Get achievement icon (handle SVG or emoji)
        getAchievementIcon(icon) {
            // If it's an SVG file, return as image with fallback, otherwise return as emoji
            if (icon && icon.includes('.svg')) {
                const iconUrl = OC.imagePath('quest', 'achievements/' + icon);
                const fallbackUrl = OC.imagePath('quest', 'achievements/default.svg');
                return `<img src="${iconUrl}" alt="Achievement icon" class="achievement-icon-img" onerror="this.src='${fallbackUrl}'">`;
            }
            return icon || '🏆';
        },

        // Get status text
        getStatusText(status, achievement) {
            switch (status) {
                case 'unlocked':
                    return `✓ Unlocked ${this.formatDate(achievement.unlocked_at)}`;
                case 'completed':
                    return '⭐ Completed';
                case 'in-progress':
                    return 'In Progress';
                case 'locked':
                default:
                    return '🔒 Locked';
            }
        },

        // Switch view (grid/list)
        switchView(view) {
            this.state.currentView = view;
            
            // Update button states
            this.elements.viewToggleBtns.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });
            
            // Show/hide appropriate containers
            if (this.elements.grid) {
                this.elements.grid.style.display = view === 'grid' ? 'grid' : 'none';
            }
            if (this.elements.list) {
                this.elements.list.style.display = view === 'list' ? 'flex' : 'none';
            }
        },

        // Update category filter
        updateCategoryFilter(value) {
            if (value === 'all') {
                this.state.selectedCategories = [];
            } else {
                this.state.selectedCategories = [value];
            }
            this.filterAndRenderAchievements();
        },

        // Update status filter
        updateStatusFilter(value) {
            if (value === 'all') {
                this.state.selectedStatuses = [];
            } else {
                this.state.selectedStatuses = [value];
            }
            this.filterAndRenderAchievements();
        },

        // Update rarity filter
        updateRarityFilter(value) {
            if (value === 'all') {
                this.state.selectedRarities = [];
            } else {
                this.state.selectedRarities = [value];
            }
            this.filterAndRenderAchievements();
        },

        // Filter by category (from category cards)
        filterByCategory(category) {
            this.state.selectedCategories = [category];
            if (this.elements.categoryFilter) {
                this.elements.categoryFilter.value = category;
            }
            this.filterAndRenderAchievements();
        },


        // Show achievement modal
        showAchievementModal(achievement) {
            if (!this.elements.modal) return;
            
            // Populate modal content
            if (this.elements.modalIcon) {
                this.elements.modalIcon.innerHTML = this.getAchievementIcon(achievement.icon);
            }
            if (this.elements.modalRarity) {
                this.elements.modalRarity.textContent = achievement.rarity;
                this.elements.modalRarity.className = `achievement-rarity-badge ${achievement.rarity.toLowerCase()}`;
            }
            if (this.elements.modalName) {
                this.elements.modalName.textContent = achievement.name;
            }
            if (this.elements.modalDescription) {
                this.elements.modalDescription.textContent = achievement.description;
            }
            if (this.elements.modalCategory) {
                this.elements.modalCategory.textContent = achievement.category;
            }
            if (this.elements.modalPoints) {
                this.elements.modalPoints.textContent = achievement.achievement_points || 0;
            }
            
            // Show/hide unlock date
            if (achievement.unlocked) {
                if (this.elements.modalUnlockDate) {
                    this.elements.modalUnlockDate.textContent = this.formatDate(achievement.unlocked_at);
                }
                if (this.elements.modalUnlockDateItem) {
                    this.elements.modalUnlockDateItem.style.display = 'flex';
                }
            } else {
                if (this.elements.modalUnlockDateItem) {
                    this.elements.modalUnlockDateItem.style.display = 'none';
                }
            }
            
            // Progress information
            const progressPercentage = achievement.progress_percentage || 0;
            const current = achievement.progress_current || 0;
            const target = achievement.progress_target || achievement.milestone || 0;
            
            if (this.elements.modalProgressText) {
                this.elements.modalProgressText.textContent = target > 0 ? `${current} / ${target}` : 'N/A';
            }
            if (this.elements.modalProgressBar) {
                this.elements.modalProgressBar.style.width = progressPercentage + '%';
            }
            if (this.elements.modalProgressPercentage) {
                this.elements.modalProgressPercentage.textContent = progressPercentage.toFixed(1) + '%';
            }
            
            // Status
            const status = achievement.unlocked ? 'unlocked' : (progressPercentage > 0 ? 'in-progress' : 'locked');
            if (this.elements.modalStatus) {
                this.elements.modalStatus.className = `achievement-status ${status}`;
                this.elements.modalStatus.textContent = this.getStatusText(status, achievement);
            }
            
            // Show/hide share button
            if (this.elements.shareBtn) {
                this.elements.shareBtn.style.display = achievement.unlocked ? 'inline-flex' : 'none';
            }
            
            // Show modal
            this.elements.modal.style.display = 'flex';
            document.body.classList.add('modal-open');
        },

        // Close modal
        closeModal() {
            if (this.elements.modal) {
                this.elements.modal.style.display = 'none';
                document.body.classList.remove('modal-open');
            }
        },

        // Share achievement
        shareAchievement() {
            // Implementation for sharing achievement
            if (navigator.share) {
                navigator.share({
                    title: 'Achievement Unlocked!',
                    text: `I just unlocked an achievement in Nextcloud Quest!`,
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                const text = 'I just unlocked an achievement in Nextcloud Quest!';
                navigator.clipboard.writeText(text).then(() => {
                    OC.Notification.showTemporary('Achievement link copied to clipboard!');
                });
            }
        },

        // Show error message
        showError(message) {
            OC.Notification.showTemporary(message, { type: 'error' });
        },

        // Format date
        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
    };

    // Auto-initialize if DOM is ready and on achievements page
    function initIfAchievementsPage() {
        console.log('🔍 Checking if should initialize achievements...');
        console.log('🔍 Current pathname:', window.location.pathname);
        console.log('🔍 achievements-grid element:', document.getElementById('achievements-grid'));

        if (window.location.pathname.includes('/achievements') || document.getElementById('achievements-grid')) {
            console.log('✅ Initializing achievements page!');
            window.QuestAchievements.init();
        } else {
            console.log('❌ Not achievements page, skipping initialization');
        }
    }

    if (document.readyState === 'loading') {
        console.log('📄 DOM still loading, waiting for DOMContentLoaded...');
        document.addEventListener('DOMContentLoaded', initIfAchievementsPage);
    } else {
        console.log('📄 DOM already loaded, initializing immediately...');
        initIfAchievementsPage();
    }

})();