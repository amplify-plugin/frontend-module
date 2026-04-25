const mix = require('laravel-mix');
const { exec } = require('child_process');
const fs = require('fs');
const path = require('path');

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

class PublishWidgetAssets {
    apply(compiler) {
        compiler.hooks.done.tap('RunCommandIfArtisanExists', (stats) => {
            if (stats.hasErrors()) return;

            // adjust path relative to your webpack.mix.js
            const artisanPath = path.resolve(__dirname, '../../artisan');

            if (fs.existsSync(artisanPath)) {
                exec('php ../../artisan vendor:publish --tag=widget-asset --ansi --force', (err, stdout, stderr) => {
                    if (err) {
                        console.error(err);
                        return;
                    }
                    console.log(stdout);
                });
            } else {
                console.log('artisan file not found, skipping...');
            }
        });
    }
}


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
    .webpackConfig({
        plugins: [
            new PublishWidgetAssets(),
        ]
    })
    .version();
