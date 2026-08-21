import { defineConfig } from "eslint/config";
import eslint from '@eslint/js';
import pluginPromise from 'eslint-plugin-promise'
import tseslint from 'typescript-eslint';
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
                "Isotope": "readonly",
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
            'no-prototype-builtins': 'off',
            'no-unused-vars': 'off',
            'promise/no-callback-in-promise': 'off',
        },
    },
    {
        files: [
            'e2e/**/*.ts',
        ],
        languageOptions: {
            parser: tseslint.parser,
            parserOptions: {
                project: './e2e/tsconfig.json',
                tsconfigRootDir: import.meta.dirname,
                ecmaVersion: 'latest',
                sourceType: 'module',
            },
            globals: {
                ...globals.node,
                ...globals.browser,
                ...globals.jquery,
                "CKEDITOR": "readonly",
                "bootbox": "readonly",
                "Sortable": "readonly",
                "ClipboardJS": "readonly",
                "Isotope": "readonly",
                "jQuery": "readonly",
            },
        },
        extends: [
            eslint.configs.recommended,
        ],
        rules: {
            'no-console': 'warn',
            'no-unused-vars': 'off',
            'no-empty': 'off',
            'no-prototype-builtins': 'off',
        },
    },
);

export default config;
