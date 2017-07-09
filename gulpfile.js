'use strict';

const gulp = require('gulp');
const less = require('gulp-less');
const clean = require('gulp-clean');
const uglify = require('gulp-uglify');
const concat = require('gulp-concat');
const cleanCSS = require('gulp-clean-css');

gulp.task('js', function() {
    return gulp.src([
        './node_modules/jquery/dist/jquery.js',
        './node_modules/bootstrap/dist/js/bootstrap.js',
        './node_modules/moment/min/moment.min.js',
        './res/assets/main.js',
    ])
    .pipe(concat('js/main.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest('./web/dist'));
});

gulp.task('css', function() {
    return gulp.src('./res/assets/style.less')
    .pipe(less())
    .pipe(concat('css/main.min.css'))
    .pipe(cleanCSS())
    .pipe(gulp.dest('./web/dist'));
});

gulp.task('fonts', function () {
    return gulp.src([
        './node_modules/font-awesome/fonts/*.*',
    ]).pipe(gulp.dest('./web/dist/fonts'));
});

gulp.task('clean', function() {
    return gulp.src([
        './web/dist',
    ],
    {read: false})
    .pipe(clean());
});

gulp.task('watch', function () {
    gulp.watch('./res/assets/**/*.js', ['js']);
    gulp.watch('./res/assets/**/*.less', ['css']);
});

gulp.task('default', ['js', 'css', 'fonts']);
