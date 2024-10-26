import gulp from 'gulp';
import uglify from 'gulp-uglify';
import size from 'gulp-size';
import sass from 'sass';
import gulpSass from 'gulp-sass';
import minify from 'gulp-minify';
import include from 'gulp-include';
import { deleteSync } from 'del';

const sassCompiler = gulpSass(sass);

process.env.NODE_NO_WARNINGS = '1';
const originalConsoleError = console.error;
console.error = function (message) {
  if (typeof message === 'string' && message.includes('deprecation')) {
    return; // Ignora i messaggi di deprecazione
  }
  originalConsoleError.apply(console, arguments); // Altrimenti, mostra il messaggio
};

gulp.task('clean:dest', function (done) {
  deleteSync(['./js/dest']);
  done(); // Segnala che il task è completato
});

gulp.task('styles', function () {
  return gulp.src('./scss/*.scss')
    .pipe(sassCompiler({
      css: 'pub/css',
      sass: 'scss',
    }).on('error', sassCompiler.logError))
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
    .pipe(gulp.dest('./pub/js'));
});

gulp.task('watch', function () {
  return gulp.watch(['./js/src/*.js', './scss/*.scss'], gulp.series('scripts', 'min-scripts', 'styles'));
});
 
gulp.task('default', gulp.parallel(gulp.series('clean:dest', gulp.series('scripts', 'min-scripts')), 'styles'));
