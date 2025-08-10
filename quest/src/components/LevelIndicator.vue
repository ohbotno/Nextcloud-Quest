<template>
    <div class="level-indicator" :class="{ detailed: detailed }">
        <div class="level-header">
            <div class="level-badge" :class="levelBadgeClass">
                <div class="level-number">{{ stats.level.level }}</div>
                <div class="level-label">{{ t('nextcloudquest', 'Level') }}</div>
            </div>
            <div class="level-info">
                <h4 v-if="detailed">{{ t('nextcloudquest', 'Your Rank') }}</h4>
                <div class="rank-title">{{ stats.level.rank_title }}</div>
                <div v-if="!detailed" class="level-subtitle">
                    {{ t('nextcloudquest', 'Level {level}', { level: stats.level.level }) }}
                </div>
            </div>
        </div>
        
        <div v-if="detailed" class="level-details">
            <!-- Next Level Preview -->
            <div class="next-level-preview">
                <div class="preview-header">
                    <span class="preview-label">{{ t('nextcloudquest', 'Next Level') }}</span>
                    <span class="preview-level">{{ stats.level.level + 1 }}</span>
                </div>
                <div class="preview-rank">{{ getNextRankTitle() }}</div>
                <div class="preview-progress">
                    <span class="xp-needed">{{ formatNumber(stats.level.xp_to_next_level) }} XP {{ t('nextcloudquest', 'needed') }}</span>
                </div>
                
                <!-- Progress to Next Level -->
                <div class="level-progress">
                    <div class="progress-track">
                        <div 
                            class="progress-fill"
                            :style="{ width: `${Math.min(stats.level.xp_progress, 100)}%` }"
                        ></div>
                    </div>
                    <div class="progress-text">
                        {{ Math.round(stats.level.xp_progress) }}% {{ t('nextcloudquest', 'to next level') }}
                    </div>
                </div>
            </div>
            
            <!-- Level History/Achievements -->
            <div class="level-milestones">
                <div class="milestones-header">{{ t('nextcloudquest', 'Level Milestones') }}</div>
                <div class="milestones-list">
                    <div 
                        v-for="milestone in levelMilestones"
                        :key="milestone.level"
                        class="milestone-item"
                        :class="{ 
                            achieved: stats.level.level >= milestone.level,
                            current: stats.level.level === milestone.level,
                            next: stats.level.level + 1 === milestone.level
                        }"
                    >
                        <div class="milestone-level">{{ milestone.level }}</div>
                        <div class="milestone-info">
                            <div class="milestone-title">{{ milestone.title }}</div>
                            <div class="milestone-description">{{ milestone.description }}</div>
                        </div>
                        <div class="milestone-status">
                            <span v-if="stats.level.level >= milestone.level" class="achieved">✓</span>
                            <span v-else-if="stats.level.level + 1 === milestone.level" class="next">→</span>
                            <span v-else class="locked">🔒</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Summary -->
            <div class="level-stats">
                <div class="stat-item">
                    <div class="stat-label">{{ t('nextcloudquest', 'Total XP') }}</div>
                    <div class="stat-value">{{ formatNumber(stats.level.lifetime_xp) }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">{{ t('nextcloudquest', 'Current Level XP') }}</div>
                    <div class="stat-value">{{ formatNumber(stats.level.current_xp) }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">{{ t('nextcloudquest', 'Levels Gained') }}</div>
                    <div class="stat-value">{{ stats.level.level - 1 }}</div>
                </div>
            </div>
        </div>
        
        <!-- Level Up Animation Trigger -->
        <transition name="level-up">
            <div v-if="showLevelUpEffect" class="level-up-effect">
                <div class="level-up-text">{{ t('nextcloudquest', 'LEVEL UP!') }}</div>
                <div class="level-up-particles">
                    <div v-for="i in 8" :key="i" class="particle" :style="getParticleStyle(i)"></div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    name: 'LevelIndicator',
    
    props: {
        detailed: {
            type: Boolean,
            default: false
        }
    },
    
    data() {
        return {
            showLevelUpEffect: false,
            previousLevel: 0
        }
    },
    
    computed: {
        ...mapState('quest', ['stats']),
        
        levelBadgeClass() {
            const level = this.stats.level.level
            if (level >= 100) return 'legendary'
            if (level >= 50) return 'epic'
            if (level >= 25) return 'rare'
            if (level >= 10) return 'uncommon'
            return 'common'
        },
        
        levelMilestones() {
            return [
                {
                    level: 5,
                    title: 'Rising Star',
                    description: 'Unlock custom themes'
                },
                {
                    level: 10,
                    title: 'Quest Apprentice',
                    description: 'Access statistics dashboard'
                },
                {
                    level: 25,
                    title: 'Productivity Knight',
                    description: 'Advanced achievements unlocked'
                },
                {
                    level: 50,
                    title: 'Master Achiever',
                    description: 'Custom avatar options'
                },
                {
                    level: 100,
                    title: 'Legendary Quest Master',
                    description: 'Exclusive legendary badge'
                }
            ].filter(milestone => 
                milestone.level <= this.stats.level.level + 10 // Show current and next few milestones
            )
        }
    },
    
    watch: {
        'stats.level.level': function(newLevel, oldLevel) {
            if (oldLevel > 0 && newLevel > oldLevel) {
                this.triggerLevelUpEffect()
            }
            this.previousLevel = newLevel
        }
    },
    
    methods: {
        formatNumber(num) {
            return new Intl.NumberFormat().format(num)
        },
        
        getNextRankTitle() {
            // This would ideally come from the XPService
            const currentLevel = this.stats.level.level
            const rankTitles = {
                1: 'Task Novice',
                3: 'Task Initiate',
                5: 'Rising Star',
                10: 'Quest Apprentice',
                15: 'Achievement Hunter',
                20: 'Task Commander',
                25: 'Productivity Knight',
                30: 'Veteran Adventurer',
                40: 'Expert Quester',
                50: 'Master Achiever',
                75: 'Epic Champion',
                100: 'Legendary Quest Master'
            }
            
            // Find next rank title
            for (let level of Object.keys(rankTitles).sort((a, b) => parseInt(a) - parseInt(b))) {
                if (parseInt(level) > currentLevel) {
                    return rankTitles[level]
                }
            }
            
            return 'Legendary Quest Master'
        },
        
        triggerLevelUpEffect() {
            this.showLevelUpEffect = true
            setTimeout(() => {
                this.showLevelUpEffect = false
            }, 3000)
        },
        
        getParticleStyle(index) {
            const angle = (index * 45) - 22.5 // Spread particles in circle
            const radius = 50 + Math.random() * 30
            const x = Math.cos(angle * Math.PI / 180) * radius
            const y = Math.sin(angle * Math.PI / 180) * radius
            
            return {
                '--particle-x': `${x}px`,
                '--particle-y': `${y}px`,
                '--delay': `${index * 0.1}s`
            }
        }
    },
    
    mounted() {
        this.previousLevel = this.stats.level.level
    }
}
</script>

<style scoped>
.level-indicator {
    position: relative;
}

.level-header {
    display: flex;
    align-items: center;
    gap: 15px;
}

.level-badge {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid;
    position: relative;
    flex-shrink: 0;
    background: radial-gradient(circle, var(--badge-bg-inner), var(--badge-bg-outer));
}

.level-badge.common {
    --badge-bg-inner: #f8f9fa;
    --badge-bg-outer: #e9ecef;
    border-color: #6c757d;
    color: #495057;
}

.level-badge.uncommon {
    --badge-bg-inner: #e3f2fd;
    --badge-bg-outer: #bbdefb;
    border-color: #2196f3;
    color: #1565c0;
}

.level-badge.rare {
    --badge-bg-inner: #f3e5f5;
    --badge-bg-outer: #ce93d8;
    border-color: #9c27b0;
    color: #6a1b9a;
}

.level-badge.epic {
    --badge-bg-inner: #fff3e0;
    --badge-bg-outer: #ffcc02;
    border-color: #ff9800;
    color: #e65100;
    box-shadow: 0 0 20px rgba(255, 152, 0, 0.4);
}

.level-badge.legendary {
    --badge-bg-inner: #fce4ec;
    --badge-bg-outer: #ad1457;
    border-color: #e91e63;
    color: #fff;
    box-shadow: 0 0 25px rgba(233, 30, 99, 0.6);
    animation: legendary-glow 2s ease-in-out infinite alternate;
}

.level-number {
    font-size: 20px;
    font-weight: bold;
    line-height: 1;
}

.level-label {
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 600;
    margin-top: 2px;
}

.level-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
}

.rank-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--color-primary);
    margin-bottom: 2px;
}

.level-subtitle {
    font-size: 14px;
    color: var(--color-text-lighter);
}

.level-details {
    margin-top: 20px;
}

.next-level-preview {
    background: var(--color-background-dark);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.preview-label {
    font-size: 14px;
    color: var(--color-text-lighter);
}

.preview-level {
    font-size: 18px;
    font-weight: bold;
    color: var(--color-primary);
}

.preview-rank {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 8px;
}

.preview-progress {
    margin-bottom: 10px;
}

.xp-needed {
    font-size: 13px;
    color: var(--color-text-lighter);
}

.level-progress {
    margin-top: 10px;
}

.progress-track {
    height: 8px;
    background: var(--color-border);
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-element));
    border-radius: inherit;
    transition: width 0.6s ease-out;
}

.progress-text {
    font-size: 12px;
    color: var(--color-text-lighter);
    text-align: center;
    margin-top: 5px;
}

.level-milestones {
    margin-bottom: 20px;
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
    gap: 12px;
    padding: 10px;
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
    box-shadow: 0 0 10px rgba(var(--color-primary-rgb), 0.2);
}

.milestone-item.next {
    background: var(--color-warning-background);
    border-color: var(--color-warning);
}

.milestone-level {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: var(--color-primary);
    color: white;
    font-size: 12px;
    font-weight: bold;
    flex-shrink: 0;
}

.milestone-item.achieved .milestone-level {
    background: var(--color-success);
}

.milestone-info {
    flex: 1;
}

.milestone-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 2px;
}

.milestone-description {
    font-size: 12px;
    color: var(--color-text-lighter);
}

.milestone-status {
    font-size: 16px;
    flex-shrink: 0;
}

.milestone-status .achieved {
    color: var(--color-success);
}

.milestone-status .next {
    color: var(--color-warning);
}

.milestone-status .locked {
    color: var(--color-text-lighter);
}

.level-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    padding: 15px;
    background: var(--color-background-dark);
    border-radius: 8px;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-bottom: 4px;
}

.stat-value {
    font-size: 16px;
    font-weight: bold;
    color: var(--color-primary);
}

/* Level Up Effect */
.level-up-effect {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 1000;
}

.level-up-text {
    font-size: 24px;
    font-weight: bold;
    color: var(--color-warning);
    text-align: center;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    animation: level-up-text 3s ease-out;
}

.level-up-particles {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.particle {
    position: absolute;
    width: 6px;
    height: 6px;
    background: var(--color-warning);
    border-radius: 50%;
    animation: particle-burst 2s ease-out var(--delay, 0s) forwards;
}

/* Animations */
@keyframes legendary-glow {
    0% { box-shadow: 0 0 25px rgba(233, 30, 99, 0.6); }
    100% { box-shadow: 0 0 35px rgba(233, 30, 99, 0.8), 0 0 50px rgba(233, 30, 99, 0.4); }
}

@keyframes level-up-text {
    0% {
        opacity: 0;
        transform: scale(0.5) translateY(20px);
    }
    20% {
        opacity: 1;
        transform: scale(1.2) translateY(-10px);
    }
    40% {
        transform: scale(1) translateY(0);
    }
    100% {
        opacity: 0;
        transform: scale(1) translateY(-30px);
    }
}

@keyframes particle-burst {
    0% {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(var(--particle-x), var(--particle-y)) scale(0);
    }
}

.level-up-enter-active {
    animation: level-up-appear 0.5s ease-out;
}

.level-up-leave-active {
    animation: level-up-disappear 2.5s ease-in;
}

@keyframes level-up-appear {
    0% { opacity: 0; transform: translate(-50%, -50%) scale(0); }
    100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}

@keyframes level-up-disappear {
    0% { opacity: 1; }
    70% { opacity: 1; }
    100% { opacity: 0; }
}

/* Theme Variations */
.theme-game .level-badge.legendary {
    animation: legendary-glow 1.5s ease-in-out infinite alternate;
}

.theme-game .progress-fill {
    background: linear-gradient(90deg, #00a8ff, #0078d4);
    box-shadow: 0 2px 10px rgba(0, 168, 255, 0.3);
}

.theme-professional .level-badge {
    box-shadow: none;
}

.theme-professional .level-badge.legendary {
    animation: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.theme-professional .progress-fill {
    background: linear-gradient(90deg, var(--color-primary), #4a4a4a);
}

/* Responsive Design */
@media (max-width: 768px) {
    .level-badge {
        width: 50px;
        height: 50px;
    }
    
    .level-number {
        font-size: 16px;
    }
    
    .level-label {
        font-size: 8px;
    }
    
    .rank-title {
        font-size: 16px;
    }
    
    .level-stats {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .milestone-item {
        padding: 8px;
        gap: 10px;
    }
    
    .milestone-level {
        width: 25px;
        height: 25px;
        font-size: 10px;
    }
    
    .level-up-text {
        font-size: 20px;
    }
}
</style>