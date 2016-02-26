var gulp = require('gulp');

// Run this to compress all the things!
gulp.task('production', [], function () {
    gulp.start(['markup', 'images', 'minifyCss', 'uglifyJs'])
});