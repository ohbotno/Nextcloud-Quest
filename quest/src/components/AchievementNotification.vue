<template>
    <transition-group name="achievement-notification" @after-leave="$emit('close')">
        <div 
            v-for="(achievement, index) in visibleAchievements"
            :key="achievement.key"
            class="achievement-overlay"
            :style="{ zIndex: 1900 + index }"
        >
            <div class="achievement-notification" @click="hideAchievement(achievement.key)">
                <div class="notification-content">
                    <!-- Achievement Icon -->
                    <div class="achievement-icon-container">
                        <div class="icon-glow"></div>
                        <img 
                            :src="getAchievementIcon(achievement.icon)" 
                            :alt="achievement.name"
                            class="achievement-icon"
                        />
                        <div class="icon-sparkles">
                            <div v-for="i in 6" :key="i" class="sparkle" :style="getSparkleStyle(i)"></div>
                        </div>
                    </div>
                    
                    <!-- Achievement Info -->
                    <div class="achievement-info">
                        <div class="achievement-badge">{{ t('nextcloudquest', 'Achievement Unlocked!') }}</div>
                        <h3 class="achievement-name">{{ achievement.name }}</h3>
                        <p class="achievement-description">{{ achievement.description }}</p>
                        <div class="achievement-rarity" :class="getAchievementRarity(achievement.key)">
                            {{ getRarityName(getAchievementRarity(achievement.key)) }}
                        </div>
                    </div>
                    
                    <!-- Close Button -->
                    <button @click.stop="hideAchievement(achievement.key)" class="close-btn">
                        ×
                    </button>
                </div>
                
                <!-- Progress Bar -->
                <div class="notification-progress">
                    <div 
                        class="progress-fill"
                        :style="{ width: `${getProgressPercentage(index)}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </transition-group>
</template>

<script>
export default {
    name: 'AchievementNotification',
    
    props: {
        achievements: {
            type: Array,
            required: true
        }
    },
    
    data() {
        return {
            visibleAchievements: [],
            displayDuration: 4000, // 4 seconds per achievement
            achievementTimers: new Map()
        }
    },
    
    methods: {
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
        
        getSparkleStyle(index) {
            const angle = index * 60 // 6 sparkles in circle
            const radius = 40 + Math.random() * 20
            const x = Math.cos(angle * Math.PI / 180) * radius
            const y = Math.sin(angle * Math.PI / 180) * radius
            
            return {
                '--sparkle-x': `${x}px`,
                '--sparkle-y': `${y}px`,
                '--delay': `${index * 0.2}s`,
                '--duration': `${1.5 + Math.random() * 1}s`
            }
        },
        
        getProgressPercentage(index) {
            // Each achievement gets 4 seconds, show progress
            const achievement = this.visibleAchievements[index]
            if (!achievement) return 0
            
            const timer = this.achievementTimers.get(achievement.key)
            if (!timer) return 100
            
            return timer.progress
        },
        
        showAchievements() {
            // Show achievements one by one with a slight delay
            this.achievements.forEach((achievement, index) => {
                setTimeout(() => {
                    this.showAchievement(achievement)
                }, index * 500) // 500ms delay between achievements
            })
        },
        
        showAchievement(achievement) {
            if (this.visibleAchievements.some(a => a.key === achievement.key)) {
                return // Already showing
            }
            
            this.visibleAchievements.push(achievement)
            
            // Set up progress timer
            const startTime = Date.now()
            const timer = {
                progress: 100,
                interval: setInterval(() => {
                    const elapsed = Date.now() - startTime
                    const remaining = Math.max(0, this.displayDuration - elapsed)
                    const progress = (remaining / this.displayDuration) * 100
                    
                    timer.progress = progress
                    
                    if (progress <= 0) {
                        this.hideAchievement(achievement.key)
                    }
                }, 50) // Update every 50ms for smooth progress
            }
            
            this.achievementTimers.set(achievement.key, timer)
            
            // Play achievement sound
            this.playAchievementSound(achievement)
        },
        
        hideAchievement(achievementKey) {
            const index = this.visibleAchievements.findIndex(a => a.key === achievementKey)
            if (index !== -1) {
                this.visibleAchievements.splice(index, 1)
            }
            
            // Clear timer
            const timer = this.achievementTimers.get(achievementKey)
            if (timer) {
                clearInterval(timer.interval)
                this.achievementTimers.delete(achievementKey)
            }
            
            // If no more achievements, emit close
            if (this.visibleAchievements.length === 0) {
                this.$emit('close')
            }
        },
        
        playAchievementSound(achievement) {
            try {
                const rarity = this.getAchievementRarity(achievement.key)
                // Play different sounds based on rarity
                // Example: new Audio(`/apps/quest/sounds/achievement_${rarity}.mp3`).play()
            } catch (error) {
                // Sound playing failed, continue silently
            }
        }
    },
    
    mounted() {
        this.showAchievements()
    },
    
    beforeDestroy() {
        // Clear all timers
        this.achievementTimers.forEach(timer => {
            clearInterval(timer.interval)
        })
        this.achievementTimers.clear()
    }
}
</script>

<style scoped>
.achievement-overlay {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1900;
}

.achievement-notification {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    cursor: pointer;
    width: 350px;
    position: relative;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    position: relative;
}

.achievement-icon-container {
    position: relative;
    flex-shrink: 0;
}

.icon-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 80px;
    height: 80px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.3), transparent);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    animation: glow-pulse 2s ease-in-out infinite alternate;
}

.achievement-icon {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    position: relative;
    z-index: 2;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
}

.icon-sparkles {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

.sparkle {
    position: absolute;
    width: 4px;
    height: 4px;
    background: #fff;
    border-radius: 50%;
    animation: sparkle-dance var(--duration, 2s) ease-in-out var(--delay, 0s) infinite;
}

.achievement-info {
    flex: 1;
    min-width: 0;
}

.achievement-badge {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 4px 8px;
    border-radius: 4px;
    display: inline-block;
    margin-bottom: 8px;
}

.achievement-name {
    color: white;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 6px 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.achievement-description {
    color: rgba(255, 255, 255, 0.9);
    font-size: 13px;
    margin: 0 0 8px 0;
    line-height: 1.4;
}

.achievement-rarity {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
}

.achievement-rarity.common {
    background: rgba(108, 117, 125, 0.3);
    color: #adb5bd;
}

.achievement-rarity.uncommon {
    background: rgba(40, 167, 69, 0.3);
    color: #28a745;
}

.achievement-rarity.rare {
    background: rgba(0, 123, 255, 0.3);
    color: #007bff;
}

.achievement-rarity.epic {
    background: rgba(111, 66, 193, 0.3);
    color: #6f42c1;
}

.achievement-rarity.legendary {
    background: rgba(253, 126, 20, 0.3);
    color: #fd7e14;
    animation: legendary-glow 2s ease-in-out infinite alternate;
}

.close-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: rgba(255, 255, 255, 0.3);
}

.notification-progress {
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    position: relative;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.1s linear;
}

/* Rarity-specific notification backgrounds */
.achievement-notification.common {
    background: linear-gradient(135deg, #6c757d, #495057);
}

.achievement-notification.uncommon {
    background: linear-gradient(135deg, #28a745, #20c997);
}

.achievement-notification.rare {
    background: linear-gradient(135deg, #007bff, #0056b3);
}

.achievement-notification.epic {
    background: linear-gradient(135deg, #6f42c1, #5a2d91);
}

.achievement-notification.legendary {
    background: linear-gradient(135deg, #fd7e14, #e8590c);
    box-shadow: 0 10px 30px rgba(253, 126, 20, 0.4);
}

/* Animations */
@keyframes glow-pulse {
    0% { opacity: 0.3; transform: translate(-50%, -50%) scale(1); }
    100% { opacity: 0.6; transform: translate(-50%, -50%) scale(1.1); }
}

@keyframes sparkle-dance {
    0%, 100% {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
    25% {
        opacity: 0.8;
        transform: translate(calc(var(--sparkle-x) * 0.3), calc(var(--sparkle-y) * 0.3)) scale(1.2);
    }
    50% {
        opacity: 0.6;
        transform: translate(calc(var(--sparkle-x) * 0.7), calc(var(--sparkle-y) * 0.7)) scale(0.8);
    }
    75% {
        opacity: 0.8;
        transform: translate(var(--sparkle-x), var(--sparkle-y)) scale(1.1);
    }
}

@keyframes legendary-glow {
    0% { text-shadow: 0 0 5px rgba(253, 126, 20, 0.5); }
    100% { text-shadow: 0 0 20px rgba(253, 126, 20, 0.8), 0 0 30px rgba(253, 126, 20, 0.4); }
}

/* Transition animations */
.achievement-notification-enter-active {
    animation: notification-slide-in 0.6s ease-out;
}

.achievement-notification-leave-active {
    animation: notification-slide-out 0.4s ease-in;
}

@keyframes notification-slide-in {
    0% {
        opacity: 0;
        transform: translateX(100%) scale(0.8);
    }
    60% {
        opacity: 1;
        transform: translateX(-10px) scale(1.05);
    }
    100% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

@keyframes notification-slide-out {
    0% {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translateX(100%) scale(0.9);
    }
}

/* Multiple notification stacking */
.achievement-notification:nth-child(2) {
    margin-top: 10px;
    transform: scale(0.95);
    opacity: 0.9;
}

.achievement-notification:nth-child(3) {
    margin-top: 20px;
    transform: scale(0.9);
    opacity: 0.8;
}

/* Theme Variations */
.theme-professional .achievement-notification {
    background: linear-gradient(135deg, var(--color-primary), #4a4a4a);
    border-color: var(--color-border);
}

.theme-professional .icon-glow {
    background: radial-gradient(circle, rgba(var(--color-primary-rgb), 0.2), transparent);
}

.theme-professional .sparkle {
    background: var(--color-primary);
}

/* Responsive Design */
@media (max-width: 768px) {
    .achievement-overlay {
        top: 10px;
        right: 10px;
        left: 10px;
    }
    
    .achievement-notification {
        width: auto;
    }
    
    .notification-content {
        padding: 15px;
        gap: 12px;
    }
    
    .achievement-icon {
        width: 50px;
        height: 50px;
    }
    
    .icon-glow {
        width: 70px;
        height: 70px;
    }
    
    .achievement-name {
        font-size: 15px;
    }
    
    .achievement-description {
        font-size: 12px;
    }
}

@media (max-width: 480px) {
    .notification-content {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .achievement-icon-container {
        align-self: center;
    }
    
    .close-btn {
        top: 5px;
        right: 5px;
    }
}
</style>