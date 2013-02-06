<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2012 - 2013 Eventum Team.                              |
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
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/index.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$tpl->assign("type", "ldap");

$role_id = Auth::getCurrentRole();
if ($role_id == User::getRoleID('administrator')) {
    $tpl->assign("show_setup_links", true);

    if (@$_POST['cat'] == 'update') {
        $setup = LDAP_Auth_Backend::loadSetup();
        $setup['host'] = $_POST['host'];
        $setup['port'] = $_POST['port'];
        $setup['binddn'] = $_POST['binddn'];
        $setup['bindpw'] = $_POST['bindpw'];
        $setup['basedn'] = $_POST['basedn'];
        $setup['userdn'] = $_POST['userdn'];
        $setup['customer_id_attribute'] = $_POST['customer_id_attribute'];
        $setup['contact_id_attribute'] = $_POST['contact_id_attribute'];
        $setup['create_users'] = $_POST['create_users'];
        $setup['default_role'] = $_POST['default_role'];
        $res = LDAP_Auth_Backend::saveSetup($setup);
        $tpl->assign("result", $res);
    }
    $options = LDAP_Auth_Backend::loadSetup(true);
    $tpl->assign("setup", $options);
    $tpl->assign("project_list", Project::getAll());
    $tpl->assign("project_roles", array(0 => "No Access") + User::getRoles());
    $tpl->assign("user_roles", User::getRoles(array('Customer')));
} else {
    $tpl->assign("show_not_allowed_msg", true);
}

$tpl->displayTemplate();
