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

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('convert_note.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);
$usr_id = Auth::getUserID();

$note_id = !empty($_GET['id']) ? $_GET['id'] : $_POST['note_id'];
$note = Note::getDetails($note_id);
$issue_id = $note['not_iss_id'];

if ((User::getRoleByUser($usr_id, Issue::getProjectID($issue_id)) < User::ROLE_USER) || (!Access::canConvertNote($issue_id, Auth::getUserID()))) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'convert') {
    if (@$_POST['add_authorized_replier'] == 1) {
        $authorize_sender = true;
    } else {
        $authorize_sender = false;
    }
    $tpl->assign('convert_result', Note::convertNote($_POST['note_id'], $_POST['target'], $authorize_sender));
} else {
    $tpl->assign('note_id', $_GET['id']);
}

$tpl->assign('current_user_prefs', Prefs::get(Auth::getUserID()));
$tpl->assign('issue_id', $issue_id);
$tpl->displayTemplate();
