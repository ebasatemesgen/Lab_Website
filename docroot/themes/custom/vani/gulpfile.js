const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const path = require('path');

// Define paths
const scssPath = path.join(__dirname, 'css/**/*.scss'); // SCSS files path
const cssPath = path.join(__dirname, 'css'); // CSS output path

// Compile SCSS to CSS
function compileScss() {
  return gulp.src(scssPath)
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest(cssPath));
}

// Watch for changes in SCSS files
function watchScss() {
  gulp.watch(scssPath, compileScss);
}

// Define default task
exports.default = gulp.series(compileScss, watchScss);
