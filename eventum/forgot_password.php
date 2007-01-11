<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
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
// @(#) $Id: forgot_password.php 3189 2007-01-11 21:57:57Z glen $
//
require_once("config.inc.php");
require_once(APP_INC_PATH . "class.template.php");
require_once(APP_INC_PATH . "class.user.php");
require_once(APP_INC_PATH . "class.mail.php");
require_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("forgot_password.tpl.html");

if (@$_POST["cat"] == "reset_password") {
    if (empty($_POST["email"])) {
        $tpl->assign("result", 4);
    }
    $usr_id = User::getUserIDByEmail($_POST["email"]);
    if (empty($usr_id)) {
        $tpl->assign("result", 5);
    } else {
        $info = User::getDetails($usr_id);
        if (!User::isActiveStatus($info["usr_status"])) {
            $tpl->assign("result", 3);
        } else {
            User::sendPasswordConfirmationEmail($usr_id);
            $tpl->assign("result", 1);
        }
    }
}

$tpl->displayTemplate();
