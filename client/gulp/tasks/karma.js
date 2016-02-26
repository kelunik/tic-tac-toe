var gulp = require('gulp');
var Server = require('karma').Server;

var karmaTask = function (done) {
    var server = new Server({
        configFile: process.cwd() + '/karma.conf.js',
        singleRun: true
    }, done);

    server.start();
};

gulp.task('karma', karmaTask);

module.exports = karmaTask;
