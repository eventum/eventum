<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('manage/field_display.tpl.html');

Auth::checkAuthentication();

$tpl->assign('type', 'field_display');

$prj_id = @$_GET['prj_id'];

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (count(@$_POST['fields']) > 0) {
    $res = Project::updateFieldDisplaySettings($prj_id, $_POST['fields']);
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the information was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the information.'), Misc::MSG_ERROR),
    ));
}

$fields = Project::getDisplayFields();

$excluded_roles = array('viewer');
if (!CRM::hasCustomerIntegration($prj_id)) {
    $excluded_roles[] = 'customer';
}
$user_roles = User::getRoles($excluded_roles);
$user_roles[9] = 'Never Display';

$tpl->assign('prj_id', $prj_id);
$tpl->assign('fields', $fields);
$tpl->assign('user_roles', $user_roles);
$tpl->assign('display_settings', Project::getFieldDisplaySettings($prj_id));

$tpl->displayTemplate();
