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

$prj_id = $_REQUEST['prj_id'];

$tpl = new Template_Helper();
$tpl->setTemplate('manage/column_display.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'save') {
    $res = Display_Column::save();
    $tpl->assign('result', $res);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, columns to display was saved successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to save columns to display.'), Misc::MSG_ERROR),
    ));
}

$page = 'list_issues';
$available = Display_Column::getAllColumns($page);
$selected = Display_Column::getSelectedColumns($prj_id, $page);

// re-order available array to match rank
$available_ordered = array();
foreach ($selected as $field_name => $field_info) {
    $available_ordered[$field_name] = $available[$field_name];
    unset($available[$field_name]);
}
if (count($available) > 0) {
    $available_ordered += $available;
}

$excluded_roles = array();
if (!CRM::hasCustomerIntegration($prj_id)) {
    $excluded_roles[] = 'customer';
}
$user_roles = User::getRoles($excluded_roles);
$user_roles[9] = 'Never Display';

// generate ranks
$ranks = array();
$navailable_ordered = count($available_ordered);
for ($i = 1; $i <= $navailable_ordered; $i++) {
    $ranks[$i] = $i;
}

$tpl->assign(array(
    'available' =>  $available_ordered,
    'selected'  =>  $selected,
    'user_roles' =>  $user_roles,
    'page'      =>  $page,
    'ranks'     =>  $ranks,
    'prj_id'    =>  $prj_id,
    'project_name'  =>  Project::getName($prj_id),
));

$tpl->displayTemplate();
