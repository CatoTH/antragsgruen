import { defineConfig } from "rollup";
import { nodeResolve } from "@rollup/plugin-node-resolve";
import commonjs from "@rollup/plugin-commonjs";
import path from "path";

// ---------------------------------------------------------------------------
// Packages to bundle
// ---------------------------------------------------------------------------
const PACKAGES = {
    "vuedraggable": "node_modules/vuedraggable/src/vuedraggable.js",
    "vue-draggable-plus": "node_modules/vue-draggable-plus/dist/vue-draggable-plus.js",
};

// ---------------------------------------------------------------------------
// Externals and their browser-path replacements
// ---------------------------------------------------------------------------
const EXTERNALS = {
    vue:        "/npm/vue.runtime.esm-browser.prod.js",
    sortablejs: "/npm/sortable.esm.js",
};

// ---------------------------------------------------------------------------
// Plugin: rewrite bare external imports to web paths at the import-declaration
//         level so the final ESM file contains real URL strings.
// ---------------------------------------------------------------------------
function remapExternals(map) {
    const ids = Object.keys(map);
    return {
        name: "remap-externals",

        // Tell Rollup these ids are external (don't bundle them).
        options(opts) {
            const existing = opts.external ?? [];
            opts.external = [
                ...(Array.isArray(existing) ? existing : [existing]),
                ...ids,
            ];
            return opts;
        },

        // Rewrite the import path in the emitted chunk.
        renderChunk(code) {
            let result = code;
            for (const [bare, webPath] of Object.entries(map)) {
                // Match:  from "vue"  /  from 'vue'
                // Also:   import "vue"  (side-effect import)
                const re = new RegExp(
                    `(from\\s+|import\\s+)(['"])${escapeRegExp(bare)}\\2`,
                    "g"
                );
                result = result.replace(re, `$1$2${webPath}$2`);
            }
            return { code: result, map: null };
        },
    };
}

function escapeRegExp(s) {
    return s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

// ---------------------------------------------------------------------------
// Rollup configuration
// ---------------------------------------------------------------------------
export default defineConfig({
    input: PACKAGES,

    output: {
        dir: "web/npm/",
        format: "es",
        // Keep the remapped URLs as live bindings (no interop shim needed
        // because the targets are proper ESM files).
        generatedCode: {
            constBindings: true,
        },
    },

    plugins: [
        remapExternals(EXTERNALS),

        // Resolve modules that live inside node_modules/<package>/…
        // `modulesOnly` is intentionally false so CJS helpers inside the
        // bundle are also inlined.
        nodeResolve({
            // Restrict resolution to the package's own directory so we don't
            // accidentally pull in top-level node_modules for things we want
            // to keep external.
            moduleDirectories: [
                "node_modules",
            ],
            // Prefer the "module" field (ESM) over "main" (CJS) when available.
            mainFields: ["module", "browser", "main"],
        }),

        // Inline any CommonJS modules used by the package.
        commonjs(),
    ],
});
