import { defineConfig } from "eslint/config";
import eslint from '@eslint/js';
import pluginPromise from 'eslint-plugin-promise'
import globals from 'globals';
import ConfusingGlobals from 'confusing-browser-globals';

const config = defineConfig(
    {
        files: [
            'web/js/modules/**/*.js',
        ],
        languageOptions: {
            parserOptions: {
                projectService: true,
            },
            globals: {
                ...globals.browser,
                ...globals.jquery,
                "bootbox": "readonly",
                "CKEDITOR": "readonly",
                "Sortable": "readonly",
                "ClipboardJS": "readonly",
                "__t": "readonly",
            },
        },
        extends: [
            eslint.configs.recommended,
            pluginPromise.configs['flat/recommended'],
        ],
        rules: {
            'no-console': 'off',
            'no-debugger': 'off',
            'no-restricted-globals': ['error', ...ConfusingGlobals],
            /*
            'no-prototype-builtins': 'off',
            'no-empty': 'off',//todo
            'no-useless-escape': 'off',//todo
            'prefer-const': 'off',//todo
            'promise/always-return': 'off',//todo
            'promise/no-callback-in-promise': 'off',//todo
             */
        },
    },
);

export default config;
