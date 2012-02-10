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
// | Authors: Dave Anderson <dave@anderson.net.nz>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/email_alias.tpl.html");

Auth::checkAuthentication(APP_COOKIE, NULL, true);

$role_id = Auth::getCurrentRole();
if (($role_id == User::getRoleID('administrator')) || ($role_id == User::getRoleID('manager'))) {
    if ($role_id == User::getRoleID('administrator')) {
        $tpl->assign("show_setup_links", true);
        $excluded_roles = array('customer');
    } else {
        $excluded_roles = array('customer', 'administrator');
    }

    $usr_id = $_REQUEST['id'];

    if (@$_POST["cat"] == "save") {
        $res = User::addAlias($usr_id, $_POST["alias"]);
        Misc::mapMessages($res, array(
                true   =>  array(ev_gettext('Thank you, the alias was added successfully.'), Misc::MSG_INFO),
                false  =>  array(ev_gettext('An error occurred while trying to add the alias.'), Misc::MSG_ERROR),
        ));
    } elseif (@$_POST["cat"] == "remove") {
    	foreach($_POST["item"] as $aliastmp){
        	$res = User::removeAlias($usr_id, $aliastmp);
        }
        Misc::mapMessages($res, array(
                true   =>  array(ev_gettext('Thank you, the alias was removed successfully.'), Misc::MSG_INFO),
                false  =>  array(ev_gettext('An error occurred while trying to remove the alias.'), Misc::MSG_ERROR),
        ));
    }

    $tpl->assign("list", User::getAliases($usr_id));
    $tpl->assign("username", User::getFullName($usr_id));
    $tpl->assign("id",$usr_id);
} else {
    $tpl->assign("show_not_allowed_msg", true);
}


$tpl->displayTemplate();
