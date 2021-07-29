var gulp         = require("gulp"),
    sass         = require("gulp-sass"),
    sourcemaps   = require("gulp-sourcemaps"),
    cleanCss     = require("gulp-clean-css"),
    rename       = require("gulp-rename"),
    watch        = require("gulp-watch"),
    postcss      = require("gulp-postcss"),
    autoprefixer = require("autoprefixer"),
    browserSync  = require("browser-sync").create();

/**
 * @task sass
 * 
 * Compile sass into CSS & auto-inject into browsers
 */
const sass_task = () => {
  return gulp
    .src("scss/style.scss")
    .pipe(sourcemaps.init())
    .pipe(sass().on("error", sass.logError))
    .pipe(
      postcss([
        autoprefixer({
          browsers: [
            "Chrome >= 35",
            "Firefox >= 38",
            "Edge >= 12",
            "Explorer >= 10",
            "iOS >= 8",
            "Safari >= 8",
            "Android 2.3",
            "Android >= 4",
            "Opera >= 12"
          ]
        })
      ])
    )
    .pipe(sourcemaps.write())
    .pipe(gulp.dest("css"))
    .pipe(cleanCss())
    .pipe(rename({ suffix: ".min" }))
    .pipe(gulp.dest("css"))
    .pipe(browserSync.stream());
}

/**
 * @task move_plugins
 * 
 * Move the plugins files into libraries folder
 */
const move_plugins = () => {
  return gulp
    .src([
      "node_modules/bootstrap/**/*.*",
      "node_modules/jquery/**/*.*",
      "node_modules/popper.js/**/*.*",
    ], { base: './node_modules' })
    .pipe(gulp.dest('../../../libraries'));
}

/**
 * @task watch
 */
const watch_only = () => {
  gulp.watch('scss/**/*.scss', sass_task);
}

/**
 * @task watch with autoreload
 */
const watch_sync = () => {
  browserSync.init({
    proxy: 'myd8boilerplate.loc/',
    online: true
  });

  gulp.watch('scss/**/*.scss').on('change', gulp.series(sass_task, browserSync.reload));
}

const default_task = gulp.series(sass_task, move_plugins)

/**
 * export the tasks to use in command line
 */
exports.mvplugins = move_plugins;
exports.sass      = sass_task;
exports.watch     = watch_only;
exports.watchsync = watch_sync;
exports.default   = default_task;
