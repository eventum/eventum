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

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

$tpl = new Template_Helper();
$tpl->setTemplate('view_email.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);
$issue_id = Support::getIssueFromEmail($_GET['id']);

if (($issue_id != 0 && !Issue::canAccess($issue_id, $usr_id)) ||
    ($issue_id == 0 && User::getRoleByUser($usr_id, $prj_id) < User::ROLE_USER)) {
    $tpl->setTemplate('permission_denied.tpl.html');
    $tpl->displayTemplate();
    exit;
}

$email = Support::getEmailDetails($_GET['ema_id'], $_GET['id']);
$email['seb_body'] = str_replace('&amp;nbsp;', '&nbsp;', $email['seb_body']);
$tpl->assign(array(
    'email'           => $email,
    'issue_id'        => $issue_id,
    // TRANSLATORS: $1 - issue_id, $2 - email subject, $3 - email_id
    'extra_title'     => ev_gettext('Issue #%1$s Email #%3$s: %2$s', $issue_id, $email['sup_subject'],
                                    Support::getSequenceByID($_GET['id'])),
    'email_accounts'  =>  Email_Account::getAssocList(array_keys(Project::getAssocList(Auth::getUserID())), true),
    'recipients'      =>  Mail_Queue::getMessageRecipients(array('customer_email', 'other_email'), $_GET['id']),
));

if (@$_GET['cat'] == 'list_emails') {
    $sides = Support::getListingSides($_GET['id']);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next'],
    ));
} elseif ((@$_GET['cat'] == 'move_email') && (Auth::getCurrentRole() >= User::ROLE_USER)) {
    $res = Support::moveEmail(@$_GET['id'], @$_GET['ema_id'], @$_GET['new_ema_id']);
    $tpl->assign('move_email_result', $res);
    $tpl->assign('current_user_prefs', Prefs::get(Auth::getUserID()));
} else {
    $sides = Support::getIssueSides($issue_id, $_GET['id']);
    $tpl->assign(array(
        'previous' => $sides['previous'],
        'next'     => $sides['next'],
    ));
}

$tpl->displayTemplate();
