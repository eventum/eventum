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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

// add APP_LOCAL_PATH to include_path
if (defined('APP_LOCAL_PATH')) {
    set_include_path(APP_LOCAL_PATH . PATH_SEPARATOR . get_include_path());
    set_include_path(APP_LOCAL_PATH . '/include/' . PATH_SEPARATOR . get_include_path());
}

// if autoloader (from composer) in place, use it and skip the rest
if (file_exists($autoload = APP_PATH . '/vendor/autoload.php')) {
    require $autoload;

    // needed for init.php and gettext.inc
    define('APP_PHP_GETTEXT_PATH', APP_PATH . '/vendor/php-gettext/php-gettext');

    // no substitution, use bundled copy
    define('APP_JPGRAPH_PATH', APP_PATH . '/lib/jpgraph');

    // indicate whether we use components installed by composer
    define('APP_USE_COMPONENTS', true);
    return;
}

// indicate whether we use components installed by composer
define('APP_USE_COMPONENTS', false);

if (!defined('APP_PEAR_PATH')) {
    define('APP_PEAR_PATH', APP_PATH . '/lib/pear');
}

if (!defined('APP_SPHINXAPI_PATH')) {
    define('APP_SPHINXAPI_PATH', APP_PATH . '/lib/sphinxapi');
}

if (!defined('APP_PHP_GETTEXT_PATH')) {
    define('APP_PHP_GETTEXT_PATH', APP_PATH . '/lib/php-gettext');
}

if (!defined('APP_SMARTY_PATH')) {
    define('APP_SMARTY_PATH', APP_PATH . '/lib/Smarty');
}

if (!defined('APP_JPGRAPH_PATH')) {
    define('APP_JPGRAPH_PATH', APP_PATH . '/lib/jpgraph');
}

// add PEAR to the include path, required by PEAR classes
if (defined('APP_PEAR_PATH') && APP_PEAR_PATH) {
    set_include_path(APP_PEAR_PATH . PATH_SEPARATOR . get_include_path());
}

require_once APP_INC_PATH . '/autoload.php';
