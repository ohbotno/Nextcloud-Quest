module.exports = {
    extends: [
        '@nextcloud/eslint-config',
        'eslint:recommended'
    ],
    parserOptions: {
        ecmaVersion: 2022,
        sourceType: 'module'
    },
    env: {
        browser: true,
        es6: true,
        node: true,
        jest: true
    },
    globals: {
        t: 'readonly',
        n: 'readonly',
        OC: 'readonly',
        OCA: 'readonly',
        OCP: 'readonly',
        __webpack_nonce__: 'writable',
        __webpack_public_path__: 'writable'
    },
    rules: {
        // Customize rules as needed
        'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
        'vue/no-unused-vars': 'error',
        'vue/require-prop-types': 'error',
        'vue/require-default-prop': 'error',
        'vue/no-v-html': 'warn',
        'import/extensions': 'off',
        'import/no-unresolved': 'off'
    },
    overrides: [
        {
            files: ['**/*.vue'],
            rules: {
                'vue/multi-word-component-names': 'off'
            }
        }
    ]
}