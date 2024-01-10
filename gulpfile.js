const gulp = require('gulp'),
    terser = require('gulp-terser'),
    concat = require('gulp-concat'),
    sass = require('gulp-sass')(require('sass')),
    ts = require('gulp-typescript'),
    postcss = require('gulp-postcss'),
    autoprefixer = require('autoprefixer'),
    tsProject = ts.createProject('tsconfig.json'),
    sourcemaps = require('gulp-sourcemaps'),

    main_js_files = [
        "node_modules/bootstrap/dist/js/bootstrap.js", "node_modules/bootbox/bootbox.all.js", "web/js/scrollintoview.js", "web/js/jquery.isonscreen.js",
        "node_modules/intl/dist/Intl.min.js"
    ];

async function taskCopyFiles() {
    await gulp.src("node_modules/@selectize/selectize/dist/js/selectize.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/bootstrap-toggle/css/bootstrap-toggle.min.css").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/bootstrap-toggle/js/bootstrap-toggle.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/clipboard/dist/clipboard.min.js").pipe(terser()).pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/corejs-typeahead/dist/typeahead.bundle.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/isotope-layout/dist/isotope.pkgd.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/jquery/dist/jquery.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/moment/min/moment-with-locales.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/requirejs/require.js").pipe(terser()).pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/sortablejs/Sortable.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/vue/dist/vue.global.prod.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/vuedraggable/dist/vuedraggable.umd.min.js").pipe(gulp.dest('./web/npm/'));
}

function taskBuildTypescript() {
    return gulp.src('web/typescript/**/*.ts')
        .pipe(sourcemaps.init())
        .pipe(tsProject())
        .js
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/js/build/'));
}

function taskBuildJsMain() {
    return gulp.src(main_js_files)
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildDatetimepicker() {
    return gulp.src(["web/js/bootstrap-datetimepicker.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('bootstrap-datetimepicker.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsDe() {
    return gulp.src(["web/js/antragsgruen-de.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-de.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsFr() {
    return gulp.src(["web/js/antragsgruen-fr.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-fr.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsNl() {
    return gulp.src(["web/js/antragsgruen-nl.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-nl.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsCa() {
    return gulp.src(["web/js/antragsgruen-ca.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-ca.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsEn() {
    return gulp.src(["web/js/antragsgruen-en.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-en.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsEnGb() {
    return gulp.src(["web/js/antragsgruen-en-gb.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-en-gb.min.js'))
        .pipe(terser())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

const taskBuildJs = gulp.parallel(taskBuildJsMain, taskBuildJsDe, taskBuildJsFr, taskBuildJsNl, taskBuildJsCa, taskBuildJsEn, taskBuildJsEnGb, taskBuildDatetimepicker);

function taskBuildCss() {
    return gulp.src("web/css/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass.sync({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/css/'));
}

function taskBuildPluginCss() {
    return gulp.src("plugins/**/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass.sync({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('plugins/'));
}

function taskWatch() {
    gulp.watch(main_js_files, {usePolling: true}, taskBuildJs);
    gulp.watch(["web/js/antragsgruen-de.js", "web/js/antragsgruen-en.js", "web/js/antragsgruen-en-gb.js"], {usePolling: true}, taskBuildJs);
    gulp.watch(["web/js/bootstrap-datetimepicker.js"], {usePolling: true}, taskBuildDatetimepicker);
    gulp.watch(["web/css/*.scss"], {usePolling: true}, gulp.parallel(taskBuildCss, taskBuildPluginCss));
    gulp.watch(["plugins/**/*.scss"], {usePolling: true}, taskBuildPluginCss);
    gulp.watch(['web/typescript/**/*.ts'], {usePolling: true}, taskBuildTypescript);
}

gulp.task('build-js', taskBuildJs);
gulp.task('build-typescript', taskBuildTypescript);
gulp.task('build-css', taskBuildCss);
gulp.task('build-plugin-css', taskBuildPluginCss);
gulp.task('copy-files', taskCopyFiles);
gulp.task('watch', taskWatch);

gulp.task('default', gulp.parallel(taskBuildJs, taskBuildTypescript, taskBuildCss, taskCopyFiles, taskBuildPluginCss));
