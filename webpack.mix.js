const mix = require('laravel-mix');

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

mix.setPublicPath('htdocs');

mix.styles([
    'htdocs/css/main.css',
    'htdocs/css/page.css',
], 'htdocs/css/all.css');

mix.scripts([
    'htdocs/js/main.js',
    'htdocs/js/page.js',
], 'htdocs/js/all.js');

mix.version([
    'htdocs/css/all.css',
    'htdocs/js/all.js',
]);
