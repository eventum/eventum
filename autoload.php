<?php
/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

// this needs to be setup before autoload itself
define('APP_PHP_GETTEXT_PATH', APP_PATH . '/vendor/php-gettext/php-gettext');

if (!file_exists($autoload = APP_PATH . '/vendor/autoload.php')) {
    echo <<<EOF

    You must set up the project dependencies, run the following commands:

    $ curl -sS https://getcomposer.org/installer | php
    $ php composer.phar install

EOF;
    exit(1);
}
require $autoload;

// fonts directory for phplot
define('APP_FONTS_PATH', APP_PATH . '/vendor/fonts/liberation');
