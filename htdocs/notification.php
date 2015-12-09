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
$tpl->setTemplate('notification.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$issue_id = isset($_POST['issue_id']) ? (int) $_POST['issue_id'] : (int) $_GET['iss_id'];
$usr_id = Auth::getUserID();

if (!Access::canViewNotificationList($issue_id, $usr_id)) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

$sub_id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$prj_id = Auth::getCurrentProject();
$default_actions = Notification::getDefaultActions();

if ($sub_id) {
    $info = Notification::getDetails($sub_id);
} else {
    $info = array(
        'updated' => 0,
        'closed' => 0,
        'files' => 0,
        'emails' => 0,
    );
    foreach ($default_actions as $action) {
        $res[$action] = 1;
    }
}

$tpl->assign(array(
    'issue_id' => $issue_id,
    'default_actions' => $default_actions,
    'info' => $info,
));

$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : (isset($_GET['cat']) ? (string) $_GET['cat'] : null);

if ($cat == 'insert') {
    $res = Notification::subscribeEmail($usr_id, $issue_id, $_POST['email'], $_POST['actions']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the email has been subscribed to the issue.'));
    }
} elseif ($cat == 'update') {
    $res = Notification::update($issue_id, $_POST['id'], $_POST['email']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the notification entry was updated successfully.'));
    } elseif ($res == -1) {
        Misc::setMessage(ev_gettext('An error occurred while trying to update the notification entry.'), Misc::MSG_ERROR);
    } elseif ($res == -2) {
        Misc::setMessage(ev_gettext('Error: the given email address is not allowed to be added to the notification list.'), Misc::MSG_ERROR);
    }
    Auth::redirect(APP_RELATIVE_URL . 'notification.php?iss_id=' . $issue_id);
} elseif ($cat == 'edit') {
} elseif ($cat == 'delete') {
    $res = Notification::remove($_POST['items']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the items have been deleted.'));
    }
}

$tpl->assign('list', Notification::getSubscriberListing($issue_id));
/*
// the autocomplete is removed, no need to fetch the data
$tpl->assign('assoc_users', Project::getAddressBook($prj_id, $issue_id));
$tpl->assign('allowed_emails', Project::getAddressBookEmails($prj_id, $issue_id));
*/

$tpl->displayTemplate();
