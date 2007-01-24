<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007 MySQL AB                  |
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
// @(#) $Id: index.php 3206 2007-01-24 20:24:35Z glen $
//
require_once(dirname(__FILE__) . '/init.php');
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.auth.php");
require_once(APP_INC_PATH . "db_access.php");

// check if templates_c is writable by the web server user
if (!Misc::isWritableDirectory(APP_PATH . 'templates_c')) {
    $errors = array("Directory 'templates_c' is not writable.");
    Misc::displayRequirementErrors($errors);
    exit;
}

$tpl = new Template_API();
$tpl->setTemplate("index.tpl.html");

if (Auth::hasValidCookie(APP_COOKIE)) {
    $cookie = Auth::getCookieInfo(APP_COOKIE);
    if (!empty($_REQUEST["url"])) {
        $extra = '?url=' . $_REQUEST["url"];
    } else {
        $extra = '';
    }
    Auth::redirect(APP_RELATIVE_URL . "select_project.php" . $extra);
}

$projects = Project::getAnonymousList();
if (empty($projects)) {
    $tpl->assign("anonymous_post", 0);
} else {
    $tpl->assign("anonymous_post", 1);
}

$tpl->displayTemplate();
