var gulp = require('gulp');
var uglify = require('gulp-uglify');
var size = require('gulp-size');
var compass = require('gulp-compass');
var minify = require('gulp-minify');
var include = require('gulp-include');
var del = require('del');

gulp.task('clean:dest', function () {
  return del([
    './js/dest'
  ]);
});

gulp.task('styles', function() {
  return gulp.src('./scss/*.scss')
    .pipe(compass({
      config_file: './config.rb',
      css: 'pub/css',
      sass: 'scss'
    }))
    .pipe(gulp.dest('pub/css'));
});

gulp.task('scripts', function () {
  return gulp
    .src('./js/src/*.js')
    .pipe(include())    
    .pipe(gulp.dest('./js/dest'));
});

gulp.task('min-scripts', function () {
  return gulp
    .src('./js/dest/*.js')
    .pipe(uglify())
    .pipe(size())
//    .pipe(minify())
    .pipe(gulp.dest('./pub/js'));
});


gulp.task('watch', function(){
    return gulp.watch(['./js/src/*.js', './scss/*.scss'], gulp.series('scripts', 'min-scripts', 'styles'));
});
 
gulp.task('default', gulp.parallel(gulp.series('scripts', 'min-scripts', 'clean:dest'), 'styles'));