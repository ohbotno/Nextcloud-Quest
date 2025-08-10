/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

class QuestAPI {
    constructor() {
        this.baseURL = generateUrl('/apps/nextcloudquest/api')
    }
    
    /**
     * Get user stats
     * @returns {Promise<Object>}
     */
    async getUserStats() {
        const response = await axios.get(`${this.baseURL}/user/stats`)
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
}

export default new QuestAPI()