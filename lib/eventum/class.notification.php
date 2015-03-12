<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+
//

/**
 * Class to handle all of the business logic related to sending email
 * notifications on actions regarding the issues.
 */
class Notification
{
    /**
     * Method used to check whether a given email address is subsbribed to
     * email notifications for a given issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $email The email address
     * @return  boolean
     */
    public static function isSubscribedToEmails($issue_id, $email)
    {
        $email = strtolower(Mail_Helper::getEmailAddress($email));
        if ($email == '@') {
            // broken address, don't send the email...
            return true;
        }
        $subscribed_emails = self::getSubscribedEmails($issue_id, 'emails');
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
     * @param   integer $issue_id The issue ID
     * @param   string $type The notification type
     * @return  array The list of email addresses
     */
    public static function getSubscribedEmails($issue_id, $type = false)
    {
        $stmt = "SELECT
                    CASE WHEN usr_id <> 0 THEN usr_email ELSE sub_email END AS email
                 FROM
                    (
                    {{%subscription}}";
        $params = array();
        if ($type != false) {
            $stmt .= ",
                    {{%subscription_type}}";
        }
        $stmt .= "
                    )
                 LEFT JOIN
                    {{%user}}
                 ON
                    usr_id=sub_usr_id
                 WHERE";
        if ($type != false) {
            $stmt .= "
                    sbt_sub_id=sub_id AND
                    sbt_type=? AND";
            $params[] = $type;
        }
        $stmt .= "
                    sub_iss_id=?";
        $params[] = $issue_id;

        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params);
        } catch (DbException $e) {
            return "";
        }

        return $res;
    }

    /**
     * Method used to build a properly encoded email address that will be
     * used by the email/note routing system.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $sender The email address of the sender
     * @param   string $type Whether this is a note or email routing message
     * @return  string The properly encoded email address
     */
    public static function getFixedFromHeader($issue_id, $sender, $type)
    {
        $setup = Setup::load();
        if ($type == 'issue') {
            $routing = 'email_routing';
        } else {
            $routing = 'note_routing';
        }
        $project_id = Issue::getProjectID($issue_id);
        // if sender is empty, get project email address
        if (empty($sender)) {
            $project_info = Project::getOutgoingSenderAddress($project_id);
            $info = array(
                "sender_name"   =>  $project_info['name'],
                'email'         =>  $project_info['email']
            );

            // if no project name, use eventum wide sender name
            if (empty($info['sender_name'])) {
                $setup_sender_info = Mail_Helper::getAddressInfo($setup['smtp']['from']);
                $info['sender_name'] = $setup_sender_info['sender_name'];
            }
        } else {
            $info = Mail_Helper::getAddressInfo($sender);
        }
        // allow flags even without routing enabled
        if (!empty($setup[$routing]['recipient_type_flag'])) {
            $flag = '[' . $setup[$routing]['recipient_type_flag'] . '] ';
        } else {
            $flag = '';
        }
        if (@$setup[$routing]['status'] != 'enabled') {
            // let's use the custom outgoing sender address
            $project_info = Project::getOutgoingSenderAddress($project_id);
            if (empty($project_info['email'])) {
                /// no project email, use main email address
                $from_email = $setup['smtp']['from'];
            } else {
                $from_email = $project_info['email'];
            }
        } else {
            $from_email = $setup[$routing]['address_prefix'] . $issue_id . "@" . $setup[$routing]['address_host'];
        }
        if (empty($info['sender_name'])) {
            // no sender name, check if this email address belongs to a user and if so use that
            $usr_id = User::getUserIDByEmail($info['email']);
            if (!empty($usr_id)) {
                $info['sender_name'] = User::getFullName($usr_id);
            } else {
                // no name exists, use email address for name as well
                $info['sender_name'] = $info['email'];
            }
        }
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
        $from = Mail_Helper::getFormattedName($info['sender_name'], $from_email);

        return Mime_Helper::encodeAddress(trim($from));
    }

    /**
     * Method used to check whether the current sender of the email is the
     * mailer daemon responsible for dealing with bounces.
     *
     * @param   string $email The email address to check against
     * @return  boolean
     */
    public static function isBounceMessage($email)
    {
        if (strtolower(substr($email, 0, 14)) == 'mailer-daemon@') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method used to check whether the given sender email address is
     * the same as the issue routing email address.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $sender The address of the sender
     * @return  boolean
     */
    public static function isIssueRoutingSender($issue_id, $sender)
    {
        $check = self::getFixedFromHeader($issue_id, $sender, 'issue');
        $check_email = strtolower(Mail_Helper::getEmailAddress($check));
        $sender_email = strtolower(Mail_Helper::getEmailAddress($sender));
        if ($check_email == $sender_email) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method used to forward the new email to the list of subscribers.
     *
     * @param   integer $user_id The user ID of the person performing this action
     * @param   integer $issue_id The issue ID
     * @param   array $message An array containing the email
     * @param   boolean $internal_only Whether the email should only be redirected to internal users or not
     * @param   boolean $assignee_only Whether the email should only be sent to the assignee
     * @param   boolean $type The type of email this is
     * @param   integer $sup_id the ID of this email
     * @return  void
     */
    public static function notifyNewEmail($usr_id, $issue_id, $message, $internal_only = false, $assignee_only = false, $type = '', $sup_id = false)
    {
        $prj_id = Issue::getProjectID($issue_id);

        $full_message = $message['full_email'];
        $sender = $message['from'];
        $sender_email = strtolower(Mail_Helper::getEmailAddress($sender));
        $structure = Mime_Helper::decode($full_message, true);

        // get ID of whoever is sending this.
        $sender_usr_id = User::getUserIDByEmail($sender_email, true);
        if (empty($sender_usr_id)) {
            $sender_usr_id = false;
        }

        // automatically subscribe this sender to email notifications on this issue
        $subscribed_emails = self::getSubscribedEmails($issue_id, 'emails');
        $subscribed_emails = array_map('strtolower', $subscribed_emails);
        if ((!self::isIssueRoutingSender($issue_id, $sender)) &&
                (!self::isBounceMessage($sender_email)) &&
                (!in_array($sender_email, $subscribed_emails)) &&
                (Workflow::shouldAutoAddToNotificationList($prj_id))) {
            $actions = array('emails');
            self::subscribeEmail($usr_id, $issue_id, $sender_email, $actions);
        }

        // get the subscribers
        $emails = array();
        $users = self::getUsersByIssue($issue_id, 'emails');
        for ($i = 0; $i < count($users); $i++) {
            if (empty($users[$i]["sub_usr_id"])) {
                if ($internal_only == false) {
                    $email = $users[$i]["sub_email"];
                }
            } else {
                // if we are only supposed to send email to internal users, check if the role is lower than standard user
                if (($internal_only == true) && (User::getRoleByUser($users[$i]["sub_usr_id"], Issue::getProjectID($issue_id)) < User::getRoleID('standard user'))) {
                    continue;
                }
                // check if we are only supposed to send email to the assignees
                if (($internal_only == true) && ($assignee_only == true)) {
                    $assignee_usr_ids = Issue::getAssignedUserIDs($issue_id);
                    if (!in_array($users[$i]["sub_usr_id"], $assignee_usr_ids)) {
                        continue;
                    }
                }
                $email = User::getFromHeader($users[$i]["sub_usr_id"]);
            }

            if (!empty($email)) {
                // don't send the email to the same person who sent it unless they want it
                if ($sender_usr_id != false) {
                    $prefs = Prefs::get($sender_usr_id);
                    if (!isset($prefs['receive_copy_of_own_action'][$prj_id])) {
                        $prefs['receive_copy_of_own_action'][$prj_id] = 0;
                    }
                    if (($prefs['receive_copy_of_own_action'][$prj_id] == 0) &&
                            ((!empty($users[$i]["sub_usr_id"])) && ($sender_usr_id == $users[$i]["sub_usr_id"]) ||
                            (strtolower(Mail_Helper::getEmailAddress($email)) == $sender_email))) {
                        continue;
                    }
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
        // make 'Joe Blow <joe@example.com>' become 'Joe Blow [CSC] <eventum_59@example.com>'
        $from = self::getFixedFromHeader($issue_id, $sender, 'issue');

        list($_headers, $body) = Mime_Helper::splitBodyHeader($full_message);
        $header_names = Mime_Helper::getHeaderNames($_headers);

        $current_headers = Mail_Helper::stripHeaders($message['headers']);
        $headers = array();
        // build the headers array required by the smtp library
        foreach ($current_headers as $header_name => $value) {
            if ($header_name == 'from') {
                $headers['From'] = $from;
            } else {
                if (is_array($value)) {
                    $value = implode("; ", $value);
                }
                $headers[$header_names[$header_name]] = $value;
            }
        }

        $headers["Subject"] = Mail_Helper::formatSubject($issue_id, $headers['Subject']);

        if (empty($type)) {
            if (($sender_usr_id != false) && (User::getRoleByUser($sender_usr_id, Issue::getProjectID($issue_id)) == User::getRoleID("Customer"))) {
                $type = 'customer_email';
            } else {
                $type = 'other_email';
            }
        }

        foreach ($emails as $to) {
            $recipient_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($to));
            // add the warning message about replies being blocked or not
            $fixed_body = Mail_Helper::addWarningMessage($issue_id, $to, $body, $headers);
            $headers['To'] = Mime_Helper::encodeAddress($to);

            Mail_Queue::add($to, $headers, $fixed_body, 1, $issue_id, $type, $sender_usr_id, $sup_id);
        }
    }

    /**
     * Method used to get the details of a given note and issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $note_id The note ID
     * @return  array The details of the note / issue
     */
    public function getNote($issue_id, $note_id)
    {
        $stmt = "SELECT
                    not_usr_id,
                    not_iss_id,
                    not_created_date,
                    not_note,
                    not_title,
                    not_unknown_user,
                    not_full_message,
                    not_message_id,
                    not_parent_id,
                    not_is_blocked,
                    usr_full_name
                 FROM
                    {{%note}},
                    {{%user}}
                 WHERE
                    not_id=? AND
                    not_usr_id=usr_id";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($note_id));
        } catch (DbException $e) {
            return "";
        }

        // if there is an unknown user, use instead of full name
        if (!empty($res["not_unknown_user"])) {
            $res["usr_full_name"] = $res["not_unknown_user"];
        }

        if (!empty($res['not_parent_id'])) {
            $res['reference_msg_id'] = Note::getMessageIDbyID($res['not_parent_id']);
        } else {
            $res['reference_msg_id'] = false;
        }

        $data = Issue::getDetails($issue_id);
        $data["note"] = $res;

        return $data;
    }

    /**
     * Method used to get the details of a given issue and its
     * associated emails.
     *
     * @param   integer $issue_id The issue ID
     * @param   array $sup_ids The list of associated emails
     * @return  array The issue / emails details
     */
    public function getEmails($issue_id, $sup_ids)
    {
        $items = DB_Helper::buildList($sup_ids);
        $stmt = "SELECT
                    sup_from,
                    sup_to,
                    sup_date,
                    sup_subject,
                    sup_has_attachment
                 FROM
                    {{%support_email}}
                 WHERE
                    sup_id IN ($items)";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $sup_ids);
        } catch (DbException $e) {
            return "";
        }

        if (count($res) == 0) {
            return "";
        }
        $data = Issue::getDetails($issue_id);
        $data["emails"] = $res;

        return $data;
    }

    /**
     * Method used to get the details of a given issue and attachment.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $attachment_id The attachment ID
     * @return  array The issue / attachment details
     */
    public function getAttachment($issue_id, $attachment_id)
    {
        $stmt = "SELECT
                    iat_id,
                    usr_full_name,
                    iat_created_date,
                    iat_description,
                    iat_unknown_user
                 FROM
                    {{%issue_attachment}},
                    {{%user}}
                 WHERE
                    iat_usr_id=usr_id AND
                    iat_iss_id=? AND
                    iat_id=?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($issue_id, $attachment_id));
        } catch (DbException $e) {
            return "";
        }

        $res["files"] = Attachment::getFileList($res["iat_id"]);
        $data = Issue::getDetails($issue_id);
        $data["attachment"] = $res;

        return $data;
    }

    /**
     * Method used to get the list of users / emails that are
     * subscribed for notifications of changes for a given issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $type The notification type
     * @return  array The list of users / emails
     */
    public function getUsersByIssue($issue_id, $type)
    {
        if ($type == 'notes') {
            $stmt = "SELECT
                        DISTINCT sub_usr_id,
                        sub_email
                     FROM
                        {{%subscription}}
                     WHERE
                        sub_iss_id=? AND
                        sub_usr_id IS NOT NULL AND
                        sub_usr_id <> 0";
            $params = array(
                $issue_id,
            );
        } else {
            $stmt = "SELECT
                        DISTINCT sub_usr_id,
                        sub_email
                     FROM
                        {{%subscription}},
                        {{%subscription_type}}
                     WHERE
                        sub_iss_id=? AND
                        sub_id=sbt_sub_id AND
                        sbt_type=?";
            $params = array(
                $issue_id, $type
            );

        }
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to send a diff-style notification email to the issue
     * subscribers about updates to its attributes.
     *
     * @param   integer $issue_id The issue ID
     * @param   array $old The old issue details
     * @param   array $new The new issue details
     */
    public static function notifyIssueUpdated($issue_id, $old, $new)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $diffs = array();
        if (@$new["keep_assignments"] == "no") {
            if (empty($new['assignments'])) {
                $new['assignments'] = array();
            }
            $assign_diff = Misc::arrayDiff($old['assigned_users'], $new['assignments']);
            if (count($assign_diff) > 0) {
                $diffs[] = '-' . ev_gettext('Assignment List') . ': ' . $old['assignments'];
                @$diffs[] = '+' . ev_gettext('Assignment List') . ': ' . implode(', ', User::getFullName($new['assignments']));
            }
        }
        if (isset($new['expected_resolution_date']) && @$old['iss_expected_resolution_date'] != $new['expected_resolution_date']) {
            $diffs[] = '-' . ev_gettext('Expected Resolution Date') . ': ' . $old['iss_expected_resolution_date'];
            $diffs[] = '+' . ev_gettext('Expected Resolution Date') . ': ' . $new['expected_resolution_date'];
        }
        if (isset($new["category"]) && $old["iss_prc_id"] != $new["category"]) {
            $diffs[] = '-' . ev_gettext('Category') . ': ' . Category::getTitle($old["iss_prc_id"]);
            $diffs[] = '+' . ev_gettext('Category') . ': ' . Category::getTitle($new["category"]);
        }
        if (isset($new["release"]) && ($old["iss_pre_id"] != $new["release"])) {
            $diffs[] = '-' . ev_gettext('Release') . ': ' . Release::getTitle($old["iss_pre_id"]);
            $diffs[] = '+' . ev_gettext('Release') . ': ' . Release::getTitle($new["release"]);
        }
        if (isset($new["priority"]) && $old["iss_pri_id"] != $new["priority"]) {
            $diffs[] = '-' . ev_gettext('Priority') . ': ' . Priority::getTitle($old["iss_pri_id"]);
            $diffs[] = '+' . ev_gettext('Priority') . ': ' . Priority::getTitle($new["priority"]);
        }
        if (isset($new["severity"]) && $old["iss_sev_id"] != $new["severity"]) {
            $diffs[] = '-' . ev_gettext('Severity') . ': ' . Severity::getTitle($old["iss_sev_id"]);
            $diffs[] = '+' . ev_gettext('Severity') . ': ' . Severity::getTitle($new["severity"]);
        }
        if (isset($new["status"]) && $old["iss_sta_id"] != $new["status"]) {
            $diffs[] = '-' . ev_gettext('Status') . ': ' . Status::getStatusTitle($old["iss_sta_id"]);
            $diffs[] = '+' . ev_gettext('Status') . ': ' . Status::getStatusTitle($new["status"]);
        }
        if (isset($new["resolution"]) && $old["iss_res_id"] != $new["resolution"]) {
            $diffs[] = '-' . ev_gettext('Resolution') . ': ' . Resolution::getTitle($old["iss_res_id"]);
            $diffs[] = '+' . ev_gettext('Resolution') . ': ' . Resolution::getTitle($new["resolution"]);
        }
        if (isset($new["estimated_dev_time"]) && $old["iss_dev_time"] != $new["estimated_dev_time"]) {
            $diffs[] = '-' . ev_gettext('Estimated Dev. Time') . ': ' . Misc::getFormattedTime($old["iss_dev_time"]*60);
            $diffs[] = '+' . ev_gettext('Estimated Dev. Time') . ': ' . Misc::getFormattedTime($new["estimated_dev_time"]*60);
        }
        if (isset($new["summary"]) && $old["iss_summary"] != $new["summary"]) {
            $diffs[] = '-' . ev_gettext('Summary') . ': ' . $old['iss_summary'];
            $diffs[] = '+' . ev_gettext('Summary') . ': ' . $new['summary'];
        }
        if (isset($new["percent_complete"]) && $old["iss_original_percent_complete"] != $new["percent_complete"]) {
            $diffs[] = '-' . ev_gettext('Percent complete') . ': ' . $old['iss_original_percent_complete'];
            $diffs[] = '+' . ev_gettext('Percent complete') . ': ' . $new['percent_complete'];
        }
        if (isset($new["description"]) && $old["iss_description"] != $new["description"]) {
            $old['iss_description'] = explode("\n", $old['iss_original_description']);
            $new['description'] = explode("\n", $new['description']);
            $diff = new Text_Diff($old["iss_description"], $new["description"]);
            $renderer = new Text_Diff_Renderer_unified();
            $desc_diff = explode("\n", trim($renderer->render($diff)));
            $diffs[] = 'Description:';
            for ($i = 0; $i < count($desc_diff); $i++) {
                $diffs[] = $desc_diff[$i];
            }
        }

        $emails = array();
        $users = self::getUsersByIssue($issue_id, 'updated');
        $user_emails = Project::getUserEmailAssocList(Issue::getProjectID($issue_id), 'active', User::getRoleID('Customer'));
        $user_emails = array_map('strtolower', $user_emails);
        for ($i = 0; $i < count($users); $i++) {
            if (empty($users[$i]["sub_usr_id"])) {
                $email = $users[$i]["sub_email"];
            } else {
                $prefs = Prefs::get($users[$i]['sub_usr_id']);
                if ((Auth::getUserID() == $users[$i]["sub_usr_id"]) &&
                        ((empty($prefs['receive_copy_of_own_action'][$prj_id])) ||
                            ($prefs['receive_copy_of_own_action'][$prj_id] == false))) {
                    continue;
                }
                $email = User::getFromHeader($users[$i]["sub_usr_id"]);
            }
            // now add it to the list of emails
            if ((!empty($email)) && (!in_array($email, $emails))) {
                $emails[] = $email;
            }
        }
        // get additional email addresses to notify
        $emails = array_merge($emails, Workflow::getAdditionalEmailAddresses($prj_id, $issue_id, 'issue_updated', array('old' => $old, 'new' => $new)));

        $data = Issue::getDetails($issue_id);
        $data['diffs'] = implode("\n", $diffs);
        $data['updated_by'] = User::getFullName(Auth::getUserID());
        self::notifySubscribers($issue_id, $emails, 'updated', $data, ev_gettext('Updated'), false);
    }

    /**
     * Method used to send a diff-style notification email to the issue
     * subscribers about status changes
     *
     * @param   integer $issue_id The issue ID
     * @param   array $old_status The old issue status
     * @param   array $new_status The new issue status
     */
    public static function notifyStatusChange($issue_id, $old_status, $new_status)
    {
        $diffs = array();
        if ($old_status != $new_status) {
            $diffs[] = '-Status: ' . Status::getStatusTitle($old_status);
            $diffs[] = '+Status: ' . Status::getStatusTitle($new_status);
        }

        if (count($diffs) < 1) {
            return false;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $emails = array();
        $users = self::getUsersByIssue($issue_id, 'updated');
        $user_emails = Project::getUserEmailAssocList(Issue::getProjectID($issue_id), 'active', User::getRoleID('Customer'));
        $user_emails = array_map('strtolower', $user_emails);
        // FIXME: unused $user_emails
        for ($i = 0; $i < count($users); $i++) {
            if (empty($users[$i]["sub_usr_id"])) {
                $email = $users[$i]["sub_email"];
            } else {
                $prefs = Prefs::get($users[$i]['sub_usr_id']);
                if ((Auth::getUserID() == $users[$i]["sub_usr_id"]) &&
                        ((empty($prefs['receive_copy_of_own_action'][$prj_id])) ||
                            ($prefs['receive_copy_of_own_action'][$prj_id] == false))) {
                    continue;
                }
                $email = User::getFromHeader($users[$i]["sub_usr_id"]);
            }
            // now add it to the list of emails
            if ((!empty($email)) && (!in_array($email, $emails))) {
                $emails[] = $email;
            }
        }
        $data = Issue::getDetails($issue_id);
        $data['diffs'] = implode("\n", $diffs);
        $data['updated_by'] = User::getFullName(Auth::getUserID());
        self::notifySubscribers($issue_id, $emails, 'updated', $data, 'Status Change', false);
    }

    /**
     * Method used to send email notifications for a given issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $type The notification type
     * @param   array $ids The list of entries that were changed
     * @param   integer $internal_only Whether the notification should only be sent to internal users or not
     */
    public static function notify($issue_id, $type, $ids = false, $internal_only = false, $extra_recipients = false)
    {
        $prj_id = Issue::getProjectID($issue_id);
        if ($extra_recipients) {
            $extra = array();
            for ($i = 0; $i < count($extra_recipients); $i++) {
                $extra[] = array(
                    'sub_usr_id' => $extra_recipients[$i],
                    'sub_email'  => ''
                );
            }
        }
        $emails = array();
        $users = self::getUsersByIssue($issue_id, $type);
        if (($extra_recipients) && (count($extra) > 0)) {
            $users = array_merge($users, $extra);
        }
        $user_emails = Project::getUserEmailAssocList(Issue::getProjectID($issue_id), 'active', User::getRoleID('Customer'));
        $user_emails = array_map('strtolower', $user_emails);
        for ($i = 0; $i < count($users); $i++) {
            if (empty($users[$i]["sub_usr_id"])) {
                if (($internal_only == false) || (in_array(strtolower($users[$i]["sub_email"]), array_values($user_emails)))) {
                    $email = $users[$i]["sub_email"];
                }
            } else {
                $prefs = Prefs::get($users[$i]['sub_usr_id']);
                if ((Auth::getUserID() == $users[$i]["sub_usr_id"]) &&
                        ((empty($prefs['receive_copy_of_own_action'][$prj_id])) ||
                            ($prefs['receive_copy_of_own_action'][$prj_id] == false))) {
                    continue;
                }
                // if we are only supposed to send email to internal users, check if the role is lower than standard user
                if (($internal_only == true) && (User::getRoleByUser($users[$i]["sub_usr_id"], Issue::getProjectID($issue_id)) < User::getRoleID('standard user'))) {
                    continue;
                }
                if ($type == 'notes' && User::isPartner($users[$i]["sub_usr_id"]) &&
                        !Partner::canUserAccessIssueSection($users[$i]["sub_usr_id"], 'notes')) {
                    continue;
                }
                $email = User::getFromHeader($users[$i]["sub_usr_id"]);
            }
            // now add it to the list of emails
            if ((!empty($email)) && (!in_array($email, $emails))) {
                $emails[] = $email;
            }
        }
        // prevent the primary customer contact from receiving two emails about the issue being closed
        if ($type == 'closed') {
            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                $stmt = "SELECT
                            iss_customer_contact_id
                         FROM
                            {{%issue}}
                         WHERE
                            iss_id=?";
                $customer_contact_id = DB_Helper::getInstance()->getOne($stmt, array($issue_id));
                if (!empty($customer_contact_id)) {
                    try {
                        $contact = $crm->getContact($customer_contact_id);
                        $contact_email = $contact->getEmail();
                    } catch (CRMException $e) {
                        $contact_email = '';
                    }
                    for ($i = 0; $i < count($emails); $i++) {
                        $email = Mail_Helper::getEmailAddress($emails[$i]);
                        if ($email == $contact_email) {
                            unset($emails[$i]);
                            $emails = array_values($emails);
                            break;
                        }
                    }
                }
            }
        }
        if (count($emails) > 0) {
            $headers = false;
            switch ($type) {
                case 'closed':
                    $data = Issue::getDetails($issue_id);
                    $data["closer_name"] = User::getFullName(History::getIssueCloser($issue_id));
                    $subject = ev_gettext('Closed');

                    if ($ids != false) {
                        $data['reason'] = Support::getEmail($ids);
                    }
                    break;
                case 'updated':
                    // this should not be used anymore
                    return false;
                    break;
                case 'notes':
                    $data = self::getNote($issue_id, $ids);
                    $headers = array(
                        'Message-ID'    =>  $data['note']['not_message_id'],
                    );
                    if (@$data['note']['reference_msg_id'] != false) {
                        $headers['In-Reply-To'] = $data['note']['reference_msg_id'];
                    } else {
                        $headers['In-Reply-To'] = Issue::getRootMessageID($issue_id);
                    }
                    $headers['References'] = Mail_Helper::fold(join(' ', Mail_Helper::getReferences($issue_id, @$data['note']['reference_msg_id'], 'note')));
                    $subject = 'Note';
                    break;
                case 'emails':
                    // this should not be used anymore
                    return false;
                    break;
                case 'files':
                    $data = self::getAttachment($issue_id, $ids);
                    $subject = 'File Attached';
                    break;
            }
            self::notifySubscribers($issue_id, $emails, $type, $data, $subject, $internal_only, $ids, $headers);
        }
    }

    /**
     * Method used to get list of addresses that were email sent to.
     *
     * @param   integer $issue_id The issue ID
     * @return  array   list of addresse
     */
    public static function getLastNotifiedAddresses($issue_id = null)
    {
        global $_EVENTUM_LAST_NOTIFIED_LIST;

        if ($_EVENTUM_LAST_NOTIFIED_LIST === null) {
            return null;
        }

        if ($issue_id === null) {
            // return all addresses in flat view
            $ret = array_values($_EVENTUM_LAST_NOTIFIED_LIST);
        } else {
            // return address list for specific issue_id only.
            $ret = $_EVENTUM_LAST_NOTIFIED_LIST[$issue_id];
        }

        return array_unique($ret);
    }

    /**
     * Method used to format and send the email notifications.
     *
     * @param   integer $issue_id The issue ID
     * @param   array $emails The list of emails
     * @param   string $type The notification type
     * @param   array $data The issue details
     * @param   string $subject The subject of the email
     * @param   integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @param   array $headers Any extra headers that need to be added to this email (Default false)
     * @return  void
     */
    public function notifySubscribers($issue_id, $emails, $type, $data, $subject, $internal_only, $type_id = false, $headers = false)
    {
        global $_EVENTUM_LAST_NOTIFIED_LIST;

        $issue_id = (int) $issue_id;

        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/' . $type . '.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"    => Misc::getToolCaption(),
            "data"         => $data,
            "current_user" => User::getFullName(Auth::getUserID()),
        ));

        // FIXME: unused $setup
        $setup = Setup::load();
        // type of notification is sent out: email, note, blocked_email
        $notify_type = $type;
        $sender_usr_id = false;
        $threading_headers = Mail_Helper::getBaseThreadingHeaders($issue_id);
        $emails = array_unique($emails);
        for ($i = 0; $i < count($emails); $i++) {

            $can_access = true;
            $email_address = Mail_Helper::getEmailAddress($emails[$i]);
            $recipient_usr_id = User::getUserIDByEmail($email_address);
            if (!empty($recipient_usr_id)) {
                if (!Issue::canAccess($issue_id, $recipient_usr_id)) {
                    $can_access = false;
                }
                $tpl->assign("recipient_role", User::getRoleByUser($recipient_usr_id, Issue::getProjectID($issue_id)));
                if (isset($data['custom_fields'])) {
                    $data['custom_fields'] = Custom_Field::getListByIssue($data['iss_prj_id'], $issue_id, $recipient_usr_id);
                }
                $is_assigned = Issue::isAssignedToUser($issue_id, $recipient_usr_id);
            } else {
                $tpl->assign("recipient_role", 0);
                unset($data['custom_fields']);

                $is_assigned = false;
            }
            $tpl->assign("data", $data);
            $tpl->assign("is_assigned", $is_assigned);

            if ($can_access != true) {
                continue;
            }

            if (!Workflow::shouldEmailAddress(Issue::getProjectID($issue_id), $email_address, $issue_id, $type)) {
                continue;
            }

            // change the current locale
            if (!empty($recipient_usr_id)) {
                Language::set(User::getLang($recipient_usr_id));
            } else {
                Language::set(APP_DEFAULT_LOCALE);
            }

            // send email (use PEAR's classes)
            $mail = new Mail_Helper();
            $mail->setTextBody($tpl->getTemplateContents());
            if ($headers != false) {
                $mail->setHeaders($headers);
            }
            if (($headers == false) || (($headers != false) && ((empty($headers['Message-ID'])) && (empty($headers['In-Reply-To'])) && (empty($headers['References']))))) {
                $mail->setHeaders($threading_headers);
            }

            if ($type == 'notes') {
                // special handling of blocked messages
                if ($data['note']['not_is_blocked'] == 1) {
                    $subject = ev_gettext('BLOCKED');
                    $notify_type = 'blocked_email';
                }
                if (!empty($data["note"]["not_unknown_user"])) {
                    $sender = $data["note"]["not_unknown_user"];
                } else {
                    $sender = User::getFromHeader($data["note"]["not_usr_id"]);
                }
                $sender_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($sender));
                if (empty($sender_usr_id)) {
                    $sender_usr_id = false;
                }

                // show the title of the note, not the issue summary
                $extra_subject = $data['note']['not_title'];
                // don't add the "[#3333] Note: " prefix to messages that already have that in the subject line
                if (strstr($extra_subject, "[#$issue_id] $subject: ")) {
                    $pos = strpos($extra_subject, "[#$issue_id] $subject: ");
                    $full_subject = substr($extra_subject, $pos);
                } else {
                    $full_subject = "[#$issue_id] $subject: $extra_subject";
                }
            } elseif (($type == 'new_issue') && ($is_assigned)) {
                $full_subject = "[#$issue_id] New Issue Assigned: " . $data['iss_summary'];
            } else {
                $extra_subject = $data['iss_summary'];
                $full_subject = "[#$issue_id] $subject: $extra_subject";
            }

            if ($notify_type == 'notes' && $sender) {
                $from = self::getFixedFromHeader($issue_id, $sender, 'note');
            } else {
                $from = self::getFixedFromHeader($issue_id, '', 'issue');
            }
            $mail->send($from, $emails[$i], $full_subject, true, $issue_id, $notify_type, $sender_usr_id, $type_id);

            $_EVENTUM_LAST_NOTIFIED_LIST[$issue_id][] = $emails[$i];
        }

        // restore correct language
        Language::restore();
    }

    /**
     * Method used to send an email notification to users that want
     * to be alerted when new issues are created in the system.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   array   $exclude_list The list of users NOT to notify.
     * @return  void
     */
    public static function notifyNewIssue($prj_id, $issue_id, $exclude_list = array())
    {
        // get all users associated with this project
        $stmt = "SELECT
                    usr_id,
                    usr_full_name,
                    usr_email,
                    pru_role,
                    usr_customer_id,
                    usr_customer_contact_id
                 FROM
                    {{%user}},
                    {{%project_user}}
                 WHERE
                    pru_prj_id=? AND
                    usr_id=pru_usr_id AND
                    usr_status = 'active' AND
                    pru_role > ?";
        $params = array(
            $prj_id, User::getRoleID("Customer")
        );

        if (count($exclude_list) > 0) {
            $stmt .= " AND
                    usr_id NOT IN (" . DB_Helper::buildList($exclude_list) . ")";
            $params = array_merge($params, $exclude_list);
        }

        $res = DB_Helper::getInstance()->getAll($stmt, $params);
        $emails = array();
        for ($i = 0; $i < count($res); $i++) {
            $subscriber = Mail_Helper::getFormattedName($res[$i]['usr_full_name'], $res[$i]['usr_email']);
            // don't send these emails to customers
            if (($res[$i]['pru_role'] == User::getRoleID('Customer')) || (!empty($res[$i]['usr_customer_id']))
                    || (!empty($res[$i]['usr_customer_contact_id']))) {
                continue;
            }
            $prefs = Prefs::get($res[$i]['usr_id']);
            if ((!empty($prefs['receive_new_issue_email'][$prj_id]))
                    && (@$prefs['receive_new_issue_email'][$prj_id])
                    && (!in_array($subscriber, $emails))) {
                $emails[] = $subscriber;
            }
        }

        // get assignees
        $stmt = "SELECT
                    usr_id,
                    usr_full_name,
                    usr_email
                 FROM
                    {{%user}},
                    {{%issue_user}}
                 WHERE
                    isu_iss_id=? AND
                    usr_id=isu_usr_id AND
                    usr_status = 'active'";
        $res = DB_Helper::getInstance()->getAll($stmt, array($issue_id));
        for ($i = 0; $i < count($res); $i++) {
            $subscriber = Mail_Helper::getFormattedName($res[$i]['usr_full_name'], $res[$i]['usr_email']);

            $prefs = Prefs::get($res[$i]['usr_id']);
            if ((!empty($prefs['receive_assigned_email'][$prj_id])) &&
            (@$prefs['receive_assigned_email'][$prj_id]) && (!in_array($subscriber, $emails))) {
                $emails[] = $subscriber;
            }
        }

        // get any email addresses from products
        $products = Product::getProductsByIssue($issue_id);
        if (count($products) > 0) {
            foreach ($products as $product) {
                $emails[] = $product['pro_email'];
            }
        }

        // get any additional emails
        $emails = array_merge($emails, Workflow::getAdditionalEmailAddresses($prj_id, $issue_id, 'new_issue'));

        $data = Issue::getDetails($issue_id, true);
        $data['attachments'] = Attachment::getList($issue_id);

        // notify new issue to irc channel
        $irc_notice = "New Issue #$issue_id (";
        $quarantine = Issue::getQuarantineInfo($issue_id);
        if (!empty($quarantine)) {
            $irc_notice .= "Quarantined; ";
        }
        $irc_notice .= "Priority: " . $data['pri_title'];
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $irc_notice .= "; Assignment: " . implode(', ', $assignment);
        }
        if (!empty($data['iss_grp_id'])) {
            $irc_notice .= "; Group: " . Group::getName($data['iss_grp_id']);
        }
        $irc_notice .= "), ";
        if (@isset($data['customer'])) {
            $irc_notice .= $data['customer']['name'] . ", ";
        }
        $irc_notice .= $data['iss_summary'];
        self::notifyIRC($prj_id, $irc_notice, $issue_id, false, false, 'new_issue');
        $data['custom_fields'] = array();// empty place holder so notifySubscribers will fill it in with appropriate data for the user
        $subject = ev_gettext('New Issue');
        // generate new Message-ID
        $message_id = Mail_Helper::generateMessageID();
        $headers = array(
            "Message-ID" => $message_id
        );

        self::notifySubscribers($issue_id, $emails, 'new_issue', $data, $subject, false, false, $headers);
    }

    /**
     * Method used to send an email notification to the sender of an
     * email message that was automatically converted into an issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   string $sender The sender of the email message (and the recipient of this notification)
     * @param   string $date The arrival date of the email message
     * @param   string $subject The subject line of the email message
     * @param bool|string $additional_recipient The user who should receive this email who is not the sender of the original email.
     * @return  void
     */
    public static function notifyAutoCreatedIssue($prj_id, $issue_id, $sender, $date, $subject, $additional_recipient = false)
    {
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            $crm->notifyAutoCreatedIssue($issue_id, $sender, $date, $subject);
            $sent = true;
        } else {
            $sent = false;
        }

        if ($sent === false) {
            if ($additional_recipient != false) {
                $recipient = $additional_recipient;
                $is_message_sender = false;
            } else {
                $recipient = $sender;
                $is_message_sender = true;
            }
            $recipient_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($recipient));

            if (!Workflow::shouldEmailAddress($prj_id, Mail_Helper::getEmailAddress($recipient), $issue_id, 'auto_created')) {
                return;
            }
            $data = Issue::getDetails($issue_id);

            // open text template
            $tpl = new Template_Helper();
            $tpl->setTemplate('notifications/new_auto_created_issue.tpl.text');
            $tpl->bulkAssign(array(
                "app_title"   => Misc::getToolCaption(),
                "data"        => $data,
                "sender_name" => Mail_Helper::getName($sender),
                'recipient_name'    => Mail_Helper::getName($recipient),
                'is_message_sender' =>  $is_message_sender
            ));

            // figure out if sender has a real account or not
            $sender_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($sender), true);
            if ((!empty($sender_usr_id)) && (Issue::canAccess($issue_id, $sender_usr_id))) {
                $can_access = 1;
            } else {
                $can_access = 0;
            }

            $tpl->assign(array(
                'sender_can_access' =>  $can_access,
                'email' => array(
                    'date'    => $date,
                    'from'    => Mime_Helper::fixEncoding($sender),
                    'subject' => $subject
                )
            ));

            // change the current locale
            if (!empty($recipient_usr_id)) {
                Language::set(User::getLang($recipient_usr_id));
            } else {
                Language::set(APP_DEFAULT_LOCALE);
            }

            $text_message = $tpl->getTemplateContents();

            // send email (use PEAR's classes)
            $mail = new Mail_Helper();
            $mail->setTextBody($text_message);
            $mail->setHeaders(Mail_Helper::getBaseThreadingHeaders($issue_id));
            $setup = $mail->getSMTPSettings();
            $from = self::getFixedFromHeader($issue_id, $setup["from"], 'issue');
            $recipient = Mime_Helper::fixEncoding($recipient);
            $mail->send($from, $recipient, "[#$issue_id] Issue Created: " . $data['iss_summary'], 0, $issue_id, 'auto_created_issue');

            Language::restore();
        }
    }

    /**
     * Method used to send an email notification to the sender of a
     * set of email messages that were manually converted into an
     * issue.
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The issue ID
     * @param   array $sup_ids The email IDs
     * @param bool|int $customer_id The customer ID
     * @return  array The list of recipient emails
     */
    public static function notifyEmailConvertedIntoIssue($prj_id, $issue_id, $sup_ids, $customer_id = false)
    {
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);

            return $crm->notifyEmailConvertedIntoIssue($issue_id, $sup_ids, $customer_id);
        } else {
            // build the list of recipients
            $recipients = array();
            $recipient_emails = array();
            for ($i = 0; $i < count($sup_ids); $i++) {
                $senders = Support::getSender(array($sup_ids[$i]));
                if (count($senders) > 0) {
                    $sender_email = Mail_Helper::getEmailAddress($senders[0]);
                    $recipients[$sup_ids[$i]] = $senders[0];
                    $recipient_emails[] = $sender_email;
                }
            }
            if (count($recipients) == 0) {
                return false;
            }

            $data = Issue::getDetails($issue_id);
            foreach ($recipients as $sup_id => $recipient) {

                $recipient_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($recipient));

                // open text template
                $tpl = new Template_Helper();
                $tpl->setTemplate('notifications/new_auto_created_issue.tpl.text');
                $tpl->bulkAssign(array(
                    "data"        => $data,
                    "sender_name" => Mail_Helper::getName($recipient),
                    "app_title"   => Misc::getToolCaption(),
                    'recipient_name'    => Mail_Helper::getName($recipient),
                ));
                $email_details = Support::getEmailDetails(Email_Account::getAccountByEmail($sup_id), $sup_id);
                $tpl->assign(array(
                    'email' => array(
                        'date'    => $email_details['sup_date'],
                        'from'    => $email_details['sup_from'],
                        'subject' => $email_details['sup_subject']
                    )
                ));

                // change the current locale
                if (!empty($recipient_usr_id)) {
                    Language::set(User::getLang($recipient_usr_id));
                } else {
                    Language::set(APP_DEFAULT_LOCALE);
                }

                $text_message = $tpl->getTemplateContents();

                // send email (use PEAR's classes)
                $mail = new Mail_Helper();
                $mail->setTextBody($text_message);
                $setup = $mail->getSMTPSettings();
                $from = self::getFixedFromHeader($issue_id, $setup["from"], 'issue');
                $mail->setHeaders(Mail_Helper::getBaseThreadingHeaders($issue_id));
                $mail->send($from, $recipient, "[#$issue_id] Issue Created: " . $data['iss_summary'], 1, $issue_id, 'email_converted_to_issue');
            }
            Language::restore();

            return $recipient_emails;
        }
    }

    /**
     * Method used to send an IRC notification about a blocked email that was
     * saved into an internal note.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $from The sender of the blocked email message
     */
    public function notifyIRCBlockedMessage($issue_id, $from)
    {
        $notice = "Issue #$issue_id updated (";
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $notice .= "Assignment: " . implode(', ', $assignment) . "; ";
        }
        $notice .= "BLOCKED email from '$from')";
        self::notifyIRC(Issue::getProjectID($issue_id), $notice, $issue_id);
    }

    /**
     * Method used to save the IRC notification message in the queue table.
     *
     * @param   integer $project_id The ID of the project.
     * @param   string  $notice The notification summary that should be displayed on IRC
     * @param   bool|integer $issue_id The issue ID
     * @param   bool|integer $usr_id The ID of the user to notify
     * @param   bool|string $category The category of this notification
     * @param   bool|string $type The type of notification (new_issue, etc)
     * @return  bool
     */
    public static function notifyIRC($project_id, $notice, $issue_id = false, $usr_id = false, $category = false,
                                     $type=false)
    {
        // don't save any irc notification if this feature is disabled
        $setup = Setup::load();
        if (@$setup['irc_notification'] != 'enabled') {
            return false;
        }

        $notice = Workflow::formatIRCMessage($project_id, $notice, $issue_id, $usr_id, $category, $type);

        if ($notice === false) {
            return;
        }

        $params = array(
            'ino_prj_id' => $project_id,
            'ino_created_date' => Date_Helper::getCurrentDateGMT(),
            'ino_status' => 'pending',
            'ino_message' => $notice,
            'ino_category' => $category,
        );

        if ($issue_id) {
            $params['ino_iss_id'] = $issue_id;
        }
        if ($usr_id) {
            $params['ino_target_usr_id']= $usr_id;
        }

        $stmt = "INSERT INTO {{%irc_notice}} SET ". DB_Helper::buildSet($params);
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to send an email notification when the account
     * details of an user is changed.
     *
     * @param   integer $usr_id The user ID
     * @return  void
     */
    public static function notifyUserAccount($usr_id)
    {
        $info = User::getDetails($usr_id);
        $info["projects"] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/updated_account.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));

        // change the current locale
        Language::set(User::getLang($usr_id));

        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_Helper();
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": " . ev_gettext("User account information updated"));

        Language::restore();
    }

    /**
     * Method used to send an email notification when the account
     * password of an user is changed.
     *
     * @param   integer $usr_id The user ID
     * @param   string $password The user' password
     * @return  void
     */
    public static function notifyUserPassword($usr_id, $password)
    {
        $info = User::getDetails($usr_id);
        $info["usr_password"] = $password;
        $info["projects"] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/updated_password.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));

        // change the current locale
        Language::set(User::getLang($usr_id));

        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_Helper();
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": " . ev_gettext("User account password changed"));

        Language::restore();
    }

    /**
     * Method used to send an email notification when a new user
     * account is created.
     *
     * @param   integer $usr_id The user ID
     * @param   string $password The user' password
     * @return  void
     */
    public static function notifyNewUser($usr_id, $password)
    {
        $info = User::getDetails($usr_id);
        $info["usr_password"] = $password;
        $info["projects"] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/new_user.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));

        // change the current locale
        Language::set(User::getLang($usr_id));

        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_Helper();
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": " . ev_gettext("New User information"));

        Language::restore();
    }

    /**
     * Send an email to all issue assignees
     *
     * @param   integer $issue_id The ID of the issue
     * @param   string $type The type of notification to send
     * @param   array $data Any extra data to pass to the template
     */
    public function notifyAssignees($issue_id, $type, $data, $title = '')
    {
        $prj_id = Issue::getProjectID($issue_id);
        $assignees = Issue::getAssignedUserIDs($issue_id);
        if (count($assignees) > 0) {

            // get issue details
            $issue = Issue::getDetails($issue_id);
            // open text template
            $tpl = new Template_Helper();
            $tpl->setTemplate('notifications/' . $type . '.tpl.text');
            $tpl->bulkAssign(array(
                "app_title"    => Misc::getToolCaption(),
                "issue"        => $issue,
                "data"         => $data
            ));

            for ($i = 0; $i < count($assignees); $i++) {
                if (!Workflow::shouldEmailAddress($prj_id, Mail_Helper::getEmailAddress(User::getFromHeader($assignees[$i])))) {
                    continue;
                }

                // change the current locale
                Language::set(User::getLang($assignees[$i]));
                $text_message = $tpl->getTemplateContents();

                // send email (use PEAR's classes)
                $mail = new Mail_Helper();
                $mail->setTextBody($text_message);
                $mail->setHeaders(Mail_Helper::getBaseThreadingHeaders($issue_id));
                $mail->send(self::getFixedFromHeader($issue_id, '', 'issue'), User::getFromHeader($assignees[$i]), "[#$issue_id] $title: " . $issue['iss_summary'], true, $issue_id, $type);
            }
            Language::restore();
        }

    }

    /**
     * Method used to send an email notification when an issue is
     * assigned to an user.
     *
     * @param   array $users The list of users
     * @param   integer $issue_id The issue ID
     * @return  void
     */
    public static function notifyNewAssignment($users, $issue_id)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $emails = array();
        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i] == Auth::getUserID()) {
                continue;
            }
            $prefs = Prefs::get($users[$i]);
            if ((!empty($prefs)) && (isset($prefs["receive_assigned_email"][$prj_id])) &&
                    ($prefs["receive_assigned_email"][$prj_id]) && ($users[$i] != Auth::getUserID())) {
                $emails[] = User::getFromHeader($users[$i]);
            }
        }
        if (count($emails) == 0) {
            return false;
        }
        // get issue details
        $issue = Issue::getDetails($issue_id);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/assigned.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"    => Misc::getToolCaption(),
            "issue"        => $issue,
            "current_user" => User::getFullName(Auth::getUserID())
        ));

        for ($i = 0; $i < count($emails); $i++) {
            $text_message = $tpl->getTemplateContents();
            Language::set(User::getLang(User::getUserIDByEmail(Mail_Helper::getEmailAddress($emails[$i]))));

            // send email (use PEAR's classes)
            $mail = new Mail_Helper();
            $mail->setTextBody($text_message);
            $mail->setHeaders(Mail_Helper::getBaseThreadingHeaders($issue_id));
            $mail->send(self::getFixedFromHeader($issue_id, '', 'issue'), $emails[$i], "[#$issue_id] New Assignment: " . $issue['iss_summary'], true, $issue_id, 'assignment');
        }
        Language::restore();
    }

    /**
     * Method used to send the account details of an user.
     *
     * @param   integer $usr_id The user ID
     * @return  void
     */
    public function notifyAccountDetails($usr_id)
    {
        $info = User::getDetails($usr_id);
        $info["projects"] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/account_details.tpl.text');
        $tpl->bulkAssign(array(
            "app_title"    => Misc::getToolCaption(),
            "user"         => $info
        ));

        Language::set(User::getLang($usr_id));
        $text_message = $tpl->getTemplateContents();

        // send email (use PEAR's classes)
        $mail = new Mail_Helper();
        $mail->setTextBody($text_message);
        $setup = $mail->getSMTPSettings();
        $mail->send($setup["from"], $mail->getFormattedName($info["usr_full_name"], $info["usr_email"]), APP_SHORT_NAME . ": " . ev_gettext("Your User Account Details"));
        Language::restore();
    }

    /**
     * Method used to get the list of subscribers for a given issue.
     *
     * @param   integer $issue_id The issue ID
     * @param   integer $type The type of subscription
     * @param   integer $min_role Only show subscribers with this role or above
     * @return  array An array containing 2 elements. Each a list of subscribers, separated by commas
     */
    public static function getSubscribers($issue_id, $type = null, $min_role = null)
    {
        $subscribers = array(
            'staff'     => array(),
            'customers' => array(),
            'all'       => array()
        );
        $prj_id = Issue::getProjectID($issue_id);
        $stmt = "SELECT
                    sub_usr_id,
                    usr_full_name,
                    usr_email,
                    pru_role
                 FROM
                    (
                    {{%subscription}},
                    {{%user}}";

        if ($type) {
            $stmt .= ",
                     {{%subscription_type}}";
        }
        $stmt .= "
                    )
                    LEFT JOIN
                        {{%project_user}}
                    ON
                        (sub_usr_id = pru_usr_id AND pru_prj_id = ?)
                 WHERE
                    sub_usr_id=usr_id AND
                    sub_iss_id=?";
        $params = array(
            $prj_id, $issue_id
        );
        if ($min_role) {
            $stmt .= " AND
                    pru_role >= ?";
            $params[] = $min_role;
        }
        if ($type) {
            $stmt .= " AND\nsbt_sub_id = sub_id AND
                      sbt_type = ?";
            $params[] = $type;
        }
        try {
            $users = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DbException $e) {
            return array();
        }

        for ($i = 0; $i < count($users); $i++) {
            if ($users[$i]['pru_role'] != User::getRoleID('Customer')) {
                $subscribers['staff'][] = $users[$i]['usr_full_name'];
            } else {
                $subscribers['customers'][] = $users[$i]['usr_full_name'];
            }
        }

        if ($min_role == false) {
            $stmt = "SELECT
                        DISTINCT sub_email,
                        usr_full_name,
                        pru_role
                     FROM
                        (
                        {{%subscription}},
                        {{%subscription_type}}
                        )
                     LEFT JOIN
                        {{%user}}
                     ON
                        usr_email = sub_email
                     LEFT JOIN
                        {{%project_user}}
                     ON
                        usr_id = pru_usr_id AND
                        pru_prj_id = $prj_id
                     WHERE
                        sub_id = sbt_sub_id AND
                        sub_iss_id=?";
            $params = array($issue_id);
            if ($type) {
                $stmt .= " AND\nsbt_type = ?";
                $params[] = $type;
            }
            try {
                $emails = DB_Helper::getInstance()->getAll($stmt, $params);
            } catch (DbException $e) {
                return array();
            }

            for ($i = 0; $i < count($emails); $i++) {
                if (empty($emails[$i]['sub_email'])) {
                    continue;
                }
                if ((!empty($emails[$i]['pru_role'])) && ($emails[$i]['pru_role'] != User::getRoleID('Customer'))) {
                    $subscribers['staff'][] = $emails[$i]['usr_full_name'];
                } else {
                    $subscribers['customers'][] = $emails[$i]['sub_email'];
                }
            }
        }

        $subscribers['all'] = @join(', ', array_merge($subscribers['staff'], $subscribers['customers']));
        $subscribers['staff'] = @implode(', ', $subscribers['staff']);
        $subscribers['customers'] = @implode(', ', $subscribers['customers']);

        return $subscribers;
    }

    /**
     * Method used to get the details of a given email notification
     * subscription.
     *
     * @param   integer $sub_id The subcription ID
     * @return  array The details of the subscription
     */
    public static function getDetails($sub_id)
    {
        $stmt = "SELECT
                    *
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_id=?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($sub_id));
        } catch (DbException $e) {
            return "";
        }

        if ($res["sub_usr_id"] != 0) {
            $user_info = User::getNameEmail($res["sub_usr_id"]);
            $res["sub_email"] = $user_info["usr_email"];
        }

        return array_merge($res, self::getSubscribedActions($sub_id));
    }

    /**
     * Method used to get the subscribed actions for a given
     * subscription ID.
     *
     * @param   integer $sub_id The subcription ID
     * @return  array The subscribed actions
     */
    public function getSubscribedActions($sub_id)
    {
        $stmt = "SELECT
                    sbt_type,
                    1
                 FROM
                    {{%subscription_type}}
                 WHERE
                    sbt_sub_id=?";
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($sub_id));
        } catch (DbException $e) {
            return "";
        }

        return $res;
    }

    /**
     * Method used to get the list of subscribers for a given issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  array The list of subscribers
     */
    public static function getSubscriberListing($issue_id)
    {
        $stmt = "SELECT
                    sub_id,
                    sub_iss_id,
                    sub_usr_id,
                    sub_email
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_iss_id=?";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($issue_id));
        } catch (DbException $e) {
            return "";
        }

        for ($i = 0; $i < count($res); $i++) {
            if ($res[$i]["sub_usr_id"] != 0) {
                $res[$i]["sub_email"] = User::getFromHeader($res[$i]["sub_usr_id"]);
            }
            // need to get the list of subscribed actions now
            $actions = self::getSubscribedActions($res[$i]["sub_id"]);
            $res[$i]["actions"] = @implode(", ", array_keys($actions));
        }

        return $res;
    }

    /**
     * Returns if the specified user is notified in this issue.
     *
     * @param   integer $issue_id The id of the issue.
     * @param   integer $usr_id The user to check.
     * @return  boolean If the specified user is notified in the issue.
     */
    public static function isUserNotified($issue_id, $usr_id)
    {
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_iss_id=? AND
                    sub_usr_id=?";
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, array($issue_id, $usr_id));
        } catch (DbException $e) {
            return null;
        }

        return $res > 0;
    }

    /**
     * Method used to remove all subscriptions associated with a given
     * set of issues.
     *
     * @param   array $ids The list of issues
     * @return  boolean
     */
    public static function removeByIssues($ids)
    {
        $items = DB_Helper::buildList($ids);
        $stmt = "SELECT
                    sub_id
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_iss_id IN ($items)";
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $ids);
        } catch (DbException $e) {
            return false;
        }

        self::remove($res);

        return true;
    }

    /**
     * Method used to remove all rows associated with a set of
     * subscription IDs
     *
     * @param   array $items The list of subscription IDs
     * @return  boolean
     */
    public static function remove($items)
    {
        $itemlist = DB_Helper::buildList($items);

        $stmt = "SELECT
                    sub_iss_id
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_id IN ($itemlist)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt, $items);

        for ($i = 0; $i < count($items); $i++) {
            $sub_id = $items[$i];
            $subscriber = self::getSubscriber($sub_id);
            $stmt = "DELETE FROM
                        {{%subscription}}
                     WHERE
                        sub_id=?";
            DB_Helper::getInstance()->query($stmt, array($sub_id));

            $stmt = "DELETE FROM
                        {{%subscription_type}}
                     WHERE
                        sbt_sub_id=?";
            DB_Helper::getInstance()->query($stmt, array($sub_id));

            // need to save a history entry for this
            History::add($issue_id, Auth::getUserID(), History::getTypeID('notification_removed'),
                            ev_gettext('Notification list entry (%1$s) removed by %2$s', $subscriber, User::getFullName(Auth::getUserID())));
        }
        Issue::markAsUpdated($issue_id);

        return true;
    }

    public static function removeByEmail($issue_id, $email)
    {
        $usr_id = User::getUserIDByEmail($email, true);
        $stmt = "SELECT
                    sub_id
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_iss_id = ? AND";
        $params = array($issue_id);
        if (empty($usr_id)) {
            $stmt .= "
                    sub_email = ?";
            $params[] = $email;
        } else {
            $stmt .= "
                    sub_usr_id = ?";
            $params[] = $usr_id;
        }
        try {
            $sub_id = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    {{%subscription}}
                 WHERE
                    sub_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($sub_id));
        } catch (DbException $e) {
            return false;
        }

        $stmt = "DELETE FROM
                    {{%subscription_type}}
                 WHERE
                    sbt_sub_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($sub_id));
        } catch (DbException $e) {
            return false;
        }

        // need to save a history entry for this
        History::add($issue_id, Auth::getUserID(), History::getTypeID('notification_removed'),
                        ev_gettext('Notification list entry (%1$s) removed by %2$s', $email, User::getFullName(Auth::getUserID())));

        Issue::markAsUpdated($issue_id);

        return true;
    }

    /**
     * Returns the email address associated with a notification list
     * subscription, user based or otherwise.
     *
     * @param   integer $sub_id The subscription ID
     * @return  string The email address
     */
    public function getSubscriber($sub_id)
    {
        $stmt = "SELECT
                    sub_usr_id,
                    sub_email
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_id=?";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($sub_id));
        } catch (DbException $e) {
            return '';
        }

        if (empty($res['sub_usr_id'])) {
            return $res['sub_email'];
        } else {
            return User::getFromHeader($res['sub_usr_id']);
        }
    }

    /**
     * Returns the subscription ID for the specified email and issue ID
     *
     * @param $issue_id
     * @param $email
     * @return  string The email address
     */
    public static function getSubscriberID($issue_id, $email)
    {
        $usr_id = User::getUserIDByEmail($email);

        $params = array($issue_id);
        $stmt = "SELECT
                    sub_id
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_iss_id = ? AND";
        if ($usr_id) {
            $stmt .= " sub_usr_id = ?";
            $params[] = $usr_id;
        } else {
            $stmt .= " sub_email = ?";
            $params[] = $email;
        }
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DbException $e) {
            return null;
        }

        return $res;
    }

    /**
     * Method used to get the full list of possible notification actions.
     *
     * @return  array All of the possible notification actions
     */
    public static function getAllActions()
    {
        return array(
            'updated',
            'closed',
            'emails',
            'files'
        );
    }

    /**
     * Method used to get the full list of default notification
     * actions.
     *
     * @param   integer $issue_id The ID of the issue the user is being subscribed too
     * @param   string  $email The email address of the user to be subscribed
     * @param   string  $source The source of this call, "add_unknown_user", "self_assign", "remote_assign", "anon_issue", "issue_update", "issue_from_email", "new_issue", "note", "add_extra_recipients"
     * @return  array The list of default notification actions
     */
    public static function getDefaultActions($issue_id = null, $email = null, $source = null)
    {
        $prj_id = Auth::getCurrentProject();
        $workflow = Workflow::getNotificationActions($prj_id, $issue_id, $email, $source);
        if ($workflow !== null) {
            return $workflow;
        }

        $actions = array();
        $setup = Setup::load();

        if (@$setup['update'] == 1) {
            $actions[] = 'updated';
        }
        if (@$setup['closed'] == 1) {
            $actions[] = 'closed';
        }
        if (@$setup['files'] == 1) {
            $actions[] = 'files';
        }
        if (@$setup['emails'] == 1) {
            $actions[] = 'emails';
        }

        return $actions;
    }

    /**
     * Method used to subscribe an user to a set of actions in an issue.
     *
     * @param   integer $usr_id The user ID of the person performing this action
     * @param   integer $issue_id The issue ID
     * @param   integer $subscriber_usr_id The user ID of the subscriber
     * @param   array $actions The list of actions to subscribe this user to
     * @param   boolean $add_history Whether to add a history entry about this change or not
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function subscribeUser($usr_id, $issue_id, $subscriber_usr_id, $actions, $add_history = true)
    {
        $prj_id = Issue::getProjectID($issue_id);

        // call workflow to modify actions or cancel adding this user.
        $email = '';
        $workflow = Workflow::handleSubscription($prj_id, $issue_id, $subscriber_usr_id, $email, $actions);
        if ($workflow === false) {
            // cancel subscribing the user
            return -2;
        }
        if ($subscriber_usr_id == APP_SYSTEM_USER_ID) {
            return -2;
        }

        $stmt = "SELECT
                    COUNT(sub_id)
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_iss_id=? AND
                    sub_usr_id=?";
        $total = DB_Helper::getInstance()->getOne($stmt, array($issue_id, $subscriber_usr_id));
        if ($total > 0) {
            return -1;
        }
        $stmt = "INSERT INTO
                    {{%subscription}}
                 (
                    sub_iss_id,
                    sub_usr_id,
                    sub_created_date,
                    sub_level,
                    sub_email
                 ) VALUES (
                    ?, ?, ?, 'issue', ''
                 )";
        try {
            DB_Helper::getInstance()->query($stmt, array($issue_id, $subscriber_usr_id, Date_Helper::getCurrentDateGMT()));
        } catch (DbException $e) {
            return -1;
        }

        $sub_id = DB_Helper::get_last_insert_id();
        for ($i = 0; $i < count($actions); $i++) {
            self::addType($sub_id, $actions[$i]);
        }
        // need to mark the issue as updated
        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        if ($add_history) {
            History::add($issue_id, $usr_id, History::getTypeID('notification_added'),
                            ev_gettext('Notification list entry (%1$s) added by %2$s', User::getFromHeader($subscriber_usr_id), User::getFullName($usr_id)));
        }

        return 1;
    }

    /**
     * Method used to add a new subscriber manually, by using the
     * email notification interface.
     *
     * @param   integer $usr_id The user ID of the person performing this change
     * @param   integer $issue_id The issue ID
     * @param   string $email The email address to subscribe
     * @param   array $actions The actions to subcribe to
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function subscribeEmail($usr_id, $issue_id, $email, $actions)
    {
        $email = Mail_Helper::getEmailAddress($email);
        if (!is_string($email)) {
            return -1;
        }

        $email = strtolower($email);
        // first check if this is an actual user or just an email address
        $sub_usr_id = User::getUserIDByEmail($email, true);
        if (!empty($sub_usr_id)) {
            return self::subscribeUser($usr_id, $issue_id, $sub_usr_id, $actions);
        }

        $prj_id = Issue::getProjectID($issue_id);

        // call workflow to modify actions or cancel adding this user.
        $subscriber_usr_id = false;
        $workflow = Workflow::handleSubscription($prj_id, $issue_id, $subscriber_usr_id, $email, $actions);
        if ($workflow === false) {
            // cancel subscribing the user
            return -2;
        }

        // manual check to prevent duplicates
        if (!empty($email)) {
            $stmt = "SELECT
                        COUNT(sub_id)
                     FROM
                        {{%subscription}}
                     WHERE
                        sub_iss_id=? AND
                        sub_email=?";
            $total = DB_Helper::getInstance()->getOne($stmt, array($issue_id, $email));
            if ($total > 0) {
                return -1;
            }
        }
        $stmt = "INSERT INTO
                    {{%subscription}}
                 (
                    sub_iss_id,
                    sub_usr_id,
                    sub_created_date,
                    sub_level,
                    sub_email
                 ) VALUES (
                    ?,
                    0,
                    ?,
                    'issue',
                    ?
                 )";
        try {
            DB_Helper::getInstance()->query($stmt, array($issue_id, Date_Helper::getCurrentDateGMT(), $email));
        } catch (DbException $e) {
            return -1;
        }

        $sub_id = DB_Helper::get_last_insert_id();
        for ($i = 0; $i < count($actions); $i++) {
            self::addType($sub_id, $actions[$i]);
        }
        // need to mark the issue as updated
        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        // FIXME: XSS possible as $email is not escaped for html?
        History::add($issue_id, $usr_id, History::getTypeID('notification_added'),
                        ev_gettext('Notification list entry (\'%1$s\') added by %2$s', $email, User::getFullName($usr_id)));

        return 1;
    }

    /**
     * Method used to add the subscription type to the given
     * subscription.
     *
     * @param   integer $sub_id The subscription ID
     * @param   string $type The subscription type
     * @return  void
     */
    public function addType($sub_id, $type)
    {
        $stmt = "INSERT INTO
                    {{%subscription_type}}
                 (
                    sbt_sub_id,
                    sbt_type
                 ) VALUES (
                    ?, ?
                 )";
        DB_Helper::getInstance()->query($stmt, array($sub_id, $type));
    }

    /**
     * Method used to update the details of a given subscription.
     *
     * @param   integer $sub_id The subscription ID
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update($sub_id)
    {
        $stmt = "SELECT
                    sub_iss_id,
                    sub_usr_id
                 FROM
                    {{%subscription}}
                 WHERE
                    sub_id=?";

        // FIXME $usr_id unused
        // TODO: need fetchmode default?
        list($issue_id, $usr_id) = DB_Helper::getInstance()->getRow($stmt, array($sub_id), DbInterface::DB_FETCHMODE_DEFAULT);

        $email = strtolower(Mail_Helper::getEmailAddress($_POST["email"]));
        $usr_id = User::getUserIDByEmail($email, true);
        if (!empty($usr_id)) {
            $email = '';
        } else {
            $usr_id = 0;
            $email = $_POST["email"];
        }
        $prj_id = Issue::getProjectID($issue_id);

        // call workflow to modify actions or cancel adding this user.
        $actions = array();
        $subscriber_usr_id = false;
        $workflow = Workflow::handleSubscription($prj_id, $issue_id, $subscriber_usr_id, $email, $actions);
        if ($workflow === false) {
            // cancel subscribing the user
            return -2;
        }

        // always set the type of notification to issue-level
        $stmt = "UPDATE
                    {{%subscription}}
                 SET
                    sub_level='issue',
                    sub_email=?,
                    sub_usr_id=?
                 WHERE
                    sub_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, array($email, $usr_id, $sub_id));
        } catch (DbException $e) {
            return -1;
        }

        $stmt = "DELETE FROM
                    {{%subscription_type}}
                 WHERE
                    sbt_sub_id=?";
        DB_Helper::getInstance()->query($stmt, array($sub_id));
        // now add them all again
        for ($i = 0; $i < count($_POST["actions"]); $i++) {
            self::addType($sub_id, $_POST["actions"][$i]);
        }
        // need to mark the issue as updated
        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        History::add($issue_id, Auth::getUserID(), History::getTypeID('notification_updated'),
                        ev_gettext('Notification list entry (\'%1$s\') updated by %2$s', self::getSubscriber($sub_id), User::getFullName(Auth::getUserID())));

        return 1;
    }
}
