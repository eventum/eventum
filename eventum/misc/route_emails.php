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
$structure = Mime_Helper::decode($full_message, true, false);

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

// check if the sender of this email is in the notification list, or is the user currently locking the issue
// -> if not, add the email body as a note to the issue, block the message and send a notification to the sender
$lock_usr_id = Issue::getLockedUserID($issue_id);
if (!empty($lock_usr_id)) {
    // check if the sender is really a staff user and not just an unknown person
    $sender_usr_id = User::getUserIDByEmail($sender_email);
    if (!empty($sender_usr_id)) {
        $lock_usr_info = User::getNameEmail($lock_usr_id);
        $stmt = "SELECT
                    usr_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    sbt_type='emails' AND
                    sbt_sub_id=sub_id AND
                    sub_iss_id=$issue_id AND
                    sub_usr_id=usr_id AND
                    usr_role > " . User::getRoleID('Viewer');
        $allowed_emails = $GLOBALS["db_api"]->dbh->getCol($stmt);
        $allowed_emails = array_map('strtolower', $allowed_emails);
        // if the person locking the issue is not already in the notification list, then 
        // add him to the list of email addresses allowed to send emails to issue-XXX@
        if (!in_array(strtolower($lock_usr_info['usr_email']), $allowed_emails)) {
            $allowed_emails[] = strtolower($lock_usr_info['usr_email']);
        }
        if (!in_array($sender_email, $allowed_emails)) {
            // add the message body as a note
            $HTTP_POST_VARS = array(
                'note'     => "The following message was blocked from being routed to the notification list of this issue:\n\n" . addslashes($full_message),
                'issue_id' => $issue_id
            );
            Note::insert();
            // send alert email back to the sender
            $text_message = "Sorry, but your message to issue #$issue_id was blocked because you are not subscribed to the notification list of this locked issue.\n\n";
            $text_message .= "Your message was not sent to the notification list but was saved as an internal note for future reference. If you want to send this email to issue #$issue_id, please add yourself to the notification list using the web interface and re-send the message.";
            $mail = new Mail_API;
            $mail->setTextBody($text_message);
            $setup = $mail->getSMTPSettings();
            $mail->send($setup["from"], $sender_email, "Blocked Message for Issue #" . $issue_id);

            echo "Message blocked from being sent to the notification list of issue #$issue_id\n";
            exit();
        }
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
    'message_id'     => @addslashes(@$structure->headers['message-id']),
    'date'           => Date_API::getCurrentUnixTimestampGMT(),
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
    // special case when emails are bounced back, so we don't want to notify the customer about those
    if (Notification::isBounceMessage($sender_email)) {
        $internal_only = true;
    }
    Notification::notifyNewEmail($issue_id, $structure, $full_message, $internal_only);
    Issue::markAsUpdated($issue_id);
}

$prj_id = Issue::getProjectID($issue_id);
$staff_emails = Project::getUserEmailAssocList($prj_id, 'active');
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
?>