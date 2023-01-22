/* eslint-env node */

module.exports = {
    root: true,
    env: {
        browser: true,
        es2015: true
    },
    parser: '@typescript-eslint/parser',
    plugins: ['@typescript-eslint'],
    extends: [
        'eslint:recommended',
        'plugin:@typescript-eslint/recommended',
        'plugin:promise/recommended',
        // 'plugin:unicorn/recommended',// More checks.
    ],
    rules: {
        'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'off',
        'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
        '@typescript-eslint/no-explicit-any': 'off',
        '@typescript-eslint/no-inferrable-types': 'off',
        '@typescript-eslint/no-this-alias': 'off',
        'no-prototype-builtins': 'off',
        'unicorn/filename-case': 'off',
        'unicorn/prevent-abbreviations': 'off',
        '@typescript-eslint/no-empty-function': 'off',//todo
        '@typescript-eslint/no-unused-vars': 'off',//todo
        'no-empty': 'off',//todo
        'no-useless-escape': 'off',//todo
        'prefer-const': 'off',//todo
        'promise/always-return': 'off',//todo
        'promise/no-callback-in-promise': 'off',//todo
    },
}
