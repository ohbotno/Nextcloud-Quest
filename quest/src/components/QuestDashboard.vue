<template>
    <div id="quest-dashboard" :class="{ 'theme-professional': isProfessionalTheme, 'theme-game': isGameTheme }">
        <!-- Header Section -->
        <div class="quest-header">
            <div class="quest-title">
                <h1>
                    <span class="quest-icon">🎯</span>
                    {{ t('quest', 'Quest') }}
                </h1>
                <div class="theme-toggle">
                    <button 
                        @click="toggleTheme" 
                        :class="{ active: isProfessionalTheme }"
                        class="theme-btn professional"
                        :title="t('nextcloudquest', 'Professional Theme')"
                    >
                        💼
                    </button>
                    <button 
                        @click="toggleTheme" 
                        :class="{ active: isGameTheme }"
                        class="theme-btn game"
                        :title="t('nextcloudquest', 'Game Theme')"
                    >
                        🎮
                    </button>
                </div>
            </div>
            
            <!-- User Stats Summary -->
            <div class="user-summary">
                <div class="summary-card level-card">
                    <LevelIndicator />
                </div>
                <div class="summary-card streak-card">
                    <StreakCounter />
                </div>
                <div class="summary-card xp-card">
                    <ProgressBar />
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="quest-content">
            <!-- Left Sidebar -->
            <div class="quest-sidebar">
                <!-- Quick Actions -->
                <div class="sidebar-section">
                    <h3>{{ t('nextcloudquest', 'Quick Actions') }}</h3>
                    <div class="quick-actions">
                        <button 
                            @click="showTaskCompletionDialog"
                            class="action-btn primary"
                            :disabled="loading.completingTask"
                        >
                            <span class="icon">✓</span>
                            {{ t('nextcloudquest', 'Complete Task') }}
                        </button>
                        <button 
                            @click="refreshStats"
                            class="action-btn secondary"
                            :disabled="loading.stats"
                        >
                            <span class="icon">🔄</span>
                            {{ t('nextcloudquest', 'Refresh') }}
                        </button>
                    </div>
                </div>
                
                <!-- Achievement Preview -->
                <div class="sidebar-section">
                    <h3>{{ t('nextcloudquest', 'Recent Achievements') }}</h3>
                    <div class="recent-achievements">
                        <div 
                            v-for="achievement in recentAchievements" 
                            :key="achievement.key"
                            class="achievement-item"
                        >
                            <img :src="getAchievementIcon(achievement.icon)" :alt="achievement.name" />
                            <div class="achievement-info">
                                <strong>{{ achievement.name }}</strong>
                                <small>{{ formatDate(achievement.unlocked_at) }}</small>
                            </div>
                        </div>
                        <div v-if="recentAchievements.length === 0" class="no-achievements">
                            {{ t('nextcloudquest', 'No achievements yet. Complete some tasks!') }}
                        </div>
                    </div>
                </div>
                
                <!-- Streak Reminder -->
                <div v-if="needsStreakReminder" class="sidebar-section streak-warning">
                    <h3>⚠️ {{ t('nextcloudquest', 'Streak Alert') }}</h3>
                    <p>{{ t('nextcloudquest', 'Your {streak}-day streak expires soon!', { streak: stats.streak.current_streak }) }}</p>
                    <button @click="showTaskCompletionDialog" class="action-btn urgent">
                        {{ t('nextcloudquest', 'Complete a Task') }}
                    </button>
                </div>
            </div>
            
            <!-- Main Panel -->
            <div class="quest-main">
                <!-- Tab Navigation -->
                <div class="tab-navigation">
                    <button 
                        v-for="tab in tabs" 
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="{ active: activeTab === tab.id }"
                        class="tab-btn"
                    >
                        <span class="tab-icon">{{ tab.icon }}</span>
                        {{ tab.label }}
                    </button>
                </div>
                
                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Stats Tab -->
                    <div v-show="activeTab === 'stats'" class="tab-panel">
                        <div class="stats-grid">
                            <div class="stat-card level-detailed">
                                <h3>{{ t('nextcloudquest', 'Level Progress') }}</h3>
                                <LevelIndicator detailed />
                                <ProgressBar detailed />
                            </div>
                            
                            <div class="stat-card streak-detailed">
                                <h3>{{ t('nextcloudquest', 'Streak Information') }}</h3>
                                <StreakCounter detailed />
                            </div>
                            
                            <div class="stat-card leaderboard-preview">
                                <h3>{{ t('nextcloudquest', 'Leaderboard Position') }}</h3>
                                <div class="rank-info">
                                    <div class="rank-number">#{{ stats.leaderboard_rank || '?' }}</div>
                                    <div class="rank-text">{{ t('nextcloudquest', 'Global Rank') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Achievements Tab -->
                    <div v-show="activeTab === 'achievements'" class="tab-panel">
                        <AchievementGallery />
                    </div>
                    
                    <!-- History Tab -->
                    <div v-show="activeTab === 'history'" class="tab-panel">
                        <StatsPanel />
                    </div>
                    
                    <!-- Leaderboard Tab -->
                    <div v-show="activeTab === 'leaderboard'" class="tab-panel">
                        <div class="leaderboard-section">
                            <h3>{{ t('nextcloudquest', 'Top Players') }}</h3>
                            <div class="leaderboard-controls">
                                <select v-model="leaderboardType" @change="loadLeaderboard">
                                    <option value="lifetime_xp">{{ t('nextcloudquest', 'Total XP') }}</option>
                                    <option value="level">{{ t('nextcloudquest', 'Level') }}</option>
                                    <option value="current_streak">{{ t('nextcloudquest', 'Current Streak') }}</option>
                                </select>
                            </div>
                            <div v-if="loading.leaderboard" class="loading">
                                {{ t('nextcloudquest', 'Loading leaderboard...') }}
                            </div>
                            <div v-else class="leaderboard-list">
                                <div 
                                    v-for="(player, index) in leaderboard.leaderboard" 
                                    :key="player.user_id"
                                    class="leaderboard-item"
                                    :class="{ 'current-user': player.user_id === user.uid }"
                                >
                                    <div class="rank">{{ index + 1 }}</div>
                                    <div class="player-info">
                                        <div class="player-name">{{ getPlayerName(player.user_id) }}</div>
                                        <div class="player-title">{{ player.rank_title }}</div>
                                    </div>
                                    <div class="player-stats">
                                        <div v-if="leaderboardType === 'lifetime_xp'">
                                            {{ formatNumber(player.lifetime_xp) }} XP
                                        </div>
                                        <div v-else-if="leaderboardType === 'level'">
                                            Level {{ player.level }}
                                        </div>
                                        <div v-else>
                                            {{ player.current_streak }} days
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Task Completion Dialog -->
        <TaskCompletionDialog 
            v-if="showTaskDialog"
            @close="showTaskDialog = false"
            @complete="handleTaskCompletion"
        />
        
        <!-- Level Up Notification -->
        <LevelUpNotification 
            v-if="showLevelUpNotification"
            :level="levelUpData.level"
            :rank-title="levelUpData.rankTitle"
            @close="showLevelUpNotification = false"
        />
        
        <!-- Achievement Notification -->
        <AchievementNotification 
            v-if="showAchievementNotification"
            :achievements="newAchievements"
            @close="showAchievementNotification = false"
        />
    </div>
</template>

<script>
import { mapState, mapGetters, mapActions } from 'vuex'
import LevelIndicator from './LevelIndicator.vue'
import ProgressBar from './ProgressBar.vue'
import StreakCounter from './StreakCounter.vue'
import AchievementGallery from './AchievementGallery.vue'
import StatsPanel from './StatsPanel.vue'
import TaskCompletionDialog from './TaskCompletionDialog.vue'
import LevelUpNotification from './LevelUpNotification.vue'
import AchievementNotification from './AchievementNotification.vue'

export default {
    name: 'QuestDashboard',
    
    components: {
        LevelIndicator,
        ProgressBar,
        StreakCounter,
        AchievementGallery,
        StatsPanel,
        TaskCompletionDialog,
        LevelUpNotification,
        AchievementNotification
    },
    
    data() {
        return {
            activeTab: 'stats',
            leaderboardType: 'lifetime_xp',
            showTaskDialog: false,
            showLevelUpNotification: false,
            showAchievementNotification: false,
            levelUpData: {},
            newAchievements: [],
            tabs: [
                { id: 'stats', label: this.t('nextcloudquest', 'Overview'), icon: '📊' },
                { id: 'achievements', label: this.t('nextcloudquest', 'Achievements'), icon: '🏆' },
                { id: 'history', label: this.t('nextcloudquest', 'History'), icon: '📈' },
                { id: 'leaderboard', label: this.t('nextcloudquest', 'Leaderboard'), icon: '👑' }
            ]
        }
    },
    
    computed: {
        ...mapState('quest', ['user', 'stats', 'achievements', 'history', 'leaderboard', 'loading']),
        ...mapGetters('quest', ['isGameTheme', 'isProfessionalTheme', 'unlockedAchievements', 'needsStreakReminder']),
        
        recentAchievements() {
            return this.unlockedAchievements
                .sort((a, b) => new Date(b.unlocked_at) - new Date(a.unlocked_at))
                .slice(0, 3)
        }
    },
    
    methods: {
        ...mapActions('quest', ['loadUserStats', 'loadAchievements', 'loadHistory', 'loadLeaderboard', 'completeTask', 'updateSettings']),
        
        async toggleTheme() {
            const newTheme = this.isGameTheme ? 'professional' : 'game'
            try {
                await this.updateSettings({
                    theme_preference: newTheme
                })
            } catch (error) {
                console.error('Failed to update theme:', error)
            }
        },
        
        showTaskCompletionDialog() {
            this.showTaskDialog = true
        },
        
        async handleTaskCompletion(taskData) {
            try {
                const result = await this.completeTask(taskData)
                
                // Show level up notification if leveled up
                if (result.xp.leveled_up) {
                    this.levelUpData = {
                        level: result.xp.level,
                        rankTitle: result.xp.rank_title
                    }
                    this.showLevelUpNotification = true
                }
                
                // Show achievement notifications
                if (result.new_achievements.length > 0) {
                    this.newAchievements = result.new_achievements
                    this.showAchievementNotification = true
                }
                
                this.showTaskDialog = false
                
            } catch (error) {
                console.error('Failed to complete task:', error)
                // Show error notification
            }
        },
        
        async refreshStats() {
            await this.loadUserStats()
            await this.loadAchievements()
        },
        
        getAchievementIcon(icon) {
            return `/apps/quest/img/achievements/${icon}`
        },
        
        getPlayerName(userId) {
            // In a real implementation, this would fetch display names
            return userId === this.user.uid ? this.t('nextcloudquest', 'You') : `User ${userId.slice(0, 8)}`
        },
        
        formatDate(dateString) {
            if (!dateString) return ''
            return new Date(dateString).toLocaleDateString()
        },
        
        formatNumber(num) {
            return new Intl.NumberFormat().format(num)
        }
    },
    
    mounted() {
        // Load initial data
        this.loadHistory()
        this.loadLeaderboard({ orderBy: this.leaderboardType })
        
        // DISABLED: Set up periodic refresh (was causing stats to be overwritten)
        // this.refreshInterval = setInterval(() => {
        //     if (!this.loading.stats) {
        //         this.loadUserStats()
        //     }
        // }, 60000) // Refresh every minute
    },
    
    beforeDestroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval)
        }
    }
}
</script>

<style scoped>
#quest-dashboard {
    min-height: 100vh;
    background: var(--color-main-background);
    padding: 20px;
}

.quest-header {
    margin-bottom: 30px;
}

.quest-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.quest-title h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    color: var(--color-main-text);
}

.quest-icon {
    font-size: 32px;
    margin-right: 10px;
}

.theme-toggle {
    display: flex;
    gap: 5px;
}

.theme-btn {
    padding: 8px 12px;
    border: 2px solid var(--color-border);
    background: var(--color-main-background);
    border-radius: 8px;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.2s ease;
}

.theme-btn.active {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}

.user-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.summary-card {
    background: var(--color-background-hover);
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.quest-content {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
}

.quest-sidebar {
    background: var(--color-background-hover);
    border-radius: 12px;
    padding: 20px;
    height: fit-content;
}

.sidebar-section {
    margin-bottom: 25px;
}

.sidebar-section h3 {
    margin: 0 0 15px 0;
    color: var(--color-main-text);
    font-size: 16px;
    font-weight: 600;
}

.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.action-btn.primary {
    background: var(--color-primary);
    color: white;
}

.action-btn.secondary {
    background: var(--color-background-dark);
    color: var(--color-main-text);
}

.action-btn.urgent {
    background: var(--color-error);
    color: white;
}

.recent-achievements {
    max-height: 200px;
    overflow-y: auto;
}

.achievement-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.achievement-item img {
    width: 24px;
    height: 24px;
}

.achievement-info {
    flex: 1;
}

.achievement-info strong {
    display: block;
    font-size: 14px;
}

.achievement-info small {
    color: var(--color-text-lighter);
    font-size: 12px;
}

.streak-warning {
    background: var(--color-warning-background);
    border: 1px solid var(--color-warning);
    border-radius: 8px;
    padding: 15px;
}

.quest-main {
    background: var(--color-background-hover);
    border-radius: 12px;
    overflow: hidden;
}

.tab-navigation {
    display: flex;
    background: var(--color-background-dark);
    border-bottom: 1px solid var(--color-border);
}

.tab-btn {
    flex: 1;
    padding: 15px 20px;
    border: none;
    background: transparent;
    color: var(--color-text-lighter);
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.tab-btn.active {
    background: var(--color-main-background);
    color: var(--color-main-text);
    border-bottom: 2px solid var(--color-primary);
}

.tab-content {
    padding: 25px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.stat-card {
    background: var(--color-main-background);
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.stat-card h3 {
    margin: 0 0 15px 0;
    color: var(--color-main-text);
    font-size: 16px;
    font-weight: 600;
}

.rank-info {
    text-align: center;
}

.rank-number {
    font-size: 48px;
    font-weight: bold;
    color: var(--color-primary);
    line-height: 1;
}

.rank-text {
    color: var(--color-text-lighter);
    margin-top: 5px;
}

.leaderboard-controls {
    margin-bottom: 20px;
}

.leaderboard-controls select {
    padding: 8px 12px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    background: var(--color-main-background);
    color: var(--color-main-text);
}

.leaderboard-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.leaderboard-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: var(--color-main-background);
    border-radius: 8px;
    border: 1px solid var(--color-border);
}

.leaderboard-item.current-user {
    border-color: var(--color-primary);
    background: var(--color-primary-light);
}

.leaderboard-item .rank {
    font-size: 18px;
    font-weight: bold;
    color: var(--color-primary);
    min-width: 30px;
    text-align: center;
}

.player-info {
    flex: 1;
}

.player-name {
    font-weight: 600;
    color: var(--color-main-text);
}

.player-title {
    font-size: 14px;
    color: var(--color-text-lighter);
}

.player-stats {
    font-weight: 600;
    color: var(--color-primary);
}

.loading {
    text-align: center;
    padding: 40px;
    color: var(--color-text-lighter);
}

.no-achievements {
    text-align: center;
    color: var(--color-text-lighter);
    font-style: italic;
    padding: 20px 0;
}

/* Game Theme Overrides */
.theme-game {
    --color-primary: #00a8ff;
    --color-primary-light: rgba(0, 168, 255, 0.1);
    --color-success: #2ed573;
    --color-warning: #ffa502;
    --color-error: #ff3742;
}

.theme-game .summary-card {
    background: linear-gradient(135deg, var(--color-background-hover) 0%, rgba(0, 168, 255, 0.05) 100%);
}

.theme-game .action-btn.primary {
    background: linear-gradient(135deg, var(--color-primary) 0%, #0092cc 100%);
    box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3);
}

/* Professional Theme Overrides */
.theme-professional {
    --color-primary: #5a5a5a;
    --color-primary-light: rgba(90, 90, 90, 0.1);
}

.theme-professional .summary-card {
    border: 1px solid var(--color-border);
}

.theme-professional .action-btn.primary {
    background: var(--color-primary);
    box-shadow: none;
}

@media (max-width: 768px) {
    .quest-content {
        grid-template-columns: 1fr;
    }
    
    .user-summary {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .tab-btn {
        padding: 12px 10px;
        font-size: 14px;
    }
    
    .tab-btn .tab-icon {
        display: none;
    }
}
</style>