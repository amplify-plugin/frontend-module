const mix = require('laravel-mix');
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */


mix.setResourceRoot('resources')
    .setPublicPath('public')
    .copyDirectory('resources/img', 'public/img')
    .sass('resources/scss/widgets.scss', 'public/css/widgets.css')
    .js('resources/js/widgets.js', 'public/js/widgets.js')
    .copy('resources/js/modernizr.min.js', 'public/js/modernizr.min.js')
    .combine([
        'resources/js/vendor/jquery.min.js',
        'resources/js/vendor/popper.min.js',
        'resources/js/vendor/photoswipe.min.js',
        'resources/js/vendor/bootstrap.min.js',
        'resources/js/vendor/imagesloaded.pkgd.min.js',
        'resources/js/vendor/isotope.pkgd.min.js',
        'resources/js/vendor/owl.carousel.min.js',
        'resources/js/vendor/photoswipe-ui-default.min.js',
        'resources/js/vendor/velocity.min.js',
        'resources/js/vendor/jquery.validate.min.js',
        'resources/js/vendor/additional-methods.min.js',
        'resources/js/vendor/select2.min.js',
        'resources/js/vendor/moment.min.js',
        'resources/js/vendor/daterangepicker.min.js',
        'resources/js/utility.js'
    ], 'public/js/vendor.js')
    .options({
        processCssUrls: false,
        terser: {
            extractComments: false,
            terserOptions: {
                format: {
                    comments: false
                }
            }
        },
    })
    .version();
