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
// @(#) $Id: s.route_notes.php 1.5 04/01/26 20:37:04-06:00 joao@kickass. $
//
include_once("../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.mime_helper.php");

$full_message = Misc::getInput();
// save the full message for logging purposes
Note::saveRoutedNote($full_message);

if (preg_match("/^(boundary=).*/m", $full_message)) {
    $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
    $replacement = '$1$2; $3$4';
    $full_message = preg_replace($pattern, $replacement, $full_message);
}

// need some validation here
if (empty($full_message)) {
    echo "Error: The email message was empty.\n";
    exit(66);
}


//
// DON'T EDIT ANYTHING BELOW THIS LINE 
//

// remove the reply-to: header
if (preg_match("/^(reply-to:).*/im", $full_message)) {
    $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
}

// check if the email routing interface is even supposed to be enabled
$setup = Setup::load();
if (@$setup['note_routing']['status'] != 'enabled') {
    echo "Error: The internal note routing interface is disabled.\n";
    exit(78);
}
$prefix = $setup['note_routing']['address_prefix'];
$mail_domain = $setup['note_routing']['address_host'];
if (empty($prefix)) {
    echo "Error: Please configure the email address prefix.\n";
    exit(78);
}
if (empty($mail_domain)) {
    echo "Error: Please configure the email address domain.\n";
    exit(78);
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
        echo "Error: The routed note had no associated Eventum issue ID or had an invalid recipient address.\n";
        exit(65);
    }
}

$prj_id = Issue::getProjectID($issue_id);
// check if the sender is allowed in this issue' project and if it is an internal user
$users = Project::getUserEmailAssocList($prj_id, 'active');
$sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));
$user_emails = array_map('strtolower', array_values($users));
if (!in_array($sender_email, $user_emails)) {
    echo "Error: The sender of this email is not allowed in the project associated with issue #$issue_id.\n";
    exit(77);
}

include_once(APP_INC_PATH . "private_key.php");
$time = time();
$cookie = base64_encode(serialize(array(
    "email"      => $sender_email,
    "login_time" => $time,
    "hash"       => md5($GLOBALS["private_key"] . md5($time) . $sender_email),
    "autologin"  => 0
)));
$HTTP_COOKIE_VARS[APP_COOKIE] = $cookie;

// parse the Cc: list, if any, and add these internal users to the issue notification list
$users = array_flip($users);
$addresses = Mail_API::getEmailAddresses(@$structure->headers['cc']);
$cc_users = array();
foreach ($addresses as $cc_email) {
    if (in_array(strtolower($cc_email), $user_emails)) {
        $cc_users[] = $users[$cc_email];
    }
}

list(,$body) = Mime_Helper::splitBodyHeader($full_message);

// insert the new note and send notification about it
$HTTP_POST_VARS = array(
    'title'                => @$structure->headers['subject'],
    'note'                 => $body,
    'note_cc'              => $cc_users,
    'add_extra_recipients' => 'yes'
);
Note::insert(Auth::getUserID(), $issue_id, false, false);
History::add($issue_id, Auth::getUserID(), History::getTypeID('note_routed'), "Note routed from " . $structure->headers['from']);
?>