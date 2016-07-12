var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    concat = require('gulp-concat'),
    sass = require('gulp-sass'),
    babel = require('gulp-babel'),
    sourcemaps = require('gulp-sourcemaps'),

    main_js_files = [
        "web/js/bootstrap.js", "web/js/bower/bootbox/bootbox.js", "web/js/scrollintoview.js", "web/js/jquery.isonscreen.js",
        "web/js/bower/intl/dist/Intl.min.js", "web/js/antragsgruen.js", "web/js/src/*.es6"
    ];

gulp.task('pdfjs', function () {
    gulp.src([
            "web/js/pdfjs-viewer/compatibility.js", "web/js/pdfjs-viewer/l10n.js",
            "web/js/bower/pdfjs-dist/build/pdf.combined.js",
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

gulp.task('build-wizard', function() {
    gulp.src('./web/js/src/SiteCreateWizard.es6')
        .pipe(sourcemaps.init())
        .pipe(babel({
			presets: ['es2015']
		}))
        .pipe(concat('SiteCreateWizard.js'))
        .pipe(uglify())
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('./web/js/build/'));
});

gulp.task('build-js', function () {
    gulp.src(main_js_files)
        .pipe(sourcemaps.init())
        .pipe(babel({
			presets: ['es2015']
		}))
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
        .pipe(sass({outputStyle: 'compressed'}))
        .pipe(sourcemaps.write('./'))
        .pipe(gulp.dest('web/css/'));
});


gulp.task('watch', function () {
    gulp.watch(main_js_files, ['build-js']);
    gulp.watch(["web/css/*.scss"], ['build-css']);
    gulp.watch(['./web/js/src/SiteCreateWizard.es6'], ['build-wizard']);
});

gulp.task('default', ['build-js', 'build-css']);