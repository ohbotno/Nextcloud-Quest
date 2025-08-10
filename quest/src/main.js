/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

import Vue from 'vue'
import QuestDashboard from './components/QuestDashboard.vue'
import store from './store'
import { generateFilePath } from '@nextcloud/router'
import { getRequestToken } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'

// Nextcloud compatibility
__webpack_nonce__ = btoa(getRequestToken())
__webpack_public_path__ = generateFilePath('nextcloudquest', '', 'js/')

// Load initial state
const initialUser = loadState('nextcloudquest', 'user')

// Initialize Vue app
Vue.mixin({
    methods: {
        t: window.t || ((app, text, vars) => text)
    }
})

// Mount the app
const app = new Vue({
    store,
    render: h => h(QuestDashboard),
    beforeCreate() {
        // Initialize store with user data
        this.$store.commit('setUser', initialUser)
        
        // Load initial quest data
        this.$store.dispatch('loadUserStats')
        this.$store.dispatch('loadAchievements')
    }
})

app.$mount('#nextcloud-quest-app')