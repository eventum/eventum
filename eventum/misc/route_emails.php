<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
//
// @(#) $Id: s.route_emails.php 1.23 04/01/26 20:37:04-06:00 joao@kickass. $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.history.php");

$email_account_id = $HTTP_SERVER_VARS['argv'][1];
$full_message = Misc::getInput();
// save the full message for logging purposes
Support::saveRoutedEmail($full_message);

if (preg_match("/^(boundary=).*/m", $full_message)) {
    $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
    $replacement = '$1$2; $3$4';
    $full_message = preg_replace($pattern, $replacement, $full_message);
}
// associate routed emails to the internal system account
$sys_account = User::getNameEmail(APP_SYSTEM_USER_ID);
$associated_user = $sys_account['usr_email'];

// need some validation here
if (empty($email_account_id)) {
    echo "Error: Please provide the email account ID.\n";
    exit(78);
}
if (empty($full_message)) {
    echo "Error: The email message was empty.\n";
    exit(66);
}
if (empty($associated_user)) {
    echo "Error: The associated user for the email routing interface needs to be set.\n";
    exit(78);
}


//
// DON'T EDIT ANYTHING BELOW THIS LINE
//

// remove the reply-to: header
if (preg_match("/^(reply-to:).*/im", $full_message)) {
    $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
}

// check for magic cookie
if (Mail_API::hasMagicCookie($full_message)) {
    // strip the magic cookie
    $full_message = Mail_API::stripMagicCookie($full_message);
    $has_magic_cookie = true;
} else {
    $has_magic_cookie = false;
}

include_once(APP_INC_PATH . "private_key.php");
$time = time();
$cookie = base64_encode(serialize(array(
    "email"      => $associated_user,
    "login_time" => $time,
    "hash"       => md5($GLOBALS["private_key"] . md5($time) . $associated_user),
    "autologin"  => 0
)));
$HTTP_COOKIE_VARS[APP_COOKIE] = $cookie;

// check if the email routing interface is even supposed to be enabled
$setup = Setup::load();
if ($setup['email_routing']['status'] != 'enabled') {
    echo "Error: The email routing interface is disabled.\n";
    exit(78);
}
$prefix = $setup['email_routing']['address_prefix'];
$mail_domain = $setup['email_routing']['address_host'];
$mail_domain_alias = @$setup['email_routing']['host_alias'];
if (!empty($mail_domain_alias)) {
    $mail_domain = "[" . $mail_domain . "|" . $mail_domain_alias . "]";
}
if (empty($prefix)) {
    echo "Error: Please configure the email address prefix.\n";
    exit(78);
}
if (empty($mail_domain)) {
    echo "Error: Please configure the email address domain.\n";
    exit(78);
}
$structure = Mime_Helper::decode($full_message, true, true);
// remove extra 'Re: ' from subject
@$structure->headers['subject'] = Mail_API::removeExcessRe($structure->headers['subject']);

// find which issue ID this email refers to
@preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['to'], $matches);
@$issue_id = $matches[1];
// validation is always a good idea
if (empty($issue_id)) {
    // we need to try the Cc header as well
    @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['cc'], $matches);
    if (!empty($matches[1])) {
        $issue_id = $matches[1];
    } else {
        echo "Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.\n";
        exit(65);
    }
}

$body = Mime_Helper::getMessageBody($structure);

// associate the email to the issue
$parts = array();
Mime_Helper::parse_output($structure, $parts);

// get the sender's email address
$sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));

// strip out the warning message sent to staff users
if (($setup['email_routing']['status'] == 'enabled') &&
        ($setup['email_routing']['warning']['status'] == 'enabled')) {
    $full_message = Mail_API::stripWarningMessage($full_message);
    $body = Mail_API::stripWarningMessage($body);
}

$prj_id = Issue::getProjectID($issue_id);
$staff_emails = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Customer'));
$staff_emails = array_map('strtolower', $staff_emails);
// only allow staff users to use the magic cookie
if (!in_array($sender_email, array_values($staff_emails))) {
    $has_magic_cookie = false;
}

if (!$has_magic_cookie) {
    // check if sender email address is associated with a real user
    if ((!Notification::isBounceMessage($sender_email)) &&
            (!Support::isAllowedToEmail($issue_id, $sender_email))) {
        // add the message body as a note
        $HTTP_POST_VARS = array(
            'blocked_msg' => $full_message,
            'title'       => @$structure->headers['subject'],
            'note'        => Mail_API::getCannedBlockedMsgExplanation() . $body
        );
        Note::insert(Auth::getUserID(), $issue_id, $structure->headers['from'], false);
        
        $HTTP_POST_VARS['issue_id'] = $issue_id;
        $HTTP_POST_VARS['from'] = $sender_email;
        
        Workflow::handleBlockedEmail($prj_id, $issue_id, $HTTP_POST_VARS, 'routed');
        
        // try to get usr_id of sender, if not, use system account
        $usr_id = User::getUserIDByEmail(Mail_API::getEmailAddress($structure->headers['from']));
        if (!$usr_id) {
            $usr_id = APP_SYSTEM_USER_ID;
        }
        // log blocked email
        History::add($issue_id, $usr_id, History::getTypeID('email_blocked'), "Email from '" . $structure->headers['from'] . "' blocked.");
        exit();
    }
}

if (@count($parts["attachments"]) > 0) {
    $has_attachments = 1;
} else {
    $has_attachments = 0;
}
$t = array(
    'issue_id'       => $issue_id,
    'ema_id'         => $email_account_id,
    'message_id'     => @$structure->headers['message-id'],
    'date'           => Date_API::getCurrentDateGMT(),
    'from'           => @$structure->headers['from'],
    'to'             => @$structure->headers['to'],
    'cc'             => @$structure->headers['cc'],
    'subject'        => @$structure->headers['subject'],
    'body'           => @$body,
    'full_email'     => @$full_message,
    'has_attachment' => $has_attachments
);
// automatically associate this incoming email with a customer
if (Customer::hasCustomerIntegration($prj_id)) {
    if (!empty($structure->headers['from'])) {
        list($customer_id,) = Customer::getCustomerIDByEmails($prj_id, array($sender_email));
        if (!empty($customer_id)) {
            $t['customer_id'] = $customer_id;
        }
    }
}
if (empty($t['customer_id'])) {
    $t['customer_id'] = "NULL";
}
$res = Support::insertEmail($t, $structure);
if ($res != -1) {
    Support::extractAttachments($issue_id, $full_message);

    // notifications about new emails are always external
    $internal_only = false;
    // special case when emails are bounced back, so we don't want a notification about those
    if (Notification::isBounceMessage($sender_email)) {
        $internal_only = true;
    }
    Notification::notifyNewEmail(Auth::getUserID(), $issue_id, $structure, $full_message, $internal_only);
    Issue::markAsUpdated($issue_id);
    // try to get usr_id of sender, if not, use system account
    $usr_id = User::getUserIDByEmail(Mail_API::getEmailAddress($structure->headers['from']));
    if (!$usr_id) {
        $usr_id = APP_SYSTEM_USER_ID;
    }
    // log blocked email
    History::add($issue_id, $usr_id, History::getTypeID('email_routed'), "Email routed from " . $structure->headers['from']);
}
?>