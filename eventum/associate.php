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
// @(#) $Id$
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "db_access.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.mail.php");

$tpl = new Template_API();
$tpl->setTemplate("associate.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

if (@$HTTP_POST_VARS['cat'] == 'associate') {
    if ($HTTP_POST_VARS['target'] == 'email') {
        $res = Support::associate();
        $tpl->assign("associate_result", $res);
    } else {
        for ($i = 0; $i < count($HTTP_POST_VARS['item']); $i++) {
            $full_message = Support::getFullEmail($HTTP_POST_VARS['item'][$i]);
            $structure = Mime_Helper::decode($full_message, true, true);
            $body = Support::getMessageBody($structure);
            // add the message body as a note
            $HTTP_POST_VARS['blocked_msg'] = $full_message;
            $HTTP_POST_VARS['title'] = 'Message manually converted to an internal note';
            $HTTP_POST_VARS['note'] = $body;
            // XXX: probably broken to use the current logged in user as the 'owner' of 
            // XXX: this new note, but that's how it was already
            $res = Note::insert(Auth::getUserID(), $HTTP_POST_VARS['issue']);
            // remove the associated email
            if ($res) {
                // notify the email being blocked to IRC
                Notification::notifyIRCBlockedMessage($HTTP_POST_VARS['issue'], $structure->headers['from']);
                Support::removeEmail($HTTP_POST_VARS['item'][$i]);
            }
        }
        $tpl->assign("associate_result", $res);
    }
    @$tpl->assign('total_emails', count($HTTP_POST_VARS['item']));
} else {
    @$tpl->assign('emails', $HTTP_GET_VARS['item']);
    @$tpl->assign('total_emails', count($HTTP_GET_VARS['item']));
    // check if the selected emails all have sender email addresses that are associated with a real user
    $senders = Support::getSender($HTTP_GET_VARS['item']);
    $sender_emails = array();
    for ($i = 0; $i < count($senders); $i++) {
        $email = Mail_API::getEmailAddress($senders[$i]);
        $sender_emails[$email] = $senders[$i];
    }
    $unknown_contacts = array();
    foreach ($sender_emails as $email => $address) {
        if (!@in_array($email, $contact_emails)) {
            $usr_id = User::getUserIDByEmail($email);
            if (empty($usr_id)) {
                $unknown_contacts[] = $address;
            }
        }
    }
    if (count($unknown_contacts) > 0) {
        $tpl->assign('unknown_contacts', $unknown_contacts);
    }
}

$tpl->assign("current_user_prefs", Prefs::get(Auth::getUserID()));

$tpl->displayTemplate();
?>