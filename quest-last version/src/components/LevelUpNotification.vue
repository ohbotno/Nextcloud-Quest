<template>
    <transition name="level-up-notification" @after-leave="$emit('close')">
        <div v-if="show" class="level-up-overlay">
            <div class="level-up-notification" @click="show = false">
                <div class="notification-content">
                    <!-- Background Effects -->
                    <div class="background-effects">
                        <div class="rays"></div>
                        <div class="particles">
                            <div v-for="i in 12" :key="i" class="particle" :style="getParticleStyle(i)"></div>
                        </div>
                    </div>
                    
                    <!-- Main Content -->
                    <div class="main-content">
                        <div class="level-up-icon">🎉</div>
                        <h2 class="level-up-title">{{ t('nextcloudquest', 'LEVEL UP!') }}</h2>
                        <div class="level-display">
                            <div class="level-number">{{ level }}</div>
                        </div>
                        <div class="rank-title">{{ rankTitle }}</div>
                        <div class="celebration-message">
                            {{ t('nextcloudquest', 'Congratulations! You\'ve reached a new level of productivity!') }}
                        </div>
                        
                        <!-- Level Benefits -->
                        <div class="level-benefits">
                            <div class="benefits-title">{{ t('nextcloudquest', 'New Benefits Unlocked:') }}</div>
                            <ul class="benefits-list">
                                <li v-for="benefit in getLevelBenefits()" :key="benefit">{{ benefit }}</li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Action Button -->
                    <div class="notification-actions">
                        <button @click="show = false" class="continue-btn">
                            {{ t('nextcloudquest', 'Continue Quest') }} →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
export default {
    name: 'LevelUpNotification',
    
    props: {
        level: {
            type: Number,
            required: true
        },
        rankTitle: {
            type: String,
            required: true
        }
    },
    
    data() {
        return {
            show: true
        }
    },
    
    methods: {
        getLevelBenefits() {
            const benefits = []
            
            // Add level-specific benefits
            if (this.level === 5) {
                benefits.push(this.t('nextcloudquest', 'Custom theme options unlocked'))
                benefits.push(this.t('nextcloudquest', 'Advanced statistics available'))
            } else if (this.level === 10) {
                benefits.push(this.t('nextcloudquest', 'Detailed progress tracking'))
                benefits.push(this.t('nextcloudquest', 'Achievement insights'))
            } else if (this.level === 25) {
                benefits.push(this.t('nextcloudquest', 'Elite achievements unlocked'))
                benefits.push(this.t('nextcloudquest', 'Productivity insights'))
            } else if (this.level === 50) {
                benefits.push(this.t('nextcloudquest', 'Master tier benefits'))
                benefits.push(this.t('nextcloudquest', 'Custom avatar options'))
            } else if (this.level === 100) {
                benefits.push(this.t('nextcloudquest', 'Legendary status achieved'))
                benefits.push(this.t('nextcloudquest', 'Exclusive legendary badge'))
            } else {
                // Default benefits for other levels
                benefits.push(this.t('nextcloudquest', 'Increased XP multiplier'))
                benefits.push(this.t('nextcloudquest', 'New achievements available'))
            }
            
            return benefits
        },
        
        getParticleStyle(index) {
            const angle = (index * 30) - 15 // Spread particles around
            const radius = 80 + Math.random() * 40
            const x = Math.cos(angle * Math.PI / 180) * radius
            const y = Math.sin(angle * Math.PI / 180) * radius
            
            return {
                '--particle-x': `${x}px`,
                '--particle-y': `${y}px`,
                '--delay': `${index * 0.1}s`,
                '--duration': `${2 + Math.random() * 2}s`
            }
        }
    },
    
    mounted() {
        // Auto-close after 8 seconds
        setTimeout(() => {
            this.show = false
        }, 8000)
        
        // Play sound effect if available
        this.playLevelUpSound()
    },
    
    methods: {
        ...this.methods,
        
        playLevelUpSound() {
            // In a real implementation, you'd play a celebratory sound
            try {
                // Example: new Audio('/apps/quest/sounds/levelup.mp3').play()
            } catch (error) {
                // Sound playing failed, continue silently
            }
        }
    }
}
</script>

<style scoped>
.level-up-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2000;
    cursor: pointer;
}

.level-up-notification {
    position: relative;
    background: radial-gradient(circle, #ffd700, #ffed4e);
    border-radius: 20px;
    padding: 40px;
    max-width: 500px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(255, 215, 0, 0.4);
    overflow: hidden;
    cursor: default;
}

.background-effects {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    overflow: hidden;
}

.rays {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 200%;
    height: 200%;
    background: repeating-conic-gradient(
        from 0deg at center,
        transparent 0deg,
        rgba(255, 255, 255, 0.1) 2deg,
        transparent 4deg
    );
    animation: rotate 8s linear infinite;
    transform: translate(-50%, -50%);
}

.particles {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.particle {
    position: absolute;
    width: 6px;
    height: 6px;
    background: #fff;
    border-radius: 50%;
    animation: particle-float var(--duration, 3s) ease-out var(--delay, 0s) infinite;
}

.main-content {
    position: relative;
    z-index: 10;
}

.level-up-icon {
    font-size: 48px;
    margin-bottom: 10px;
    animation: bounce 1s ease-out infinite alternate;
}

.level-up-title {
    font-size: 36px;
    font-weight: 900;
    color: #8b4513;
    margin: 0 0 20px 0;
    text-shadow: 2px 2px 4px rgba(139, 69, 19, 0.3);
    letter-spacing: 2px;
}

.level-display {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}

.level-number {
    font-size: 72px;
    font-weight: 900;
    color: #8b4513;
    text-shadow: 3px 3px 6px rgba(139, 69, 19, 0.4);
    border: 4px solid #8b4513;
    border-radius: 50%;
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.2);
    animation: pulse-scale 2s ease-in-out infinite alternate;
}

.rank-title {
    font-size: 24px;
    font-weight: 700;
    color: #8b4513;
    margin-bottom: 20px;
    text-shadow: 1px 1px 2px rgba(139, 69, 19, 0.2);
}

.celebration-message {
    font-size: 16px;
    color: #5d4e37;
    margin-bottom: 25px;
    line-height: 1.5;
    font-weight: 500;
}

.level-benefits {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    text-align: left;
}

.benefits-title {
    font-size: 16px;
    font-weight: 600;
    color: #8b4513;
    margin-bottom: 10px;
    text-align: center;
}

.benefits-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.benefits-list li {
    padding: 5px 0;
    color: #5d4e37;
    font-weight: 500;
    position: relative;
    padding-left: 20px;
}

.benefits-list li::before {
    content: '✨';
    position: absolute;
    left: 0;
    top: 5px;
}

.notification-actions {
    position: relative;
    z-index: 10;
}

.continue-btn {
    background: linear-gradient(135deg, #ff6b35, #f7931e);
    color: white;
    border: none;
    padding: 15px 30px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.continue-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 53, 0.6);
}

/* Animations */
@keyframes rotate {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

@keyframes bounce {
    0% { transform: translateY(0); }
    100% { transform: translateY(-10px); }
}

@keyframes pulse-scale {
    0% { transform: scale(1); }
    100% { transform: scale(1.05); }
}

@keyframes particle-float {
    0% {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
    50% {
        opacity: 0.8;
        transform: translate(calc(var(--particle-x) * 0.5), calc(var(--particle-y) * 0.5)) scale(1.2);
    }
    100% {
        opacity: 0;
        transform: translate(var(--particle-x), var(--particle-y)) scale(0.8);
    }
}

/* Transition animations */
.level-up-notification-enter-active {
    animation: notification-enter 0.8s ease-out;
}

.level-up-notification-leave-active {
    animation: notification-leave 0.5s ease-in;
}

@keyframes notification-enter {
    0% {
        opacity: 0;
        transform: scale(0.3) rotate(-10deg);
    }
    50% {
        opacity: 1;
        transform: scale(1.1) rotate(2deg);
    }
    100% {
        opacity: 1;
        transform: scale(1) rotate(0deg);
    }
}

@keyframes notification-leave {
    0% {
        opacity: 1;
        transform: scale(1);
    }
    100% {
        opacity: 0;
        transform: scale(0.8) translateY(-50px);
    }
}

/* Theme Variations */
.theme-professional .level-up-notification {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.theme-professional .level-up-title,
.theme-professional .level-number,
.theme-professional .rank-title {
    color: var(--color-main-text);
}

.theme-professional .celebration-message,
.theme-professional .benefits-list li {
    color: var(--color-text-lighter);
}

.theme-professional .continue-btn {
    background: var(--color-primary);
}

.theme-professional .rays {
    display: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .level-up-notification {
        padding: 30px 20px;
        margin: 20px;
        width: calc(100% - 40px);
    }
    
    .level-up-title {
        font-size: 28px;
    }
    
    .level-number {
        font-size: 56px;
        width: 100px;
        height: 100px;
    }
    
    .rank-title {
        font-size: 20px;
    }
    
    .celebration-message {
        font-size: 14px;
    }
    
    .level-benefits {
        padding: 15px;
    }
    
    .continue-btn {
        padding: 12px 24px;
        font-size: 14px;
    }
}

@media (max-width: 480px) {
    .level-up-notification {
        padding: 25px 15px;
    }
    
    .level-up-title {
        font-size: 24px;
        letter-spacing: 1px;
    }
    
    .level-number {
        font-size: 48px;
        width: 80px;
        height: 80px;
    }
    
    .rank-title {
        font-size: 18px;
    }
}
</style>