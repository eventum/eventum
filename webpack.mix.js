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
mix.options({
    // Process/optimize relative stylesheet url()'s. Set to false, if you don't want them touched.
    processCssUrls: true,
});
mix.setResourceRoot('..');

mix.sass('res/assets/sass/app.scss', 'htdocs/css/app.css');

mix.styles([
    'node_modules/font-awesome/css/font-awesome.css',
    'node_modules/chosen-js/chosen.css',
    'node_modules/dropzone/dist/basic.css',
], 'htdocs/css/components.css');

mix.copy('node_modules/chosen-js/*.png', 'htdocs/css');
mix.copy('node_modules/font-awesome/fonts', 'htdocs/fonts');

mix.js([
    'res/assets/scripts/app.js',
], 'htdocs/js/app.js');

mix.sass('res/assets/sass/jquery-ui.scss', 'htdocs/css/jquery-ui.css');
mix.scripts([
    // core.js
    'node_modules/jquery-ui/ui/version.js',

    'node_modules/jquery-ui/ui/keycode.js',
    'node_modules/jquery-ui/ui/scroll-parent.js',
    'node_modules/jquery-ui/ui/unique-id.js',
    'node_modules/jquery-ui/ui/focusable.js',
    'node_modules/jquery-ui/ui/tabbable.js',

    'node_modules/jquery-ui/ui/data.js',
    'node_modules/jquery-ui/ui/jquery-1-7.js',
    'node_modules/jquery-ui/ui/ie.js',

    'node_modules/jquery-ui/ui/disable-selection.js',
    'node_modules/jquery-ui/ui/plugin.js',
    'node_modules/jquery-ui/ui/widgets/datepicker.js',
    'node_modules/jquery-ui/ui/widget.js',
    'node_modules/jquery-ui/ui/widgets/mouse.js',
    'node_modules/jquery-ui/ui/position.js',
    'node_modules/jquery-ui/ui/widgets/accordion.js', // new
    'node_modules/jquery-ui/ui/widgets/menu.js',
    'node_modules/jquery-ui/ui/widgets/autocomplete.js', // new
    'node_modules/jquery-ui/ui/widgets/button.js', // new
    'node_modules/jquery-ui/ui/widgets/datepicker.js', // new
    'node_modules/jquery-ui/ui/widgets/draggable.js', // new
    'node_modules/jquery-ui/ui/widgets/resizable.js', // new
    'node_modules/jquery-ui/ui/widgets/dialog.js', // new
    'node_modules/jquery-ui/ui/widgets/droppable.js', // new
    'node_modules/jquery-ui/ui/effect.js', // new
    'node_modules/jquery-ui/ui/effects/effect-blind.js', // new
    'node_modules/jquery-ui/ui/effects/effect-bounce.js', // new
    'node_modules/jquery-ui/ui/effects/effect-clip.js', // new
    'node_modules/jquery-ui/ui/effects/effect-drop.js', // new
    'node_modules/jquery-ui/ui/effects/effect-explode.js', // new
    'node_modules/jquery-ui/ui/effects/effect-fade.js', // new
    'node_modules/jquery-ui/ui/effects/effect-fold.js', // new
    'node_modules/jquery-ui/ui/effects/effect-highlight.js', // new
    'node_modules/jquery-ui/ui/effects/effect-size.js', // new
    'node_modules/jquery-ui/ui/effects/effect-scale.js', // new
    'node_modules/jquery-ui/ui/effects/effect-puff.js', // new
    'node_modules/jquery-ui/ui/effects/effect-pulsate.js', // new
    'node_modules/jquery-ui/ui/effects/effect-shake.js', // new
    'node_modules/jquery-ui/ui/effects/effect-slide.js', // new
    'node_modules/jquery-ui/ui/effects/effect-transfer.js', // new
    'node_modules/jquery-ui/ui/widgets/progressbar.js',
    'node_modules/jquery-ui/ui/widgets/selectable.js', // new
    'node_modules/jquery-ui/ui/widgets/selectmenu.js',
    'node_modules/jquery-ui/ui/widgets/slider.js', // new
    'node_modules/jquery-ui/ui/widgets/sortable.js',
    'node_modules/jquery-ui/ui/widgets/spinner.js', // new
    'node_modules/jquery-ui/ui/widgets/tabs.js', // new
    'node_modules/jquery-ui/ui/widgets/tooltip.js', // new

    // 'node_modules/jquery-ui/ui/form.js', // unused
    // 'node_modules/jquery-ui/ui/labels.js', // unused
    // 'node_modules/jquery-ui/ui/safe-active-element.js', // unused
    // 'node_modules/jquery-ui/ui/safe-blur.js', // unused
], 'htdocs/js/jquery-ui.js');

mix.scripts([
    'node_modules/jquery/jquery.js',
    'node_modules/jquery/jquery-migrate.js',
    'node_modules/block-ui/jquery.blockUI.js',
    'node_modules/jquery-form/src/jquery.form.js',
    'node_modules/js-cookie/src/js.cookie.js',
    'node_modules/chosen-js/chosen.jquery.js',
    'node_modules/dropzone/dist/dropzone.js',
    'node_modules/autosize/dist/autosize.js',
    'node_modules/timeago/jquery.timeago.js',
    'node_modules/file-reader-wrapper/filereader.js',
    'node_modules/drmonty-garlicjs/js/garlic.min.js',
    'node_modules/cmd-ctrl-enter/src/cmd-ctrl-enter.js',
    'node_modules/mermaid/dist/mermaid.js',
], 'htdocs/js/components.js');

mix.scripts([
    'node_modules/raven-js/dist/raven.min.js',
], 'htdocs/js/raven.js');

mix.styles([
    'node_modules/datatables/media/css/jquery.dataTables.css',
], 'htdocs/css/datatables.css');
mix.scripts([
    'node_modules/datatables/media/js/jquery.dataTables.js',
], 'htdocs/js/datatables.js');
mix.copy('node_modules/datatables/media/images/*.png', 'htdocs/images');

mix.scripts([
    'res/assets/scripts/dynamic_custom_field.js',
], 'htdocs/js/dynamic_custom_field.js');

mix.version();

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
