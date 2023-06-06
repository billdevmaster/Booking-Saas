const path = require('path');
const mix = require('laravel-mix');
const BrowserSyncPlugin = require('browser-sync-webpack-plugin');

mix.webpackConfig({
   resolve: {
      alias: {
         "@": ".."
      }
   },
   output:{
      publicPath: "/booking-saas/laravel/public/"
    }
});



/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')
   .webpackConfig({
      plugins: [
         new BrowserSyncPlugin({
            host: 'localhost',
            port: 3000,
            proxy: 'http://localhost/booking-saas',
            files: [
               'app/**/*.php',
               'resources/views/**/*.php',
               'public/js/**/*.js',
               'public/css/**/*.css'
            ]
         })
      ]
   });

mix.copy('../coreui/public', 'public');


