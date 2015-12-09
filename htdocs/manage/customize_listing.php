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
$tpl->setTemplate('manage/customize_listing.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_REPORTER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}
$tpl->assign('project_list', Project::getAll());

if (@$_POST['cat'] == 'new') {
    $res = Status::insertCustomization($_POST['project'], $_POST['status'], $_POST['date_field'], $_POST['label']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the customization was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new customization.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new customization'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = Status::updateCustomization($_POST['id'], $_POST['project'], $_POST['status'], $_POST['date_field'], $_POST['label']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the customization was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the customization information.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this customization.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    $res = Status::removeCustomization(@$_POST['items']);
    Misc::mapMessages($res, array(
            true   =>  array(ev_gettext('Thank you, the customization was deleted successfully.'), Misc::MSG_INFO),
            false  =>  array(ev_gettext('An error occurred while trying to delete the customization information.'), Misc::MSG_ERROR),
    ));
}

if (@$_GET['cat'] == 'edit') {
    $details = Status::getCustomizationDetails($_GET['id']);
    $tpl->assign(array(
        'info'        => $details,
        'project_id'  => $details['psd_prj_id'],
        'status_list' => Status::getAssocStatusList($details['psd_prj_id'], true),
    ));
}

$display_customer_fields = false;
@$prj_id = $_POST['prj_id'] ? $_POST['prj_id'] : $_GET['prj_id'];
if (!empty($prj_id)) {
    $tpl->assign('status_list', Status::getAssocStatusList($prj_id, true));
    $tpl->assign('project_id', $prj_id);
    $display_customer_fields = CRM::hasCustomerIntegration($prj_id);
}

$tpl->assign('date_fields', Issue::getDateFieldsAssocList($display_customer_fields));
$tpl->assign('project_list', Project::getAll());
$tpl->assign('list', Status::getCustomizationList());

$tpl->displayTemplate();
