<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright 2014, Elan Ruusamäe <glen@delfi.ee>                        |
// | Copyright (c) 2014 - 2015 Eventum Team.                              |
// +----------------------------------------------------------------------+
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

if (!file_exists($autoload = APP_PATH . '/vendor/autoload.php')) {
    echo <<<EOF

    You must set up the project dependencies, run the following commands:

    $ curl -sS https://getcomposer.org/installer | php
    $ php composer.phar install

EOF;
    exit(1);
}
require $autoload;

// needed for init.php and gettext.inc
define('APP_PHP_GETTEXT_PATH', APP_PATH . '/vendor/php-gettext/php-gettext');

// fonts directory for phplot
define('APP_FONTS_PATH', APP_PATH . '/vendor/fonts/liberation');
