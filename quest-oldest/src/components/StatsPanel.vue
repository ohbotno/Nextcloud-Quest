<template>
    <div class="stats-panel">
        <div class="panel-header">
            <h3>{{ t('quest', 'Statistics & History') }}</h3>
            <div class="time-filter">
                <select v-model="selectedPeriod" @change="loadData">
                    <option value="7">{{ t('quest', 'Last 7 days') }}</option>
                    <option value="30">{{ t('quest', 'Last 30 days') }}</option>
                    <option value="90">{{ t('quest', 'Last 90 days') }}</option>
                    <option value="0">{{ t('quest', 'All time') }}</option>
                </select>
            </div>
        </div>
        
        <!-- Quick Stats Cards -->
        <div class="quick-stats">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <div class="stat-value">{{ historyStats.total_tasks }}</div>
                    <div class="stat-label">{{ t('quest', 'Tasks Completed') }}</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-info">
                    <div class="stat-value">{{ formatNumber(historyStats.total_xp) }}</div>
                    <div class="stat-label">{{ t('quest', 'XP Earned') }}</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📈</div>
                <div class="stat-info">
                    <div class="stat-value">{{ historyStats.average_per_day.toFixed(1) }}</div>
                    <div class="stat-label">{{ t('quest', 'Avg per Day') }}</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🏆</div>
                <div class="stat-info">
                    <div class="stat-value">{{ stats.achievements.unlocked }}</div>
                    <div class="stat-label">{{ t('quest', 'Achievements') }}</div>
                </div>
            </div>
        </div>
        
        <!-- Activity Chart -->
        <div class="chart-section">
            <div class="chart-header">
                <h4>{{ t('quest', 'Daily Activity') }}</h4>
                <div class="chart-legend">
                    <div class="legend-item">
                        <div class="legend-color tasks"></div>
                        <span>{{ t('quest', 'Tasks') }}</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color xp"></div>
                        <span>{{ t('quest', 'XP') }}</span>
                    </div>
                </div>
            </div>
            
            <div class="activity-chart">
                <div class="chart-container">
                    <div class="chart-y-axis">
                        <div v-for="tick in yAxisTicks" :key="tick" class="y-tick">
                            {{ tick }}
                        </div>
                    </div>
                    <div class="chart-area">
                        <div class="chart-grid">
                            <div v-for="i in 5" :key="i" class="grid-line"></div>
                        </div>
                        <div class="chart-bars">
                            <div 
                                v-for="(day, index) in chartData"
                                :key="index"
                                class="bar-group"
                                @mouseenter="showTooltip($event, day)"
                                @mouseleave="hideTooltip"
                            >
                                <div 
                                    class="bar tasks"
                                    :style="{ height: `${(day.tasks / maxTasks) * 100}%` }"
                                ></div>
                                <div 
                                    class="bar xp"
                                    :style="{ height: `${(day.xp / maxXP) * 100}%` }"
                                ></div>
                                <div class="bar-label">{{ day.label }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent History -->
        <div class="history-section">
            <div class="history-header">
                <h4>{{ t('quest', 'Recent Completions') }}</h4>
                <button @click="loadMoreHistory" :disabled="loading.history" class="load-more-btn">
                    {{ loading.history ? t('quest', 'Loading...') : t('quest', 'Load More') }}
                </button>
            </div>
            
            <div class="history-list">
                <div 
                    v-for="entry in history.slice(0, displayedHistoryCount)"
                    :key="entry.id"
                    class="history-item"
                >
                    <div class="history-icon">✓</div>
                    <div class="history-info">
                        <div class="history-title">{{ entry.task_title }}</div>
                        <div class="history-meta">
                            <span class="history-xp">+{{ entry.xp_earned }} XP</span>
                            <span class="history-date">{{ formatRelativeTime(entry.completed_at) }}</span>
                        </div>
                    </div>
                    <div class="history-badge" :class="getXPBadgeClass(entry.xp_earned)">
                        {{ getXPBadgeText(entry.xp_earned) }}
                    </div>
                </div>
                
                <div v-if="history.length === 0 && !loading.history" class="no-history">
                    <div class="no-history-icon">📝</div>
                    <div class="no-history-text">
                        {{ t('quest', 'No tasks completed yet. Start your quest by completing your first task!') }}
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tooltip -->
        <div v-if="tooltip.show" class="chart-tooltip" :style="tooltip.style">
            <div class="tooltip-content">
                <div class="tooltip-date">{{ tooltip.data.date }}</div>
                <div class="tooltip-stats">
                    <div class="tooltip-stat">
                        <span class="tooltip-label">{{ t('quest', 'Tasks:') }}</span>
                        <span class="tooltip-value">{{ tooltip.data.tasks }}</span>
                    </div>
                    <div class="tooltip-stat">
                        <span class="tooltip-label">{{ t('quest', 'XP:') }}</span>
                        <span class="tooltip-value">{{ tooltip.data.xp }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState, mapActions } from 'vuex'

export default {
    name: 'StatsPanel',
    
    data() {
        return {
            selectedPeriod: 30,
            displayedHistoryCount: 10,
            tooltip: {
                show: false,
                style: {},
                data: {}
            }
        }
    },
    
    computed: {
        ...mapState('quest', ['stats', 'history', 'historyStats', 'loading']),
        
        chartData() {
            const data = []
            const dailyStats = this.historyStats.daily_stats || {}
            const days = this.selectedPeriod || 30
            
            for (let i = days - 1; i >= 0; i--) {
                const date = new Date()
                date.setDate(date.getDate() - i)
                const dateStr = date.toISOString().split('T')[0]
                
                const dayData = dailyStats[dateStr] || { tasks: 0, xp: 0 }
                
                data.push({
                    date: dateStr,
                    label: i === 0 ? 'Today' : (i === 1 ? 'Yesterday' : date.toLocaleDateString('en', { weekday: 'short' })),
                    fullDate: date.toLocaleDateString(),
                    tasks: dayData.tasks,
                    xp: dayData.xp
                })
            }
            
            return data
        },
        
        maxTasks() {
            return Math.max(...this.chartData.map(d => d.tasks), 1)
        },
        
        maxXP() {
            return Math.max(...this.chartData.map(d => d.xp), 1)
        },
        
        yAxisTicks() {
            const max = Math.max(this.maxTasks, this.maxXP / 10) // Scale XP down for display
            const step = Math.ceil(max / 4)
            return Array.from({ length: 5 }, (_, i) => (4 - i) * step)
        }
    },
    
    methods: {
        ...mapActions('quest', ['loadHistory']),
        
        formatNumber(num) {
            return new Intl.NumberFormat().format(num)
        },
        
        formatRelativeTime(dateString) {
            const date = new Date(dateString)
            const now = new Date()
            const diffInSeconds = Math.floor((now - date) / 1000)
            
            if (diffInSeconds < 60) {
                return this.t('quest', 'Just now')
            } else if (diffInSeconds < 3600) {
                const minutes = Math.floor(diffInSeconds / 60)
                return this.t('quest', '{minutes}m ago', { minutes })
            } else if (diffInSeconds < 86400) {
                const hours = Math.floor(diffInSeconds / 3600)
                return this.t('quest', '{hours}h ago', { hours })
            } else if (diffInSeconds < 604800) {
                const days = Math.floor(diffInSeconds / 86400)
                return this.t('quest', '{days}d ago', { days })
            } else {
                return date.toLocaleDateString()
            }
        },
        
        getXPBadgeClass(xpEarned) {
            if (xpEarned >= 25) return 'high'
            if (xpEarned >= 15) return 'medium'
            return 'low'
        },
        
        getXPBadgeText(xpEarned) {
            if (xpEarned >= 25) return this.t('quest', 'High XP')
            if (xpEarned >= 15) return this.t('quest', 'Good XP')
            return this.t('quest', 'Base XP')
        },
        
        async loadData() {
            await this.loadHistory({ limit: 50, offset: 0 })
        },
        
        async loadMoreHistory() {
            const currentCount = this.history.length
            await this.loadHistory({ limit: 20, offset: currentCount })
            this.displayedHistoryCount = Math.min(this.displayedHistoryCount + 10, this.history.length)
        },
        
        showTooltip(event, dayData) {
            this.tooltip.show = true
            this.tooltip.data = dayData
            
            const rect = event.currentTarget.getBoundingClientRect()
            this.tooltip.style = {
                left: `${rect.left + rect.width / 2}px`,
                top: `${rect.top - 10}px`,
                transform: 'translate(-50%, -100%)'
            }
        },
        
        hideTooltip() {
            this.tooltip.show = false
        }
    },
    
    mounted() {
        this.loadData()
    }
}
</script>

<style scoped>
.stats-panel {
    width: 100%;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.panel-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
}

.time-filter select {
    padding: 6px 12px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    background: var(--color-main-background);
    color: var(--color-main-text);
    font-size: 14px;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: var(--color-background-hover);
    border-radius: 8px;
    border: 1px solid var(--color-border);
}

.stat-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 20px;
    font-weight: bold;
    color: var(--color-primary);
    line-height: 1;
}

.stat-label {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 2px;
}

.chart-section {
    background: var(--color-main-background);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--color-border);
    margin-bottom: 30px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.chart-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
}

.chart-legend {
    display: flex;
    gap: 15px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--color-text-lighter);
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 2px;
}

.legend-color.tasks { background: var(--color-primary); }
.legend-color.xp { background: var(--color-success); }

.activity-chart {
    height: 200px;
    overflow-x: auto;
}

.chart-container {
    display: flex;
    height: 100%;
    min-width: 600px;
}

.chart-y-axis {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding-right: 10px;
    width: 40px;
    font-size: 11px;
    color: var(--color-text-lighter);
}

.y-tick {
    height: 1px;
    display: flex;
    align-items: center;
}

.chart-area {
    flex: 1;
    position: relative;
    display: flex;
    flex-direction: column;
}

.chart-grid {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.grid-line {
    height: 1px;
    background: var(--color-border);
    opacity: 0.5;
}

.chart-bars {
    display: flex;
    align-items: flex-end;
    height: calc(100% - 20px);
    gap: 2px;
}

.bar-group {
    flex: 1;
    display: flex;
    align-items: flex-end;
    gap: 1px;
    cursor: pointer;
    position: relative;
}

.bar {
    flex: 1;
    min-height: 2px;
    border-radius: 2px 2px 0 0;
    transition: all 0.2s ease;
}

.bar.tasks { background: var(--color-primary); }
.bar.xp { background: var(--color-success); }

.bar-group:hover .bar {
    opacity: 0.8;
    transform: scaleY(1.1);
}

.bar-label {
    position: absolute;
    bottom: -18px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 10px;
    color: var(--color-text-lighter);
    white-space: nowrap;
}

.history-section {
    background: var(--color-main-background);
    border-radius: 8px;
    padding: 20px;
    border: 1px solid var(--color-border);
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.history-header h4 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
}

.load-more-btn {
    padding: 6px 12px;
    background: var(--color-primary);
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.load-more-btn:hover:not(:disabled) {
    background: var(--color-primary-element);
}

.load-more-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.history-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    max-height: 400px;
    overflow-y: auto;
}

.history-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--color-background-hover);
    border-radius: 6px;
    border: 1px solid var(--color-border);
}

.history-icon {
    width: 24px;
    height: 24px;
    background: var(--color-success);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    flex-shrink: 0;
}

.history-info {
    flex: 1;
    min-width: 0;
}

.history-title {
    font-size: 14px;
    font-weight: 500;
    color: var(--color-main-text);
    margin-bottom: 2px;
    word-wrap: break-word;
}

.history-meta {
    display: flex;
    gap: 10px;
    font-size: 12px;
    color: var(--color-text-lighter);
}

.history-xp {
    color: var(--color-success);
    font-weight: 500;
}

.history-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
    flex-shrink: 0;
}

.history-badge.low {
    background: var(--color-background-dark);
    color: var(--color-text-lighter);
}

.history-badge.medium {
    background: var(--color-warning-background);
    color: var(--color-warning);
}

.history-badge.high {
    background: var(--color-success-background);
    color: var(--color-success);
}

.no-history {
    text-align: center;
    padding: 40px 20px;
    color: var(--color-text-lighter);
}

.no-history-icon {
    font-size: 32px;
    margin-bottom: 10px;
}

.no-history-text {
    line-height: 1.5;
}

/* Tooltip */
.chart-tooltip {
    position: fixed;
    background: var(--color-background-dark);
    border: 1px solid var(--color-border);
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    pointer-events: none;
}

.tooltip-date {
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 4px;
}

.tooltip-stats {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.tooltip-stat {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.tooltip-label {
    color: var(--color-text-lighter);
}

.tooltip-value {
    font-weight: 500;
    color: var(--color-main-text);
}

/* Theme Variations */
.theme-game .stat-card {
    background: linear-gradient(135deg, var(--color-background-hover), rgba(0, 168, 255, 0.05));
}

.theme-game .bar.tasks {
    background: linear-gradient(to top, var(--color-primary), #0078d4);
}

.theme-game .bar.xp {
    background: linear-gradient(to top, var(--color-success), #20bf6b);
}

/* Responsive Design */
@media (max-width: 768px) {
    .panel-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .quick-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .stat-card {
        padding: 12px;
        gap: 10px;
    }
    
    .stat-icon {
        font-size: 20px;
    }
    
    .stat-value {
        font-size: 16px;
    }
    
    .chart-legend {
        gap: 10px;
    }
    
    .chart-container {
        min-width: 400px;
    }
    
    .history-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .history-item {
        padding: 10px;
        gap: 10px;
    }
    
    .history-meta {
        flex-direction: column;
        gap: 2px;
    }
}

@media (max-width: 480px) {
    .quick-stats {
        grid-template-columns: 1fr;
    }
    
    .chart-section,
    .history-section {
        padding: 15px;
    }
}
</style>