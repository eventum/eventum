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
$tpl->setTemplate('manage/faq.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = FAQ::insert();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the FAQ entry was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the FAQ entry.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this FAQ entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this FAQ entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = FAQ::update();
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the FAQ entry was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the FAQ entry information.'), Misc::MSG_ERROR),
            -2  =>  array(ev_gettext('Please enter the title for this FAQ entry.'), Misc::MSG_ERROR),
            -3  =>  array(ev_gettext('Please enter the message for this FAQ entry.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    FAQ::remove();
} elseif (!empty($_GET['prj_id'])) {
    $tpl->assign('info', array('faq_prj_id' => $_GET['prj_id']));
    if (CRM::hasCustomerIntegration($_GET['prj_id'])) {
        $crm = CRM::getInstance($_GET['prj_id']);
        $tpl->assign('support_levels', $crm->getSupportLevelAssocList());
    }
}

if (@$_GET['cat'] == 'edit') {
    $info = FAQ::getDetails($_GET['id']);
    if (!empty($_GET['prj_id'])) {
        $info['faq_prj_id'] = $_GET['prj_id'];
    }
    if (CRM::hasCustomerIntegration($info['faq_prj_id'])) {
        $crm = CRM::getInstance($info['faq_prj_id']);
        $tpl->assign('support_levels', $crm->getSupportLevelAssocList());
    }
    $tpl->assign('info', $info);
} elseif (@$_GET['cat'] == 'change_rank') {
    FAQ::changeRank($_GET['id'], $_GET['rank']);
}

$tpl->assign('list', FAQ::getList());
$tpl->assign('project_list', Project::getAll());

$tpl->displayTemplate();
