import { readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { minify } from 'terser';

/**
 * Builds the legacy jQuery-based bundles by concatenating and minifying the
 * source files in order - deliberately NOT as an ES module build: these files
 * are classic scripts relying on the global jQuery, and bootbox's UMD wrapper
 * must run as a plain script so that it sets window.bootbox (which is used as
 * a global by web/js/modules/ and antragsgruen.js).
 */
const BUNDLES = {
    'web/js/antragsgruen.min.js': [
        'node_modules/entreprise7pro-bootstrap/js/tooltip.js',
        'node_modules/entreprise7pro-bootstrap/js/dropdown.js',
        'node_modules/entreprise7pro-bootstrap/js/modal.js',
        'node_modules/entreprise7pro-bootstrap/js/popover.js',
        'node_modules/bootbox/dist/bootbox.all.js',
        'web_src/js/jquery.isonscreen.js',
        'web_src/js/antragsgruen.js',
    ],
    'web/js/bootstrap-datetimepicker.min.js': [
        'web_src/js/bootstrap-datetimepicker.js',
    ],
};

async function buildBundle(outFile, files) {
    const sources = {};
    for (const file of files) {
        sources[file] = await readFile(file, 'utf8');
    }
    const result = await minify(sources, {
        sourceMap: {
            filename: path.basename(outFile),
            url: path.basename(outFile) + '.map',
            includeSources: true,
        },
    });
    await writeFile(outFile, result.code);
    await writeFile(outFile + '.map', result.map);
}

export function legacyBundlesPlugin() {
    let changedFiles = null; // null = first build, builds everything

    return {
        name: 'antragsgruen:legacy-bundles',
        buildStart() {
            for (const files of Object.values(BUNDLES)) {
                for (const file of files) {
                    this.addWatchFile(path.resolve(file));
                }
            }
        },
        watchChange(id) {
            (changedFiles ??= new Set()).add(id);
        },
        async closeBundle() {
            for (const [outFile, files] of Object.entries(BUNDLES)) {
                const needsBuild = changedFiles === null
                    || files.some((file) => changedFiles.has(path.resolve(file)));
                if (needsBuild) {
                    await buildBundle(outFile, files);
                    console.log(`[legacy-bundles] built ${outFile}`);
                }
            }
            changedFiles = new Set();
        },
    };
}
