'use strict';

var gulp = require('gulp'),
    sass = require('gulp-sass'),
    cssmin = require('gulp-cssmin'),
    minify = require('gulp-minify'),
    rename = require('gulp-rename'),
    watch = require('gulp-watch'),
    concat = require('gulp-concat'),
    browserSync = require('browser-sync').create();

sass.compiler = require('node-sass');

gulp.task('sass', function() {
    return gulp.src('./scss/*.scss')
        .pipe(sass().on('error', sass.logError))
        .pipe(cssmin())
        .pipe(concat('style.css')) 
        .pipe(gulp.dest('./css'))
        .pipe(browserSync.stream());
});

gulp.task('js', function() {
    return gulp.src('./assets/*.js')
    .pipe(browserSync.stream());
});

//Watch task
gulp.task('default', function() {
    browserSync.init({
        proxy: "solaradvice.local"
    });
    gulp.watch('./scss/*.scss', gulp.series('sass'));
    gulp.watch('./assets/*.js', gulp.series('js'));
});
