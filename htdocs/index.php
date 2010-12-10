<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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

require_once dirname(__FILE__) . '/../init.php';

// check if templates_c is writable by the web server user
if (!Misc::isWritableDirectory(APP_TPL_COMPILE_PATH)) {
    $errors = array("Directory '" . APP_TPL_COMPILE_PATH . "' is not writable.");
    Misc::displayRequirementErrors($errors);
    exit;
}

$tpl = new Template_Helper();
$tpl->setTemplate("index.tpl.html");

// log anonymous users out so they can use the login form
if (Auth::hasValidCookie(APP_COOKIE) && Auth::isAnonUser()) {
    Auth::logout();
}

if (Auth::hasValidCookie(APP_COOKIE) && !Auth::isAnonUser()) {
    $cookie = Auth::getCookieInfo(APP_COOKIE);
    if (!empty($_REQUEST["url"])) {
        $extra = '?url=' . $_REQUEST["url"];
    } else {
        $extra = '';
    }
    Auth::redirect("select_project.php" . $extra);
}

$projects = Project::getAnonymousList();
if (empty($projects)) {
    $tpl->assign("anonymous_post", 0);
} else {
    $tpl->assign("anonymous_post", 1);
}

$tpl->displayTemplate();
