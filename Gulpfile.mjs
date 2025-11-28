import gulp, { series, watch, parallel } from 'gulp';
import uglify from 'gulp-uglify';
import size from 'gulp-size';
import sass from 'sass';
import gulpSass from 'gulp-sass';
import include from 'gulp-include';
import rename from 'gulp-rename'; 
import sourcemaps from 'gulp-sourcemaps';
import { deleteSync } from 'del';

// Import di Rollup e plugin
import rollup from '@rollup/stream';
import { nodeResolve } from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import source from 'vinyl-source-stream';
import buffer from 'vinyl-buffer';

const sassCompiler = gulpSass(sass);

// Evita i messaggi di deprecazione
process.env.NODE_NO_WARNINGS = '1';
const originalConsoleError = console.error;
console.error = function (message) {
  if (typeof message === 'string' && message.includes('deprecation')) {
    return;
  }
  originalConsoleError.apply(console, arguments);
};

// --- CONFIGURAZIONE PERCORSI
const paths = {
  js: {
    src: './js/src',
    dest: './pub/js',
    adminEntry: './js/src/admin.js',
    siteEntry: './js/src/site.js',
    allJs: './js/src/**/*.js',
    // 🚨 NUOVO: Definiamo i file da escludere nel task 'scripts:misc'
    excludeEntries: [
        '!./js/src/admin.js',
        '!./js/src/site.js',
    ]
  },
  scss: {
    src: './scss/*.scss',
    allScss: './scss/**/*.scss',
    dest: './pub/css',
  }
};

gulp.task('clean:dest', function (done) {
  // Pulizia più aggressiva per includere tutti i file di output
  deleteSync([
    paths.js.dest + '/*.min.js', 
    paths.js.dest + '/*.js', 
    paths.js.dest + '/*.min.js.map', 
    paths.js.dest + '/*.js.map'
  ]);
  done();
});

gulp.task('styles', function () {
  return gulp.src(paths.scss.src)
    .pipe(sassCompiler({
      css: paths.scss.dest,
      sass: 'scss',
    }).on('error', sassCompiler.logError))
    .pipe(gulp.dest(paths.scss.dest));
});

// ----------------------------------------------------
// TASK 1: scripts:site (gulp-include per site.js)
// ----------------------------------------------------
gulp.task('scripts:site', function () {
  return gulp.src(paths.js.siteEntry)
    .pipe(include()) 
    .pipe(sourcemaps.init())
    .pipe(rename({ basename: 'site' }))
    .pipe(gulp.dest(paths.js.dest)) 
    .pipe(uglify())
    .pipe(rename({ extname: '.min.js' }))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.js.dest));
});

// ----------------------------------------------------
// TASK 2: scripts:admin (ROLLUP con UMD per admin.js)
// ----------------------------------------------------
gulp.task('scripts:admin', function () {
  return rollup({
      input: paths.js.adminEntry, 
      
      // Dichiara jquery come modulo esterno
      external: ['jquery'], 

      output: {
          // Usa UMD per la compatibilità con jQuery
          format: 'umd', 
          name: 'AppAdminRollupBundle', 
          
          // Mappa il modulo 'jquery' alla variabile globale 'jQuery'
          globals: {
              'jquery': 'jQuery'
          }
      },
      plugins: [
          nodeResolve(), 
          commonjs(), 
      ]
  })
    .pipe(source('admin.js')) 
    .pipe(buffer()) 
    
    .pipe(sourcemaps.init({ loadMaps: true }))
    .pipe(gulp.dest(paths.js.dest)) 
    
    .pipe(uglify())
    .pipe(rename({ extname: '.min.js' }))
    
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(paths.js.dest));
});

// ----------------------------------------------------
// TASK 3: scripts:misc (File orfani, es. debugbar-EnvironmentWidget.js)
// ----------------------------------------------------
gulp.task('scripts:misc', function () {
    return gulp.src([paths.js.src + '/*.js', ...paths.js.excludeEntries])
        .pipe(include()) 
        .pipe(sourcemaps.init())
        .pipe(gulp.dest(paths.js.dest)) 
        .pipe(uglify())
        .pipe(rename({ extname: '.min.js' }))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest(paths.js.dest));
});


// Task per l'esecuzione di tutti gli script in parallelo
gulp.task('scripts', parallel('scripts:site', 'scripts:admin', 'scripts:misc'));

gulp.task('watch', function () {
  watch(paths.js.allJs, series('scripts')); 
  watch(paths.scss.allScss, series('styles'));
});

// Task di default: pulisci, compila e avvia watch
gulp.task('default', series('clean:dest', 'styles', 'scripts', 'watch'));
// Task per la build di produzione (senza watch)
gulp.task('build', series('clean:dest', 'styles', 'scripts'));