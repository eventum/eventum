const mix = require('laravel-mix');
const collect = require('collect.js');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 | https://laravel.com/docs/5.8/mix
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.setPublicPath('htdocs');

mix.sass('res/assets/sass/all.scss', 'htdocs/css/all.css').options({
    processCssUrls: false
});

mix.styles([
    'htdocs/components/font-awesome/css/font-awesome.css',
    'htdocs/components/jquery-ui/themes/base/all.css',
    'node_modules/chosen-js/chosen.css',
    'node_modules/dropzone/dist/basic.css',
], 'htdocs/css/components.css');

mix.copy('node_modules/chosen-js/*.png', 'htdocs/css');
mix.copy('vendor/fortawesome/font-awesome/fonts', 'htdocs/fonts');

mix.scripts([
    'htdocs/js/main.js',
    'htdocs/js/page.js',
], 'htdocs/js/all.js');
mix.scripts([
    'node_modules/jquery/jquery.js',
    'node_modules/jquery/jquery-migrate.js',
    'node_modules/block-ui/jquery.blockUI.js',
    'node_modules/jquery-form/src/jquery.form.js',
    'htdocs/components/jquery-cookie/jquery.cookie.js',
    'htdocs/components/jquery-ui/ui/core.js',
    'htdocs/components/jquery-ui/ui/datepicker.js',
    'htdocs/components/jquery-ui/ui/widget.js',
    'htdocs/components/jquery-ui/ui/mouse.js',
    'htdocs/components/jquery-ui/ui/progressbar.js',
    'htdocs/components/jquery-ui/ui/position.js',
    'htdocs/components/jquery-ui/ui/menu.js',
    'htdocs/components/jquery-ui/ui/selectmenu.js',
    'htdocs/components/jquery-ui/ui/sortable.js',
    'node_modules/chosen-js/chosen.jquery.js',
    'node_modules/dropzone/dist/dropzone.js',
    'node_modules/autosize/dist/autosize.js',
    'node_modules/timeago/jquery.timeago.js',
    'htdocs/components/filereader.js/filereader.js',
    'node_modules/drmonty-garlicjs/js/garlic.min.js',
    'htdocs/components/cmd-ctrl-enter/src/cmd-ctrl-enter.js',
], 'htdocs/js/components.js');

mix.version([
    'htdocs/css/all.css',
    'htdocs/js/all.js',
]);

if (mix.inProduction()) {
    mix.disableNotifications();
}

/**
 * Update manifest to remove leading slash of key => value pairs
 * @author Elan Ruusamäe <glen@pld-linux.org>
 * @see https://github.com/symfony/symfony/issues/36234
 */
mix.extend('updateManifestPathsRelative', (config) => {
    config.plugins.push(new class {
        apply(compiler) {
            compiler.plugin('done', () => {
                const manifest = {};
                collect(Mix.manifest.get()).each((value, key) => {
                    key = this.normalizePath(key);
                    value = this.normalizePath(value);
                    manifest[key] = value;
                });
                Mix.manifest.manifest = manifest;
                Mix.manifest.refresh();
            });
        }

        /**
         * @param {string} filePath
         */
        normalizePath(filePath) {
            if (filePath.startsWith('/')) {
                filePath = filePath.substring(1);
            }

            return filePath;
        }
    })
});

mix.updateManifestPathsRelative();
