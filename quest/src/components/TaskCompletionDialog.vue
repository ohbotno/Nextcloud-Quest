<template>
    <div class="task-dialog-overlay" @click="$emit('close')">
        <div class="task-dialog" @click.stop>
            <div class="dialog-header">
                <h3>{{ t('nextcloudquest', 'Complete Task') }}</h3>
                <button @click="$emit('close')" class="close-btn">×</button>
            </div>
            
            <form @submit.prevent="submitTask" class="dialog-content">
                <div class="form-group">
                    <label for="task-title">{{ t('nextcloudquest', 'Task Title') }}</label>
                    <input 
                        id="task-title"
                        v-model="taskData.title"
                        type="text"
                        :placeholder="t('nextcloudquest', 'Enter task description...')"
                        required
                        class="form-input"
                        maxlength="255"
                    />
                </div>
                
                <div class="form-group">
                    <label for="task-priority">{{ t('nextcloudquest', 'Priority') }}</label>
                    <select id="task-priority" v-model="taskData.priority" class="form-select">
                        <option value="low">{{ t('nextcloudquest', 'Low') }} (+0 bonus XP)</option>
                        <option value="medium">{{ t('nextcloudquest', 'Medium') }} (+5 bonus XP)</option>
                        <option value="high">{{ t('nextcloudquest', 'High') }} (+10 bonus XP)</option>
                    </select>
                </div>
                
                <!-- XP Preview -->
                <div class="xp-preview">
                    <div class="preview-header">{{ t('nextcloudquest', 'XP Preview') }}</div>
                    <div class="xp-breakdown">
                        <div class="xp-item">
                            <span class="xp-label">{{ t('nextcloudquest', 'Base XP:') }}</span>
                            <span class="xp-value">10</span>
                        </div>
                        <div class="xp-item">
                            <span class="xp-label">{{ t('nextcloudquest', 'Priority Bonus:') }}</span>
                            <span class="xp-value">+{{ getPriorityBonus() }}</span>
                        </div>
                        <div class="xp-item" v-if="stats.streak.current_streak > 0">
                            <span class="xp-label">{{ t('nextcloudquest', 'Streak Multiplier:') }}</span>
                            <span class="xp-value">×{{ getStreakMultiplier() }}</span>
                        </div>
                        <div class="xp-total">
                            <span class="xp-label">{{ t('nextcloudquest', 'Total XP:') }}</span>
                            <span class="xp-value total">{{ calculateTotalXP() }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Current Streak Info -->
                <div v-if="stats.streak.current_streak > 0" class="streak-info">
                    <div class="streak-icon">🔥</div>
                    <div class="streak-text">
                        {{ t('nextcloudquest', 'Current streak: {streak} days', { streak: stats.streak.current_streak }) }}
                    </div>
                </div>
                
                <div class="dialog-actions">
                    <button type="button" @click="$emit('close')" class="btn-secondary">
                        {{ t('nextcloudquest', 'Cancel') }}
                    </button>
                    <button 
                        type="submit" 
                        class="btn-primary"
                        :disabled="!taskData.title.trim() || submitting"
                    >
                        <span v-if="submitting" class="loading">⏳</span>
                        {{ submitting ? t('nextcloudquest', 'Completing...') : t('nextcloudquest', 'Complete Task') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex'

export default {
    name: 'TaskCompletionDialog',
    
    data() {
        return {
            taskData: {
                title: '',
                priority: 'medium'
            },
            submitting: false
        }
    },
    
    computed: {
        ...mapState('quest', ['stats'])
    },
    
    methods: {
        getPriorityBonus() {
            const bonuses = { low: 0, medium: 5, high: 10 }
            return bonuses[this.taskData.priority] || 0
        },
        
        getStreakMultiplier() {
            const multiplier = Math.min(1 + (this.stats.streak.current_streak * 0.1), 2.0)
            return multiplier.toFixed(1)
        },
        
        calculateTotalXP() {
            const base = 10
            const bonus = this.getPriorityBonus()
            const multiplier = this.stats.streak.current_streak > 0 ? parseFloat(this.getStreakMultiplier()) : 1
            
            return Math.round((base + bonus) * multiplier)
        },
        
        async submitTask() {
            if (!this.taskData.title.trim()) return
            
            this.submitting = true
            
            try {
                const taskData = {
                    taskId: 'manual_' + Date.now(), // Generate a unique ID for manual tasks
                    taskTitle: this.taskData.title.trim(),
                    priority: this.taskData.priority
                }
                
                await this.$emit('complete', taskData)
                
                // Reset form
                this.taskData.title = ''
                this.taskData.priority = 'medium'
                
            } catch (error) {
                console.error('Failed to complete task:', error)
                // Show error message (in a real app, you'd use a proper notification system)
                alert(this.t('nextcloudquest', 'Failed to complete task. Please try again.'))
            } finally {
                this.submitting = false
            }
        }
    },
    
    mounted() {
        // Focus the input when dialog opens
        this.$nextTick(() => {
            const input = this.$el.querySelector('#task-title')
            if (input) input.focus()
        })
    }
}
</script>

<style scoped>
.task-dialog-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.task-dialog {
    background: var(--color-main-background);
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 20px 0;
    margin-bottom: 20px;
}

.dialog-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: var(--color-main-text);
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
    transition: all 0.2s ease;
}

.close-btn:hover {
    background: var(--color-background-hover);
    color: var(--color-main-text);
}

.dialog-content {
    padding: 0 20px 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: var(--color-main-text);
    margin-bottom: 8px;
}

.form-input,
.form-select {
    width: 100%;
    padding: 12px;
    border: 1px solid var(--color-border);
    border-radius: 6px;
    background: var(--color-main-background);
    color: var(--color-main-text);
    font-size: 14px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.form-input:focus,
.form-select:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 2px rgba(var(--color-primary-rgb), 0.2);
}

.form-input::placeholder {
    color: var(--color-text-lighter);
}

.xp-preview {
    background: var(--color-background-hover);
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid var(--color-border);
}

.preview-header {
    font-size: 14px;
    font-weight: 600;
    color: var(--color-main-text);
    margin-bottom: 10px;
}

.xp-breakdown {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.xp-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
}

.xp-label {
    color: var(--color-text-lighter);
}

.xp-value {
    font-weight: 500;
    color: var(--color-success);
}

.xp-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 600;
    padding-top: 8px;
    margin-top: 8px;
    border-top: 1px solid var(--color-border);
}

.xp-total .xp-label {
    color: var(--color-main-text);
}

.xp-total .xp-value.total {
    color: var(--color-primary);
    font-size: 16px;
}

.streak-info {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--color-warning-background);
    border: 1px solid var(--color-warning);
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 20px;
}

.streak-icon {
    font-size: 16px;
}

.streak-text {
    font-size: 13px;
    color: var(--color-main-text);
    font-weight: 500;
}

.dialog-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn-secondary,
.btn-primary {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-secondary {
    background: var(--color-background-dark);
    color: var(--color-main-text);
    border: 1px solid var(--color-border);
}

.btn-secondary:hover {
    background: var(--color-background-hover);
}

.btn-primary {
    background: var(--color-primary);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background: var(--color-primary-element);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.loading {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Theme Variations */
.theme-game .xp-preview {
    background: linear-gradient(135deg, var(--color-background-hover), rgba(0, 168, 255, 0.05));
    border-color: rgba(0, 168, 255, 0.2);
}

.theme-game .btn-primary {
    background: linear-gradient(135deg, var(--color-primary), #0078d4);
    box-shadow: 0 4px 15px rgba(0, 168, 255, 0.3);
}

.theme-professional .xp-preview {
    border: 1px solid var(--color-border);
}

.theme-professional .btn-primary {
    background: var(--color-primary);
    box-shadow: none;
}

/* Responsive Design */
@media (max-width: 768px) {
    .task-dialog {
        width: 95%;
        margin: 20px;
    }
    
    .dialog-header {
        padding: 15px 15px 0;
    }
    
    .dialog-content {
        padding: 0 15px 15px;
    }
    
    .dialog-actions {
        flex-direction: column-reverse;
    }
    
    .btn-secondary,
    .btn-primary {
        width: 100%;
        justify-content: center;
    }
}
</style>