<template>
    <div class="streak-counter" :class="{ detailed: detailed, warning: isStreakAtRisk }">
        <div class="streak-header">
            <div class="streak-icon">
                <span v-if="stats.streak.current_streak === 0">⭕</span>
                <span v-else-if="stats.streak.current_streak < 7">🔥</span>
                <span v-else-if="stats.streak.current_streak < 30">🔥🔥</span>
                <span v-else>🔥🔥🔥</span>
            </div>
            <div class="streak-info">
                <h4 v-if="detailed">{{ t('quest', 'Current Streak') }}</h4>
                <div class="streak-number">
                    <span class="number">{{ stats.streak.current_streak }}</span>
                    <span class="label">{{ t('quest', 'days') }}</span>
                </div>
                <div v-if="!detailed && stats.streak.current_streak > 0" class="streak-status">
                    {{ getStreakMessage() }}
                </div>
            </div>
        </div>
        
        <div v-if="detailed" class="streak-details">
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">{{ t('quest', 'Longest Streak') }}</div>
                    <div class="detail-value">
                        {{ stats.streak.longest_streak }} {{ t('quest', 'days') }}
                        <span v-if="stats.streak.current_streak === stats.streak.longest_streak" class="record-badge">
                            🏆 {{ t('quest', 'Record!') }}
                        </span>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">{{ t('quest', 'Last Completion') }}</div>
                    <div class="detail-value">
                        {{ formatLastCompletion() }}
                    </div>
                </div>
                
                <div v-if="stats.streak.current_streak > 0" class="detail-item">
                    <div class="detail-label">{{ t('quest', 'Status') }}</div>
                    <div class="detail-value" :class="{ 'status-active': stats.streak.is_active_today, 'status-risk': isStreakAtRisk }">
                        {{ getDetailedStatus() }}
                    </div>
                </div>
            </div>
            
            <!-- Streak Progress Visualization -->
            <div class="streak-visualization">
                <div class="streak-calendar">
                    <div class="calendar-header">{{ t('quest', 'Last 7 Days') }}</div>
                    <div class="calendar-days">
                        <div 
                            v-for="(day, index) in last7Days"
                            :key="index"
                            class="calendar-day"
                            :class="{ 
                                completed: day.completed, 
                                today: day.isToday,
                                future: day.isFuture
                            }"
                            :title="day.date"
                        >
                            <div class="day-label">{{ day.label }}</div>
                            <div class="day-indicator">
                                <span v-if="day.completed">✓</span>
                                <span v-else-if="day.isToday && !day.completed">📅</span>
                                <span v-else-if="!day.isFuture">✗</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Streak Milestones -->
            <div class="streak-milestones">
                <div class="milestones-header">{{ t('quest', 'Streak Milestones') }}</div>
                <div class="milestones-list">
                    <div 
                        v-for="milestone in streakMilestones"
                        :key="milestone.days"
                        class="milestone-item"
                        :class="{ 
                            achieved: stats.streak.longest_streak >= milestone.days,
                            current: stats.streak.current_streak >= milestone.days && stats.streak.current_streak < (milestone.next || 999)
                        }"
                    >
                        <div class="milestone-icon">{{ milestone.icon }}</div>
                        <div class="milestone-info">
                            <div class="milestone-name">{{ milestone.name }}</div>
                            <div class="milestone-requirement">{{ milestone.days }} {{ t('quest', 'days') }}</div>
                        </div>
                        <div class="milestone-status">
                            <span v-if="stats.streak.longest_streak >= milestone.days" class="achieved">✓</span>
                            <span v-else class="progress">{{ Math.min(stats.streak.current_streak, milestone.days) }}/{{ milestone.days }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Warning Message -->
        <div v-if="isStreakAtRisk && detailed" class="streak-warning">
            <div class="warning-icon">⚠️</div>
            <div class="warning-content">
                <div class="warning-title">{{ t('quest', 'Streak at Risk!') }}</div>
                <div class="warning-message">
                    {{ getWarningMessage() }}
                </div>
            </div>
        </div>
        
        <!-- Encouragement Message -->
        <div v-if="stats.streak.current_streak === 0 && detailed" class="streak-encouragement">
            <div class="encouragement-icon">💪</div>
            <div class="encouragement-content">
                <div class="encouragement-title">{{ t('quest', 'Start Your Streak!') }}</div>
                <div class="encouragement-message">
                    {{ t('quest', 'Complete a task today to begin your productivity streak.') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapGetters } from 'vuex'

export default {
    name: 'StreakCounter',
    
    props: {
        detailed: {
            type: Boolean,
            default: false
        }
    },
    
    computed: {
        ...mapState('quest', ['stats']),
        ...mapGetters('quest', ['needsStreakReminder']),
        
        isStreakAtRisk() {
            return this.needsStreakReminder || (!this.stats.streak.is_active_today && this.stats.streak.current_streak > 0)
        },
        
        last7Days() {
            const days = []
            const today = new Date()
            
            for (let i = 6; i >= 0; i--) {
                const date = new Date(today)
                date.setDate(date.getDate() - i)
                
                const isToday = i === 0
                const isFuture = i < 0 // This shouldn't happen in this loop
                
                // In a real implementation, we'd check actual completion data
                // For now, we'll simulate based on streak
                const completed = this.isDateInStreak(date)
                
                days.push({
                    date: date.toLocaleDateString(),
                    label: isToday ? 'Today' : date.toLocaleDateString('en', { weekday: 'short' }),
                    completed,
                    isToday,
                    isFuture
                })
            }
            
            return days
        },
        
        streakMilestones() {
            return [
                { days: 3, name: 'Getting Started', icon: '🌱', next: 7 },
                { days: 7, name: 'Week Warrior', icon: '⚡', next: 14 },
                { days: 14, name: 'Fortnight Fighter', icon: '💪', next: 30 },
                { days: 30, name: 'Monthly Master', icon: '🏆', next: 50 },
                { days: 50, name: 'Dedication Expert', icon: '💎', next: 100 },
                { days: 100, name: 'Century Champion', icon: '👑', next: null }
            ]
        }
    },
    
    methods: {
        getStreakMessage() {
            if (this.stats.streak.current_streak === 0) {
                return this.t('quest', 'No streak yet')
            } else if (this.stats.streak.current_streak === 1) {
                return this.t('quest', 'Great start!')
            } else if (this.stats.streak.current_streak < 7) {
                return this.t('quest', 'Building momentum')
            } else if (this.stats.streak.current_streak < 30) {
                return this.t('quest', 'On fire!')
            } else {
                return this.t('quest', 'Legendary!')
            }
        },
        
        getDetailedStatus() {
            if (this.stats.streak.is_active_today) {
                return this.t('quest', 'Active today ✓')
            } else if (this.isStreakAtRisk) {
                return this.t('quest', 'At risk ⚠️')
            } else {
                return this.t('quest', 'Waiting for completion')
            }
        },
        
        formatLastCompletion() {
            if (!this.stats.streak.last_completion) {
                return this.t('quest', 'Never')
            }
            
            const date = new Date(this.stats.streak.last_completion)
            const now = new Date()
            const diffDays = Math.floor((now - date) / (1000 * 60 * 60 * 24))
            
            if (diffDays === 0) {
                return this.t('quest', 'Today')
            } else if (diffDays === 1) {
                return this.t('quest', 'Yesterday')
            } else {
                return this.t('quest', '{days} days ago', { days: diffDays })
            }
        },
        
        getWarningMessage() {
            if (!this.stats.streak.grace_period_ends) {
                return this.t('quest', 'Complete a task today to maintain your streak.')
            }
            
            const graceEnd = new Date(this.stats.streak.grace_period_ends)
            const now = new Date()
            const hoursLeft = Math.max(0, Math.floor((graceEnd - now) / (1000 * 60 * 60)))
            
            if (hoursLeft <= 1) {
                return this.t('quest', 'Less than 1 hour left to maintain your streak!')
            } else {
                return this.t('quest', '{hours} hours left to maintain your streak.', { hours: hoursLeft })
            }
        },
        
        isDateInStreak(date) {
            // Simplified streak calculation for visualization
            // In a real implementation, this would check actual completion data
            if (!this.stats.streak.last_completion) return false
            
            const lastCompletion = new Date(this.stats.streak.last_completion)
            const streakStart = new Date(lastCompletion)
            streakStart.setDate(streakStart.getDate() - this.stats.streak.current_streak + 1)
            
            return date >= streakStart && date <= lastCompletion
        }
    }
}
</script>

<style scoped>
.streak-counter {
    position: relative;
}

.streak-header {
    display: flex;
    align-items: center;
    gap: 12px;
}

.streak-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.streak-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
}

.streak-number {
    display: flex;
    align-items: baseline;
    gap: 4px;
}

.streak-number .number {
    font-size: 28px;
    font-weight: bold;
    color: var(--color-primary);
    line-height: 1;
}

.streak-number .label {
    font-size: 14px;
    color: var(--color-text-lighter);
}

.streak-status {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 2px;
}

.streak-details {
    margin-top: 15px;
}

.detail-grid {
    display: grid;
    gap: 12px;
    margin-bottom: 20px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--color-border);
}

.detail-label {
    font-size: 14px;
    color: var(--color-text-lighter);
}

.detail-value {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-main-text);
    display: flex;
    align-items: center;
    gap: 6px;
}

.detail-value.status-active {
    color: var(--color-success);
}

.detail-value.status-risk {
    color: var(--color-error);
}

.record-badge {
    font-size: 12px;
    background: var(--color-warning);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
}

.streak-visualization {
    margin: 20px 0;
}

.calendar-header {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 10px;
}

.calendar-days {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.calendar-day {
    text-align: center;
    padding: 8px 4px;
    border-radius: 6px;
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    transition: all 0.2s ease;
}

.calendar-day.completed {
    background: var(--color-success);
    color: white;
    border-color: var(--color-success);
}

.calendar-day.today {
    border-color: var(--color-primary);
    border-width: 2px;
}

.calendar-day.today.completed {
    background: var(--color-success);
    border-color: var(--color-success);
}

.day-label {
    font-size: 10px;
    color: inherit;
    margin-bottom: 2px;
}

.day-indicator {
    font-size: 12px;
    font-weight: bold;
}

.streak-milestones {
    margin-top: 20px;
}

.milestones-header {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 10px;
}

.milestones-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.milestone-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px;
    border-radius: 6px;
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    transition: all 0.2s ease;
}

.milestone-item.achieved {
    background: var(--color-success-background);
    border-color: var(--color-success);
}

.milestone-item.current {
    background: var(--color-primary-light);
    border-color: var(--color-primary);
}

.milestone-icon {
    font-size: 18px;
    flex-shrink: 0;
}

.milestone-info {
    flex: 1;
}

.milestone-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--color-main-text);
}

.milestone-requirement {
    font-size: 11px;
    color: var(--color-text-lighter);
}

.milestone-status {
    font-size: 12px;
    font-weight: 600;
}

.milestone-status .achieved {
    color: var(--color-success);
}

.milestone-status .progress {
    color: var(--color-text-lighter);
}

.streak-warning, .streak-encouragement {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    border-radius: 8px;
    margin-top: 15px;
}

.streak-warning {
    background: var(--color-warning-background);
    border: 1px solid var(--color-warning);
}

.streak-encouragement {
    background: var(--color-primary-light);
    border: 1px solid var(--color-primary);
}

.warning-icon, .encouragement-icon {
    font-size: 20px;
    flex-shrink: 0;
}

.warning-title, .encouragement-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 4px;
}

.warning-message, .encouragement-message {
    font-size: 13px;
    color: var(--color-text-lighter);
}

/* Warning state styling */
.streak-counter.warning .streak-number .number {
    color: var(--color-error);
    animation: pulse 2s infinite;
}

.streak-counter.warning .streak-icon {
    animation: shake 0.5s ease-in-out infinite alternate;
}

/* Animations */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-2px); }
    75% { transform: translateX(2px); }
    100% { transform: translateX(0); }
}

/* Theme Variations */
.theme-game .streak-counter {
    /* Enhanced visual effects for game theme */
}

.theme-game .calendar-day.completed {
    background: linear-gradient(135deg, var(--color-success), #20bf6b);
    box-shadow: 0 2px 8px rgba(46, 213, 115, 0.3);
}

.theme-professional .calendar-day.completed {
    background: var(--color-success);
}

.theme-professional .milestone-item.current {
    background: var(--color-background-hover);
    border-color: var(--color-border-dark);
}

/* Responsive Design */
@media (max-width: 768px) {
    .streak-number .number {
        font-size: 24px;
    }
    
    .detail-grid {
        gap: 8px;
    }
    
    .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 4px;
    }
    
    .calendar-days {
        gap: 2px;
    }
    
    .calendar-day {
        padding: 6px 2px;
    }
    
    .milestone-item {
        padding: 6px;
        gap: 8px;
    }
    
    .milestone-icon {
        font-size: 16px;
    }
}
</style>