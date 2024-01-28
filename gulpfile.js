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

import { readFileSync } from 'fs'
import { deleteAsync } from 'del'

import autoprefixer from 'gulp-autoprefixer'
import concat from 'gulp-concat'
import gulp from 'gulp'
import gzip from 'gulp-gzip'
import replace from 'gulp-replace'
import sort from 'gulp-sort'
import tar from 'gulp-tar'
import zip from 'gulp-zip'

import * as dartSass from 'sass'
import gulpSass from 'gulp-sass'
const sass = gulpSass(dartSass)

const p = JSON.parse(readFileSync('./package.json'))

const dirs = {
  build: 'dist/',
  theme: 'dist/themes/' + p.name
}

// --- build targets -----------------------------------------------------

gulp.task('clean', async () => {
  return await deleteAsync([
    [dirs.build] + '/**/*',
    [dirs.build] + '/**/.*'
  ])
})

gulp.task('fonts', () => {
  return gulp.src([
    'src/fonts/*/*woff',
    'src/fonts/*/*woff2'
  ])
    .pipe(gulp.dest(dirs.theme + '/fonts/'))
})

gulp.task('scss', () => {
  return gulp.src([
    'src/scss/main.scss'
    // include additional vendor-css from /node_modules here
  ])
    .pipe(concat('style.css'))
    .pipe(replace('$VERSION$', p.version, { skipBinary: true }))
    .pipe(sass({ outputStyle: 'compressed' }))
    .pipe(autoprefixer())
    .pipe(gulp.dest(dirs.theme))
})

gulp.task('php', () => {
  return gulp.src([
    'src/php/**/*.php'
  ])
    .pipe(replace('$VERSION$', p.version, { skipBinary: true }))
    .pipe(replace('$URL$', p.homepage, { skipBinary: true }))
    .pipe(replace('$BGCOLOR$', p.bgColor, { skipBinary: true }))
    .pipe(gulp.dest(dirs.theme))
})

gulp.task('I18N', () => {
  return gulp.src([
    'src/I18N/**/*'
  ])
    .pipe(gulp.dest(dirs.theme + '/I18N'))
})

gulp.task('favicon', () => {
  return gulp.src([
    'src/favicon/**/*'
  ])
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
  return gulp.src([
    dirs.build + '/themes/**/*'
  ], { base: dirs.build, dot: true })
    .pipe(sort())
    .pipe(tar('wiki.md-theme-sunset-' + p.version + '.tar'))
    .pipe(gzip({ gzipOptions: { level: 9 } }))
    .pipe(gulp.dest(dirs.build))
})

gulp.task('package-zip', function () {
  return gulp.src([
    dirs.build + '/themes/**/*'
  ], { base: dirs.build, dot: true })
    .pipe(sort())
    .pipe(zip('wiki.md-theme-sunset-' + p.version + '.zip'))
    .pipe(gulp.dest(dirs.build))
})

gulp.task('package', gulp.series('clean', 'dist', 'package-tgz', 'package-zip'))
