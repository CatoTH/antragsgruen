import { globSync } from 'node:fs';
import { writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as sass from 'sass';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';

/**
 * Compiles the SCSS trees "in place": each non-partial .scss file becomes a .css
 * (+ .css.map) file next to its source. This cannot use Vite's own CSS pipeline,
 * as that only supports a single output directory.
 *
 * Note that the SCSS sources in web/css/ are also compiled at runtime by
 * components/CssCompiler.php (scssphp) for custom themes, so they need to stay
 * compatible with both compilers.
 */

/** @see https://sass-lang.com/documentation/js-api/interfaces/options/ */
const sassOptions = {
    style: 'compressed',
    loadPaths: ['web/'],
    quietDeps: true,
    silenceDeprecations: ['color-functions', 'import', 'global-builtin'],
    sourceMap: true,
};

const TREES = [
    { id: 'web-css', pattern: 'web/css/*.scss', root: 'web/css' },
    { id: 'plugins', pattern: 'plugins/**/*.scss', root: 'plugins', exclude: /node_modules/ },
    { id: 'html2pdf', pattern: 'assets/html2pdf/*.scss', root: 'assets/html2pdf' },
];

// The plugin themes import shared partials from web/css/ (via loadPaths),
// so a change there needs to rebuild the plugin CSS as well.
const REBUILD_TRIGGERS = {
    'web-css': ['web-css', 'plugins'],
    'plugins': ['plugins'],
    'html2pdf': ['html2pdf'],
};

function treeFiles(tree) {
    return globSync(tree.pattern).filter((file) => !(tree.exclude && tree.exclude.test(file)));
}

async function compileFile(scssFile) {
    const cssFile = scssFile.replace(/\.scss$/, '.css');
    const compiled = sass.compile(scssFile, sassOptions);

    // sass reports sources as file:// URLs; make them relative to the output file
    compiled.sourceMap.sources = compiled.sourceMap.sources.map(
        (source) => path.relative(path.dirname(cssFile), fileURLToPath(source))
    );

    const result = await postcss([autoprefixer()]).process(compiled.css, {
        from: cssFile,
        to: cssFile,
        map: {
            prev: compiled.sourceMap,
            inline: false,
            // explicit, as postcss otherwise omits the annotation comment
            // (the sass-generated input CSS does not carry one)
            annotation: path.basename(cssFile) + '.map',
        },
    });

    await writeFile(cssFile, result.css);
    await writeFile(cssFile + '.map', result.map.toString());
}

export function scssPlugin() {
    let changedFiles = null; // null = first build, compiles everything

    return {
        name: 'antragsgruen:scss',
        buildStart() {
            for (const tree of TREES) {
                for (const file of treeFiles(tree)) {
                    this.addWatchFile(path.resolve(file));
                }
            }
        },
        watchChange(id) {
            (changedFiles ??= new Set()).add(id);
        },
        async closeBundle() {
            const rebuildTrees = new Set();
            if (changedFiles === null) {
                TREES.forEach((tree) => rebuildTrees.add(tree.id));
            } else {
                for (const changed of changedFiles) {
                    if (!changed.endsWith('.scss')) {
                        continue;
                    }
                    for (const tree of TREES) {
                        if (changed.startsWith(path.resolve(tree.root) + path.sep)) {
                            REBUILD_TRIGGERS[tree.id].forEach((id) => rebuildTrees.add(id));
                        }
                    }
                }
            }
            changedFiles = new Set();

            for (const tree of TREES) {
                if (!rebuildTrees.has(tree.id)) {
                    continue;
                }
                const entries = treeFiles(tree).filter((file) => !path.basename(file).startsWith('_'));
                for (const file of entries) {
                    await compileFile(file);
                }
                console.log(`[scss] compiled ${entries.length} file(s) in ${tree.root}/`);
            }
        },
    };
}
