<template>
    <div class="achievement-gallery">
        <div class="gallery-header">
            <div class="gallery-title">
                <h3>{{ t('nextcloudquest', 'Achievement Gallery') }}</h3>
                <div class="achievement-stats">
                    <span class="stats-text">
                        {{ unlockedAchievements.length }} / {{ achievements.length }} 
                        ({{ Math.round((unlockedAchievements.length / achievements.length) * 100) }}%)
                    </span>
                    <div class="stats-bar">
                        <div 
                            class="stats-fill"
                            :style="{ width: `${(unlockedAchievements.length / achievements.length) * 100}%` }"
                        ></div>
                    </div>
                </div>
            </div>
            
            <div class="gallery-filters">
                <button 
                    @click="activeFilter = 'all'"
                    :class="{ active: activeFilter === 'all' }"
                    class="filter-btn"
                >
                    {{ t('nextcloudquest', 'All') }} ({{ achievements.length }})
                </button>
                <button 
                    @click="activeFilter = 'unlocked'"
                    :class="{ active: activeFilter === 'unlocked' }"
                    class="filter-btn"
                >
                    {{ t('nextcloudquest', 'Unlocked') }} ({{ unlockedAchievements.length }})
                </button>
                <button 
                    @click="activeFilter = 'locked'"
                    :class="{ active: activeFilter === 'locked' }"
                    class="filter-btn"
                >
                    {{ t('nextcloudquest', 'Locked') }} ({{ lockedAchievements.length }})
                </button>
            </div>
        </div>
        
        <div class="achievement-categories">
            <div 
                v-for="category in achievementCategories"
                :key="category.name"
                class="category-section"
            >
                <div class="category-header">
                    <h4>{{ category.name }}</h4>
                    <div class="category-progress">
                        {{ getCategoryProgress(category) }}
                    </div>
                </div>
                
                <div class="achievements-grid">
                    <div
                        v-for="achievement in getFilteredAchievements(category.achievements)"
                        :key="achievement.key"
                        class="achievement-card"
                        :class="{
                            unlocked: achievement.status === 'unlocked',
                            completed: achievement.status === 'completed',
                            locked: achievement.status === 'locked',
                            recent: isRecentlyUnlocked(achievement)
                        }"
                        @click="showAchievementDetails(achievement)"
                    >
                        <div class="achievement-icon">
                            <img
                                v-if="achievement.status !== 'locked' || showLockedIcons"
                                :src="getAchievementIcon(achievement.icon)"
                                :alt="achievement.name"
                                loading="lazy"
                            />
                            <div v-else class="locked-icon">🔒</div>
                        </div>

                        <div class="achievement-info">
                            <div class="achievement-name">
                                {{ achievement.status !== 'locked' || showLockedNames ? achievement.name : '???' }}
                            </div>
                            <div class="achievement-description">
                                {{ achievement.status !== 'locked' || showLockedDescriptions ? achievement.description : 'Complete more tasks to unlock' }}
                            </div>
                            <div v-if="achievement.status === 'unlocked'" class="achievement-date">
                                {{ t('nextcloudquest', 'Unlocked {date}', { date: formatDate(achievement.unlocked_at) }) }}
                            </div>
                            <div v-else-if="achievement.status === 'completed'" class="achievement-completed-text">
                                {{ t('nextcloudquest', 'Completed - Complete a task to unlock!') }}
                            </div>
                        </div>

                        <div class="achievement-status">
                            <div v-if="achievement.status === 'unlocked'" class="status-unlocked">✓</div>
                            <div v-else-if="achievement.status === 'completed'" class="status-completed">⭐</div>
                            <div v-else class="status-locked">🔒</div>
                        </div>
                        
                        <!-- Progress indicator for locked achievements with progress -->
                        <div
                            v-if="achievement.status === 'locked' && achievement.progress_percentage !== undefined && achievement.progress_percentage > 0"
                            class="achievement-progress"
                        >
                            <div class="progress-bar">
                                <div
                                    class="progress-fill"
                                    :style="{ width: `${achievement.progress_percentage}%` }"
                                ></div>
                            </div>
                            <div class="progress-text">
                                {{ Math.round(achievement.progress_percentage) }}%
                            </div>
                        </div>
                        
                        <!-- Rarity indicator -->
                        <div class="achievement-rarity" :class="achievement.rarity">
                            <div class="rarity-indicator"></div>
                        </div>
                        
                        <!-- New/Recent indicator -->
                        <div v-if="isRecentlyUnlocked(achievement)" class="new-badge">
                            {{ t('nextcloudquest', 'NEW!') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Achievement Details Modal -->
        <div v-if="selectedAchievement" class="achievement-modal" @click="closeModal">
            <div class="modal-content" @click.stop>
                <div class="modal-header">
                    <button @click="closeModal" class="close-btn">×</button>
                </div>
                
                <div class="modal-body">
                    <div class="achievement-large-icon">
                        <img 
                            v-if="selectedAchievement.unlocked"
                            :src="getAchievementIcon(selectedAchievement.icon)"
                            :alt="selectedAchievement.name"
                        />
                        <div v-else class="locked-large-icon">🔒</div>
                    </div>
                    
                    <div class="achievement-details">
                        <h3 class="achievement-title">
                            {{ selectedAchievement.unlocked ? selectedAchievement.name : 'Hidden Achievement' }}
                        </h3>
                        
                        <p class="achievement-desc">
                            {{ selectedAchievement.unlocked ? selectedAchievement.description : 'Complete more tasks to reveal this achievement.' }}
                        </p>
                        
                        <div v-if="selectedAchievement.unlocked" class="achievement-meta">
                            <div class="meta-item">
                                <span class="meta-label">{{ t('nextcloudquest', 'Unlocked:') }}</span>
                                <span class="meta-value">{{ formatFullDate(selectedAchievement.unlocked_at) }}</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">{{ t('nextcloudquest', 'Rarity:') }}</span>
                                <span class="meta-value rarity" :class="selectedAchievement.rarity">
                                    {{ getRarityName(selectedAchievement.rarity) }}
                                </span>
                            </div>
                        </div>
                        
                        <div v-if="selectedAchievement.tips" class="achievement-tips">
                            <h4>{{ t('nextcloudquest', 'Tips:') }}</h4>
                            <ul>
                                <li v-for="tip in selectedAchievement.tips" :key="tip">{{ tip }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Empty State -->
        <div v-if="getFilteredAchievements().length === 0" class="empty-state">
            <div class="empty-icon">🏆</div>
            <div class="empty-title">
                {{ activeFilter === 'unlocked' ? t('nextcloudquest', 'No achievements unlocked yet') : t('nextcloudquest', 'No achievements found') }}
            </div>
            <div class="empty-message">
                {{ activeFilter === 'unlocked' ? t('nextcloudquest', 'Complete some tasks to start earning achievements!') : t('nextcloudquest', 'Try adjusting your filter.') }}
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
    name: 'AchievementGallery',
    
    data() {
        return {
            activeFilter: 'all',
            selectedAchievement: null,
            showLockedIcons: false,
            showLockedNames: false,
            showLockedDescriptions: false,
            
            achievementCategories: [
                {
                    name: this.t('nextcloudquest', 'Getting Started'),
                    achievements: ['first_task', 'tasks_10']
                },
                {
                    name: this.t('nextcloudquest', 'Task Mastery'),
                    achievements: ['tasks_100', 'tasks_1000', 'perfect_day']
                },
                {
                    name: this.t('nextcloudquest', 'Streaks'),
                    achievements: ['streak_7', 'streak_30', 'streak_100']
                },
                {
                    name: this.t('nextcloudquest', 'Levels'),
                    achievements: ['level_5', 'level_10', 'level_25', 'level_50', 'level_100']
                },
                {
                    name: this.t('nextcloudquest', 'Time-based'),
                    achievements: ['early_bird', 'night_owl', 'weekend_warrior']
                },
                {
                    name: this.t('nextcloudquest', 'Special'),
                    achievements: ['speed_demon']
                }
            ]
        }
    },
    
    computed: {
        ...mapState('quest', ['achievements', 'loading']),
        ...mapGetters('quest', ['unlockedAchievements', 'lockedAchievements']),
        
        achievementsWithRarity() {
            return this.achievements.map(achievement => ({
                ...achievement,
                rarity: this.getAchievementRarity(achievement.key),
                tips: this.getAchievementTips(achievement.key)
            }))
        }
    },
    
    methods: {
        getFilteredAchievements(categoryAchievements = null) {
            let achievements = categoryAchievements 
                ? this.achievementsWithRarity.filter(a => categoryAchievements.includes(a.key))
                : this.achievementsWithRarity
            
            switch (this.activeFilter) {
                case 'unlocked':
                    return achievements.filter(a => a.unlocked)
                case 'locked':
                    return achievements.filter(a => !a.unlocked)
                default:
                    return achievements
            }
        },
        
        getCategoryProgress(category) {
            const categoryAchievements = this.achievementsWithRarity.filter(a => 
                category.achievements.includes(a.key)
            )
            const unlocked = categoryAchievements.filter(a => a.unlocked).length
            const total = categoryAchievements.length
            
            return `${unlocked}/${total}`
        },
        
        getAchievementIcon(icon) {
            return `/apps/quest/img/achievements/${icon}`
        },
        
        getAchievementRarity(key) {
            const rarityMap = {
                'first_task': 'common',
                'tasks_10': 'common',
                'streak_7': 'common',
                'level_5': 'common',
                'early_bird': 'uncommon',
                'night_owl': 'uncommon',
                'tasks_100': 'uncommon',
                'streak_30': 'uncommon',
                'level_10': 'uncommon',
                'weekend_warrior': 'rare',
                'level_25': 'rare',
                'speed_demon': 'rare',
                'perfect_day': 'epic',
                'tasks_1000': 'epic',
                'streak_100': 'epic',
                'level_50': 'epic',
                'level_100': 'legendary'
            }
            
            return rarityMap[key] || 'common'
        },
        
        getAchievementTips(key) {
            const tipsMap = {
                'first_task': [this.t('nextcloudquest', 'Complete any task in Nextcloud Tasks')],
                'tasks_10': [this.t('nextcloudquest', 'Focus on completing small, manageable tasks')],
                'streak_7': [this.t('nextcloudquest', 'Complete at least one task every day for a week')],
                'streak_30': [this.t('nextcloudquest', 'Build a daily habit of task completion')],
                'perfect_day': [this.t('nextcloudquest', 'Plan your day carefully and complete all scheduled tasks')],
                'early_bird': [this.t('nextcloudquest', 'Set an alarm and tackle tasks first thing in the morning')],
                'night_owl': [this.t('nextcloudquest', 'Complete tasks during late evening hours')],
                'speed_demon': [this.t('nextcloudquest', 'Batch similar tasks together for efficiency')]
            }
            
            return tipsMap[key] || []
        },
        
        getRarityName(rarity) {
            const names = {
                'common': this.t('nextcloudquest', 'Common'),
                'uncommon': this.t('nextcloudquest', 'Uncommon'),
                'rare': this.t('nextcloudquest', 'Rare'),
                'epic': this.t('nextcloudquest', 'Epic'),
                'legendary': this.t('nextcloudquest', 'Legendary')
            }
            
            return names[rarity] || this.t('nextcloudquest', 'Common')
        },
        
        isRecentlyUnlocked(achievement) {
            if (!achievement.unlocked || !achievement.unlocked_at) return false
            
            const unlockedDate = new Date(achievement.unlocked_at)
            const daysSince = (Date.now() - unlockedDate.getTime()) / (1000 * 60 * 60 * 24)
            
            return daysSince <= 7 // Consider achievements unlocked in last 7 days as "recent"
        },
        
        showAchievementDetails(achievement) {
            this.selectedAchievement = achievement
        },
        
        closeModal() {
            this.selectedAchievement = null
        },
        
        formatDate(dateString) {
            if (!dateString) return ''
            return new Date(dateString).toLocaleDateString()
        },
        
        formatFullDate(dateString) {
            if (!dateString) return ''
            return new Date(dateString).toLocaleString()
        }
    },
    
    mounted() {
        // Load settings for showing locked achievement info
        // In a real implementation, this would come from user settings
        this.showLockedIcons = false
        this.showLockedNames = false
        this.showLockedDescriptions = false
    }
}
</script>

<style scoped>
.achievement-gallery {
    width: 100%;
}

.gallery-header {
    margin-bottom: 25px;
}

.gallery-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.gallery-title h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
}

.achievement-stats {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 5px;
}

.stats-text {
    font-size: 14px;
    color: var(--color-text-lighter);
    font-weight: 500;
}

.stats-bar {
    width: 150px;
    height: 6px;
    background: var(--color-border);
    border-radius: 3px;
    overflow: hidden;
}

.stats-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-primary), var(--color-success));
    border-radius: inherit;
    transition: width 0.6s ease-out;
}

.gallery-filters {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 8px 16px;
    border: 1px solid var(--color-border);
    background: var(--color-main-background);
    color: var(--color-main-text);
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.filter-btn:hover {
    background: var(--color-background-hover);
}

.filter-btn.active {
    background: var(--color-primary);
    color: white;
    border-color: var(--color-primary);
}

.achievement-categories {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.category-section {
    background: var(--color-main-background);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--color-border);
}

.category-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.category-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
}

.category-progress {
    font-size: 14px;
    color: var(--color-text-lighter);
    font-weight: 500;
}

.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
}

.achievement-card {
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 15px 15px 28px;
    min-height: 90px;
    border: 2px solid var(--color-border);
    border-radius: 8px;
    background: var(--color-background-hover);
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: visible;
}

.achievement-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.achievement-card.unlocked {
    border-color: var(--color-success);
    background: var(--color-success-background);
}

.achievement-card.completed {
    border-color: #f59e0b;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(251, 191, 36, 0.05));
    box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
}

.achievement-card.locked {
    opacity: 0.7;
    filter: grayscale(0.5);
}

.achievement-card.recent {
    animation: glow 2s infinite alternate;
}

.achievement-icon {
    width: 48px;
    height: 48px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    overflow: hidden;
}

.achievement-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.locked-icon {
    font-size: 24px;
    color: var(--color-text-lighter);
}

.achievement-info {
    flex: 1;
    min-width: 0;
}

.achievement-name {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 4px;
    word-wrap: break-word;
}

.achievement-description {
    font-size: 13px;
    color: var(--color-text-lighter);
    line-height: 1.4;
    margin-bottom: 4px;
}

.achievement-date {
    font-size: 11px;
    color: var(--color-text-lighter);
    font-style: italic;
}

.achievement-completed-text {
    font-size: 11px;
    color: #f59e0b;
    font-weight: 600;
    font-style: italic;
}

.achievement-status {
    flex-shrink: 0;
    font-size: 20px;
}

.status-unlocked {
    color: var(--color-success);
}

.status-completed {
    color: #f59e0b;
    font-size: 22px;
    animation: pulse 2s infinite;
}

.status-locked {
    color: var(--color-text-lighter);
}

.achievement-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--color-border);
}

.progress-fill {
    height: 100%;
    background: var(--color-primary);
    transition: width 0.6s ease-out;
}

.progress-text {
    position: absolute;
    bottom: 8px;
    right: 10px;
    font-size: 10px;
    color: var(--color-text-lighter);
    background: var(--color-main-background);
    padding: 2px 4px;
    border-radius: 2px;
}

.achievement-rarity {
    position: absolute;
    top: 5px;
    right: 5px;
}

.rarity-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.achievement-rarity.common .rarity-indicator { background: #6c757d; }
.achievement-rarity.uncommon .rarity-indicator { background: #28a745; }
.achievement-rarity.rare .rarity-indicator { background: #007bff; }
.achievement-rarity.epic .rarity-indicator { background: #6f42c1; }
.achievement-rarity.legendary .rarity-indicator { 
    background: #fd7e14; 
    box-shadow: 0 0 10px rgba(253, 126, 20, 0.5);
}

.new-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: var(--color-error);
    color: white;
    font-size: 10px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 4px;
    transform: rotate(15deg);
}

/* Modal Styles */
.achievement-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: var(--color-main-background);
    border-radius: 12px;
    padding: 0;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.modal-header {
    display: flex;
    justify-content: flex-end;
    padding: 15px 20px 0;
}

.close-btn {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--color-text-lighter);
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
}

.modal-body {
    padding: 0 20px 20px;
    text-align: center;
}

.achievement-large-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    overflow: hidden;
}

.achievement-large-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.locked-large-icon {
    font-size: 40px;
    color: var(--color-text-lighter);
}

.achievement-title {
    font-size: 24px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 0 0 10px 0;
}

.achievement-desc {
    font-size: 16px;
    color: var(--color-text-lighter);
    line-height: 1.5;
    margin: 0 0 20px 0;
}

.achievement-meta {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.meta-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.meta-label {
    font-weight: 500;
    color: var(--color-main-text);
}

.meta-value {
    color: var(--color-text-lighter);
}

.meta-value.rarity.common { color: #6c757d; }
.meta-value.rarity.uncommon { color: #28a745; }
.meta-value.rarity.rare { color: #007bff; }
.meta-value.rarity.epic { color: #6f42c1; }
.meta-value.rarity.legendary { color: #fd7e14; font-weight: 600; }

.achievement-tips {
    text-align: left;
}

.achievement-tips h4 {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
    margin: 0 0 10px 0;
}

.achievement-tips ul {
    margin: 0;
    padding-left: 20px;
}

.achievement-tips li {
    color: var(--color-text-lighter);
    margin-bottom: 5px;
    line-height: 1.4;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--color-text-lighter);
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 8px;
}

.empty-message {
    font-size: 14px;
    line-height: 1.5;
}

/* Animations */
@keyframes glow {
    0% { box-shadow: 0 0 5px rgba(var(--color-primary-rgb), 0.3); }
    100% { box-shadow: 0 0 20px rgba(var(--color-primary-rgb), 0.6); }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

/* Theme Variations */
.theme-game .achievement-card.unlocked {
    background: linear-gradient(135deg, var(--color-success-background), rgba(46, 213, 115, 0.1));
}

.theme-game .stats-fill {
    background: linear-gradient(90deg, #00a8ff, #2ed573);
}

/* Responsive Design */
@media (max-width: 768px) {
    .gallery-title {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .achievement-stats {
        align-items: flex-start;
    }
    
    .stats-bar {
        width: 120px;
    }
    
    .gallery-filters {
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .filter-btn {
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .achievements-grid {
        grid-template-columns: 1fr;
        gap: 12px;
    }
    
    .achievement-card {
        padding: 12px;
        gap: 10px;
    }
    
    .achievement-icon {
        width: 40px;
        height: 40px;
    }
    
    .achievement-name {
        font-size: 15px;
    }
    
    .achievement-description {
        font-size: 12px;
    }
    
    .modal-content {
        width: 95%;
        margin: 20px;
    }
    
    .achievement-large-icon {
        width: 60px;
        height: 60px;
    }
    
    .achievement-title {
        font-size: 20px;
    }
    
    .achievement-desc {
        font-size: 14px;
    }
}
</style>