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
        "node_modules/bootstrap/dist/js/bootstrap.js", "node_modules/bootbox/bootbox.js", "web/js/scrollintoview.js", "web/js/jquery.isonscreen.js",
        "node_modules/intl/dist/Intl.min.js"
    ];

const taskCopyFiles = gulp.series((done) => {
    gulp.src("node_modules/fuelux/dist/css/fuelux*").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/fuelux/dist/js/fuelux*").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/sortablejs/Sortable.min.js").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/typeahead.js/dist/typeahead.bundle.min.js").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/moment/min/moment-with-locales.min.js").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/jquery/dist/jquery.min.js").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/requirejs/require.js").pipe(uglify()).pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/clipboard/dist/clipboard.min.js").pipe(uglify()).pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/bootstrap-toggle/css/bootstrap-toggle.min.css").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/bootstrap-toggle/js/bootstrap-toggle.min.js").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/isotope-layout/dist/isotope.pkgd.min.js").pipe(gulp.dest('./web/npm/'));
    done();
});

const taskBuildPdfjs = gulp.series((done) => {
    gulp.src([
            "web/js/pdfjs-viewer/compatibility.js", "web/js/pdfjs-viewer/l10n.js",
            "node_modules/pdfjs-dist/build/pdf.combined.js",
            "web/js/pdfjs-viewer/ui_utils.js", "web/js/pdfjs-viewer/default_preferences.js", "web/js/pdfjs-viewer/preferences.js", "web/js/pdfjs-viewer/download_manager.js",
            "web/js/pdfjs-viewer/view_history.js", "web/js/pdfjs-viewer/pdf_link_service.js", "web/js/pdfjs-viewer/pdf_rendering_queue.js", "web/js/pdfjs-viewer/pdf_page_view.js",
            "web/js/pdfjs-viewer/text_layer_builder.js", "web/js/pdfjs-viewer/annotations_layer_builder.js", "web/js/pdfjs-viewer/pdf_viewer.js", "web/js/pdfjs-viewer/pdf_thumbnail_view.js",
            "web/js/pdfjs-viewer/pdf_thumbnail_viewer.js", "web/js/pdfjs-viewer/pdf_outline_view.js", "web/js/pdfjs-viewer/pdf_attachment_view.js",
            "web/js/pdfjs-viewer/pdf_find_bar.js", "web/js/pdfjs-viewer/pdf_find_controller.js", "web/js/pdfjs-viewer/pdf_history.js", "web/js/pdfjs-viewer/secondary_toolbar.js",
            "web/js/pdfjs-viewer/pdf_presentation_mode.js", "web/js/pdfjs-viewer/grab_to_pan.js", "web/js/pdfjs-viewer/hand_tool.js", "web/js/pdfjs-viewer/overlay_manager.js",
            "web/js/pdfjs-viewer/password_prompt.js", "web/js/pdfjs-viewer/pdf_document_properties.js", "web/js/pdfjs-viewer/viewer.js"
        ])
        .pipe(concat('pdfjs-viewer.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('./web/js/build/'));
    done();
});

const taskBuildTypescript = gulp.series((done) => {
    let tsResult = gulp.src('web/typescript/**/*.ts')
        .pipe(sourcemaps.init())
        .pipe(tsProject());

    tsResult.js
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/js/build/'));
    done();
});
taskBuildTypescript.displayName = 'Building Typescript';

const taskBuildJs = gulp.series((done) => {
    gulp.src(main_js_files)
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));

    gulp.src(["web/js/antragsgruen-de.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-de.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));

    gulp.src(["web/js/antragsgruen-en.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-en.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));

    gulp.src(["web/js/antragsgruen-en-gb.js"])
        .pipe(sourcemaps.init())
        .pipe(concat('antragsgruen-en-gb.min.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
    done();
});

const taskBuildCss = gulp.series((done) => {
    gulp.src("web/css/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(postcss([ autoprefixer({browsers: [">1%", "last 10 versions", "IE 9", "Firefox 3"]}) ]))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/css/'));
    done();
});

const taskBuildPluginCss = gulp.series((done) => {
    gulp.src("plugins/**/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(postcss([ autoprefixer({browsers: [">1%", "last 10 versions", "IE 9", "Firefox 3"]}) ]))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('plugins/'));
    done();
});

const taskWatch = gulp.parallel((done) => {
    gulp.watch(main_js_files, taskBuildJs);
    gulp.watch(["web/js/antragsgruen-de.js", "web/js/antragsgruen-en.js", "web/js/antragsgruen-en-gb.js"], taskBuildJs);
    gulp.watch(["web/css/*.scss"], gulp.parallel(taskBuildCss, taskBuildPluginCss));
    gulp.watch(["plugins/**/*.scss"], taskBuildPluginCss);
    gulp.watch(['./web/typescript/**/*.ts'], taskBuildTypescript);
    done();
});

gulp.task('build-js', taskBuildJs);
gulp.task('build-typescript', taskBuildTypescript);
gulp.task('pdfjs', taskBuildPdfjs);
gulp.task('build-css', taskBuildCss);
gulp.task('build-plugin-css', taskBuildPluginCss);
gulp.task('copy-files', taskCopyFiles);
gulp.task('watch', taskWatch);

gulp.task('default', gulp.parallel(taskBuildJs, taskBuildTypescript, taskBuildCss, taskBuildPdfjs, taskCopyFiles));