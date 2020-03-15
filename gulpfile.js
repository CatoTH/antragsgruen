const gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    sass = require('gulp-sass'),
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
    await gulp.src("node_modules/fuelux/dist/css/fuelux*").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/fuelux/dist/js/fuelux*").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/sortablejs/Sortable.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/corejs-typeahead/dist/typeahead.bundle.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/moment/min/moment-with-locales.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/jquery/dist/jquery.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/requirejs/require.js").pipe(uglify()).pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/clipboard/dist/clipboard.min.js").pipe(uglify()).pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/bootstrap-toggle/css/bootstrap-toggle.min.css").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/bootstrap-toggle/js/bootstrap-toggle.min.js").pipe(gulp.dest('./web/npm/'));
    await gulp.src("node_modules/isotope-layout/dist/isotope.pkgd.min.js").pipe(gulp.dest('./web/npm/'));
}

function taskBuildTypescript() {
    return gulp.src('web/typescript/**/*.ts')
        .pipe(sourcemaps.init())
        .pipe(tsProject())
        .js
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/js/build/'));
}

function taskBuildJsMain() {
    return gulp.src(main_js_files)
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildDatetimepicker() {
    return gulp.src(["web/js/bootstrap-datetimepicker.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('bootstrap-datetimepicker.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsDe() {
    return gulp.src(["web/js/antragsgruen-de.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-de.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsEn() {
    return gulp.src(["web/js/antragsgruen-en.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-en.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

function taskBuildJsEnGb() {
    return gulp.src(["web/js/antragsgruen-en-gb.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-en-gb.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
}

const taskBuildJs = gulp.parallel(taskBuildJsMain, taskBuildJsDe, taskBuildJsEn, taskBuildJsEnGb, taskBuildDatetimepicker);

function taskBuildCss() {
    return gulp.src("web/css/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/css/'));
}

function taskBuildPluginCss() {
    return gulp.src("plugins/**/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('plugins/'));
}

function taskWatch() {
    gulp.watch(main_js_files, taskBuildJs);
    gulp.watch(["web/js/antragsgruen-de.js", "web/js/antragsgruen-en.js", "web/js/antragsgruen-en-gb.js"], taskBuildJs);
    gulp.watch(["web/js/bootstrap-datetimepicker.js"], taskBuildDatetimepicker);
    gulp.watch(["web/css/*.scss"], gulp.parallel(taskBuildCss, taskBuildPluginCss));
    gulp.watch(["plugins/**/*.scss"], taskBuildPluginCss);
    gulp.watch(['./web/typescript/**/*.ts'], taskBuildTypescript);
}

gulp.task('build-js', taskBuildJs);
gulp.task('build-typescript', taskBuildTypescript);
gulp.task('build-css', taskBuildCss);
gulp.task('build-plugin-css', taskBuildPluginCss);
gulp.task('copy-files', taskCopyFiles);
gulp.task('watch', taskWatch);

gulp.task('default', gulp.parallel(taskBuildJs, taskBuildTypescript, taskBuildCss, taskCopyFiles, taskBuildPluginCss));
