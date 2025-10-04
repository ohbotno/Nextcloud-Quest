<template>
    <div class="progress-bar-container" :class="{ detailed: detailed }">
        <div class="progress-header">
            <div class="progress-info">
                <h4 v-if="detailed">{{ t('quest', 'Experience Points') }}</h4>
                <div class="xp-display">
                    <span class="current-xp">{{ formatNumber(stats.level.current_xp) }}</span>
                    <span class="separator">/</span>
                    <span class="target-xp">{{ formatNumber(stats.level.xp_for_next_level) }}</span>
                    <span class="xp-label">XP</span>
                </div>
                <div v-if="detailed" class="lifetime-xp">
                    {{ t('quest', 'Total: {xp} XP', { xp: formatNumber(stats.level.lifetime_xp) }) }}
                </div>
            </div>
            <div class="progress-percentage">
                {{ Math.round(stats.level.xp_progress) }}%
            </div>
        </div>
        
        <div class="progress-track">
            <div 
                class="progress-fill"
                :style="{ width: `${Math.min(stats.level.xp_progress, 100)}%` }"
                :class="{ 'level-complete': stats.level.xp_progress >= 100 }"
            >
                <div class="progress-shine"></div>
            </div>
            <div class="progress-markers" v-if="detailed">
                <div 
                    v-for="marker in progressMarkers"
                    :key="marker.position"
                    class="marker"
                    :style="{ left: `${marker.position}%` }"
                    :class="{ passed: marker.position <= stats.level.xp_progress }"
                >
                    <div class="marker-tooltip">{{ marker.label }}</div>
                </div>
            </div>
        </div>
        
        <div v-if="detailed" class="progress-details">
            <div class="detail-item">
                <span class="label">{{ t('quest', 'XP to next level:') }}</span>
                <span class="value">{{ formatNumber(stats.level.xp_to_next_level) }}</span>
            </div>
            <div class="detail-item">
                <span class="label">{{ t('quest', 'Next level:') }}</span>
                <span class="value">{{ stats.level.level + 1 }}</span>
            </div>
        </div>
        
        <!-- XP Gain Animation -->
        <transition name="xp-gain">
            <div v-if="showXPGain" class="xp-gain-animation">
                +{{ lastXPGain }} XP
            </div>
        </transition>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    name: 'ProgressBar',
    
    props: {
        detailed: {
            type: Boolean,
            default: false
        }
    },
    
    data() {
        return {
            showXPGain: false,
            lastXPGain: 0,
            previousXP: 0
        }
    },
    
    computed: {
        ...mapState('quest', ['stats']),
        
        progressMarkers() {
            if (!this.detailed) return []
            
            return [
                { position: 25, label: '25%' },
                { position: 50, label: '50%' },
                { position: 75, label: '75%' }
            ]
        }
    },
    
    watch: {
        'stats.level.current_xp': function(newXP, oldXP) {
            if (oldXP > 0 && newXP > oldXP) {
                this.animateXPGain(newXP - oldXP)
            }
        }
    },
    
    methods: {
        formatNumber(num) {
            return new Intl.NumberFormat().format(num)
        },
        
        animateXPGain(xpGained) {
            this.lastXPGain = xpGained
            this.showXPGain = true
            
            setTimeout(() => {
                this.showXPGain = false
            }, 2000)
        }
    }
}
</script>

<style scoped>
.progress-bar-container {
    width: 100%;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}

.progress-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--color-main-text);
}

.xp-display {
    display: flex;
    align-items: baseline;
    gap: 4px;
    font-size: 18px;
    font-weight: 600;
}

.current-xp {
    color: var(--color-primary);
}

.separator {
    color: var(--color-text-lighter);
}

.target-xp {
    color: var(--color-text-lighter);
}

.xp-label {
    font-size: 14px;
    color: var(--color-text-lighter);
    margin-left: 2px;
}

.lifetime-xp {
    font-size: 12px;
    color: var(--color-text-lighter);
    margin-top: 2px;
}

.progress-percentage {
    font-size: 16px;
    font-weight: 600;
    color: var(--color-primary);
}

.progress-track {
    position: relative;
    height: 12px;
    background: var(--color-border);
    border-radius: 6px;
    overflow: hidden;
    margin: 8px 0;
}

.detailed .progress-track {
    height: 16px;
    border-radius: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--color-primary), var(--color-primary-element));
    border-radius: inherit;
    transition: width 0.6s ease-out;
    position: relative;
    overflow: hidden;
}

.progress-fill.level-complete {
    background: linear-gradient(90deg, var(--color-success), #2ed573);
    animation: pulse 2s infinite;
}

.progress-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    animation: shine 2s infinite;
}

.progress-markers {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
}

.marker {
    position: absolute;
    top: -4px;
    width: 2px;
    height: 24px;
    background: var(--color-border-dark);
    border-radius: 1px;
    transform: translateX(-50%);
}

.marker.passed {
    background: var(--color-primary);
}

.marker-tooltip {
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--color-background-dark);
    color: var(--color-main-text);
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 4px;
    white-space: nowrap;
    opacity: 0;
    transition: opacity 0.2s ease;
    pointer-events: none;
}

.marker:hover .marker-tooltip {
    opacity: 1;
}

.progress-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-top: 10px;
    font-size: 14px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.detail-item .label {
    color: var(--color-text-lighter);
}

.detail-item .value {
    font-weight: 600;
    color: var(--color-main-text);
}

.xp-gain-animation {
    position: absolute;
    top: -30px;
    right: 0;
    background: var(--color-success);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    z-index: 10;
}

/* Animations */
@keyframes shine {
    0% { left: -100%; }
    50% { left: 100%; }
    100% { left: 100%; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.xp-gain-enter-active {
    animation: xpGainIn 0.5s ease-out;
}

.xp-gain-leave-active {
    animation: xpGainOut 1.5s ease-in;
}

@keyframes xpGainIn {
    0% {
        opacity: 0;
        transform: translateY(10px) scale(0.8);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes xpGainOut {
    0% {
        opacity: 1;
        transform: translateY(0);
    }
    70% {
        opacity: 1;
        transform: translateY(-10px);
    }
    100% {
        opacity: 0;
        transform: translateY(-20px);
    }
}

/* Theme Variations */
.theme-game .progress-fill {
    background: linear-gradient(90deg, #00a8ff, #0078d4);
    box-shadow: 0 2px 10px rgba(0, 168, 255, 0.3);
}

.theme-game .progress-fill.level-complete {
    background: linear-gradient(90deg, #2ed573, #20bf6b);
    box-shadow: 0 2px 10px rgba(46, 213, 115, 0.3);
}

.theme-professional .progress-fill {
    background: linear-gradient(90deg, var(--color-primary), #4a4a4a);
}

.theme-professional .progress-shine {
    display: none;
}

.theme-professional .progress-fill.level-complete {
    background: linear-gradient(90deg, var(--color-success), #1e8e3e);
}

/* Responsive Design */
@media (max-width: 768px) {
    .xp-display {
        font-size: 16px;
    }
    
    .progress-details {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .detail-item {
        font-size: 13px;
    }
    
    .marker-tooltip {
        display: none;
    }
}
</style>