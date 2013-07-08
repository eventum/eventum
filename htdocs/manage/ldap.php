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
$tpl->setTemplate("manage/ldap.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('administrator')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST['cat'] == 'update') {
    $setup = LDAP_Auth_Backend::loadSetup();
    $setup['host'] = $_POST['host'];
    $setup['port'] = $_POST['port'];
    $setup['binddn'] = $_POST['binddn'];
    $setup['bindpw'] = $_POST['bindpw'];
    $setup['basedn'] = $_POST['basedn'];
    $setup['userdn'] = $_POST['userdn'];
    $setup['user_filter'] = $_POST['user_filter'];
    $setup['customer_id_attribute'] = $_POST['customer_id_attribute'];
    $setup['contact_id_attribute'] = $_POST['contact_id_attribute'];
    $setup['create_users'] = $_POST['create_users'];
    $setup['default_role'] = $_POST['default_role'];
    $res = LDAP_Auth_Backend::saveSetup($setup);
    Misc::mapMessages($res, array(
            1   =>  array('Thank you, the setup information was saved successfully.', Misc::MSG_INFO),
            -1  =>  array("ERROR: The system doesn't have the appropriate permissions to create the configuration file
                            in the setup directory (" . APP_CONFIG_PATH . "). Please contact your local system
                            administrator and ask for write privileges on the provided path.", Misc::MSG_HTML_BOX),
            -2  =>  array("ERROR: The system doesn't have the appropriate permissions to update the configuration file
                            in the setup directory (" . APP_CONFIG_PATH . "/ldap.php). Please contact your local system
                            administrator and ask for write privileges on the provided filename.", Misc::MSG_HTML_BOX),
    ));

    $tpl->assign("result", $res);
}
$options = LDAP_Auth_Backend::loadSetup(true);
$tpl->assign("setup", $options);
$tpl->assign("project_list", Project::getAll());
$tpl->assign("project_roles", array(0 => "No Access") + User::getRoles());
$tpl->assign("user_roles", User::getRoles(array('Customer')));

$tpl->displayTemplate();
