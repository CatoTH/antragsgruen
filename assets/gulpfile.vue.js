// gulpfile.mjs (or gulpfile.js with "type": "module" in package.json)
import gulp from 'gulp';
import { compileScript, compileTemplate, compileStyle, parse } from '@vue/compiler-sfc';
import { Transform } from 'stream';
import path from 'path';
import crypto from 'crypto';

/**
 * Generates a short, stable scope ID from the file path.
 * This is used for CSS scoping (data-v-xxxxxx attributes).
 */
function generateScopeId(filePath) {
    return 'v-' + crypto.createHash('md5').update(filePath).digest('hex').slice(0, 8);
}

/**
 * Transforms a single .vue SFC into a JS module string.
 */
function transformVueSfc(source, filePath, vueUrl) {
    const id = generateScopeId(filePath);
    const filename = path.basename(filePath);

    // 1. Parse the SFC into its blocks
    const { descriptor, errors } = parse(source, { filename });

    if (errors.length) {
        Object.keys(errors).forEach( it =>
            console.warn(errors[it])
        )
        throw new Error(`[vue-sfc] Parse errors in ${filename}:\n` + errors.join('\n'));
    }

    const parts = [];

    // 2. Compile <script> / <script setup>
    let scriptCode = 'const __script = {};';
    if (descriptor.script || descriptor.scriptSetup) {
        const compiled = compileScript(descriptor, {
            id,
            // Enables <script setup> support
            inlineTemplate: !!descriptor.scriptSetup,
        });

        // Strip the default export so we can re-export it ourselves
        scriptCode = compiled.content
            .replace(/export default/, 'const __script =')
            // Handle `export { X as default }` pattern
            .replace(/export\s*\{\s*(\w+)\s+as\s+default\s*\}/, (_, name) => `const __script = ${name}`);
    }
    parts.push(scriptCode);

    // 3. Compile <template>
    if (descriptor.template && !descriptor.scriptSetup) {
        // With <script setup>, the template is already inlined above
        const compiled = compileTemplate({
            id,
            filename,
            source: descriptor.template.content,
            scoped: descriptor.styles.some((s) => s.scoped),
            compilerOptions: {
                scopeId: id,
                whitespace: "condense",
            },
        });

        // Rewrite any bare `from 'vue'` (or `from "vue"`) import to the provided URL
        const rewritten = compiled.code.replace(
            /from\s+(['"])vue\1/g,
            `from '${vueUrl}'`
        );

        if (compiled.errors.length) {
            throw new Error(`[vue-sfc] Template errors in ${filename}:\n` + compiled.errors.join('\n'));
        }

        parts.push(rewritten);
        parts.push('__script.render = render;');
    }

    // 4. Compile <style> blocks (inject into <head> at runtime)
    const styleChunks = descriptor.styles.map((style, i) => {
        const compiled = compileStyle({
            id,
            filename,
            source: style.content,
            scoped: style.scoped,
        });

        if (compiled.errors.length) {
            throw new Error(`[vue-sfc] Style errors in ${filename}:\n` + compiled.errors.join('\n'));
        }

        return compiled.code;
    });

    if (styleChunks.length) {
        const allStyles = styleChunks.join('\n').replace(/`/g, '\\`');
        parts.push(`
(function injectStyles() {
  const el = document.createElement('style');
  el.setAttribute('data-v-file', ${JSON.stringify(filename)});
  el.textContent = \`${allStyles}\`;
  document.head.appendChild(el);
})();`);
    }

    // 5. Attach scope ID and export
    parts.push(`__script.__scopeId = ${JSON.stringify(id)};`);
    parts.push(`__script.__file = ${JSON.stringify(filename)};`);
    parts.push('export default __script;');

    return parts.join('\n\n');
}

/**
 * Creates a Node.js Transform stream that converts .vue → .js
 */
export function createVueTransform(vuePath) {
    return new Transform({
        objectMode: true,
        transform(file, encoding, callback) {
            if (file.isNull()) return callback(null, file);

            if (file.isStream()) {
                return callback(new Error('[vue-sfc] Streaming vinyl files are not supported.'));
            }

            if (path.extname(file.path) !== '.vue') {
                return callback(null, file); // Pass non-.vue files through unchanged
            }

            try {
                const source = file.contents.toString('utf-8');
                const compiled = transformVueSfc(source, file.path, vuePath);

                // Swap out the file contents and rename .vue → .js
                file.contents = Buffer.from(compiled, 'utf-8');
                file.path = file.path.replace(/\.vue$/, '.js');

                callback(null, file);
            } catch (err) {
                callback(err);
            }
        },
    });
}
