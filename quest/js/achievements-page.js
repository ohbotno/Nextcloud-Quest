/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

/**
 * Achievement Gallery JavaScript
 * Handles achievement display, filtering, animations, and interactions
 */

(function() {
    'use strict';

    // Achievement Gallery Application
    const AchievementGallery = {
        // Application state
        state: {
            achievements: [],
            categories: {},
            filteredAchievements: [],
            currentFilter: 'all',
            currentCategory: 'all',
            currentView: 'grid',
            searchQuery: '',
            isLoading: true
        },

        // DOM elements
        elements: {},

        // Initialize the application
        init() {
            this.bindElements();
            this.bindEvents();
            this.registerWithQuestApp();
            this.loadAchievements();
        },

        // Bind DOM elements
        bindElements() {
            this.elements = {
                // Main containers
                galleryLoading: document.querySelector('.gallery-loading'),
                achievementCategories: document.querySelector('.achievement-categories'),
                achievementGrid: document.querySelector('.achievement-grid'),
                
                // Controls
                searchInput: document.getElementById('achievement-search'),
                filterSelect: document.getElementById('achievement-filter'),
                viewBtns: document.querySelectorAll('.view-btn'),
                categoryNavLinks: document.querySelectorAll('.category-nav a'),
                
                // Statistics
                progressCircle: document.querySelector('.progress-circle'),
                percentageText: document.querySelector('.percentage-text'),
                unlockedCount: document.querySelector('.unlocked-count'),
                totalCount: document.querySelector('.total-count'),
                rarityItems: document.querySelectorAll('.rarity-item'),
                
                // Modal
                modal: document.getElementById('achievement-modal'),
                modalOverlay: document.querySelector('.modal-overlay'),
                modalClose: document.querySelectorAll('.modal-close'),
                shareBtn: document.querySelector('.share-achievement'),
                
                // Animation
                unlockAnimation: document.getElementById('achievement-unlock-animation'),
                
                // Recent achievements
                recentList: document.querySelector('.recent-list')
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

            // Filter dropdown
            if (this.elements.filterSelect) {
                this.elements.filterSelect.addEventListener('change', (e) => {
                    this.state.currentFilter = e.target.value;
                    this.filterAndRenderAchievements();
                });
            }

            // View toggle buttons
            this.elements.viewBtns.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const view = e.currentTarget.dataset.view;
                    this.switchView(view);
                });
            });

            // Category navigation
            this.elements.categoryNavLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const category = e.currentTarget.dataset.category;
                    this.switchCategory(category);
                });
            });

            // Modal events
            if (this.elements.modalOverlay) {
                this.elements.modalOverlay.addEventListener('click', () => {
                    this.closeModal();
                });
            }

            this.elements.modalClose.forEach(btn => {
                btn.addEventListener('click', () => {
                    this.closeModal();
                });
            });

            if (this.elements.shareBtn) {
                this.elements.shareBtn.addEventListener('click', () => {
                    this.shareAchievement();
                });
            }

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.elements.modal.style.display !== 'none') {
                    this.closeModal();
                }
            });

            // Unlock animation click to close
            if (this.elements.unlockAnimation) {
                this.elements.unlockAnimation.addEventListener('click', () => {
                    this.hideUnlockAnimation();
                });
            }
        },

        // Register with QuestApp for stats updates
        registerWithQuestApp() {
            const self = this;
            
            console.log('🏆 Achievements page: Registering with QuestApp');
            
            // Wait for QuestApp to be available
            function connectToQuestApp() {
                if (typeof window !== 'undefined' && window.QuestApp && window.QuestApp.initialized) {
                    console.log('🏆 Achievements page: QuestApp is ready, registering as consumer');
                    
                    window.QuestApp.registerStatsConsumer('achievements-page', {
                        onUpdate: function(stats) {
                            console.log('🏆 Achievements page: Received stats update from QuestApp:', stats);
                            // Sidebar will be updated automatically by QuestApp's DomUpdater
                        },
                        onError: function(error) {
                            console.error('🏆 Achievements page: QuestApp stats error:', error);
                        },
                        onLoading: function(isLoading) {
                            console.log('🏆 Achievements page: QuestApp loading state:', isLoading);
                        }
                    });
                    
                    // Get current stats immediately if available
                    var currentStats = window.QuestApp.getCurrentStats();
                    if (currentStats) {
                        console.log('🏆 Achievements page: Getting current stats immediately from QuestApp');
                    }
                    
                } else {
                    console.log('🏆 Achievements page: QuestApp not ready yet, retrying...');
                    setTimeout(connectToQuestApp, 500);
                }
            }
            
            // Try to connect immediately or wait for QuestApp ready event
            if (typeof window !== 'undefined' && window.QuestApp && window.QuestApp.initialized) {
                connectToQuestApp();
            } else {
                document.addEventListener('questAppReady', connectToQuestApp);
                setTimeout(connectToQuestApp, 1000); // Fallback
            }
        },

        // Load achievements data from API
        async loadAchievements() {
            try {
                this.state.isLoading = true;
                this.showLoading();

                // Load achievements data
                const response = await fetch(OC.generateUrl('/apps/quest/api/achievements/categories'));
                if (!response.ok) {
                    throw new Error('Failed to load achievements');
                }

                const data = await response.json();
                this.state.achievements = data.achievements || [];
                this.state.categories = data.categories || {};

                // Load recent achievements
                await this.loadRecentAchievements();

                // Update statistics
                this.updateStatistics();

                // Filter and render achievements
                this.filterAndRenderAchievements();

                this.state.isLoading = false;
                this.hideLoading();

            } catch (error) {
                console.error('Error loading achievements:', error);
                this.showError('Failed to load achievements. Please refresh the page.');
                this.state.isLoading = false;
                this.hideLoading();
            }
        },

        // Load recent achievements
        async loadRecentAchievements() {
            try {
                const response = await fetch(OC.generateUrl('/apps/quest/api/achievements/recent'));
                if (!response.ok) return;

                const recentAchievements = await response.json();
                this.renderRecentAchievements(recentAchievements);
            } catch (error) {
                console.error('Error loading recent achievements:', error);
            }
        },

        // Show loading state
        showLoading() {
            if (this.elements.galleryLoading) {
                this.elements.galleryLoading.style.display = 'flex';
            }
            if (this.elements.achievementGrid) {
                this.elements.achievementGrid.style.display = 'none';
            }
            if (this.elements.achievementCategories) {
                this.elements.achievementCategories.style.display = 'none';
            }
        },

        // Hide loading state
        hideLoading() {
            if (this.elements.galleryLoading) {
                this.elements.galleryLoading.style.display = 'none';
            }
            if (this.elements.achievementGrid) {
                this.elements.achievementGrid.style.display = 'grid';
            }
            if (this.elements.achievementCategories) {
                this.elements.achievementCategories.style.display = 'block';
            }
        },

        // Show error message
        showError(message) {
            if (this.elements.galleryLoading) {
                this.elements.galleryLoading.innerHTML = `
                    <div class="error-state">
                        <span class="icon-error"></span>
                        <p>${message}</p>
                    </div>
                `;
            }
        },

        // Filter and render achievements
        filterAndRenderAchievements() {
            this.state.filteredAchievements = this.filterAchievements();
            
            if (this.state.currentCategory === 'all') {
                this.renderAchievementsByCategory();
            } else {
                this.renderAchievementGrid();
            }
        },

        // Filter achievements based on current filters
        filterAchievements() {
            let filtered = [...this.state.achievements];

            // Filter by category
            if (this.state.currentCategory !== 'all') {
                filtered = filtered.filter(achievement => 
                    achievement.category === this.state.currentCategory
                );
            }

            // Filter by unlock status
            switch (this.state.currentFilter) {
                case 'unlocked':
                    filtered = filtered.filter(achievement => achievement.unlocked);
                    break;
                case 'locked':
                    filtered = filtered.filter(achievement => !achievement.unlocked);
                    break;
                case 'in-progress':
                    filtered = filtered.filter(achievement => 
                        !achievement.unlocked && achievement.progress && achievement.progress.percentage > 0
                    );
                    break;
            }

            // Filter by search query
            if (this.state.searchQuery) {
                filtered = filtered.filter(achievement =>
                    achievement.name.toLowerCase().includes(this.state.searchQuery) ||
                    achievement.description.toLowerCase().includes(this.state.searchQuery) ||
                    achievement.category.toLowerCase().includes(this.state.searchQuery)
                );
            }

            return filtered;
        },

        // Render achievements grouped by category
        renderAchievementsByCategory() {
            if (!this.elements.achievementCategories) return;

            const categoryGroups = {};
            
            // Group filtered achievements by category
            this.state.filteredAchievements.forEach(achievement => {
                const category = achievement.category;
                if (!categoryGroups[category]) {
                    categoryGroups[category] = {
                        name: category,
                        achievements: [],
                        total: 0,
                        unlocked: 0
                    };
                }
                categoryGroups[category].achievements.push(achievement);
                categoryGroups[category].total++;
                if (achievement.unlocked) {
                    categoryGroups[category].unlocked++;
                }
            });

            // Calculate percentages
            Object.values(categoryGroups).forEach(category => {
                category.percentage = category.total > 0 
                    ? Math.round((category.unlocked / category.total) * 100) 
                    : 0;
            });

            // Render categories
            const html = Object.values(categoryGroups).map(category => `
                <div class="achievement-category">
                    <div class="category-header">
                        <h3 class="category-title">${this.escapeHtml(category.name)}</h3>
                        <div class="category-progress">
                            <span>${category.unlocked}/${category.total}</span>
                            <div class="category-progress-bar">
                                <div class="category-progress-fill" style="width: ${category.percentage}%"></div>
                            </div>
                            <span>${category.percentage}%</span>
                        </div>
                    </div>
                    <div class="achievement-grid">
                        ${category.achievements.map(achievement => this.renderAchievementCard(achievement)).join('')}
                    </div>
                </div>
            `).join('');

            this.elements.achievementCategories.innerHTML = html;
            this.elements.achievementCategories.style.display = 'block';
            this.elements.achievementGrid.style.display = 'none';

            // Bind achievement card events
            this.bindAchievementCardEvents();
        },

        // Render achievement grid
        renderAchievementGrid() {
            if (!this.elements.achievementGrid) return;

            const html = this.state.filteredAchievements
                .map(achievement => this.renderAchievementCard(achievement))
                .join('');

            this.elements.achievementGrid.innerHTML = html;
            this.elements.achievementGrid.classList.toggle('list-view', this.state.currentView === 'list');
            this.elements.achievementGrid.style.display = 'grid';
            this.elements.achievementCategories.style.display = 'none';

            // Bind achievement card events
            this.bindAchievementCardEvents();
        },

        // Render individual achievement card
        renderAchievementCard(achievement) {
            const iconUrl = OC.generateUrl(`/apps/quest/img/achievements/${achievement.icon}`);
            const unlockedClass = achievement.unlocked ? 'unlocked' : 'locked';
            const rarityClass = `rarity-${achievement.rarity.toLowerCase()}`;
            const listViewClass = this.state.currentView === 'list' ? 'list-view' : '';

            let progressHtml = '';
            if (!achievement.unlocked && achievement.progress) {
                progressHtml = `
                    <div class="achievement-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${achievement.progress.percentage}%"></div>
                        </div>
                        <div class="progress-text">
                            ${achievement.progress.current} / ${achievement.progress.target}
                        </div>
                    </div>
                `;
            }

            let unlockDateHtml = '';
            if (achievement.unlocked && achievement.unlocked_at) {
                const date = new Date(achievement.unlocked_at);
                unlockDateHtml = `
                    <div class="achievement-unlock-date">
                        Unlocked: ${date.toLocaleDateString()}
                    </div>
                `;
            }

            return `
                <div class="achievement-card ${unlockedClass} ${rarityClass} ${listViewClass}" 
                     data-achievement-key="${achievement.key}">
                    <div class="achievement-icon">
                        <img src="${iconUrl}" alt="${this.escapeHtml(achievement.name)}" 
                             onerror="this.src='${OC.generateUrl('/apps/quest/img/achievements/default.svg')}'">
                    </div>
                    <div class="achievement-content">
                        <h4 class="achievement-name">${this.escapeHtml(achievement.name)}</h4>
                        <p class="achievement-description">${this.escapeHtml(achievement.description)}</p>
                        <div class="achievement-meta">
                            <span class="achievement-category-badge">${this.escapeHtml(achievement.category)}</span>
                            <span class="achievement-rarity ${achievement.rarity.toLowerCase()}">${this.escapeHtml(achievement.rarity)}</span>
                        </div>
                        ${progressHtml}
                        ${unlockDateHtml}
                    </div>
                </div>
            `;
        },

        // Bind events to achievement cards
        bindAchievementCardEvents() {
            const cards = document.querySelectorAll('.achievement-card');
            cards.forEach(card => {
                card.addEventListener('click', () => {
                    const achievementKey = card.dataset.achievementKey;
                    this.showAchievementDetail(achievementKey);
                });
            });
        },

        // Show achievement detail modal
        showAchievementDetail(achievementKey) {
            const achievement = this.state.achievements.find(a => a.key === achievementKey);
            if (!achievement) return;

            // Populate modal content
            const modal = this.elements.modal;
            const iconUrl = OC.generateUrl(`/apps/quest/img/achievements/${achievement.icon}`);
            
            modal.querySelector('.achievement-image').src = iconUrl;
            modal.querySelector('.achievement-name').textContent = achievement.name;
            modal.querySelector('.achievement-description').textContent = achievement.description;
            modal.querySelector('.achievement-category').textContent = achievement.category;
            modal.querySelector('.achievement-rarity').textContent = achievement.rarity;
            modal.querySelector('.achievement-rarity').className = `achievement-rarity ${achievement.rarity.toLowerCase()}`;

            // Update rarity glow
            const rarityGlow = modal.querySelector('.rarity-glow');
            rarityGlow.className = `rarity-glow ${achievement.rarity.toLowerCase()}`;

            // Progress bar
            const progressBar = modal.querySelector('.progress-bar');
            const progressFill = modal.querySelector('.progress-fill');
            const progressCurrent = modal.querySelector('.progress-current');
            const progressTarget = modal.querySelector('.progress-target');
            const progressContainer = modal.querySelector('.achievement-progress');

            if (achievement.progress && !achievement.unlocked) {
                progressContainer.style.display = 'block';
                progressFill.style.width = `${achievement.progress.percentage}%`;
                progressCurrent.textContent = achievement.progress.current;
                progressTarget.textContent = achievement.progress.target;
            } else {
                progressContainer.style.display = 'none';
            }

            // Status
            const statusUnlocked = modal.querySelector('.status-unlocked');
            const statusLocked = modal.querySelector('.status-locked');
            const unlockDate = modal.querySelector('.unlock-date');
            const shareBtn = modal.querySelector('.share-achievement');

            if (achievement.unlocked) {
                statusUnlocked.style.display = 'flex';
                statusLocked.style.display = 'none';
                shareBtn.style.display = 'block';
                
                if (achievement.unlocked_at) {
                    const date = new Date(achievement.unlocked_at);
                    unlockDate.textContent = `Unlocked on ${date.toLocaleDateString()}`;
                }
            } else {
                statusUnlocked.style.display = 'none';
                statusLocked.style.display = 'flex';
                shareBtn.style.display = 'none';
            }

            // Show modal
            modal.style.display = 'flex';
            modal.setAttribute('data-achievement-key', achievementKey);
        },

        // Close achievement detail modal
        closeModal() {
            if (this.elements.modal) {
                this.elements.modal.style.display = 'none';
            }
        },

        // Share achievement
        shareAchievement() {
            const achievementKey = this.elements.modal.getAttribute('data-achievement-key');
            const achievement = this.state.achievements.find(a => a.key === achievementKey);
            
            if (!achievement || !achievement.unlocked) return;

            // Create share text
            const shareText = `🏆 I just unlocked "${achievement.name}" in Quest! ${achievement.description}`;
            
            // Use Web Share API if available
            if (navigator.share) {
                navigator.share({
                    title: 'Achievement Unlocked!',
                    text: shareText,
                    url: window.location.href
                }).catch(console.error);
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(shareText).then(() => {
                    OC.Notification.showTemporary('Achievement details copied to clipboard!');
                }).catch(() => {
                    // Final fallback: show share dialog
                    const shareUrl = encodeURIComponent(window.location.href);
                    const shareTextEncoded = encodeURIComponent(shareText);
                    const twitterUrl = `https://twitter.com/intent/tweet?text=${shareTextEncoded}&url=${shareUrl}`;
                    window.open(twitterUrl, '_blank');
                });
            }
        },

        // Switch view (grid/list)
        switchView(view) {
            this.state.currentView = view;
            
            // Update view buttons
            this.elements.viewBtns.forEach(btn => {
                btn.classList.toggle('active', btn.dataset.view === view);
            });

            // Re-render achievements
            this.filterAndRenderAchievements();
        },

        // Switch category
        switchCategory(category) {
            this.state.currentCategory = category;
            
            // Update navigation
            this.elements.categoryNavLinks.forEach(link => {
                link.classList.toggle('active', link.dataset.category === category);
            });

            // Filter and render
            this.filterAndRenderAchievements();
        },

        // Update statistics display
        updateStatistics() {
            const totalAchievements = this.state.achievements.length;
            const unlockedAchievements = this.state.achievements.filter(a => a.unlocked).length;
            const percentage = totalAchievements > 0 ? Math.round((unlockedAchievements / totalAchievements) * 100) : 0;

            // Update progress circle
            if (this.elements.progressCircle) {
                const gradientStop = (percentage * 360) / 100;
                this.elements.progressCircle.style.background = 
                    `conic-gradient(var(--quest-primary) 0deg ${gradientStop}deg, var(--quest-gray-200) ${gradientStop}deg 360deg)`;
            }

            if (this.elements.percentageText) {
                this.elements.percentageText.textContent = `${percentage}%`;
            }

            if (this.elements.unlockedCount) {
                this.elements.unlockedCount.textContent = unlockedAchievements;
            }

            if (this.elements.totalCount) {
                this.elements.totalCount.textContent = totalAchievements;
            }

            // Update rarity breakdown
            const rarityStats = {
                common: { total: 0, unlocked: 0 },
                rare: { total: 0, unlocked: 0 },
                epic: { total: 0, unlocked: 0 },
                legendary: { total: 0, unlocked: 0 }
            };

            this.state.achievements.forEach(achievement => {
                const rarity = achievement.rarity.toLowerCase();
                if (rarityStats[rarity]) {
                    rarityStats[rarity].total++;
                    if (achievement.unlocked) {
                        rarityStats[rarity].unlocked++;
                    }
                }
            });

            this.elements.rarityItems.forEach(item => {
                const rarity = item.classList.contains('common') ? 'common' :
                             item.classList.contains('rare') ? 'rare' :
                             item.classList.contains('epic') ? 'epic' : 'legendary';
                
                const stats = rarityStats[rarity];
                const countElement = item.querySelector('.rarity-count');
                if (countElement) {
                    countElement.textContent = `${stats.unlocked}/${stats.total}`;
                }
            });
        },

        // Render recent achievements
        renderRecentAchievements(recentAchievements) {
            if (!this.elements.recentList) return;

            if (recentAchievements.length === 0) {
                this.elements.recentList.innerHTML = `
                    <div class="no-recent">
                        <span class="icon-info"></span>
                        <small>No recent achievements</small>
                    </div>
                `;
                return;
            }

            const html = recentAchievements.map(achievement => {
                const iconUrl = OC.generateUrl(`/apps/quest/img/achievements/${achievement.icon}`);
                const date = new Date(achievement.unlocked_at);
                
                return `
                    <div class="recent-achievement" data-achievement-key="${achievement.key}">
                        <img src="${iconUrl}" alt="${this.escapeHtml(achievement.name)}" class="recent-icon">
                        <div class="recent-info">
                            <div class="recent-name">${this.escapeHtml(achievement.name)}</div>
                            <div class="recent-date">${date.toLocaleDateString()}</div>
                        </div>
                    </div>
                `;
            }).join('');

            this.elements.recentList.innerHTML = html;

            // Bind click events
            this.elements.recentList.querySelectorAll('.recent-achievement').forEach(item => {
                item.addEventListener('click', () => {
                    const achievementKey = item.dataset.achievementKey;
                    this.showAchievementDetail(achievementKey);
                });
            });
        },

        // Show achievement unlock animation
        showUnlockAnimation(achievement) {
            if (!this.elements.unlockAnimation) return;

            const iconUrl = OC.generateUrl(`/apps/quest/img/achievements/${achievement.icon}`);
            
            this.elements.unlockAnimation.querySelector('.achievement-image').src = iconUrl;
            this.elements.unlockAnimation.querySelector('.unlock-name').textContent = achievement.name;
            this.elements.unlockAnimation.querySelector('.unlock-description').textContent = achievement.description;

            this.elements.unlockAnimation.style.display = 'flex';

            // Auto-hide after animation duration
            setTimeout(() => {
                this.hideUnlockAnimation();
            }, 3000);
        },

        // Hide achievement unlock animation
        hideUnlockAnimation() {
            if (this.elements.unlockAnimation) {
                this.elements.unlockAnimation.style.display = 'none';
            }
        },

        // Utility: Escape HTML
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // Listen for achievement unlocks from other parts of the app
        listenForAchievementUnlocks() {
            // Listen for custom events
            document.addEventListener('achievementUnlocked', (event) => {
                const achievement = event.detail;
                this.showUnlockAnimation(achievement);
                
                // Reload achievements to update the display
                setTimeout(() => {
                    this.loadAchievements();
                }, 3000);
            });
        }
    };

    // Legacy StatsManager integration removed - now using consumer registration in AchievementGallery.registerStatsManagerConsumer()

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize achievements gallery
        AchievementGallery.init();
        AchievementGallery.listenForAchievementUnlocks();
    });

    // Export for global access
    window.AchievementGallery = AchievementGallery;

})();