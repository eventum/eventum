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

$email_account_id = $HTTP_SERVER_VARS['argv'][1];
$full_message = Misc::getInput();
// save the full message for logging purposes
Support::saveRoutedEmail($full_message);

if (preg_match("/^(boundary=).*/m", $full_message)) {
    $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
    $replacement = '$1$2; $3$4';
    $full_message = preg_replace($pattern, $replacement, $full_message);
}
$associated_user = 'admin@domain.com'; // SETUP: this needs to be configured properly

// need some validation here
if (empty($email_account_id)) {
    echo "Error: Please provide the email account ID.\n";
    exit(100);
}
if (empty($full_message)) {
    echo "Error: The email message was empty.\n";
    exit(100);
}
if (empty($associated_user)) {
    echo "Error: The associated user for the email routing interface needs to be set.\n";
    exit(100);
}


//
// DON'T EDIT ANYTHING BELOW THIS LINE
//

// remove the reply-to: header
if (preg_match("/^(reply-to:).*/im", $full_message)) {
    $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
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
    exit(100);
}
$prefix = $setup['email_routing']['address_prefix'];
$mail_domain = $setup['email_routing']['address_host'];
if (empty($prefix)) {
    echo "Error: Please configure the email address prefix.\n";
    exit(100);
}
if (empty($mail_domain)) {
    echo "Error: Please configure the email address domain.\n";
    exit(100);
}
$structure = Mime_Helper::decode($full_message, true, true);

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
        exit(100);
    }
}

list($headers, $body) = Mime_Helper::splitBodyHeader($full_message);

// associate the email to the issue
$parts = array();
Mime_Helper::parse_output($structure, $parts);

// get the sender's email address
$sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));

// strip out the warning message sent to non-support developers
if (!empty($setup['email_routing']['warning']['message'])) {
    $full_message = str_replace($setup['email_routing']['warning']['message'], '', $full_message);
    $body = str_replace($setup['email_routing']['warning']['message'], '', $body);
}

// check if sender email address is associated with a real user
if (!Support::isAllowedToEmail($issue_id, $sender_email)) {
    $body = Support::getMessageBody($structure);
    // add the message body as a note
    $HTTP_POST_VARS = array(
        'blocked_msg' => addslashes($full_message),
        'title'       => "Blocked email message",
        'note'        => addslashes($body),
        'issue_id'    => $issue_id
    );
    Note::insert();
    exit();
}

if (@count($parts["attachments"]) > 0) {
    $has_attachments = 1;
} else {
    $has_attachments = 0;
}
$t = array(
    'issue_id'       => $issue_id,
    'ema_id'         => $email_account_id,
    'message_id'     => @addslashes(@$structure->headers['message-id']),
    'date'           => Date_API::getCurrentDateGMT(),
    'from'           => @addslashes($structure->headers['from']),
    'to'             => @addslashes($structure->headers['to']),
    'cc'             => @addslashes($structure->headers['cc']),
    'subject'        => @addslashes($structure->headers['subject']),
    'body'           => @addslashes($body),
    'full_email'     => @addslashes($full_message),
    'has_attachment' => $has_attachments
);
$res = Support::insertEmail($t, $structure);
if ($res != -1) {
    Support::extractAttachments($issue_id, $full_message);

    // notifications about new emails are always external
    $internal_only = false;
    // special case when emails are bounced back, so we don't want a notification about those
    if (Notification::isBounceMessage($sender_email)) {
        $internal_only = true;
    }
    Notification::notifyNewEmail($issue_id, $structure, $full_message, $internal_only);
    Issue::markAsUpdated($issue_id);
}

if (Notification::isBounceMessage($sender_email)) {
    // only change the status of the associated issue if the current status is not
    // currently marked to a status with a closed context
    $current_status_id = Issue::getStatusID($issue_id);
    if (!Status::hasClosedContext($current_status_id)) {
        Issue::markAsUpdated($issue_id);
    }
} else {
    $prj_id = Issue::getProjectID($issue_id);
    $staff_emails = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Reporter'));
    $staff_emails = array_map('strtolower', $staff_emails);
    // handle the first_response_date / last_response_date fields
    if (in_array($sender_email, array_values($staff_emails))) {
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_last_response_date='" . Date_API::getCurrentDateGMT() . "'
                 WHERE
                    iss_id=$issue_id";
        $GLOBALS["db_api"]->dbh->query($stmt);

        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                 SET
                    iss_first_response_date='" . Date_API::getCurrentDateGMT() . "'
                 WHERE
                    iss_first_response_date IS NULL AND
                    iss_id=$issue_id";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }
}
?>