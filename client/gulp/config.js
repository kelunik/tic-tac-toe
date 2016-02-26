var historyApiFallback = require("connect-history-api-fallback");
var babelify = require("babelify");

var dest = "./../public";
var src = './src';

var flaggedBabelify = function (file) {
    return babelify(file, {presets: ["es2015", "react"]});
};

module.exports = {
    browserSync: {
        server: {
            // Serve up our build folder
            baseDir: dest,
            middleware: [historyApiFallback()]
        }
    },
    css: {
        src: src + "/js/vendor/highlight.js/styles/docco.css",
        dest: dest
    },
    sass: {
        src: src + "/sass/**/*.scss",
        dest: dest,
        settings: {
            outputStyle: "compressed",
            indentedSyntax: false, // Disable .sass syntax!
            imagePath: 'img' // Used by the image-url helper
        }
    },
    images: {
        src: src + "/img/**",
        dest: dest + "/img"
    },
    markup: {
        src: src + "/{index.html,favicon.ico}",
        dest: dest
    },
    browserify: {
        bundleConfigs: [{
            entries: src + '/js/index.js',
            dest: dest,
            outputName: 'bundle.js',
            extensions: [],
            transform: [flaggedBabelify, "browserify-optional"]
        }]
    },
    production: {
        cssSrc: dest + '/*.css',
        jsSrc: dest + '/*.js',
        dest: dest
    }
};