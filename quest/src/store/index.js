/**
 * @copyright Copyright (c) 2025 Quest Team
 *
 * @license GNU AGPL version 3 or any later version
 */

import Vue from 'vue'
import Vuex from 'vuex'
import quest from './modules/quest'

Vue.use(Vuex)

export default new Vuex.Store({
    modules: {
        quest
    },
    strict: process.env.NODE_ENV !== 'production'
})