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
$tpl->setTemplate('authorized_replier.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$prj_id = Auth::getCurrentProject();
$issue_id = @$_POST['issue_id'] ? $_POST['issue_id'] : $_GET['iss_id'];
$tpl->assign('issue_id', $issue_id);
if (!Access::canViewAuthorizedRepliers($issue_id, Auth::getUserID())) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

if (@$_POST['cat'] == 'insert') {
    $res = Authorized_Replier::manualInsert($issue_id, $_POST['email']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the authorized replier was inserted successfully.'));
    } elseif ($res == -1) {
        Misc::setMessage(ev_gettext('An error occurred while trying to insert the authorized replier.'), Misc::MSG_ERROR);
    } elseif ($res == -2) {
        Misc::setMessage(ev_gettext("Users with a role of 'customer' or below are not allowed to be added to the authorized repliers list."), Misc::MSG_ERROR);
    }
} elseif (@$_POST['cat'] == 'delete') {
    $res = Authorized_Replier::removeRepliers($_POST['items']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the authorized replier was deleted successfully.'));
    } elseif ($res == -1) {
        Misc::setMessage(ev_gettext('An error occurred while trying to delete the authorized replier.'), Misc::MSG_ERROR);
    }
}

list(, $repliers) = Authorized_Replier::getAuthorizedRepliers($issue_id);
$tpl->assign('list', $repliers);

$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign('assoc_users', $t);

$tpl->displayTemplate();
