var babelify = require("babelify");

var flaggedBabelify = function (file) {
    return babelify(file, {
        presets: ["es2015", "react"]
    });
};

module.exports = function (config) {
    var configuration = {
        // base path that will be used to resolve all patterns (eg. files, exclude)
        basePath: '',

        // frameworks to use
        // available frameworks: https://npmjs.org/browse/keyword/karma-adapter
        frameworks: ['mocha', 'sinon-chai', 'browserify'],

        // list of files / patterns to load in the browser
        files: [
            'tests/**/*.js'
        ],

        // list of files to exclude
        exclude: [],

        // preprocess matching files before serving them to the browser
        // available preprocessors: https://npmjs.org/browse/keyword/karma-preprocessor
        preprocessors: {
            "tests/**/*.js": ["browserify"],
            "src/js/**/*.js": ["browserify", "coverage"]
        },

        browserify: {
            debug: true,
            extensions: ['.js'],
            transform: [flaggedBabelify, "browserify-optional", require('browserify-istanbul')({
                includeUntested: true,
                instrumenterConfig: { embedSource: true }
            })]
        },

        // test results reporter to use
        // possible values: 'dots', 'progress'
        // available reporters: https://npmjs.org/browse/keyword/karma-reporter
        reporters: ['nyan', 'coverage'],

        // web server port
        port: 9876,

        // enable / disable colors in the output (reporters and logs)
        colors: true,

        // level of logging
        // possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
        logLevel: config.LOG_INFO,

        // enable / disable watching file and executing tests whenever any file changes
        autoWatch: true,

        // start these browsers
        // available browser launchers: https://npmjs.org/browse/keyword/karma-launcher
        browsers: ['Chrome'],

        customLaunchers: {
            Chrome_CI: {
                base: "Chrome",
                flags: ["--no-sandbox"]
            }
        },

        // Continuous Integration mode
        // if true, Karma captures browsers, runs the tests and exits
        singleRun: false,

        // Helps to address an issue on TravisCI where activity can time out
        browserNoActivityTimeout: 30000,

        // "html" currently fails, see https://github.com/karma-runner/karma-coverage/issues/157
        coverageReporter: {
            type: "html",
            dir: "coverage/"
        }
    };

    if (process.env.TRAVIS) {
        configuration.browsers = ["Chrome_CI"];
        configuration.reporters = ['dots', 'coverage', 'coveralls'];
        configuration.logLevel = config.LOG_DEBUG;
        configuration.coverageReporter = {
            type: "lcov",
            dir: "coverage/"
        }
    }

    config.set(configuration);
};
