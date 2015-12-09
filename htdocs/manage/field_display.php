<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

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
