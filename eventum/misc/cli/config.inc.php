<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.config.inc.php 1.2 04/01/19 15:19:26-00:00 jpradomaia $
//
ini_set("display_errors", 1);
error_reporting(E_ALL);
@set_time_limit(0);

if (isset($_GET)) {
    $HTTP_POST_VARS = $_POST;
    $HTTP_GET_VARS = $_GET;
    $HTTP_SERVER_VARS = $_SERVER;
    $HTTP_ENV_VARS = $_ENV;
    $HTTP_POST_FILES = $_FILES;
    // seems like PHP 4.1.0 didn't implement the $_SESSION auto-global...
    if (isset($_SESSION)) {
        $HTTP_SESSION_VARS = $_SESSION;
    }
    $HTTP_COOKIE_VARS = $_COOKIE;
}

// definitions of path related variables
@define("APP_PATH", dirname(__FILE__) . '/');
@define("APP_INC_PATH", APP_PATH . "include/");
@define("APP_PEAR_PATH", APP_INC_PATH . "pear/");
if (stristr(PHP_OS, 'darwin')) {
    ini_set("include_path", ".:" . APP_PEAR_PATH);
} elseif (stristr(PHP_OS, 'win')) {
    ini_set("include_path", ".;" . APP_PEAR_PATH);
} else {
    ini_set("include_path", ".:" . APP_PEAR_PATH);
}

@define("APP_BENCHMARK", false);
?>