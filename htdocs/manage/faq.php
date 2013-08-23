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
$tpl->setTemplate("manage/faq.tpl.html");

Auth::checkAuthentication(APP_COOKIE);

$role_id = Auth::getCurrentRole();
if ($role_id < User::getRoleID('manager')) {
    Misc::setMessage("Sorry, you are not allowed to access this page.", Misc::MSG_ERROR);
    $tpl->displayTemplate();exit;
}

if (@$_POST["cat"] == "new") {
    $res = FAQ::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the FAQ entry was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the FAQ entry.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this FAQ entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this FAQ entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "update") {
    $res = FAQ::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the FAQ entry was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the FAQ entry information.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this FAQ entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this FAQ entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST["cat"] == "delete") {
    FAQ::remove();
} elseif (!empty($_GET['prj_id'])) {
    $tpl->assign("info", array('faq_prj_id' => $_GET['prj_id']));
    if (CRM::hasCustomerIntegration($_GET['prj_id'])) {
        $crm = CRM::getInstance($_GET['prj_id']);
        $tpl->assign("support_levels", $crm->getSupportLevelAssocList());
    }
}

if (@$_GET["cat"] == "edit") {
    $info = FAQ::getDetails($_GET["id"]);
    if (!empty($_GET['prj_id'])) {
        $info['faq_prj_id'] = $_GET['prj_id'];
    }
    if (CRM::hasCustomerIntegration($info['faq_prj_id'])) {
        $crm = CRM::getInstance($info['faq_prj_id']);
        $tpl->assign("support_levels", $crm->getSupportLevelAssocList());
    }
    $tpl->assign("info", $info);
} elseif (@$_GET["cat"] == "change_rank") {
    FAQ::changeRank($_GET['id'], $_GET['rank']);
}

$tpl->assign("list", FAQ::getList());
$tpl->assign("project_list", Project::getAll());

$tpl->displayTemplate();
