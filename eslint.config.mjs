import eslint from '@eslint/js';
import tseslint from 'typescript-eslint';
import pluginPromise from 'eslint-plugin-promise'
import globals from 'globals';
import ConfusingGlobals from 'confusing-browser-globals';

const config = tseslint.config(
    {
        files: ['web/typescript/**/*.ts'],
        languageOptions: {
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
            globals: {
                ...globals.browser,
                ...globals.jquery,
            },
        },
        extends: [
            eslint.configs.recommended,
            ...tseslint.configs.recommended,
            pluginPromise.configs['flat/recommended'],
        ],
        rules: {
            'no-console': 'off',
            'no-debugger': 'off',
            '@typescript-eslint/no-deprecated': 'warn',
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-inferrable-types': 'off',
            '@typescript-eslint/no-this-alias': 'off',
            'no-restricted-globals': ['error', ...ConfusingGlobals],
            'no-prototype-builtins': 'off',
            '@typescript-eslint/no-empty-function': 'off',//todo
            '@typescript-eslint/no-unused-vars': 'off',//todo
            'no-empty': 'off',//todo
            'no-useless-escape': 'off',//todo
            'prefer-const': 'off',//todo
            'promise/always-return': 'off',//todo
            'promise/no-callback-in-promise': 'off',//todo
        },
    },
);

export default config;
