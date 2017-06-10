var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    sass = require('gulp-sass'),
    ts = require('gulp-typescript'),
    tsProject = ts.createProject('tsconfig.json'),
    sourcemaps = require('gulp-sourcemaps'),

    main_js_files = [
        "node_modules/bootstrap/dist/js/bootstrap.js", "node_modules/bootbox/bootbox.js", "web/js/scrollintoview.js", "web/js/jquery.isonscreen.js",
        "node_modules/intl/dist/Intl.min.js"
    ];

gulp.task('copy-files', function() {
    gulp.src("node_modules/fuelux/dist/css/fuelux*").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/fuelux/dist/js/fuelux*").pipe(gulp.dest('./web/npm/'));
    gulp.src("node_modules/html5shiv/dist/html5shiv*").pipe(gulp.dest('./web/npm/'));
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
});

gulp.task('pdfjs', function () {
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
});

gulp.task('build-typescript', function() {
    var tsResult = gulp.src('web/typescript/**/*.ts')
        .pipe(sourcemaps.init())
        .pipe(tsProject());

    tsResult.js
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/js/build/'));
});

gulp.task('build-js', function () {
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
});

gulp.task('build-css', function () {
    gulp.src("web/css/*.scss")
        .pipe(sourcemaps.init())
        .pipe(sass({outputStyle: 'compressed'}).on('error', sass.logError))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/css/'));
});


gulp.task('watch', function () {
    gulp.watch(main_js_files, ['build-js']);
    gulp.watch(["web/js/antragsgruen-de.js", "web/js/antragsgruen-en.js", "web/js/antragsgruen-en-gb.js"], ['build-js']);
    gulp.watch(["web/css/*.scss"], ['build-css']);
    gulp.watch(['./web/typescript/**/*.ts'], ['build-typescript']);
});

gulp.task('default', ['build-js', 'build-typescript', 'build-css', 'pdfjs', 'copy-files']);