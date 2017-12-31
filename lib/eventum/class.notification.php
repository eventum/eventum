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

use Eventum\Attachment\AttachmentManager;
use Eventum\Db\DatabaseException;
use Eventum\Mail\Helper\AddressHeader;
use Eventum\Mail\Helper\WarningMessage;
use Eventum\Mail\MailBuilder;
use Eventum\Mail\MailMessage;

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
     * @param   int $issue_id The issue ID
     * @param   string $email The email address
     * @return  bool
     */
    public static function isSubscribedToEmails($issue_id, $email)
    {
        $email = Mail_Helper::getEmailAddress($email);
        if ($email == '@') {
            // XXX: never happens with ZF, try catch above call?
            // broken address, don't send the email...
            return true;
        }
        $subscribed_emails = self::getSubscribedEmails($issue_id, 'emails');
        $subscribed_emails = Misc::lowercase($subscribed_emails);
        if (in_array($email, $subscribed_emails)) {
            return true;
        }

        return false;
    }

    /**
     * Method used to get the list of email addresses currently
     * subscribed to a notification type for a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param bool|string $type The notification type
     * @return  array The list of email addresses
     */
    public static function getSubscribedEmails($issue_id, $type = false)
    {
        $stmt = 'SELECT
                    CASE WHEN usr_id <> 0 THEN usr_email ELSE sub_email END AS email
                 FROM
                    (
                    `subscription`';
        $params = [];
        if ($type != false) {
            $stmt .= ',
                    `subscription_type`';
        }
        $stmt .= '
                    )
                 LEFT JOIN
                    `user`
                 ON
                    usr_id=sub_usr_id
                 WHERE';
        if ($type != false) {
            $stmt .= '
                    sbt_sub_id=sub_id AND
                    sbt_type=? AND';
            $params[] = $type;
        }
        $stmt .= '
                    sub_iss_id=?';
        $params[] = $issue_id;

        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of names and email addresses currently
     * subscribed to a notification type for a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param bool|string $type The notification type
     * @return  array The list of email addresses
     */
    public static function getSubscribedNameEmails($issue_id, $type = false)
    {
        $stmt = 'SELECT
                    usr_full_name,
                    CASE WHEN usr_id <> 0 THEN usr_email ELSE sub_email END AS email
                 FROM
                    (
                    `subscription`';
        $params = [];
        if ($type != false) {
            $stmt .= ',
                    `subscription_type`';
        }
        $stmt .= '
                    )
                 LEFT JOIN
                    `user`
                 ON
                    usr_id=sub_usr_id
                 WHERE';
        if ($type != false) {
            $stmt .= '
                    sbt_sub_id=sub_id AND
                    sbt_type=? AND';
            $params[] = $type;
        }
        $stmt .= '
                    sub_iss_id=?';
        $params[] = $issue_id;

        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }
        $data = [];
        foreach ($res as $row) {
            if (!empty($row['usr_full_name'])) {
                $data[] = Mail_Helper::getFormattedName($row['usr_full_name'], $row['email']);
            } else {
                $data[] = $row['email'];
            }
        }

        return $data;
    }

    /**
     * Method used to build a properly encoded email address that will be
     * used by the email/note routing system.
     *
     * @param   int $issue_id The issue ID
     * @param   string $sender The email address of the sender
     * @param   string $type Whether this is a note or email routing message
     * @return  string The properly encoded email address: =?UTF-8?Q?Elan_Ruusam=C3=A4e?= <default_project@example.com>
     * @deprecated kill this monstrocity!
     */
    public static function getFixedFromHeader($issue_id, $sender, $type)
    {
        $setup = Setup::get();
        if ($type === 'issue') {
            $routing = 'email_routing';
        } else {
            $routing = 'note_routing';
        }
        $project_id = Issue::getProjectID($issue_id);
        $project_info = Project::getOutgoingSenderAddress($project_id);
        // if sender is empty, get project email address
        if (empty($sender)) {
            $info = [
                'sender_name' => $project_info['name'],
                'email' => $project_info['email'],
            ];

            // if no project name, use eventum wide sender name
            if (empty($info['sender_name'])) {
                $setup_sender_info = self::getAddressInfo($setup['smtp']['from']);
                $info['sender_name'] = $setup_sender_info['sender_name'];
            }
        } else {
            $info = self::getAddressInfo($sender);
        }
        // use per project flag first
        $flag = '';
        $flag_location = '';
        if (!empty($project_info['flag'])) {
            $flag = '[' . $project_info['flag'] . '] ';
            $flag_location = $project_info['flag_location'];
        } elseif ($setup[$routing]['recipient_type_flag']) {
            $flag = '[' . $setup[$routing]['recipient_type_flag'] . '] ';
            $flag_location = $setup[$routing]['flag_location'];
        }
        if ($setup[$routing]['status'] !== 'enabled') {
            // let's use the custom outgoing sender address
            $project_info = Project::getOutgoingSenderAddress($project_id);
            if (empty($project_info['email'])) {
                /// no project email, use main email address
                $from_email = $setup['smtp']['from'];
            } else {
                $from_email = $project_info['email'];
            }
        } else {
            $from_email = $setup[$routing]['address_prefix'] . $issue_id . '@' . $setup[$routing]['address_host'];
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
        if (substr($info['sender_name'], strlen($info['sender_name']) - 1) === '"') {
            if ($flag_location === 'before') {
                $info['sender_name'] = '"' . $flag . substr($info['sender_name'], 1);
            } else {
                $info['sender_name'] = substr($info['sender_name'], 0, -1) . ' ' . trim($flag) . '"';
            }
        } else {
            if ($flag_location === 'before') {
                $info['sender_name'] = '"' . $flag . $info['sender_name'] . '"';
            } else {
                $info['sender_name'] = '"' . $info['sender_name'] . ' ' . trim($flag) . '"';
            }
        }
        $from = Mail_Helper::getFormattedName($info['sender_name'], $from_email);

        return Mime_Helper::encodeAddress(trim($from));
    }

    /**
     * Method used to break down the email address information and
     * return it for easy manipulation.
     *
     * Expands "Groups" into single addresses.
     *
     * @param   string $address The email address value
     * @param   bool $multiple If multiple addresses should be returned
     * @return  array The address information
     * @deprecated used by getFixedFromHeader, kill them both
     */
    private static function getAddressInfo($address, $multiple = false)
    {
        $header = AddressHeader::fromString($address);

        $addresses = [];
        foreach ($header->getAddressList() as $address) {
            $email = $address->getEmail();
            $sender_name = $address->getName();

            list($username, $hostname) = explode('@', $email);
            $item = [
                'email' => $email,
                'sender_name' => $sender_name ? sprintf('"%s"', $sender_name) : '',
                'username' => $username,
                'host' => $hostname,
            ];
            $addresses[] = $item;
        }

        if (!$multiple) {
            return $addresses[0];
        }

        return $addresses;
    }

    /**
     * Method used to check whether the current sender of the email is the
     * mailer daemon responsible for dealing with bounces.
     *
     * @param   string $email The email address to check against
     * @return  bool
     */
    public static function isBounceMessage($email)
    {
        if (strtolower(substr($email, 0, 14)) == 'mailer-daemon@') {
            return true;
        }

        return false;
    }

    /**
     * Method used to check whether the given sender email address is
     * the same as the issue routing email address.
     *
     * @param   int $issue_id The issue ID
     * @param   string $sender The address of the sender
     * @return  bool
     */
    public static function isIssueRoutingSender($issue_id, $sender)
    {
        $check = self::getFixedFromHeader($issue_id, $sender, 'issue');
        $check_email = Mail_Helper::getEmailAddress($check);
        $sender_email = Mail_Helper::getEmailAddress($sender);
        if ($check_email == $sender_email) {
            return true;
        }

        return false;
    }

    /**
     * Method used to forward the new email to the list of subscribers.
     *
     * @param MailMessage $mail The Mail object
     * @param array $options
     * - int $usr_id The user ID of the person performing this action
     * - int $issue_id The issue ID
     * - bool $internal_only Whether the email should only be redirected to internal users or not
     * - bool $assignee_only Whether the email should only be sent to the assignee
     * - string|bool $type The type of email this is
     * - int $sup_id the ID of this email
     */
    public static function notifyNewEmail(MailMessage $mail, array $options = [])
    {
        $internal_only = isset($options['internal_only']) ? $options['internal_only'] : false;
        $assignee_only = isset($options['assignee_only']) ? $options['assignee_only'] : false;
        $type = isset($options['type']) ? $options['type'] : '';
        $sup_id = isset($options['sup_id']) ? $options['sup_id'] : false;
        $usr_id = $options['usr_id'];
        $issue_id = $options['issue_id'];
        $prj_id = Issue::getProjectID($issue_id);

        $sender = $mail->from;
        $sender_email = $mail->getSender();

        // get ID of whoever is sending this.
        $sender_usr_id = User::getUserIDByEmail($sender_email, true) ?: false;

        // automatically subscribe this sender to email notifications on this issue
        $subscribed_emails = self::getSubscribedEmails($issue_id, 'emails');
        $subscribed_emails = Misc::lowercase($subscribed_emails);
        if ((!self::isIssueRoutingSender($issue_id, $sender)) &&
                (!$mail->isBounceMessage()) &&
                (!in_array($sender_email, $subscribed_emails)) &&
                (Workflow::shouldAutoAddToNotificationList($prj_id))) {
            $actions = ['emails'];
            self::subscribeEmail($usr_id, $issue_id, $sender_email, $actions);
        }

        // get the subscribers
        $emails = [];
        $users = self::getUsersByIssue($issue_id, 'emails');
        foreach ($users as $user) {
            if (empty($user['sub_usr_id'])) {
                if ($internal_only == false) {
                    $email = $user['sub_email'];
                }
            } else {
                // if we are only supposed to send email to internal users, check if the role is lower than standard user
                if ($internal_only == true && (User::getRoleByUser($user['sub_usr_id'], $prj_id) < User::ROLE_USER)) {
                    continue;
                }
                // check if we are only supposed to send email to the assignees
                if ($internal_only == true && $assignee_only == true) {
                    $assignee_usr_ids = Issue::getAssignedUserIDs($issue_id);
                    if (!in_array($user['sub_usr_id'], $assignee_usr_ids)) {
                        continue;
                    }
                }
                $email = User::getFromHeader($user['sub_usr_id']);
            }

            if (empty($email)) {
                continue;
            }

            // don't send the email to the same person who sent it unless they want it
            if ($sender_usr_id) {
                $prefs = Prefs::get($sender_usr_id);
                if (!isset($prefs['receive_copy_of_own_action'][$prj_id])) {
                    $prefs['receive_copy_of_own_action'][$prj_id] = 0;
                }
                if (($prefs['receive_copy_of_own_action'][$prj_id] == 0) &&
                        ((!empty($user['sub_usr_id'])) && ($sender_usr_id == $user['sub_usr_id']) ||
                        (Mail_Helper::getEmailAddress($email) == $sender_email))) {
                    continue;
                }
            }

            $emails[] = $email;
        }

        if (!$emails) {
            return;
        }

        // change the sender of the message to {prefix}{issue_id}@{host}
        //  - keep everything else in the message, except 'From:', 'Sender:', 'To:', 'Cc:'
        // make 'Joe Blow <joe@example.com>' become 'Joe Blow [CSC] <eventum_59@example.com>'
        $from = self::getFixedFromHeader($issue_id, $sender, 'issue');
        $from = AddressHeader::fromString($from)->getAddressList();
        $mail->setFrom($from);
        $mail->stripHeaders();
        $mail->setSubject(Mail_Helper::formatSubject($issue_id, $mail->subject));

        if (!$type) {
            $usr_role = User::getRoleByUser($sender_usr_id, $prj_id);
            if ($sender_usr_id && $usr_role == User::ROLE_CUSTOMER) {
                $type = 'customer_email';
            } else {
                $type = 'other_email';
            }
        }

        $options = [
            'save_email_copy' => 1,
            'issue_id' => $issue_id,
            'type' => $type,
            'sender_usr_id' => $sender_usr_id,
            'type_id' => $sup_id,
        ];

        foreach ($emails as $to) {
            $m = clone $mail;
            // stripHeaders removed To header, add it back
            $m->getHeaderByName('To');
            $m->setTo(Mime_Helper::encodeAddress($to));

            // add the warning message about replies being blocked or not
            $recipient_email = Mail_Helper::getEmailAddress($to);
            $wm = new WarningMessage($m);
            $wm->add($issue_id, $recipient_email);

            Mail_Queue::queue($m, $to, $options);
        }
    }

    /**
     * Method used to get the details of a given note and issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $note_id The note ID
     * @return  array The details of the note / issue
     */
    public static function getNote($issue_id, $note_id)
    {
        $stmt = 'SELECT
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
                    `note`,
                    `user`
                 WHERE
                    not_id=? AND
                    not_usr_id=usr_id';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$note_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        // if there is an unknown user, use instead of full name
        if (!empty($res['not_unknown_user'])) {
            $res['usr_full_name'] = $res['not_unknown_user'];
        }

        if (!empty($res['not_parent_id'])) {
            $res['reference_msg_id'] = Note::getMessageIDbyID($res['not_parent_id']);
        } else {
            $res['reference_msg_id'] = false;
        }

        $data = Issue::getDetails($issue_id);
        $data['note'] = $res;

        return $data;
    }

    /**
     * Method used to get the details of a given issue and attachment.
     *
     * @param   int $issue_id The issue ID
     * @param   int $attachment_id The attachment ID
     * @return  array The issue / attachment details
     */
    public static function getAttachment($issue_id, $attachment_id)
    {
        $stmt = 'SELECT
                    iat_id,
                    usr_full_name,
                    iat_created_date,
                    iat_description,
                    iat_unknown_user
                 FROM
                    `issue_attachment`,
                    `user`
                 WHERE
                    iat_usr_id=usr_id AND
                    iat_iss_id=? AND
                    iat_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id, $attachment_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $res['files'] = AttachmentManager::getAttachmentList($res['iat_id']);
        $data = Issue::getDetails($issue_id);
        $data['attachment'] = $res;

        return $data;
    }

    /**
     * Method used to get the list of users / emails that are
     * subscribed for notifications of changes for a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   string $type The notification type
     * @return  array The list of users / emails
     */
    public static function getUsersByIssue($issue_id, $type)
    {
        $prj_id = Issue::getProjectID($issue_id);
        if ($type == 'notes') {
            $stmt = "SELECT
                        DISTINCT sub_usr_id,
                        sub_email
                     FROM
                        `subscription`,
                        `user`,
                        `project_user`
                     WHERE
                        usr_id = sub_usr_id AND
                        pru_usr_id = usr_id AND
                        pru_prj_id = ? AND
                        pru_role >= ? AND
                        usr_status = 'active' AND
                        sub_iss_id=?";
            $params = [
                $prj_id,
                User::ROLE_USER,
                $issue_id,
            ];
        } else {
            $stmt = "SELECT
                        DISTINCT sub_usr_id,
                        sub_email,
                        pru_role
                     FROM
                        (
                        `subscription`,
                        `subscription_type`
                        )
                        LEFT JOIN
                          `project_user`
                          ON
                            sub_usr_id = pru_usr_id AND
                            pru_prj_id = ?
                        LEFT JOIN
                          `user`
                          ON
                            sub_usr_id = usr_id
                     WHERE
                        (usr_status = 'active' OR usr_status IS NULL) AND
                        sub_iss_id=? AND
                        sub_id=sbt_sub_id AND
                        sbt_type=?";
            $params = [
                Issue::getProjectID($issue_id), $issue_id, $type,
            ];
        }
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to send a diff-style notification email to the issue
     * subscribers about updates to its attributes.
     *
     * @param   int $issue_id The issue ID
     * @param   array $old The old issue details
     * @param   array $new The new issue details
     * @param   array $updated_custom_fields an array of the custom fields that were changed
     */
    public static function notifyIssueUpdated($issue_id, $old, $new, $updated_custom_fields)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $diffs = [];
        if (@$new['keep_assignments'] == 'no') {
            if (empty($new['assignments'])) {
                $new['assignments'] = [];
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
        if (isset($new['category']) && $old['iss_prc_id'] != $new['category']) {
            $diffs[] = '-' . ev_gettext('Category') . ': ' . Category::getTitle($old['iss_prc_id']);
            $diffs[] = '+' . ev_gettext('Category') . ': ' . Category::getTitle($new['category']);
        }
        if (isset($new['release']) && ($old['iss_pre_id'] != $new['release'])) {
            $diffs[] = '-' . ev_gettext('Release') . ': ' . Release::getTitle($old['iss_pre_id']);
            $diffs[] = '+' . ev_gettext('Release') . ': ' . Release::getTitle($new['release']);
        }
        if (isset($new['priority']) && $old['iss_pri_id'] != $new['priority']) {
            $diffs[] = '-' . ev_gettext('Priority') . ': ' . Priority::getTitle($old['iss_pri_id']);
            $diffs[] = '+' . ev_gettext('Priority') . ': ' . Priority::getTitle($new['priority']);
        }
        if (isset($new['severity']) && $old['iss_sev_id'] != $new['severity']) {
            $diffs[] = '-' . ev_gettext('Severity') . ': ' . Severity::getTitle($old['iss_sev_id']);
            $diffs[] = '+' . ev_gettext('Severity') . ': ' . Severity::getTitle($new['severity']);
        }
        if (isset($new['status']) && $old['iss_sta_id'] != $new['status']) {
            $diffs[] = '-' . ev_gettext('Status') . ': ' . Status::getStatusTitle($old['iss_sta_id']);
            $diffs[] = '+' . ev_gettext('Status') . ': ' . Status::getStatusTitle($new['status']);
        }
        if (isset($new['resolution']) && $old['iss_res_id'] != $new['resolution']) {
            $diffs[] = '-' . ev_gettext('Resolution') . ': ' . Resolution::getTitle($old['iss_res_id']);
            $diffs[] = '+' . ev_gettext('Resolution') . ': ' . Resolution::getTitle($new['resolution']);
        }
        if (isset($new['estimated_dev_time']) && $old['iss_dev_time'] != $new['estimated_dev_time']) {
            $diffs[] = '-' . ev_gettext('Estimated Dev. Time') . ': ' . Misc::getFormattedTime($old['iss_dev_time'] * 60);
            $diffs[] = '+' . ev_gettext('Estimated Dev. Time') . ': ' . Misc::getFormattedTime($new['estimated_dev_time'] * 60);
        }
        if (isset($new['summary']) && $old['iss_summary'] != $new['summary']) {
            $diffs[] = '-' . ev_gettext('Summary') . ': ' . $old['iss_summary'];
            $diffs[] = '+' . ev_gettext('Summary') . ': ' . $new['summary'];
        }
        if (isset($new['percentage_complete']) && $old['iss_original_percent_complete'] != $new['percentage_complete']) {
            $diffs[] = '-' . ev_gettext('Percent complete') . ': ' . $old['iss_original_percent_complete'];
            $diffs[] = '+' . ev_gettext('Percent complete') . ': ' . $new['percentage_complete'];
        }
        if (isset($new['description']) && $old['iss_original_description'] != $new['description']) {
            $old['iss_description'] = explode("\n", $old['iss_original_description']);
            $new['description'] = explode("\n", $new['description']);
            $diff = new Text_Diff($old['iss_description'], $new['description']);
            $renderer = new Text_Diff_Renderer_unified();
            $desc_diff = explode("\n", trim($renderer->render($diff)));
            $diffs[] = ev_gettext('Description') . ':';
            foreach ($desc_diff as $diff) {
                $diffs[] = $diff;
            }
        }

        $data = Issue::getDetails($issue_id);
        $data['diffs'] = implode("\n", $diffs);
        $data['updated_by'] = User::getFullName(Auth::getUserID());

        $all_emails = [];
        $role_emails = [
            User::ROLE_VIEWER => [],
            User::ROLE_REPORTER => [],
            User::ROLE_CUSTOMER => [],
            User::ROLE_USER => [],
            User::ROLE_DEVELOPER => [],
            User::ROLE_MANAGER => [],
            User::ROLE_ADMINISTRATOR => [],
        ];
        $users = self::getUsersByIssue($issue_id, 'updated');
        foreach ($users as $user) {
            if (empty($user['sub_usr_id'])) {
                $email = $user['sub_email'];
                // non users are treated as "Viewers" for permission checks
                $role = User::ROLE_VIEWER;
            } else {
                $prefs = Prefs::get($user['sub_usr_id']);
                if ((Auth::getUserID() == $user['sub_usr_id']) &&
                        ((empty($prefs['receive_copy_of_own_action'][$prj_id])) ||
                            ($prefs['receive_copy_of_own_action'][$prj_id] == false))) {
                    continue;
                }
                $email = User::getFromHeader($user['sub_usr_id']);
                $role = $user['pru_role'];
            }

            // now add it to the list of emails
            if (!empty($email) && !in_array($email, $all_emails)) {
                $all_emails[] = $email;
                $role_emails[$role][] = $email;
            }
        }

        // get additional email addresses to notify
        $additional_emails = Workflow::getAdditionalEmailAddresses($prj_id, $issue_id, 'issue_updated', ['old' => $old, 'new' => $new]);
        $data['custom_field_diffs'] = implode("\n", Custom_Field::formatUpdatesToDiffs($updated_custom_fields, User::ROLE_VIEWER));
        foreach ($additional_emails as $email) {
            if (!in_array($email, $all_emails)) {
                $role_emails[User::ROLE_VIEWER][] = $email;
            }
        }

        // send email to each role separately due to custom field restrictions
        foreach ($role_emails as $role => $emails) {
            if (count($emails) > 0) {
                $data['custom_field_diffs'] = implode("\n", Custom_Field::formatUpdatesToDiffs($updated_custom_fields, $role));
                if (!empty($data['custom_field_diffs']) || !empty($data['diffs'])) {
                    self::notifySubscribers($issue_id, $emails, 'updated', $data, ev_gettext('Updated'), false);
                }
            }
        }
    }

    /**
     * Method used to send a diff-style notification email to the issue
     * subscribers about status changes
     *
     * @param   int $issue_id The issue ID
     * @param   int $old_status The old issue status
     * @param   int $new_status The new issue status
     */
    public static function notifyStatusChange($issue_id, $old_status, $new_status)
    {
        $diffs = [];
        if ($old_status != $new_status) {
            $diffs[] = '-Status: ' . Status::getStatusTitle($old_status);
            $diffs[] = '+Status: ' . Status::getStatusTitle($new_status);
        }

        if (count($diffs) < 1) {
            return;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $emails = [];
        $users = self::getUsersByIssue($issue_id, 'updated');
        foreach ($users as $user) {
            if (empty($user['sub_usr_id'])) {
                $email = $user['sub_email'];
            } else {
                $prefs = Prefs::get($user['sub_usr_id']);
                if ((Auth::getUserID() == $user['sub_usr_id']) &&
                        ((empty($prefs['receive_copy_of_own_action'][$prj_id])) ||
                            ($prefs['receive_copy_of_own_action'][$prj_id] == false))) {
                    continue;
                }
                $email = User::getFromHeader($user['sub_usr_id']);
            }
            // now add it to the list of emails
            if ((!empty($email)) && (!in_array($email, $emails))) {
                $emails[] = $email;
            }
        }
        $data = Issue::getDetails($issue_id);
        $data['diffs'] = implode("\n", $diffs);
        $data['updated_by'] = User::getFullName(Auth::getUserID());

        self::notifySubscribers($issue_id, $emails, 'updated', $data, ev_gettext('Status Change'), false);
    }

    /**
     * Convenience method for notifying the assignment has changed.
     *
     * @param int $issue_id
     * @param array $old_assignees array of old assignee user ids
     * @param array $new_assignees array of new assignee user ids
     */
    public static function notifyAssignmentChange($issue_id, $old_assignees, $new_assignees)
    {
        $old = [
            'assigned_users' => $old_assignees,
            'assignments' => implode(', ', User::getFullName($old_assignees)),
        ];
        $new = [
            'assignments' => $new_assignees,
            'keep_assignments' => 'no',
        ];
        self::notifyIssueUpdated($issue_id, $old, $new, []);
    }

    /**
     * Method used to send email notifications for a given issue.
     *
     * @param int $issue_id The issue ID
     * @param string $type The notification type
     * @param int $entry_id The entries id that was changed
     * @param bool $internal_only Whether the notification should only be sent to internal users or not
     * @param string[] $extra_recipients extra recipients to notify (usr_id list)
     */
    public static function notify($issue_id, $type, $entry_id, $internal_only, $extra_recipients = [])
    {
        $prj_id = Issue::getProjectID($issue_id);
        $extra = [];
        if ($extra_recipients) {
            foreach ($extra_recipients as $user) {
                $extra[] = [
                    'sub_usr_id' => $user,
                    'sub_email' => '',
                ];
            }
        }
        $emails = [];
        $users = self::getUsersByIssue($issue_id, $type);
        if ($extra_recipients && count($extra) > 0) {
            $users = array_merge($users, $extra);
        }
        $user_emails = Project::getUserEmailAssocList(Issue::getProjectID($issue_id), 'active', User::ROLE_CUSTOMER);
        $user_emails = Misc::lowercase($user_emails);

        foreach ($users as $user) {
            if (empty($user['sub_usr_id'])) {
                if ($internal_only == false || in_array(strtolower($user['sub_email']), array_values($user_emails))) {
                    $email = $user['sub_email'];
                }
            } else {
                $prefs = Prefs::get($user['sub_usr_id']);
                if (Auth::getUserID() == $user['sub_usr_id'] &&
                        ((empty($prefs['receive_copy_of_own_action'][$prj_id])) ||
                            ($prefs['receive_copy_of_own_action'][$prj_id] == false))) {
                    continue;
                }
                // if we are only supposed to send email to internal users, check if the role is lower than standard user
                if ($internal_only == true && (User::getRoleByUser($user['sub_usr_id'], Issue::getProjectID($issue_id)) < User::ROLE_USER)) {
                    continue;
                }
                if ($type == 'notes' && User::isPartner($user['sub_usr_id']) &&
                        !Partner::canUserAccessIssueSection($user['sub_usr_id'], 'notes')) {
                    continue;
                }
                $email = User::getFromHeader($user['sub_usr_id']);
            }

            // now add it to the list of emails
            if (!empty($email) && !in_array($email, $emails)) {
                $emails[] = $email;
            }
        }

        // prevent the primary customer contact from receiving two emails about the issue being closed
        if ($type == 'closed') {
            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                $stmt = 'SELECT
                            iss_customer_contact_id
                         FROM
                            `issue`
                         WHERE
                            iss_id=?';
                $customer_contact_id = DB_Helper::getInstance()->getOne($stmt, [$issue_id]);
                if (!empty($customer_contact_id)) {
                    try {
                        $contact = $crm->getContact($customer_contact_id);
                        $contact_email = $contact->getEmail();
                    } catch (CRMException $e) {
                        $contact_email = '';
                    }
                    foreach ($emails as $i => $email) {
                        $email = Mail_Helper::getEmailAddress($email);
                        if ($email == $contact_email) {
                            unset($emails[$i]);
                            $emails = array_values($emails);
                            break;
                        }
                    }
                }
            }
        }

        if (!$emails) {
            return;
        }

        $data = [];
        $headers = [];
        $subject = null;
        switch ($type) {
            case 'closed':
                $data = Issue::getDetails($issue_id);
                $data['closer_name'] = User::getFullName(History::getIssueCloser($issue_id));
                $subject = ev_gettext('Closed');

                if ($entry_id) {
                    $data['reason'] = Support::getEmail($entry_id);
                }
                break;

            case 'notes':
                $data = self::getNote($issue_id, $entry_id);
                $headers = [
                    'Message-ID' => $data['note']['not_message_id'],
                ];
                $reference_msg_id = @$data['note']['reference_msg_id'];
                if ($reference_msg_id) {
                    $headers['In-Reply-To'] = $reference_msg_id;
                } else {
                    $headers['In-Reply-To'] = Issue::getRootMessageID($issue_id);
                }
                $headers['References'] = implode(' ', Mail_Helper::getReferences($issue_id, $reference_msg_id, 'note'));
                $subject = ev_gettext('Note');
                break;

            case 'files':
                $data = self::getAttachment($issue_id, $entry_id);
                $subject = ev_gettext('File Attached');
                break;

            case 'updated':
                // this should not be used anymore
                return;

            case 'emails':
                // this should not be used anymore
                return;
        }

        self::notifySubscribers($issue_id, $emails, $type, $data, $subject, $internal_only, $entry_id, $headers);
    }

    /**
     * Method used to get list of addresses that were email sent to.
     *
     * @param   int $issue_id The issue ID
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
     * NOTE: $internal_only is unused
     *
     * @param   int $issue_id The issue ID
     * @param   array $emails The list of emails
     * @param   string $type The notification type
     * @param   array $data The issue details
     * @param   string $subject The subject of the email
     * @param bool $internal_only
     * @param   int $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @param   array $headers Any extra headers that need to be added to this email (Default false)
     */
    private static function notifySubscribers($issue_id, $emails, $type, $data, $subject, $internal_only, $type_id = null, $headers = [])
    {
        global $_EVENTUM_LAST_NOTIFIED_LIST;

        $issue_id = (int)$issue_id;

        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/' . $type . '.tpl.text');
        $tpl->assign([
            'app_title' => Misc::getToolCaption(),
            'data' => $data,
            'current_user' => User::getFullName(Auth::getUserID()),
        ]);

        // type of notification is sent out: email, note, blocked_email
        $notify_type = $type;
        $sender_usr_id = false;

        $have_no_threading_headers = empty($headers['Message-ID']) && empty($headers['In-Reply-To']) && empty($headers['References']);
        $add_headers = [];
        if ($headers) {
            $add_headers = $headers;
        }
        if (!$add_headers || $have_no_threading_headers) {
            $add_headers = Mail_Helper::getBaseThreadingHeaders($issue_id);
        }

        $emails = array_unique($emails);
        foreach ($emails as $email) {
            $sender = null;
            $can_access = true;
            $email_address = Mail_Helper::getEmailAddress($email);
            $recipient_usr_id = User::getUserIDByEmail($email_address);
            if (!empty($recipient_usr_id)) {
                if (!Issue::canAccess($issue_id, $recipient_usr_id)) {
                    $can_access = false;
                }
                $tpl->assign('recipient_role', User::getRoleByUser($recipient_usr_id, Issue::getProjectID($issue_id)));
                if (isset($data['custom_fields'])) {
                    $data['custom_fields'] = Custom_Field::getListByIssue($data['iss_prj_id'], $issue_id, $recipient_usr_id);
                }
                $is_assigned = Issue::isAssignedToUser($issue_id, $recipient_usr_id);
            } else {
                $tpl->assign('recipient_role', 0);
                unset($data['custom_fields']);

                $is_assigned = false;
            }
            $tpl->assign('data', $data);
            $tpl->assign('is_assigned', $is_assigned);

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

            if ($type == 'notes') {
                // special handling of blocked messages
                if ($data['note']['not_is_blocked'] == 1) {
                    $subject = ev_gettext('BLOCKED');
                    $notify_type = 'blocked_email';
                }
                if (!empty($data['note']['not_unknown_user'])) {
                    $sender = $data['note']['not_unknown_user'];
                } else {
                    $sender = User::getFromHeader($data['note']['not_usr_id']);
                }
                $sender_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($sender));
                if (empty($sender_usr_id)) {
                    $sender_usr_id = false;
                }

                // show the title of the note, not the issue summary
                $extra_subject = $data['note']['not_title'];
                // don't add the "[#3333] Note: " prefix to messages that already have that in the subject line
                if (strpos($extra_subject, "[#$issue_id] $subject: ") !== false) {
                    $pos = strpos($extra_subject, "[#$issue_id] $subject: ");
                    $full_subject = substr($extra_subject, $pos);
                } else {
                    // TRANSLATORS: %1 - issue_id, %2: subject, %3: note title
                    $full_subject = ev_gettext('[#%1$s] %2$s: %3$s', $issue_id, $subject, $extra_subject);
                }
            } elseif ($type == 'new_issue' && $is_assigned) {
                // TRANSLATORS: %1 - issue_id, %2: issue summary
                $full_subject = ev_gettext('[#%1$s] New Issue Assigned: %2$s', $issue_id, $data['iss_summary']);
            } else {
                // TRANSLATORS: %1 - issue_id, %2: subject, %3: issue summary
                $full_subject = ev_gettext('[#%1$s] %2$s: %3$s', $issue_id, $subject, $data['iss_summary']);
            }

            if ($notify_type == 'notes' && $sender) {
                $from = self::getFixedFromHeader($issue_id, $sender, 'note');
            } else {
                $from = self::getFixedFromHeader($issue_id, '', 'issue');
            }

            $builder = new MailBuilder();
            $builder->addTextPart($tpl->getTemplateContents());
            $builder->getMessage()
                ->setSubject($full_subject)
                ->setTo($email)
                ->setFrom($from);

            $mail = $builder->toMailMessage();

            if ($add_headers) {
                $mail->addHeaders($add_headers);
            }

            $options = [
                'save_email_copy' => true,
                'issue_id' => $issue_id,
                'type' => $notify_type,
                'type_id' => $type_id,
                'sender_usr_id' => $sender_usr_id,
            ];
            Mail_Queue::queue($mail, $email, $options);

            $_EVENTUM_LAST_NOTIFIED_LIST[$issue_id][] = $email;
        }

        // restore correct language
        Language::restore();
    }

    /**
     * Method used to send an email notification to users that want
     * to be alerted when new issues are created in the system.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The issue ID
     * @param   array $exclude_list The list of users NOT to notify. This can either be usr_ids or email addresses
     */
    public static function notifyNewIssue($prj_id, $issue_id, $exclude_list = [])
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
                    `user`,
                    `project_user`
                 WHERE
                    pru_prj_id=? AND
                    usr_id=pru_usr_id AND
                    usr_status = 'active' AND
                    pru_role > ?";
        $params = [
            $prj_id, User::ROLE_CUSTOMER,
        ];

        if (count($exclude_list) > 0) {
            $stmt .= ' AND
                    usr_id NOT IN (' . DB_Helper::buildList($exclude_list) . ')';
            $params = array_merge($params, $exclude_list);
        }

        $res = DB_Helper::getInstance()->getAll($stmt, $params);
        $emails = [];
        foreach ($res as $row) {
            $subscriber = Mail_Helper::getFormattedName($row['usr_full_name'], $row['usr_email']);
            // don't send these emails to customers
            if (($row['pru_role'] == User::ROLE_CUSTOMER) || (!empty($row['usr_customer_id']))
                    || (!empty($row['usr_customer_contact_id']))) {
                continue;
            }
            $prefs = Prefs::get($row['usr_id']);
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
                    `user`,
                    `issue_user`
                 WHERE
                    isu_iss_id=? AND
                    usr_id=isu_usr_id AND
                    usr_status = 'active'";
        $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        foreach ($res as $row) {
            $subscriber = Mail_Helper::getFormattedName($row['usr_full_name'], $row['usr_email']);

            $prefs = Prefs::get($row['usr_id']);
            if ((!empty($prefs['receive_assigned_email'][$prj_id])) &&
            (@$prefs['receive_assigned_email'][$prj_id]) && (!in_array($subscriber, $emails))) {
                $emails[] = $subscriber;
            }
        }

        // get any email addresses from products
        $products = Product::getProductsByIssue($issue_id);
        if (count($products) > 0) {
            foreach ($products as $product) {
                if (!empty($product['pro_email'])) {
                    $emails[] = $product['pro_email'];
                }
            }
        }

        // get notification list members
        $emails = array_merge($emails, self::getSubscribedNameEmails($issue_id));

        // get any additional emails
        $emails = array_merge($emails, Workflow::getAdditionalEmailAddresses($prj_id, $issue_id, 'new_issue'));

        $data = Issue::getDetails($issue_id, true);
        $data['attachments'] = AttachmentManager::getList($issue_id);

        // notify new issue to irc channel
        $irc_notice = "New Issue #$issue_id (";
        $quarantine = Issue::getQuarantineInfo($issue_id);
        if (!empty($quarantine)) {
            $irc_notice .= 'Quarantined; ';
        }
        $irc_notice .= 'Priority: ' . $data['pri_title'];
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $irc_notice .= '; Assignment: ' . implode(', ', $assignment);
        }
        if (!empty($data['iss_grp_id'])) {
            $irc_notice .= '; Group: ' . Group::getName($data['iss_grp_id']);
        }
        $irc_notice .= '), ';
        if (@isset($data['customer'])) {
            $irc_notice .= $data['customer']['name'] . ', ';
        }
        $irc_notice .= $data['iss_summary'];
        self::notifyIRC($prj_id, $irc_notice, $issue_id, false, false, 'new_issue');
        $data['custom_fields'] = [];// empty place holder so notifySubscribers will fill it in with appropriate data for the user
        $subject = ev_gettext('New Issue');
        // generate new Message-ID
        $message_id = Mail_Helper::generateMessageID();
        $headers = [
            'Message-ID' => $message_id,
        ];

        // remove excluded emails
        $emails = array_diff($emails, $exclude_list);

        self::notifySubscribers($issue_id, $emails, 'new_issue', $data, $subject, false, false, $headers);
    }

    /**
     * Method used to send an email notification to the sender of an
     * email message that was automatically converted into an issue.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The issue ID
     * @param   string $sender The sender of the email message (and the recipient of this notification)
     * @param   string $date The arrival date of the email message
     * @param   string $subject The subject line of the email message
     * @param bool|string $additional_recipient the user who should receive this email who is not the sender of the original email
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
            $tpl->assign([
                'app_title' => Misc::getToolCaption(),
                'data' => $data,
                'sender_name' => Mail_Helper::getName($sender),
                'recipient_name' => Mail_Helper::getName($recipient),
                'is_message_sender' => $is_message_sender,
            ]);

            // figure out if sender has a real account or not
            $sender_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($sender), true);
            if ((!empty($sender_usr_id)) && (Issue::canAccess($issue_id, $sender_usr_id))) {
                $can_access = 1;
            } else {
                $can_access = 0;
            }

            $tpl->assign([
                'sender_can_access' => $can_access,
                'email' => [
                    'date' => $date,
                    'from' => Mime_Helper::decodeQuotedPrintable($sender),
                    'subject' => $subject,
                ],
            ]);

            // change the current locale
            if (!empty($recipient_usr_id)) {
                Language::set(User::getLang($recipient_usr_id));
            } else {
                Language::set(APP_DEFAULT_LOCALE);
            }

            $text_message = $tpl->getTemplateContents();
            $setup = Setup::get()->smtp->toArray();
            $from = self::getFixedFromHeader($issue_id, $setup['from'], 'issue');
            $recipient = Mime_Helper::decodeQuotedPrintable($recipient);
            // TRANSLATORS: %1: $issue_id, %2 = iss_summary
            $subject = ev_gettext('[#%1$s] Issue Created: %2$s', $issue_id, $data['iss_summary']);

            $options = [
                'type' => 'auto_created_issue',
            ];
            self::notifyByMail($text_message, $from, $recipient, $subject, $issue_id, $options);

            Language::restore();
        }
    }

    /**
     * Method used to send an email notification to the sender of a
     * set of email messages that were manually converted into an
     * issue.
     *
     * @param   int $prj_id The project ID
     * @param   int $issue_id The issue ID
     * @param   array $sup_ids The email IDs
     * @param bool|int $customer_id The customer ID
     * @return  array The list of recipient emails
     */
    public static function notifyEmailConvertedIntoIssue($prj_id, $issue_id, $sup_ids, $customer_id = false)
    {
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);

            return $crm->notifyEmailConvertedIntoIssue($issue_id, $sup_ids, $customer_id);
        }

        // build the list of recipients
        $recipients = [];
        $recipient_emails = [];
        foreach ($sup_ids as $sup_id) {
            $senders = Support::getSender([$sup_id]);
            if (count($senders) > 0) {
                $sender_email = Mail_Helper::getEmailAddress($senders[0]);
                $recipients[$sup_id] = $senders[0];
                $recipient_emails[] = $sender_email;
            }
        }

        if (!$recipients) {
            return false;
        }

        $data = Issue::getDetails($issue_id);
        foreach ($recipients as $sup_id => $recipient) {
            $recipient_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($recipient));

            // open text template
            $tpl = new Template_Helper();
            $tpl->setTemplate('notifications/new_auto_created_issue.tpl.text');
            $tpl->assign([
                'data' => $data,
                'sender_name' => Mail_Helper::getName($recipient),
                'app_title' => Misc::getToolCaption(),
                'recipient_name' => Mail_Helper::getName($recipient),
            ]);
            $email_details = Support::getEmailDetails($sup_id);
            $tpl->assign([
                'email' => [
                    'date' => $email_details['sup_date'],
                    'from' => $email_details['sup_from'],
                    'subject' => $email_details['sup_subject'],
                ],
            ]);

            // change the current locale
            if (!empty($recipient_usr_id)) {
                Language::set(User::getLang($recipient_usr_id));
            } else {
                Language::set(APP_DEFAULT_LOCALE);
            }

            // TRANSLATORS: %1 - issue_id, %2 - iss_summary
            $subject = ev_gettext('[#%1$s] Issue Created: %2$s', $issue_id, $data['iss_summary']);
            $text_message = $tpl->getTemplateContents();

            $setup = Setup::get()->smtp->toArray();
            $from = self::getFixedFromHeader($issue_id, $setup['from'], 'issue');
            $options = [
                'save_email_copy' => 1,
                'type' => 'email_converted_to_issue',
            ];
            self::notifyByMail($text_message, $from, $recipient, $subject, $issue_id, $options);
        }
        Language::restore();

        return $recipient_emails;
    }

    /**
     * Method used to send an IRC notification about a blocked email that was
     * saved into an internal note.
     *
     * @api
     * @param   int $issue_id The issue ID
     * @param   string $from The sender of the blocked email message
     */
    public static function notifyIRCBlockedMessage($issue_id, $from)
    {
        $notice = "Issue #$issue_id updated (";
        // also add information about the assignee, if any
        $assignment = Issue::getAssignedUsers($issue_id);
        if (count($assignment) > 0) {
            $notice .= 'Assignment: ' . implode(', ', $assignment) . '; ';
        }
        $notice .= "BLOCKED email from '$from')";
        self::notifyIRC(Issue::getProjectID($issue_id), $notice, $issue_id);
    }

    /**
     * Method used to save the IRC notification message in the queue table.
     *
     * @param   int $project_id the ID of the project
     * @param   string $notice The notification summary that should be displayed on IRC
     * @param   int $issue_id The issue ID
     * @param   bool $usr_id The ID of the user to notify
     * @param   bool|string $category The category of this notification
     * @param   bool|string $type The type of notification (new_issue, etc)
     */
    public static function notifyIRC($project_id, $notice, $issue_id = null, $usr_id = null, $category = false, $type = false)
    {
        // don't save any irc notification if this feature is disabled
        $setup = Setup::get();
        if ($setup['irc_notification'] != 'enabled') {
            return;
        }

        $notice = Workflow::formatIRCMessage($project_id, $notice, $issue_id, $usr_id, $category, $type);

        if ($notice === false) {
            return;
        }

        $params = [
            'ino_prj_id' => $project_id,
            'ino_created_date' => Date_Helper::getCurrentDateGMT(),
            'ino_status' => 'pending',
            'ino_message' => $notice,
            'ino_category' => $category,
        ];

        if ($issue_id) {
            $params['ino_iss_id'] = $issue_id;
        }
        if ($usr_id) {
            $params['ino_target_usr_id'] = $usr_id;
        }

        $stmt = 'INSERT INTO `irc_notice` SET ' . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);
    }

    /**
     * Method used to send an email notification when the account
     * details of an user is changed.
     *
     * @param   int $usr_id The user ID
     */
    public static function notifyUserAccount($usr_id)
    {
        $info = User::getDetails($usr_id);
        $info['projects'] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/updated_account.tpl.text');
        $tpl->assign([
            'app_title' => Misc::getToolCaption(),
            'user' => $info,
        ]);

        // TRANSLATORS: %s - APP_SHORT_NAME
        $subject = ev_gettext('%s: User account information updated', APP_SHORT_NAME);
        $text_message = $tpl->getTemplateContents();
        self::notifyUserByMail($usr_id, $subject, $text_message);
    }

    /**
     * Method used to send an email notification when the account
     * password of an user is changed.
     *
     * @param   int $usr_id The user ID
     * @param   string $password The user' password
     */
    public static function notifyUserPassword($usr_id, $password)
    {
        $info = User::getDetails($usr_id);
        $info['usr_password'] = $password;
        $info['projects'] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/updated_password.tpl.text');
        $tpl->assign([
            'app_title' => Misc::getToolCaption(),
            'user' => $info,
        ]);

        // TRANSLATORS: %s - APP_SHORT_NAME
        $subject = ev_gettext('%s: User account password changed', APP_SHORT_NAME);
        $text_message = $tpl->getTemplateContents();
        self::notifyUserByMail($usr_id, $subject, $text_message);
    }

    /**
     * Method used to send an email notification when a new user
     * account is created.
     *
     * @param   int $usr_id The user ID
     * @param   string $password The user' password
     */
    public static function notifyNewUser($usr_id, $password)
    {
        $info = User::getDetails($usr_id);
        $info['usr_password'] = $password;
        $info['projects'] = Project::getAssocList($usr_id, true, true);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/new_user.tpl.text');
        $tpl->assign([
            'app_title' => Misc::getToolCaption(),
            'user' => $info,
        ]);

        // TRANSLATORS: %s - APP_SHORT_NAME
        $subject = ev_gettext('%s: New User information', APP_SHORT_NAME);
        $text_message = $tpl->getTemplateContents();
        self::notifyUserByMail($usr_id, $subject, $text_message);
    }

    /**
     * Method used to send an email notification when an issue is
     * assigned to an user.
     *
     * @param   array $users The list of users
     * @param   int $issue_id The issue ID
     */
    public static function notifyNewAssignment($users, $issue_id)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $emails = [];
        foreach ($users as $usr_id) {
            if ($usr_id == Auth::getUserID()) {
                continue;
            }
            $prefs = Prefs::get($usr_id);
            if ((!empty($prefs)) && (isset($prefs['receive_assigned_email'][$prj_id])) &&
                    ($prefs['receive_assigned_email'][$prj_id]) && ($usr_id != Auth::getUserID())) {
                $emails[] = User::getFromHeader($usr_id);
            }
        }

        if (!$emails) {
            return;
        }

        // get issue details
        $issue = Issue::getDetails($issue_id);
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/assigned.tpl.text');
        $tpl->assign([
            'app_title' => Misc::getToolCaption(),
            'issue' => $issue,
            'current_user' => User::getFullName(Auth::getUserID()),
        ]);

        foreach ($emails as $email) {
            $text_message = $tpl->getTemplateContents();
            Language::set(User::getLang(User::getUserIDByEmail(Mail_Helper::getEmailAddress($email))));
            // TRANSLATORS: %1 - issue_id, %2: issue summary
            $subject = ev_gettext('[#%1$s] New Assignment: %2$s', $issue_id, $issue['iss_summary']);
            $from = self::getFixedFromHeader($issue_id, '', 'issue');

            $options = [
                'type' => 'assignment',
                'save_email_copy' => true,
            ];
            self::notifyByMail($text_message, $from, $email, $subject, $issue_id, $options);
        }
        Language::restore();
    }

    /**
     * Method used to get the list of subscribers for a given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $type The type of subscription
     * @param   int $min_role Only show subscribers with this role or above
     * @return  array An array containing 2 elements. Each a list of subscribers, separated by commas
     */
    public static function getSubscribers($issue_id, $type = null, $min_role = null)
    {
        $subscribers = [
            'staff' => [],
            'customers' => [],
            'all' => [],
        ];
        $prj_id = Issue::getProjectID($issue_id);
        $stmt = 'SELECT
                    sub_usr_id,
                    usr_full_name,
                    usr_email,
                    pru_role
                 FROM
                    (
                    `subscription`,
                    `user`';

        if ($type) {
            $stmt .= ',
                     `subscription_type`';
        }
        $stmt .= "
                    )
                    LEFT JOIN
                        `project_user`
                    ON
                        (sub_usr_id = pru_usr_id AND pru_prj_id = ?)
                 WHERE
                    sub_usr_id=usr_id AND
                    usr_status = 'active' AND
                    sub_iss_id=?";
        $params = [
            $prj_id, $issue_id,
        ];
        if ($min_role) {
            $stmt .= ' AND
                    pru_role >= ?';
            $params[] = $min_role;
        }
        if ($type) {
            $stmt .= " AND\nsbt_sub_id = sub_id AND
                      sbt_type = ?";
            $params[] = $type;
        }
        try {
            $users = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return [];
        }

        foreach ($users as $user) {
            if ($user['pru_role'] != User::ROLE_CUSTOMER) {
                $subscribers['staff'][] = $user['usr_full_name'];
            } else {
                $subscribers['customers'][] = $user['usr_full_name'];
            }
        }

        if ($min_role == false) {
            $stmt = "SELECT
                        DISTINCT sub_email,
                        usr_full_name,
                        pru_role
                     FROM
                        (
                        `subscription`,
                        `subscription_type`
                        )
                     LEFT JOIN
                        `user`
                     ON
                        usr_email = sub_email
                     LEFT JOIN
                        `project_user`
                     ON
                        usr_id = pru_usr_id AND
                        pru_prj_id = $prj_id
                     WHERE
                        sub_id = sbt_sub_id AND
                        sub_iss_id=?";
            $params = [$issue_id];
            if ($type) {
                $stmt .= " AND\nsbt_type = ?";
                $params[] = $type;
            }
            try {
                $emails = DB_Helper::getInstance()->getAll($stmt, $params);
            } catch (DatabaseException $e) {
                return [];
            }

            foreach ($emails as $email) {
                if (empty($email['sub_email'])) {
                    continue;
                }
                if ((!empty($email['pru_role'])) && ($email['pru_role'] != User::ROLE_CUSTOMER)) {
                    $subscribers['staff'][] = $email['usr_full_name'];
                } else {
                    $subscribers['customers'][] = $email['sub_email'];
                }
            }
        }

        $subscribers['all'] = @implode(', ', array_merge($subscribers['staff'], $subscribers['customers']));
        $subscribers['staff'] = @implode(', ', $subscribers['staff']);
        $subscribers['customers'] = @implode(', ', $subscribers['customers']);

        return $subscribers;
    }

    /**
     * Method used to get the details of a given email notification
     * subscription.
     *
     * @param   int $sub_id The subscription ID
     * @return  array The details of the subscription
     */
    public static function getDetails($sub_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `subscription`
                 WHERE
                    sub_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$sub_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        if ($res['sub_usr_id'] != 0) {
            $user_info = User::getNameEmail($res['sub_usr_id']);
            $res['sub_email'] = $user_info['usr_email'];
        }

        return array_merge($res, self::getSubscribedActions($sub_id));
    }

    /**
     * Method used to get the subscribed actions for a given
     * subscription ID.
     *
     * @param   int $sub_id The subscription ID
     * @return  array The subscribed actions
     */
    public static function getSubscribedActions($sub_id)
    {
        $stmt = 'SELECT
                    sbt_type,
                    1
                 FROM
                    `subscription_type`
                 WHERE
                    sbt_sub_id=?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$sub_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of subscribers for a given issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of subscribers
     */
    public static function getSubscriberListing($issue_id)
    {
        $stmt = 'SELECT
                    sub_id,
                    sub_iss_id,
                    sub_usr_id,
                    sub_email
                 FROM
                    `subscription`
                 WHERE
                    sub_iss_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            if ($row['sub_usr_id'] != 0) {
                $row['sub_email'] = User::getFromHeader($row['sub_usr_id']);
            }

            // need to get the list of subscribed actions now
            $actions = self::getSubscribedActions($row['sub_id']);
            $row['actions'] = implode(', ', array_keys($actions));
        }

        return $res;
    }

    /**
     * Returns if the specified user is notified in this issue.
     *
     * @param   int $issue_id the id of the issue
     * @param   int $usr_id the user to check
     * @return  null|bool if the specified user is notified in the issue
     */
    public static function isUserNotified($issue_id, $usr_id)
    {
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    `subscription`
                 WHERE
                    sub_iss_id=? AND
                    sub_usr_id=?';
        $res = DB_Helper::getInstance()->getOne($stmt, [$issue_id, $usr_id]);

        return $res > 0;
    }

    /**
     * Method used to remove all rows associated with a set of
     * subscription IDs
     *
     * @param   array $items The list of subscription IDs
     * @return  bool
     */
    public static function remove($items)
    {
        $itemlist = DB_Helper::buildList($items);

        $stmt = "SELECT
                    sub_iss_id
                 FROM
                    `subscription`
                 WHERE
                    sub_id IN ($itemlist)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt, $items);

        $usr_id = Auth::getUserID();
        $user_fullname = User::getFullName($usr_id);
        $htt_id = History::getTypeID('notification_removed');
        foreach ($items as $sub_id) {
            $subscriber = self::getSubscriber($sub_id);
            $stmt = 'DELETE FROM
                        `subscription`
                     WHERE
                        sub_id=?';
            DB_Helper::getInstance()->query($stmt, [$sub_id]);

            $stmt = 'DELETE FROM
                        `subscription_type`
                     WHERE
                        sbt_sub_id=?';
            DB_Helper::getInstance()->query($stmt, [$sub_id]);

            History::add($issue_id, $usr_id, $htt_id, 'Notification list entry ({email}) removed by {user}', [
                'email' => $subscriber,
                'user' => $user_fullname,
            ]);
        }
        Issue::markAsUpdated($issue_id);

        return true;
    }

    public static function removeByEmail($issue_id, $email)
    {
        $usr_id = User::getUserIDByEmail($email, true);
        $stmt = 'SELECT
                    sub_id
                 FROM
                    `subscription`
                 WHERE
                    sub_iss_id = ? AND';
        $params = [$issue_id];
        if (empty($usr_id)) {
            $stmt .= '
                    sub_email = ?';
            $params[] = $email;
        } else {
            $stmt .= '
                    sub_usr_id = ?';
            $params[] = $usr_id;
        }
        try {
            $sub_id = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = 'DELETE FROM
                    `subscription`
                 WHERE
                    sub_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$sub_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = 'DELETE FROM
                    `subscription_type`
                 WHERE
                    sbt_sub_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$sub_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        // need to save a history entry for this
        $current_usr_id = Auth::getUserID();
        History::add($issue_id, $current_usr_id, 'notification_removed', 'Notification list entry ({email}) removed by {user}', [
            'email' => $email,
            'user' => User::getFullName($current_usr_id),
        ]);

        Issue::markAsUpdated($issue_id);

        return true;
    }

    /**
     * Returns the email address associated with a notification list
     * subscription, user based or otherwise.
     *
     * @param   int $sub_id The subscription ID
     * @return  string The email address
     */
    public static function getSubscriber($sub_id)
    {
        $stmt = 'SELECT
                    sub_usr_id,
                    sub_email
                 FROM
                    `subscription`
                 WHERE
                    sub_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$sub_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        if (empty($res['sub_usr_id'])) {
            return $res['sub_email'];
        }

        return User::getFromHeader($res['sub_usr_id']);
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

        $params = [$issue_id];
        $stmt = 'SELECT
                    sub_id
                 FROM
                    `subscription`
                 WHERE
                    sub_iss_id = ? AND';
        if ($usr_id) {
            $stmt .= ' sub_usr_id = ?';
            $params[] = $usr_id;
        } else {
            $stmt .= ' sub_email = ?';
            $params[] = $email;
        }
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return null;
        }

        return $res;
    }

    /**
     * Method used to get the full list of possible notification actions.
     *
     * @return  string[] All of the possible notification actions
     */
    public static function getAllActions()
    {
        return [
            'updated',
            'closed',
            'emails',
            'files',
        ];
    }

    /**
     * Method used to get the full list of default notification
     * actions.
     *
     * @param   int $issue_id The ID of the issue the user is being subscribed too
     * @param   string $email The email address of the user to be subscribed
     * @param   string $source The source of this call, "add_unknown_user", "self_assign", "remote_assign", "anon_issue", "issue_update", "issue_from_email", "new_issue", "note", "add_extra_recipients"
     * @return  array The list of default notification actions
     */
    public static function getDefaultActions($issue_id = null, $email = null, $source = null)
    {
        $prj_id = Auth::getCurrentProject();
        $workflow = Workflow::getNotificationActions($prj_id, $issue_id, $email, $source);
        if ($workflow !== null) {
            return $workflow;
        }

        $actions = [];
        $setup = Setup::get();

        if ($setup['update'] == 1) {
            $actions[] = 'updated';
        }
        if ($setup['closed'] == 1) {
            $actions[] = 'closed';
        }
        if ($setup['files'] == 1) {
            $actions[] = 'files';
        }
        if ($setup['emails'] == 1) {
            $actions[] = 'emails';
        }

        return $actions;
    }

    /**
     * Method used to subscribe an user to a set of actions in an issue.
     *
     * @param   int $usr_id The user ID of the person performing this action
     * @param   int $issue_id The issue ID
     * @param   int $subscriber_usr_id The user ID of the subscriber
     * @param   array $actions The list of actions to subscribe this user to
     * @param   bool $add_history Whether to add a history entry about this change or not
     * @return  int 1 if the update worked, -1 otherwise
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

        $stmt = 'SELECT
                    COUNT(sub_id)
                 FROM
                    `subscription`
                 WHERE
                    sub_iss_id=? AND
                    sub_usr_id=?';
        $total = DB_Helper::getInstance()->getOne($stmt, [$issue_id, $subscriber_usr_id]);
        if ($total > 0) {
            return -1;
        }
        $stmt = "INSERT INTO
                    `subscription`
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
            DB_Helper::getInstance()->query($stmt, [$issue_id, $subscriber_usr_id, Date_Helper::getCurrentDateGMT()]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $sub_id = DB_Helper::get_last_insert_id();
        foreach ($actions as $sbt_type) {
            self::addType($sub_id, $sbt_type);
        }
        // need to mark the issue as updated
        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        if ($add_history) {
            History::add($issue_id, $usr_id, 'notification_added', 'Notification list entry ({email}) added by {user}', [
                'email' => User::getFromHeader($subscriber_usr_id),
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to add a new subscriber manually, by using the
     * email notification interface.
     *
     * @param   int $usr_id The user ID of the person performing this change
     * @param   int $issue_id The issue ID
     * @param   string $email The email address to subscribe
     * @param   array $actions The actions to subcribe to
     * @return  int 1 if the update worked, -1 otherwise
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
            $stmt = 'SELECT
                        COUNT(sub_id)
                     FROM
                        `subscription`
                     WHERE
                        sub_iss_id=? AND
                        sub_email=?';
            $total = DB_Helper::getInstance()->getOne($stmt, [$issue_id, $email]);
            if ($total > 0) {
                return -1;
            }
        }
        $stmt = "INSERT INTO
                    `subscription`
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
            DB_Helper::getInstance()->query($stmt, [$issue_id, Date_Helper::getCurrentDateGMT(), $email]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $sub_id = DB_Helper::get_last_insert_id();
        foreach ($actions as $sbt_type) {
            self::addType($sub_id, $sbt_type);
        }
        // need to mark the issue as updated
        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        // FIXME: XSS possible as $email is not escaped for html?
        History::add($issue_id, $usr_id, 'notification_added', "Notification list entry ('{subscriber}') added by {user}", [
            'subscriber' => $email,
            'user' => User::getFullName($usr_id),
        ]);

        return 1;
    }

    /**
     * Method used to add the subscription type to the given
     * subscription.
     *
     * @param   int $sub_id The subscription ID
     * @param   string $type The subscription type
     */
    public static function addType($sub_id, $type)
    {
        $stmt = 'INSERT INTO
                    `subscription_type`
                 (
                    sbt_sub_id,
                    sbt_type
                 ) VALUES (
                    ?, ?
                 )';
        DB_Helper::getInstance()->query($stmt, [$sub_id, $type]);
    }

    /**
     * Method used to update the details of a given subscription.
     *
     * @param   int $issue_id
     * @param   int $sub_id The subscription ID
     * @param   $email
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function update($issue_id, $sub_id, $email)
    {
        $usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($email), true);
        if (!empty($usr_id)) {
            $email = '';
        } else {
            $usr_id = 0;
        }
        $prj_id = Issue::getProjectID($issue_id);

        // call workflow to modify actions or cancel adding this user.
        $actions = [];
        $subscriber_usr_id = false;
        $workflow = Workflow::handleSubscription($prj_id, $issue_id, $subscriber_usr_id, $email, $actions);
        if ($workflow === false) {
            // cancel subscribing the user
            return -2;
        }

        // always set the type of notification to issue-level
        $stmt = "UPDATE
                    `subscription`
                 SET
                    sub_level='issue',
                    sub_email=?,
                    sub_usr_id=?
                 WHERE
                    sub_id=?";
        try {
            DB_Helper::getInstance()->query($stmt, [$email, $usr_id, $sub_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $stmt = 'DELETE FROM
                    `subscription_type`
                 WHERE
                    sbt_sub_id=?';
        DB_Helper::getInstance()->query($stmt, [$sub_id]);
        // now add them all again
        foreach ($_POST['actions'] as $sbt_type) {
            // FIXME: $sbt_type not validated for sane values
            self::addType($sub_id, $sbt_type);
        }
        // need to mark the issue as updated
        Issue::markAsUpdated($issue_id);
        $current_usr_id = Auth::getUserID();
        History::add($issue_id, $current_usr_id, 'notification_updated', "Notification list entry ('{subscriber}') updated by {user}", [
            'subscriber' => self::getSubscriber($sub_id),
            'user' => User::getFullName($current_usr_id),
        ]);

        return 1;
    }

    /**
     * Send email to $usr_id
     *
     * @param int $usr_id
     * @param string $subject
     * @param string $text_message
     */
    private static function notifyUserByMail($usr_id, $subject, $text_message)
    {
        $info = User::getDetails($usr_id);

        // change the current locale
        Language::set(User::getLang($usr_id));

        $to = Mail_Helper::getFormattedName($info['usr_full_name'], $info['usr_email']);

        $builder = new MailBuilder();
        $builder->addTextPart($text_message)
            ->getMessage()
            ->setSubject($subject)
            ->setTo($to);

        Mail_Queue::queue($builder, $to);

        Language::restore();
    }

    /**
     * Common notify method used by Notification class
     *
     * @param string $text_message
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param int $issue_id
     * @param array $options
     * @internal used by reminder_action, otherwise would be private
     */
    public static function notifyByMail($text_message, $from, $to, $subject, $issue_id, $options = [])
    {
        $to = AddressHeader::fromString($to)->getAddress();
        $from = AddressHeader::fromString($from ?: Setup::get()->smtp->from)->getAddress();

        $builder = new MailBuilder();
        $builder->addTextPart($text_message)
            ->getMessage()
            ->setSubject($subject)
            ->setFrom($from->getEmail(), $from->getName())
            ->setTo($to->getEmail(), $to->getName());

        $mail = $builder->toMailMessage();
        $mail->addHeaders(Mail_Helper::getBaseThreadingHeaders($issue_id));

        $options['issue_id'] = $issue_id;

        Mail_Queue::queue($mail, $to, $options);
    }
}
