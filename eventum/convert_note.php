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
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.draft.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.notification.php");

$tpl = new Template_API();
$tpl->setTemplate("convert_note.tpl.html");

Auth::checkAuthentication(APP_COOKIE, 'index.php?err=5', true);

if (@$HTTP_POST_VARS['cat'] == 'convert') {
    $issue_id = Note::getIssueID($HTTP_POST_VARS['note_id']);
    $email_account_id = Support::getEmailAccount();
    $blocked_message = Note::getBlockedMessage($HTTP_POST_VARS['note_id']);
    $structure = Mime_Helper::decode($blocked_message, true, true);
    $body = Support::getMessageBody($structure);
    $sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));
    $parts = array();
    Mime_Helper::parse_output($structure, $parts);
    if ($HTTP_POST_VARS['target'] == 'email') {
        // XXX: need to eventually reuse this code in a function
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
            'full_email'     => @addslashes($blocked_message),
            'has_attachment' => $has_attachments
        );
        $res = Support::insertEmail($t, $structure);
        if ($res != -1) {
            Support::extractAttachments($issue_id, $blocked_message);
            // notifications about new emails are always external
            $internal_only = false;
            // special case when emails are bounced back, so we don't want a notification about those
            if (Notification::isBounceMessage($sender_email)) {
                $internal_only = true;
            }
            Notification::notifyNewEmail($issue_id, $structure, $blocked_message, $internal_only);
            Issue::markAsUpdated($issue_id);
            Note::remove($HTTP_POST_VARS['note_id']);
        }
        $tpl->assign("convert_result", $res);
    } else {
        // save message as a draft
        @$res = Draft::saveEmail($issue_id, $structure->headers['to'], $structure->headers['cc'], addslashes($structure->headers['subject']), addslashes($body));
        // remove the note, if the draft was created successfully
        if ($res) {
            Note::remove($HTTP_POST_VARS['note_id']);
        }
        $tpl->assign("convert_result", $res);
    }
} else {
    $tpl->assign("note_id", $HTTP_GET_VARS['id']);
}

$tpl->assign("current_user_prefs", Prefs::get(Auth::getUserID()));

$tpl->displayTemplate();
?>