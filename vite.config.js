import { globSync } from 'node:fs';
import path from 'node:path';
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { scssPlugin } from './assets/vite/scss-plugin.js';
import { legacyBundlesPlugin } from './assets/vite/legacy-bundles-plugin.js';
import { copyNpmFilesPlugin } from './assets/vite/copy-npm-files-plugin.js';

// Antragsgrün serves unbundled ES modules to the browser (see StaticResourceTools):
// PHP views import files like /js/modules/backend/VotingAdmin.js directly, and an
// import map handles CDN paths / cache busting. Therefore this build does NOT bundle
// application code - it compiles each .vue SFC into a standalone ES module in
// web/js/vue/, keeping all /js/... and /npm/... imports as external URLs.
const vueEntries = Object.fromEntries(
    globSync('web_src/js/vue/**/*.vue').map((file) => [
        'js/vue/' + path.relative('web_src/js/vue', file).replace(/\.vue$/, ''),
        path.resolve(file),
    ])
);

// npm packages that are pre-bundled into single ESM files served from web/npm/
// (vue and sortablejs stay external, remapped to their web/npm/ URLs below)
const npmEntries = {
    'npm/vuedraggable': path.resolve('node_modules/vuedraggable/src/vuedraggable.js'),
    'npm/vue-draggable-plus': path.resolve('node_modules/vue-draggable-plus/dist/vue-draggable-plus.js'),
};

export default defineConfig({
    publicDir: false,
    plugins: [
        vue(),
        scssPlugin(),
        legacyBundlesPlugin(),
        copyNpmFilesPlugin(),
    ],
    build: {
        outDir: 'web',
        // web/ is the document root and contains hand-written files (web/js/modules/ etc.)
        emptyOutDir: false,
        modulePreload: false,
        reportCompressedSize: false,
        rollupOptions: {
            input: { ...vueEntries, ...npmEntries },
            // the entries are consumed as ES modules by the browser - keep their exports
            preserveEntrySignatures: 'strict',
            external: (id) => id === 'vue' || id === 'sortablejs' || id.startsWith('/js/') || id.startsWith('/npm/'),
            // keep external imports like /js/vue/Translate.vue.js as absolute URLs
            makeAbsoluteExternalsRelative: false,
            output: {
                format: 'es',
                entryFileNames: '[name].js',
                // shared helper chunks (e.g. the @vitejs/plugin-vue export helper);
                // no hash, as the output files are committed to git
                chunkFileNames: 'js/vue/[name].js',
                paths: {
                    vue: '/npm/vue.runtime.esm-browser.prod.js',
                    sortablejs: '/npm/sortable.esm.js',
                },
            },
        },
    },
});
