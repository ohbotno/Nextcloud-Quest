/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

import api from '../../services/api'

const state = {
    user: {
        uid: null,
        displayName: null,
        theme_preference: 'game'
    },
    stats: {
        level: {
            level: 1,
            rank_title: 'Task Novice',
            xp: 0,
            lifetime_xp: 0,
            xp_to_next: 100,
            progress_percentage: 0
        },
        streak: {
            current_streak: 0,
            longest_streak: 0,
            last_completion: null,
            is_active_today: false,
            grace_period_ends: null
        },
        achievements: {
            total: 17,
            unlocked: 0,
            percentage: 0
        },
        leaderboard_rank: null
    },
    achievements: [],
    history: [],
    historyStats: {
        total_tasks: 0,
        total_xp: 0,
        average_per_day: 0,
        daily_stats: {}
    },
    leaderboard: [],
    loading: {
        stats: false,
        achievements: false,
        history: false,
        leaderboard: false,
        completingTask: false
    },
    settings: {
        theme_preference: 'game',
        notifications: {
            achievements: true,
            level_up: true,
            streak_reminder: true,
            daily_summary: false
        },
        display: {
            show_xp_popup: true,
            show_streak_counter: true,
            show_level_progress: true,
            compact_view: false
        },
        privacy: {
            show_on_leaderboard: true,
            anonymous_leaderboard: false
        }
    }
}

const mutations = {
    setUser(state, user) {
        state.user = { ...state.user, ...user }
    },
    
    setStats(state, stats) {
        state.stats = { ...state.stats, ...stats }
    },
    
    setAchievements(state, achievements) {
        state.achievements = achievements
    },
    
    setHistory(state, { history, stats }) {
        state.history = history
        state.historyStats = stats
    },
    
    setLeaderboard(state, leaderboard) {
        state.leaderboard = leaderboard
    },
    
    setSettings(state, settings) {
        state.settings = { ...state.settings, ...settings }
    },
    
    setLoading(state, { type, loading }) {
        state.loading[type] = loading
    },
    
    addToHistory(state, entry) {
        state.history.unshift(entry)
        // Keep only last 100 entries in memory
        if (state.history.length > 100) {
            state.history = state.history.slice(0, 100)
        }
    },
    
    updateStats(state, newStats) {
        state.stats = { ...state.stats, ...newStats }
        
        // Sync back to QuestApp if available
        if (typeof window !== 'undefined' && window.QuestApp && window.QuestApp.initialized) {
            try {
                // QuestApp manages stats centrally, so we don't need to sync back
                // Stats updated, QuestApp handles consistency
            } catch (e) {
                // Could not communicate with QuestApp
            }
        }
    },
    
    unlockAchievement(state, achievement) {
        const existingIndex = state.achievements.findIndex(a => a.key === achievement.key)
        if (existingIndex === -1) {
            state.achievements.push({
                ...achievement,
                unlocked: true,
                unlocked_at: new Date().toISOString()
            })
            state.stats.achievements.unlocked++
            state.stats.achievements.percentage = Math.round((state.stats.achievements.unlocked / state.stats.achievements.total) * 100)
        }
    },
    
    clearCache(state) {
        // Clearing cache
        // Reset to default stats
        state.stats = {
            level: {
                level: 1,
                rank_title: 'Task Novice',
                xp: 0,
                lifetime_xp: 0,
                xp_to_next: 100,
                progress_percentage: 0
            },
            streak: {
                current_streak: 0,
                longest_streak: 0,
                last_completion: null,
                is_active_today: false,
                grace_period_ends: null
            },
            achievements: {
                total: 17,
                unlocked: 0,
                percentage: 0
            },
            leaderboard_rank: null
        };
    }
}

const actions = {
    // Initialize QuestApp integration
    initQuestApp({ commit, dispatch }) {
        // Initializing QuestApp integration
        
        // Wait for QuestApp to be available
        function connectToQuestApp() {
            if (typeof window !== 'undefined' && window.QuestApp && window.QuestApp.initialized) {
                // Registering as QuestApp consumer
                
                // Register as consumer
                window.QuestApp.registerStatsConsumer('vue-store', {
                    onUpdate: function(stats) {
                        // Received stats update from QuestApp
                        commit('setStats', stats);
                        if (stats.user) {
                            commit('setUser', stats.user);
                        }
                    },
                    onError: function(error) {
                        // QuestApp error occurred
                    },
                    onLoading: function(isLoading) {
                        commit('setLoading', { type: 'stats', loading: isLoading });
                    }
                });
                
                // Get current stats immediately
                const currentStats = window.QuestApp.getCurrentStats();
                if (currentStats) {
                    // Loading current stats immediately
                    commit('setStats', currentStats);
                }
                
            } else {
                // QuestApp not available, retrying...
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
    
    // Legacy method for backward compatibility (now uses QuestApp)
    async loadUserStats({ dispatch }) {
        // loadUserStats is deprecated, using QuestApp instead
        dispatch('initQuestApp');
    },
    
    async loadAchievements({ commit }) {
        commit('setLoading', { type: 'achievements', loading: true })
        try {
            const response = await api.getAchievements()
            if (response.status === 'success') {
                commit('setAchievements', response.data)
            }
        } catch (error) {
            // Failed to load achievements
        } finally {
            commit('setLoading', { type: 'achievements', loading: false })
        }
    },
    
    async loadHistory({ commit }, { limit = 50, offset = 0 } = {}) {
        commit('setLoading', { type: 'history', loading: true })
        try {
            const response = await api.getHistory(limit, offset)
            if (response.status === 'success') {
                commit('setHistory', response.data)
            }
        } catch (error) {
            // Failed to load history
        } finally {
            commit('setLoading', { type: 'history', loading: false })
        }
    },
    
    async loadLeaderboard({ commit }, { orderBy = 'lifetime_xp', limit = 10, offset = 0 } = {}) {
        commit('setLoading', { type: 'leaderboard', loading: true })
        try {
            const response = await api.getLeaderboard(orderBy, limit, offset)
            if (response.status === 'success') {
                commit('setLeaderboard', response.data)
            }
        } catch (error) {
            // Failed to load leaderboard
        } finally {
            commit('setLoading', { type: 'leaderboard', loading: false })
        }
    },
    
    async completeTask({ commit, dispatch }, { taskId, taskTitle, priority = 'medium' }) {
        commit('setLoading', { type: 'completingTask', loading: true })
        try {
            const response = await api.completeTask(taskId, taskTitle, priority)
            if (response.status === 'success') {
                const { xp, streak, new_achievements } = response.data
                
                // Update stats
                commit('updateStats', {
                    level: {
                        ...state.stats.level,
                        level: xp.level,
                        xp: xp.current_xp,
                        lifetime_xp: xp.lifetime_xp,
                        progress_percentage: xp.progress_to_next_level,
                        xp_to_next: xp.next_level_xp - xp.current_xp
                    },
                    streak: {
                        ...state.stats.streak,
                        current_streak: streak.current_streak,
                        longest_streak: streak.longest_streak,
                        last_completion: new Date().toISOString(),
                        is_active_today: true
                    }
                })
                
                // Add to history
                commit('addToHistory', {
                    task_id: taskId,
                    task_title: taskTitle,
                    xp_earned: xp.xp_earned,
                    completed_at: new Date().toISOString()
                })
                
                // Unlock new achievements
                new_achievements.forEach(achievement => {
                    commit('unlockAchievement', achievement)
                })
                
                // Refresh full stats
                dispatch('loadUserStats')
                
                return response.data
            }
        } catch (error) {
            // Failed to complete task
            throw error
        } finally {
            commit('setLoading', { type: 'completingTask', loading: false })
        }
    },
    
    async loadSettings({ commit }) {
        try {
            const response = await api.getSettings()
            if (response.status === 'success') {
                commit('setSettings', response.data)
            }
        } catch (error) {
            // Failed to load settings
        }
    },
    
    async updateSettings({ commit }, settings) {
        try {
            const response = await api.updateSettings(settings)
            if (response.status === 'success') {
                commit('setSettings', settings)
            }
            return response
        } catch (error) {
            // Failed to update settings
            throw error
        }
    }
}

const getters = {
    isGameTheme: state => state.user.theme_preference === 'game',
    isProfessionalTheme: state => state.user.theme_preference === 'professional',
    
    unlockedAchievements: state => state.achievements.filter(a => a.unlocked),
    lockedAchievements: state => state.achievements.filter(a => !a.unlocked),
    
    recentComplettion: state => {
        return state.history.slice(0, 5)
    },
    
    isStreakActive: state => {
        if (!state.stats.streak.last_completion) return false
        const lastCompletion = new Date(state.stats.streak.last_completion)
        const today = new Date()
        const diffDays = Math.floor((today - lastCompletion) / (1000 * 60 * 60 * 24))
        return diffDays === 0
    },
    
    needsStreakReminder: state => {
        if (state.stats.streak.current_streak === 0) return false
        if (state.stats.streak.is_active_today) return false
        if (!state.stats.streak.grace_period_ends) return false
        
        const gradeEnd = new Date(state.stats.streak.grace_period_ends)
        const now = new Date()
        const hoursLeft = Math.max(0, Math.floor((gradeEnd - now) / (1000 * 60 * 60)))
        
        return hoursLeft > 0 && hoursLeft <= 4
    }
}

export default {
    namespaced: true,
    state,
    mutations,
    actions,
    getters
}