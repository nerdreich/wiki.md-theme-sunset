// Copyright 2020 Markus Leupold-Löwenthal
//
// This file is part of wiki.md-theme-sunset (Sunset).
//
// Sunset is free software: you can redistribute it and/or modify it under the
// terms of the GNU Affero General Public License as published by the Free
// Software Foundation, either version 3 of the License, or (at your option) any
// later version.
//
// Sunset is distributed in the hope that it will be useful, but WITHOUT ANY
// WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
// A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with Sunset. If not, see <https://www.gnu.org/licenses/>.

const p = require('./package.json')

const gulp = require('gulp')
const replace = require('gulp-replace')

var dirs = {
  build: 'dist/',
  theme: 'dist/themes/' + p.name
}

// --- testing targets ---------------------------------------------------

gulp.task('test-sass', function () {
  var sassLint = require('gulp-sass-lint')
  return gulp.src(['src/**/*.s+(a|c)ss'])
    .pipe(sassLint({ configFile: '.sass-lint.yml' }))
    .pipe(sassLint.format())
    .pipe(sassLint.failOnError())
})

gulp.task('test-php', function () {
  const phpcs = require('gulp-phpcs')
  return gulp.src([
    'src/php/**/*.php'
  ])
    .pipe(phpcs({
      bin: 'tools/phpcs.phar',
      standard: 'PSR12',
      colors: 1,
      warningSeverity: 0
    }))
    .pipe(phpcs.reporter('log'))
    .pipe(phpcs.reporter('fail'))
})

gulp.task('tests', gulp.series('test-sass', 'test-php'))

// --- build targets -----------------------------------------------------

gulp.task('clean', function () {
  var del = require('del')
  return del([
    [dirs.build] + '/**/*',
    [dirs.build] + '/**/.*'
  ])
})

gulp.task('fonts', function () {
  return gulp.src([
    'src/fonts/*/*woff',
    'src/fonts/*/*woff2'
  ])
    .pipe(gulp.dest(dirs.theme + '/fonts/'))
})

gulp.task('scss', gulp.series('test-sass', function () {
  var sass = require('gulp-sass')
  var concat = require('gulp-concat')
  var autoprefixer = require('gulp-autoprefixer')

  return gulp.src([
    'src/scss/main.scss'
    // include additional vendor-css from /node_modules here
  ])
    .pipe(concat('style.css'))
    .pipe(replace('$VERSION$', p.version, { skipBinary: true }))
    .pipe(sass({ outputStyle: 'compressed' }))
    .pipe(autoprefixer())
    .pipe(gulp.dest(dirs.theme))
}))

gulp.task('php', gulp.series('test-php', function () {
  return gulp.src([
    'src/php/**/*.php'
  ])
    .pipe(replace('$VERSION$', p.version, { skipBinary: true }))
    .pipe(replace('$URL$', p.homepage, { skipBinary: true }))
    .pipe(replace('$BGCOLOR$', p.bgColor, { skipBinary: true }))
    .pipe(gulp.dest(dirs.theme))
}))

gulp.task('I18N', function () {
  return gulp.src([
    'src/I18N/**/*'
  ])
    .pipe(gulp.dest(dirs.theme + '/I18N'))
})

gulp.task('favicon', function () {
  const imagemin = require('gulp-imagemin')
  const imageminPngquant = require('imagemin-pngquant')

  return gulp.src([
    'src/favicon/**/*'
  ])
    .pipe(imagemin([
      imageminPngquant({ quality: [0.8, 0.9], strip: true })
    ], { verbose: true }))
    .pipe(replace('$NAME$', p.name, { skipBinary: true }))
    .pipe(replace('$BGCOLOR$', p.bgColor, { skipBinary: true }))
    .pipe(gulp.dest(dirs.theme))
})

gulp.task('theme', gulp.parallel('fonts', 'scss', 'php', 'favicon', 'I18N'))

gulp.task('dist', gulp.series('clean', 'theme'))

gulp.task('debug', gulp.series('clean', 'dist', function () {
  return gulp.src([
    'dist/**/*'
  ])
    .pipe(gulp.dest('../wiki.md/dist/wiki.md'))
}))

gulp.task('package-tgz', function () {
  const tar = require('gulp-tar')
  const gzip = require('gulp-gzip')
  const sort = require('gulp-sort')

  return gulp.src([
    dirs.build + '/themes/**/*'
  ], { base: dirs.build, dot: true })
    .pipe(sort())
    .pipe(tar('wiki.md-theme-sunset-' + p.version + '.tar'))
    .pipe(gzip({ gzipOptions: { level: 9 } }))
    .pipe(gulp.dest(dirs.build))
})

gulp.task('package-zip', function () {
  const zip = require('gulp-zip')
  const sort = require('gulp-sort')

  return gulp.src([
    dirs.build + '/themes/**/*'
  ], { base: dirs.build, dot: true })
    .pipe(sort())
    .pipe(zip('wiki.md-theme-sunset-' + p.version + '.zip'))
    .pipe(gulp.dest(dirs.build))
})

gulp.task('release', gulp.series('clean', 'dist', 'package-tgz', 'package-zip'))
