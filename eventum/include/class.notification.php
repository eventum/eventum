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
// @(#) $Id: s.class.notification.php 1.60 04/01/26 20:37:04-06:00 joao@kickass. $
//


/**
 * Class to handle all of the business logic related to sending email
 * notifications on actions regarding the issues.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.prefs.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.history.php");
include_once(APP_INC_PATH . "class.issue.php");

class Notification
{
    /**
     * Method used to check whether a given email address is subsbribed to
     * email notifications for a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $email The email address
     * @return  boolean
     */
    function isSubscribedToEmails($issue_id, $email)
    {
        $email = strtolower(Mail_API::getEmailAddress($email));
        if ($email == '@') {
            // broken address, don't send the email...
            return true;
        }
        $subscribed_emails = Notification::getSubscribedEmails($issue_id, 'emails');
        $subscribed_emails = array_map('strtolower', $subscribed_emails);
        if (@in_array($email, $subscribed_emails)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to get the list of email addresses currently
     * subscribed to a notification type for a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $type The notification type
     * @return  array The list of email addresses
     */
    function getSubscribedEmails($issue_id, $type)
    {
        $stmt = "SELECT
                    IF(usr_id <> 0, usr_email, sub_email) AS email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 ON
                    usr_id=sub_usr_id
                 WHERE
                    sbt_sub_id=sub_id AND
                    sbt_type='$type' AND
                    sub_iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to build a properly encoded email address that will be
     * used by the email/note routing system.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $sender The email address of the sender
     * @param   string $type Whether this is a note or email routing message
     * @return  string The properly encoded email address
     */
    function getFixedFromHeader($issue_id, $sender, $type)
    {
        if ($type == 'issue') {
            $routing = 'email_routing';
        } else {
            $routing = 'note_routing';
        }
        $info = Mail_API::getAddressInfo($sender);
        $setup = Setup::load();
        if (@$setup[$routing]['status'] != 'enabled') {
            // let's use the custom outgoing sender address
            $project_id = Issue::getProjectID($issue_id);
            $project_info = Project::getOutgoingSenderAddress($project_id);
            if (empty($project_info['email'])) {
                // if we have the real sender name, let's use that one
                if (!empty($info['sender_name'])) {
                    $from = Mail_API::getFormattedName($info['sender_name'], $setup['smtp']['from']);
                } else {
                    $from = $setup['smtp']['from'];
                }
            } else {
                // if we have the real sender name, let's use that one
                if (!empty($info['sender_name'])) {
                    $from = Mail_API::getFormattedName($info['sender_name'], $project_info['email']);
                } else {
                    $from = Mail_API::getFormattedName($project_info['name'], $project_info['email']);
                }
            }
        } else {
            $flag = '[' . $setup[$routing]['recipient_type_flag'] . '] ';
            $from_email = $setup[$routing]['address_prefix'] . $issue_id . "@" . $setup[$routing]['address_host'];
            if (!empty($info['sender_name'])) {
                // also check where we need to append/prepend a special string to the sender name
                if (substr($info['sender_name'], strlen($info['sender_name']) - 1) == '"') {
                    if (@$setup[$routing]['flag_location'] == 'before') {
                        $info['sender_name'] = '"' . $flag . substr($info['sender_name'], 1);
                    } else {
                        $info['sender_name'] = substr($info['sender_name'], 0, strlen($info['sender_name']) - 1) . ' ' . trim($flag) . '"';
                    }
                } else {
                    if (@$setup[$routing]['flag_location'] == 'before') {
                        $info['sender_name'] = '"' . $flag . $info['sender_name'] . '"';
                    } else {
                        $info['sender_name'] = '"' . $info['sender_name'] . ' ' . trim($flag) . '"';
                    }
                }
                $from = Mail_API::getFormattedName($info['sender_name'], $from_email);
            } else {
                // let's use the custom outgoing sender address
                $project_id = Issue::getProjectID($issue_id);
                $info = Project::getOutgoingSenderAddress($project_id);
                if (!empty($info['name'])) {
                    $from = Mail_API::getFormattedName($info['name'], $from_email);
                } else {
                    $from = $from_email;
                }
            }
        }
        return MIME_Helper::encodeAddress($from);
    }


    /**
     * Method used to check whether the current sender of the email is the
     * mailer daemon responsible for dealing with bounces.
     *
     * @access  public
     * @param   string $email The email address to check against
     * @return  boolean
     */
    function isBounceMessage($email)
    {
        if (strtolower(substr($email, 0, 14)) == 'mailer-daemon@') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Method used to forward the new email to the list of subscribers.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   object $structure The parsed email structure
     * @param   string $full_message The full email message
     * @param   boolean $internal_only Whether the email should only be redirected to internal users or not
     * @return  void
     */
    function notifyNewEmail($issue_id, $structure, $full_message, $internal_only = FALSE)
    {
        if (@get_magic_quotes_gpc() == 1) {
            $full_message = stripslashes($full_message);
            $structure->headers = Misc::array_map_deep($structure->headers, "stripslashes");
        }
        // XXX: need to rewrite the full email and change the To: header manually

        $sender = $structure->headers['from'];
        // automatically subscribe this sender to all email notifications for this issue
        $subscribed_emails = Notification::getSubscribedEmails($issue_id, 'emails');
        $subscribed_emails = array_map('strtolower', $subscribed_emails);
        $sender_email = strtolower(Mail_API::getEmailAddress($sender));
        if ((!Notification::isBounceMessage($sender_email)) && (!in_array($sender_email, $subscribed_emails))) {
            Notification::manualInsert($issue_id, $sender_email, array('emails'));
        }

        // get the subscribers
        $emails = array();
        $users = Notification::getUsersByIssue($issue_id, 'emails');
        for ($i = 0; $i < count($users); $i++) {
            if (empty($users[$i]["sub_usr_id"])) {
                if ($internal_only == false) {
                    $email = $users[$i]["sub_email"];
                }
            } else {
                // if we are only supposed to send email to internal users, check if the role is lower than standard user
                if (($internal_only == true) && (User::getRoleByUser($users[$i]["sub_usr_id"]) < User::getRoleID('standard user'))) {
                    continue;
                }
                $email = User::getFromHeader($users[$i]["sub_usr_id"]);
            }
            if (!empty($email)) {
                // don't send the email to the same person who sent it
                if (strtolower(Mail_API::getEmailAddress($email)) == $sender_email) {
                    continue;
                }
                $emails[] = $email;
            }
        }
        if (count($emails) == 0) {
            return;
        }
        $setup = Setup::load();
        // change the sender of the message to {prefix}{issue_id}@{host}
        //  - keep everything else in the message, except 'From:', 'Sender:', 'To:', 'Cc:'
        // make 'Joe Blow <joe@domain.com>' become 'Joe Blow [CSC] <eventum_59@domain.com>'
        $from = Notification::getFixedFromHeader($issue_id, $sender, 'issue');

        list($_headers, $body) = Mime_Helper::splitBodyHeader($full_message);
        $header_names = Mime_Helper::getHeaderNames($_headers);
        // we don't want to keep the (B)Cc list for an eventum-based email
        $ignore_headers = array(
            'To',
            'Cc',
            'Bcc'
        );
        $headers = array();
        // build the headers array required by the smtp library
        foreach ($structure->headers as $header_name => $value) {
            if (in_array($header_name, $ignore_headers)) {
                continue;
            } elseif ($header_name == 'from') {
                $headers['From'] = $from;
            } else {
                if (is_array($value)) {
                    $value = implode("; ", $value);
                }
                $headers[$header_names[$header_name]] = $value;
            }
        }
        @include_once(APP_PEAR_PATH . 'Mail.php');
        @include_once(APP_PEAR_PATH . 'Mail/mime.php');
        $params = Mail_API::getSMTPSettings();
        $mail =& Mail::factory('smtp', $params);
        foreach ($emails as $to) {
            $to = MIME_Helper::encodeAddress($to);
            // add a warning if we are sending an issue related email to
            // an user that has its permission level lower than a configurable setting
            $fixed_body = Mail_API::addWarningMessage($from, $to, $body);
            $headers['To'] = $to;
            $mime = new Mail_mime("\r\n");
            $hdrs = $mime->headers($headers);
            $res = $mail->send($to, $hdrs, $fixed_body);
            // save a copy of this email for debugging purposes
            Mail_API::saveEmailInformation($from, $hdrs, $fixed_body);
        }
    }


    /**
     * Method used to get the details of a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The issue details
     */
    function getIssueDetails($issue_id)
    {
        $stmt = "SELECT
                    iss_id,
                    iss_summary,
                    iss_description,
                    prj_title,
                    usr_full_name,
                    prc_title,
                    pre_title,
                    pri_title,
                    sta_title,
                    sta_color
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority
                 ON
                    iss_pri_id=pri_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_category
                 ON
                    iss_prc_id=prc_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_release
                 ON
                    iss_pre_id=pre_id
                 LEFT JOIN
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "status
                 ON
                    iss_sta_id=sta_id
                 WHERE
                    iss_id=$issue_id AND
                    iss_prj_id=prj_id AND
                    iss_usr_id=usr_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res['assigned_users'] = implode(", ", Issue::getAssignedUsers($issue_id));
            return $res;
        }
    }


    /**
     * Method used to get the details of a given note and issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $note_id The note ID
     * @return  array The details of the note / issue
     */
    function getNote($issue_id, $note_id)
    {
        $stmt = "SELECT
                    not_iss_id,
                    not_created_date,
                    not_note,
                    usr_full_name
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "note,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    not_id=$note_id AND
                    not_usr_id=usr_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $data = Notification::getIssueDetails($issue_id);
            $data["note"] = $res;
            return $data;
        }
    }


    /**
     * Method used to get the details of a given issue and its 
     * associated emails.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   array $sup_ids The list of associated emails
     * @return  array The issue / emails details
     */
    function getEmails($issue_id, $sup_ids)
    {
        $items = @implode(", ", $sup_ids);
        $stmt = "SELECT
                    sup_from,
                    sup_to,
                    sup_date,
                    sup_subject,
                    sup_has_attachment
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email
                 WHERE
                    sup_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if (count($res) == 0) {
                return "";
            } else {
                $data = Notification::getIssueDetails($issue_id);
                $data["emails"] = $res;
                return $data;
            }
        }
    }


    /**
     * Method used to get the details of a given issue and attachment.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $attachment_id The attachment ID
     * @return  array The issue / attachment details
     */
    function getAttachment($issue_id, $attachment_id)
    {
        $stmt = "SELECT
                    iat_id,
                    usr_full_name,
                    iat_created_date,
                    iat_description
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue_attachment,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user
                 WHERE
                    iat_usr_id=usr_id AND
                    iat_iss_id=$issue_id AND
                    iat_id=$attachment_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $res["files"] = Attachment::getFileList($res["iat_id"]);
            $data = Notification::getIssueDetails($issue_id);
            $data["attachment"] = $res;
            return $data;
        }
    }


    /**
     * Method used to get the list of users / emails that are 
     * subscribed for notifications of changes for a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $type The notification type
     * @return  array The list of users / emails
     */
    function getUsersByIssue($issue_id, $type)
    {
        if ($type == 'notes') {
            $stmt = "SELECT
                        DISTINCT sub_usr_id,
                        sub_email
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                     WHERE
                        sub_iss_id=$issue_id AND
                        sub_usr_id IS NOT NULL AND
                        sub_usr_id <> 0";
        } else {
            $stmt = "SELECT
                        DISTINCT sub_usr_id,
                        sub_email
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription,
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                     WHERE
                        sub_iss_id=$issue_id AND
                        sub_id=sbt_sub_id AND
                        sbt_type='$type'";
        }
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return array();
        } else {
            return $res;
        }
    }


    /**
     * Method used to send email notifications for a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $type The notification type
     * @param   array $ids The list of entries that were changed
     * @param   integer $internal_only Whether the notification should only be sent to internal users or not
     * @return  void
     */
    function notify($issue_id, $type, $ids = FALSE, $internal_only = FALSE)
    {
        $emails = array();
        $users = Notification::getUsersByIssue($issue_id, $type);
        $user_emails = Project::getUserEmailAssocList(Issue::getProjectID($issue_id), 'active', User::getRoleID('Viewer'));
        $user_emails = array_map('strtolower', $user_emails);
        for ($i = 0; $i < count($users); $i++) {
            if (empty($users[$i]["sub_usr_id"])) {
                if (($internal_only == false) || (in_array(strtolower($users[$i]["sub_email"]), array_values($user_emails)))) {
                    $emails[] = $users[$i]["sub_email"];
                }
            } else {
                // if we are only supposed to send email to internal users, check if the role is lower than standard user
                if (($internal_only == true) && (User::getRoleByUser($users[$i]["sub_usr_id"]) < User::getRoleID('standard user'))) {
                    continue;
                }
                $emails[] = User::getFromHeader($users[$i]["sub_usr_id"]);
            }
        }
        if (count($emails) > 0) {
            switch ($type) {
                case 'closed':
                    $data = Notification::getIssueDetails($issue_id);
                    $subject = 'Closed';
                    break;
                case 'updated':
                    $data = Notification::getIssueDetails($issue_id);
                    $subject = 'Updated';
                    break;
                case 'notes':
                    $data = Notification::getNote($issue_id, $ids);
                    $subject = 'Note';
                    break;
                case 'emails':
                    // this should not be used anymore
                    return false;
                    break;
                case 'files':
                    $data = Notification::getAttachment($issue_id, $ids);
                    $subject = 'File Attached';
                    break;
            }
            Notification::notifySubscribers($issue_id, $emails, $type, $data, $subject, $internal_only);
        }
    }


    /**
     * Method used to format and send the email notifications.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   array $emails The list of emails
     * @param   string $type The notification type
     * @param   array $data The issue details
     * @param   string $subject The subject of the email
     * @return  void
     */
    function notifySubscribers($issue_id, $emails, $type, $data, $subject, $internal_only)
    {
        // open text template
        $tpl = new Template_API;
        $tpl->setTemplate('notifications/' . $type . '.tpl.text');
        $tpl->bulkAssign(array(
            "app_base_url" => APP_BASE_URL,
            "app_title"    => Misc::getToolCaption(),
            "data"         => $data
        ));
        $text_message = $tpl->getTemplateContents();

        $setup = Setup::load();
        for ($i = 0; $i < count($emails); $i++) {
            // send email (use PEAR's classes)
            $mail = new Mail_API;
            $mail->setTextBody($text_message);
            if ($type == 'notes') {
                $from = Notification::getFixedFromHeader($issue_id, $setup['smtp']['from'], 'note');
            } else {
                $from = Notification::getFixedFromHeader($issue_id, $setup['smtp']['from'], 'issue');
            }
            $mail->send($from, $emails[$i], "[#$issue_id] $subject: " . $data['iss_summary'], TRUE);
        }
    }


    /**
     * Method used to send an email notification to users that want
     * to be alerted when new issues are created in the system.
     *
     * @access  public
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   array $exclude_list The list of user IDs that should not receive these emails
     * @return  void
     */
    function notifyNewIssue($prj_id, $issue_id, $exclude_list)
    {
        // get all users associated with this project
        $stmt = "SELECT
                    usr_id,
                    usr_full_name,
                    usr_email,
                    usr_preferences,
                    usr_role
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "user,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "project_user
                 WHERE
                    pru_prj_id=$prj_id AND
                    usr_id=pru_usr_id";
        if (count($exclude_list) > 0) {
            $stmt .= " AND
                    usr_id NOT IN (" . implode(", ", $exclude_list) . ")";
        }
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        $emails = array();
        for ($i = 0; $i < count($res); $i++) {
            $res[$i]['usr_preferences'] = @unserialize($res[$i]['usr_preferences']);
            $subscriber = Mail_API::getFormattedName($res[$i]['usr_full_name'], $res[$i]['usr_email']);
            if ((@$res[$i]['usr_preferences']['receive_new_emails']) && (!in_array($subscriber, $emails))) {
                $emails[] = $subscriber;
            }
        }
        $data = Issue::getDetails($issue_id);
        $data['custom_fields'] = Custom_Field::getListByIssue($data['iss_prj_id'], $issue_id);
        $subject = 'New Issue';
        Notification::notifySubscribers($issue_id, $emails, 'new_issue', $data, $subject, false);
    }


    /**
     * Method used to send an email notification when the account
     * details of an user is changed.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  void
     */
    function notifyUserAccount($usr_id)
    {
        $info = User::getDetails($usr_id);
        $info["role"] = User::getRole($info["usr_role"]);
        $info["projects"] = @implode(", ", array_values(Project::getAssocList($usr_id)));
        // open text template
        $tpl = new Template_API;
        $tpl->setTemplate('notifications/updated_account.tpl.text');
        $tpl->bulkAssign(array(
            "app_base_url" => APP_BASE_URL,
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));
        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_API;
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": User account information updated");
    }


    /**
     * Method used to send an email notification when the account
     * password of an user is changed.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   string $password The user' password
     * @return  void
     */
    function notifyUserPassword($usr_id, $password)
    {
        $info = User::getDetails($usr_id);
        $info["usr_password"] = $password;
        $info["role"] = User::getRole($info["usr_role"]);
        $info["projects"] = @implode(", ", array_values(Project::getAssocList($usr_id)));
        // open text template
        $tpl = new Template_API;
        $tpl->setTemplate('notifications/updated_password.tpl.text');
        $tpl->bulkAssign(array(
            "app_base_url" => APP_BASE_URL,
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));
        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_API;
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": User account password changed");
    }


    /**
     * Method used to send an email notification when a new user 
     * account is created.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @param   string $password The user' password
     * @return  void
     */
    function notifyNewUser($usr_id, $password)
    {
        $info = User::getDetails($usr_id);
        $info["usr_password"] = $password;
        $info["role"] = User::getRole($info["usr_role"]);
        // open text template
        $tpl = new Template_API;
        $tpl->setTemplate('notifications/new_user.tpl.text');
        $tpl->bulkAssign(array(
            "app_base_url" => APP_BASE_URL,
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));
        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_API;
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": New User information");
    }


    /**
     * Method used to send an email notification when an issue is
     * assigned to an user.
     *
     * @access  public
     * @param   array $users The list of users
     * @param   integer $issue_id The issue ID
     * @return  void
     */
    function notifyAssignedUsers($users, $issue_id)
    {
        $emails = array();
        for ($i = 0; $i < count($users); $i++) {
            $prefs = Prefs::get($users[$i]);
            if ($prefs["receive_assigned_emails"]) {
                $emails[] = User::getFromHeader($users[$i]);
            }
        }
        if (count($emails) == 0) {
            return false;
        }
        // get issue details
        $issue = Notification::getIssueDetails($issue_id);
        // open text template
        $tpl = new Template_API;
        $tpl->setTemplate('notifications/new.tpl.text');
        $tpl->bulkAssign(array(
            "app_base_url" => APP_BASE_URL,
            "app_title"    => Misc::getToolCaption(),
            "issue"        => $issue
        ));
        $text_message = $tpl->getTemplateContents();

        for ($i = 0; $i < count($emails); $i++) {
            // send email (use PEAR's classes)
            $mail = new Mail_API;
            $mail->setTextBody($text_message);
            $setup = $mail->getSMTPSettings();
            $mail->send($setup["from"], $emails[$i], APP_SHORT_NAME . ": New issue notification (ID: $issue_id)", TRUE);
        }
    }


    /**
     * Method used to send the account details of an user.
     *
     * @access  public
     * @param   integer $usr_id The user ID
     * @return  void
     */
    function notifyAccountDetails($usr_id)
    {
        $info = User::getDetails($usr_id);
        $info["role"] = User::getRole($info["usr_role"]);
        $info["projects"] = @implode(", ", array_values(Project::getAssocList($usr_id)));
        // open text template
        $tpl = new Template_API;
        $tpl->setTemplate('notifications/account_details.tpl.text');
        $tpl->bulkAssign(array(
            "app_base_url" => APP_BASE_URL,
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));
        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_API;
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": Your User Account Details");
    }


    /**
     * Method used to get the list of subscribers for a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  string The list of subscribers, separated by commas
     */
    function getSubscribers($issue_id)
    {
        $stmt = "SELECT
                    sub_usr_id,
                    sub_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            $temp = array();
            for ($i = 0; $i < count($res); $i++) {
                if ($res[$i]["sub_usr_id"] != 0) {
                    $info = User::getNameEmail($res[$i]["sub_usr_id"]);
                    $temp[] = $info["usr_full_name"];
                } else {
                    $temp[] = $res[$i]["sub_email"];
                }
            }
            return @implode(", ", $temp);
        }
    }


    /**
     * Method used to get the details of a given email notification
     * subscription.
     *
     * @access  public
     * @param   integer $sub_id The subcription ID
     * @return  array The details of the subscription
     */
    function getDetails($sub_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_id=$sub_id";
        $res = $GLOBALS["db_api"]->dbh->getRow($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            if ($res["sub_usr_id"] != 0) {
                $user_info = User::getNameEmail($res["sub_usr_id"]);
                $res["sub_email"] = $user_info["usr_email"];
            }
            return array_merge($res, Notification::getSubscribedActions($sub_id));
        }
    }


    /**
     * Method used to get the subscribed actions for a given 
     * subscription ID.
     *
     * @access  public
     * @param   integer $sub_id The subcription ID
     * @return  array The subscribed actions
     */
    function getSubscribedActions($sub_id)
    {
        $stmt = "SELECT
                    sbt_type,
                    1
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                 WHERE
                    sbt_sub_id=$sub_id";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the list of subscribers for a given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @return  array The list of subscribers
     */
    function getSubscriberListing($issue_id)
    {
        $stmt = "SELECT
                    sub_id,
                    sub_iss_id,
                    sub_usr_id,
                    sub_email
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_iss_id=$issue_id";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            for ($i = 0; $i < count($res); $i++) {
                if ($res[$i]["sub_usr_id"] != 0) {
                    $info = User::getNameEmail($res[$i]["sub_usr_id"]);
                    $res[$i]["sub_email"] = $info["usr_email"];
                }
                // need to get the list of subscribed actions now
                $actions = Notification::getSubscribedActions($res[$i]["sub_id"]);
                $res[$i]["actions"] = @implode(", ", array_keys($actions));
            }
            return $res;
        }
    }


    /**
     * Method used to remove all subscriptions associated with a given
     * set of issues.
     *
     * @access  public
     * @param   array $ids The list of issues
     * @return  boolean
     */
    function removeByIssues($ids)
    {
        $items = @implode(", ", $ids);
        $stmt = "SELECT
                    sub_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_iss_id IN ($items)";
        $res = $GLOBALS["db_api"]->dbh->getCol($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            Notification::remove($res);
            return true;
        }
    }


    /**
     * Method used to remove all rows associated with a set of
     * subscription IDs
     *
     * @access  public
     * @param   array $items The list of subscription IDs
     * @return  boolean
     */
    function remove($items)
    {
        $stmt = "SELECT
                    sub_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_id IN (" . implode(", ", $items) . ")";
        $issue_id = $GLOBALS["db_api"]->dbh->getOne($stmt);

        for ($i = 0; $i < count($items); $i++) {
            $sub_id = $items[$i];
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                     WHERE
                        sub_id=$sub_id";
            $GLOBALS["db_api"]->dbh->query($stmt);
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                     WHERE
                        sbt_sub_id=$sub_id";
            $GLOBALS["db_api"]->dbh->query($stmt);
            // need to save a history entry for this
            History::add($issue_id, 'Notification list entry removed by ' . User::getFullName(Auth::getUserID()));
        }
        Issue::markAsUpdated($issue_id);
        return true;
    }


    /**
     * Method used to subscribe the reporter of a new issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   array $actions The actions to subscribe to
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function subscribeReporter($issue_id, $usr_id, $actions)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 (
                    sub_iss_id,
                    sub_usr_id,
                    sub_created_date,
                    sub_level,
                    sub_email
                 ) VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    'issue',
                    ''
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $sub_id = $GLOBALS["db_api"]->get_last_insert_id();
            for ($i = 0; $i < count($actions); $i++) {
                $stmt = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                         (
                            sbt_sub_id,
                            sbt_type
                         ) VALUES (
                            $sub_id,
                            '" . $actions[$i] . "'
                         )";
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
            return 1;
        }
    }


    /**
     * Method used to get the full list of possible notification actions.
     *
     * @access  public
     * @return  array All of the possible notification actions
     */
    function getAllActions()
    {
        return array(
            'updated',
            'closed',
            'emails',
            'files'
        );
    }


    /**
     * Method used to subscribe an user to a set of actions in an issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @param   array $actions The list of actions to subscribe this user to
     * @param   boolean $add_history Whether to add a history entry about this change or not
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function subscribeUser($issue_id, $usr_id, $actions, $add_history = TRUE)
    {
        $stmt = "SELECT
                    COUNT(sub_id)
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_iss_id=$issue_id AND
                    sub_usr_id=$usr_id";
        $total = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if ($total > 0) {
            return -1;
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 (
                    sub_iss_id,
                    sub_usr_id,
                    sub_created_date,
                    sub_level,
                    sub_email
                 ) VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    'issue',
                    ''
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $sub_id = $GLOBALS["db_api"]->get_last_insert_id();
            for ($i = 0; $i < count($actions); $i++) {
                Notification::addType($sub_id, $actions[$i]);
            }
            // need to mark the issue as updated
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            if ($add_history) {
                History::add($issue_id, 'Notification list entry added by ' . User::getFullName(Auth::getUserID()));
            }
            return 1;
        }
    }


    /**
     * Method used to add a new subscriber manually, by using the
     * email notification interface.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $form_email The email address to subscribe
     * @param   array $actions The actions to subcribe to
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function manualInsert($issue_id, $form_email, $actions)
    {
        $form_email = strtolower($form_email);
        // first check if this is an actual user or just an email address
        $user_emails = User::getAssocEmailList();
        $user_emails = array_map('strtolower', $user_emails);
        if (in_array($form_email, array_keys($user_emails))) {
            return Notification::subscribeUser($issue_id, $user_emails[$form_email], $actions);
        }

        $usr_id = Auth::getUserID();
        $info = User::getNameEmail($usr_id);
        if (strtolower($info["usr_email"]) == $form_email) {
            return Notification::subscribeUser($issue_id, $usr_id, $actions);
        }
        $usr_id = 0;
        $email = Misc::runSlashes($form_email);
        // manual check to prevent duplicates
        if (!empty($email)) {
            $stmt = "SELECT
                        COUNT(sub_id)
                     FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                     WHERE
                        sub_iss_id=$issue_id AND
                        sub_email='$email'";
            $total = $GLOBALS["db_api"]->dbh->getOne($stmt);
            if ($total > 0) {
                return -1;
            }
        }
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 (
                    sub_iss_id,
                    sub_usr_id,
                    sub_created_date,
                    sub_level,
                    sub_email
                 ) VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    'issue',
                    '$email'
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $sub_id = $GLOBALS["db_api"]->get_last_insert_id();
            for ($i = 0; $i < count($actions); $i++) {
                Notification::addType($sub_id, $actions[$i]);
            }
            // need to mark the issue as updated
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, 'Notification list entry added by ' . User::getFullName(Auth::getUserID()));
            return 1;
        }
    }


    /**
     * Method used to subscribe the user to changes regarding the 
     * given issue.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   integer $usr_id The user ID
     * @return  boolean
     */
    function insert($issue_id, $usr_id)
    {
        // check for the user-level preference regarding notifications
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 (
                    sub_iss_id,
                    sub_usr_id,
                    sub_created_date,
                    sub_level,
                    sub_email
                 ) VALUES (
                    $issue_id,
                    $usr_id,
                    '" . Date_API::getCurrentDateGMT() . "',
                    'user',
                    ''
                 )";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return false;
        } else {
            $sub_id = $GLOBALS["db_api"]->get_last_insert_id();
            $prefs = Prefs::get($usr_id);
            if (@$prefs['updated']) {
                Notification::addType($sub_id, 'updated');
            }
            if (@$prefs['closed']) {
                Notification::addType($sub_id, 'closed');
            }
            if (@$prefs['emails']) {
                Notification::addType($sub_id, 'emails');
            }
            if (@$prefs['files']) {
                Notification::addType($sub_id, 'files');
            }
            return true;
        }
    }


    /**
     * Method used to add the subscription type to the given 
     * subscription.
     *
     * @access  public
     * @param   integer $sub_id The subscription ID
     * @param   string $type The subscription type
     * @return  void
     */
    function addType($sub_id, $type)
    {
        $stmt = "INSERT INTO
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                 (
                    sbt_sub_id,
                    sbt_type
                 ) VALUES (
                    $sub_id,
                    '$type'
                 )";
        $GLOBALS["db_api"]->dbh->query($stmt);
    }


    /**
     * Method used to update the details of a given subscription.
     *
     * @access  public
     * @param   integer $sub_id The subscription ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    function update($sub_id)
    {
        global $HTTP_POST_VARS;

        $stmt = "SELECT
                    sub_iss_id
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 WHERE
                    sub_id=$sub_id";
        $issue_id = $GLOBALS["db_api"]->dbh->getOne($stmt);

        // XXX: review this later on
        $usr_id = Auth::getUserID();
        if ($usr_id != 0) {
            $email = '';
        } else {
            $email = Misc::runSlashes($HTTP_POST_VARS["email"]);
        }
        // always set the type of notification to issue-level
        $stmt = "UPDATE
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription
                 SET
                    sub_level='issue',
                    sub_email='$email'
                 WHERE
                    sub_id=$sub_id";
        $res = $GLOBALS["db_api"]->dbh->query($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return -1;
        } else {
            $stmt = "DELETE FROM
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                     WHERE
                        sbt_sub_id=$sub_id";
            $GLOBALS["db_api"]->dbh->query($stmt);
            // now add them all again
            for ($i = 0; $i < count($HTTP_POST_VARS["actions"]); $i++) {
                $stmt = "INSERT INTO
                            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "subscription_type
                         (
                            sbt_sub_id,
                            sbt_type
                         ) VALUES (
                            $sub_id,
                            '" . $HTTP_POST_VARS["actions"][$i] . "'
                         )";
                $GLOBALS["db_api"]->dbh->query($stmt);
            }
            // need to mark the issue as updated
            Issue::markAsUpdated($issue_id);
            // need to save a history entry for this
            History::add($issue_id, 'Notification list entry updated by ' . User::getFullName(Auth::getUserID()));
            return 1;
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Notification Class');
}
?>