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

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("view_email.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);
$issue_id = Support::getIssueFromEmail($_GET["id"]);

if (!Issue::canAccess($issue_id, Auth::getUserID())) {
    $tpl->setTemplate("permission_denied.tpl.html");
    $tpl->displayTemplate();
    exit;
}

$email = Support::getEmailDetails($_GET["ema_id"], $_GET["id"]);
$email['seb_body'] = str_replace("&amp;nbsp;", "&nbsp;", $email['seb_body']);
$tpl->bulkAssign(array(
    "email"           => $email,
    "issue_id"        => $issue_id,
    // TRANSLATORS: $1 - issue_id, $2 - email subject, $3 - email_id
    'extra_title'     => ev_gettext('Issue #%1$s Email #%3$s: %2$s', $issue_id, $email['sup_subject'],
                                    Support::getSequenceByID($_GET['id'])),
    'email_accounts'  =>  Email_Account::getAssocList(array_keys(Project::getAssocList(Auth::getUserID())), true),
    'recipients'      =>  Mail_Queue::getMessageRecipients(array('customer_email', 'other_email'), $_GET["id"]),
));

if (@$_GET['cat'] == 'list_emails') {
    $sides = Support::getListingSides($_GET["id"]);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next']
    ));
} elseif ((@$_GET['cat'] == 'move_email') && (Auth::getCurrentRole() >= User::getRoleID("Standard User"))) {
    $res = Support::moveEmail(@$_GET['id'], @$_GET['ema_id'], @$_GET['new_ema_id']);
    $tpl->assign("move_email_result", $res);
    $tpl->assign("current_user_prefs", Prefs::get(Auth::getUserID()));
} else {
    $sides = Support::getIssueSides($issue_id, $_GET["id"]);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next']
    ));
}

$tpl->displayTemplate();
