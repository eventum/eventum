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
// @(#) $Id: s.forgot_password.php 1.8 03/12/12 19:09:43-00:00 jpradomaia $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("forgot_password.tpl.html");

if (@$HTTP_POST_VARS["cat"] == "reset_password") {
    if (empty($HTTP_POST_VARS["email"])) {
        $tpl->assign("result", 4);
    }
    $usr_id = User::getUserIDByEmail($HTTP_POST_VARS["email"]);
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
?>