<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// @(#) $Id: s.confirm.php 1.3 03/12/04 17:58:15-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("confirm.tpl.html");

if (@$_GET['cat'] == 'newuser') {
    $res = @User::checkHash($_GET["email"], $_GET["hash"]);
    if ($res == 1) {
        User::confirmVisitorAccount($_GET["email"]);
        // redirect user to login form with pretty message
        Auth::redirect('index.php?err=8&email=' . $_GET["email"]);
        exit;
    }
    $tpl->assign("confirm_result", $res);
} elseif (@$_GET['cat'] == 'password') {
    $res = @User::checkHash($_GET["email"], $_GET["hash"]);
    if ($res == 1) {
        User::confirmNewPassword($_GET["email"]);
        $tpl->assign("email", $_GET["email"]);
    }
    $tpl->assign("confirm_result", $res);
}

$tpl->displayTemplate();
?>