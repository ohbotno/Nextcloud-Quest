/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 * 
 * Quest API Utility Functions
 * 
 * IMPORTANT: This file provides low-level API utilities only.
 * For stats data, use StatsManager.registerConsumer() instead of calling getUserStats() directly.
 * StatsManager provides caching, error handling, and unified event system.
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

class QuestAPI {
    constructor() {
        this.baseURL = generateUrl('/apps/quest/api')
    }
    
    /**
     * Get user stats (utility function for StatsManager)
     * NOTE: This should only be used by StatsManager. 
     * All other components should use StatsManager.registerConsumer() instead.
     * @returns {Promise<Object>}
     * @deprecated Use StatsManager instead of calling this directly
     */
    async getUserStats() {
        // api.getUserStats() should only be used by StatsManager
        const response = await axios.get(`${this.baseURL}/stats`)
        return response.data
    }
    
    /**
     * Get achievements
     * @returns {Promise<Object>}
     */
    async getAchievements() {
        const response = await axios.get(`${this.baseURL}/achievements`)
        return response.data
    }
    
    /**
     * Complete a task
     * @param {string} taskId 
     * @param {string} taskTitle 
     * @param {string} priority 
     * @returns {Promise<Object>}
     */
    async completeTask(taskId, taskTitle, priority = 'medium') {
        const response = await axios.post(`${this.baseURL}/complete-task`, {
            taskId,
            taskTitle,
            priority
        })
        return response.data
    }
    
    /**
     * Complete a task from a specific task list
     * @param {string|number} taskId 
     * @param {string|number} listId 
     * @returns {Promise<Object>}
     */
    async completeTaskFromList(taskId, listId) {
        const response = await axios.post(`${this.baseURL}/complete-quest`, {
            task_id: taskId,
            list_id: listId
        })
        return response.data
    }
    
    /**
     * Get completion history
     * @param {number} limit 
     * @param {number} offset 
     * @returns {Promise<Object>}
     */
    async getHistory(limit = 50, offset = 0) {
        const response = await axios.get(`${this.baseURL}/history`, {
            params: { limit, offset }
        })
        return response.data
    }
    
    /**
     * Get leaderboard
     * @param {string} orderBy 
     * @param {number} limit 
     * @param {number} offset 
     * @returns {Promise<Object>}
     */
    async getLeaderboard(orderBy = 'lifetime_xp', limit = 10, offset = 0) {
        const response = await axios.get(`${this.baseURL}/leaderboard`, {
            params: { orderBy, limit, offset }
        })
        return response.data
    }
    
    /**
     * Get user settings
     * @returns {Promise<Object>}
     */
    async getSettings() {
        const response = await axios.get(`${this.baseURL}/settings`)
        return response.data
    }
    
    /**
     * Update user settings
     * @param {Object} settings 
     * @returns {Promise<Object>}
     */
    async updateSettings(settings) {
        const response = await axios.put(`${this.baseURL}/settings`, settings)
        return response.data
    }
    
    /**
     * Get quest lists from Tasks app
     * @returns {Promise<Object>}
     */
    async getQuestLists() {
        const response = await axios.get(`${this.baseURL}/quest-lists`)
        return response.data
    }
}

export default new QuestAPI()