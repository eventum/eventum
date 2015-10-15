<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('notification.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();
$issue_id = isset($_POST['issue_id']) ? (int) $_POST['issue_id'] : (int) $_GET['iss_id'];
$tpl->assign('issue_id', $issue_id);

// assign default to avoid warnings
$tpl->assign('info', null);

if (!Access::canViewNotificationList($issue_id, Auth::getUserID())) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

// format default actions properly
$default = Notification::getDefaultActions();
// first setup defaults
$res = array(
    'updated' => 0,
    'closed' => 0,
    'files' => 0,
    'emails' => 0,
);
foreach ($default as $action) {
    $res[$action] = 1;
}
$tpl->assign('default_actions', $res);

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
    $res = Notification::getDetails($_GET['id']);
    $tpl->assign('info', $res);
} elseif ($cat == 'delete') {
    $res = Notification::remove($_POST['items']);
    if ($res == 1) {
        Misc::setMessage(ev_gettext('Thank you, the items have been deleted.'));
    }
}

$tpl->assign('list', Notification::getSubscriberListing($issue_id));
$t = Project::getAddressBook($prj_id, $issue_id);
$tpl->assign('assoc_users', $t);
$tpl->assign('allowed_emails', Project::getAddressBookEmails($prj_id, $issue_id));

$tpl->displayTemplate();
