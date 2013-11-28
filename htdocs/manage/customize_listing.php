<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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

require_once dirname(__FILE__) . '/../../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("manage/customize_listing.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('administrator')) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}
$tpl->assign("project_list", Project::getAll());

if (@$_POST["cat"] == "new") {
    $res = Status::insertCustomization($_POST['project'], $_POST['status'], $_POST['date_field'], $_POST['label']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the customization was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new customization.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this new customization'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = Status::updateCustomization($_POST['id'], $_POST['project'], $_POST['status'], $_POST['date_field'], $_POST['label']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the customization was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the customization information.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this customization.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    $res = Status::removeCustomization(@$_POST['items']);
    Misc::mapMessages($res, array(
            true   =>  array(ev_gettext('Thank you, the customization was deleted successfully.'), Misc::MSG_INFO),
            false  =>  array(ev_gettext('An error occurred while trying to delete the customization information.'), Misc::MSG_ERROR),
    ));
}

if (@$_GET["cat"] == "edit") {
    $details = Status::getCustomizationDetails($_GET["id"]);
    $tpl->assign(array(
        "info"        => $details,
        'project_id'  => $details['psd_prj_id'],
        'status_list' => Status::getAssocStatusList($details['psd_prj_id'], TRUE)
    ));
}

$display_customer_fields = false;
@$prj_id = $_POST["prj_id"] ? $_POST["prj_id"] : $_GET["prj_id"];
if (!empty($prj_id)) {
    $tpl->assign("status_list", Status::getAssocStatusList($prj_id, TRUE));
    $tpl->assign('project_id', $prj_id);
    $display_customer_fields = Customer::hasCustomerIntegration($prj_id);
}

$tpl->assign("date_fields", Issue::getDateFieldsAssocList($display_customer_fields));
$tpl->assign("project_list", Project::getAll());
$tpl->assign("list", Status::getCustomizationList());

$tpl->displayTemplate();
