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
$tpl->setTemplate('manage/customer_notes.tpl.html');

Auth::checkAuthentication();

$role_id = Auth::getCurrentRole();
if ($role_id < User::ROLE_MANAGER) {
    Misc::setMessage(ev_gettext('Sorry, you are not allowed to access this page.'), Misc::MSG_ERROR);
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'new') {
    $res = CRM::insertNote($_POST['project'], $_POST['customer'], $_POST['note']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the note was added successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to add the new note.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'update') {
    $res = CRM::updateNote($_POST['id'], $_POST['project'], $_POST['customer'], $_POST['note']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the note was updated successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to update the note.'), Misc::MSG_ERROR),
    ));
} elseif (@$_POST['cat'] == 'delete') {
    $res = CRM::removeNotes($_POST['items']);
    Misc::mapMessages($res, array(
            1   =>  array(ev_gettext('Thank you, the note was deleted successfully.'), Misc::MSG_INFO),
            -1  =>  array(ev_gettext('An error occurred while trying to delete the note.'), Misc::MSG_ERROR),
    ));
} elseif (!empty($_GET['prj_id'])) {
    $tpl->assign('info', array('cno_prj_id' => $_GET['prj_id']));
    $tpl->assign('customers', CRM::getInstance($_GET['prj_id'])->getCustomerAssocList());
}

if (@$_GET['cat'] == 'edit') {
    $info = CRM::getNoteDetailsByID($_GET['id']);
    if (!empty($_GET['prj_id'])) {
        $info['cno_prj_id'] = $_GET['prj_id'];
    }
    $tpl->assign('customers', CRM::getInstance($info['cno_prj_id'])->getCustomerAssocList());
    $tpl->assign('info', $info);
}

$tpl->assign('list', CRM::getNoteList());
$tpl->assign('project_list', Project::getAll(false));

$tpl->displayTemplate();
