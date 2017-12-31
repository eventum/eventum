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

use Eventum\Attachment\Attachment;
use Eventum\Attachment\AttachmentManager;
use Eventum\Attachment\Exceptions\AttachmentException;
use Eventum\Db\DatabaseException;
use Eventum\Mail\Exception\RoutingException;
use Eventum\Mail\Helper\AddressHeader;
use Eventum\Mail\Helper\WarningMessage;
use Eventum\Mail\ImapMessage;
use Eventum\Mail\MailBuilder;
use Eventum\Mail\MailMessage;
use Eventum\Monolog\Logger;
use Zend\Mail\AddressList;

/**
 * Class to handle the business logic related to the email feature of
 * the application.
 *
 * NOTE: this class needs splitting to more specific logic
 */
class Support
{
    /**
     * Permanently removes the given support emails from the associated email
     * server.
     *
     * @param   array $sup_ids The list of support emails
     * @return  int 1 if the removal worked, -1 otherwise
     */
    public static function expungeEmails($sup_ids)
    {
        $accounts = [];

        $stmt = 'SELECT
                    sup_id,
                    sup_message_id,
                    sup_ema_id
                 FROM
                    `support_email`
                 WHERE
                    sup_id IN (' . DB_Helper::buildList($sup_ids) . ')';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $sup_ids);
        } catch (DatabaseException $e) {
            return -1;
        }

        foreach ($res as $row) {
            // don't remove emails from the imap/pop3 server if the email
            // account is set to leave a copy of the messages on the server
            $account_details = Email_Account::getDetails($row['sup_ema_id']);
            if (!$account_details['leave_copy']) {
                // try to re-use an open connection to the imap server
                if (!in_array($row['sup_ema_id'], array_keys($accounts))) {
                    $accounts[$row['sup_ema_id']] = self::connectEmailServer(Email_Account::getDetails($row['sup_ema_id'], true));
                }
                $mbox = $accounts[$row['sup_ema_id']];
                if ($mbox !== false) {
                    // now try to find the UID of the current message-id
                    $matches = @imap_search($mbox, 'TEXT "' . $row['sup_message_id'] . '"');
                    if (count($matches) > 0) {
                        foreach ($matches as $match) {
                            $headers = imap_headerinfo($mbox, $match);
                            // if the current message also matches the message-id header, then remove it!
                            if ($headers->message_id == $row['sup_message_id']) {
                                @imap_delete($mbox, $match);
                                break;
                            }
                        }
                    }
                }
            }

            // remove the email record from the table
            self::removeEmail($row['sup_id']);
        }

        return 1;
    }

    /**
     * Removes the given support email from the database table.
     *
     * @param   int $sup_id The support email ID
     * @return  bool
     */
    public static function removeEmail($sup_id)
    {
        $stmt = 'DELETE FROM
                    `support_email`
                 WHERE
                    sup_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$sup_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        $stmt = 'DELETE FROM
                    `support_email_body`
                 WHERE
                    seb_sup_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$sup_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the next and previous messages in order to build
     * side links when viewing a particular email.
     *
     * @param   int $sup_id The email ID
     * @return  array Information on the next and previous messages
     */
    public static function getListingSides($sup_id)
    {
        $options = self::saveSearchParams();

        $stmt = 'SELECT
                    sup_id,
                    sup_ema_id
                 FROM
                    (
                    `support_email`,
                    `email_account`
                    )
                    LEFT JOIN
                        `issue`
                    ON
                        sup_iss_id = iss_id';
        if (!empty($options['keywords'])) {
            $stmt .= ', `support_email_body`';
        }
        $stmt .= self::buildWhereClause($options);
        $stmt .= '
                 ORDER BY
                    ' . $options['sort_by'] . ' ' . $options['sort_order'];
        try {
            $res = DB_Helper::getInstance()->getPair($stmt);
        } catch (DatabaseException $e) {
            return '';
        }

        $email_ids = array_keys($res);
        $index = array_search($sup_id, $email_ids);
        if (!empty($email_ids[$index + 1])) {
            $next = $email_ids[$index + 1];
        }
        if (!empty($email_ids[$index - 1])) {
            $previous = $email_ids[$index - 1];
        }

        return [
            'next' => [
                'sup_id' => @$next,
                'ema_id' => @$res[$next],
            ],
            'previous' => [
                'sup_id' => @$previous,
                'ema_id' => @$res[$previous],
            ],
        ];
    }

    /**
     * Method used to get the next and previous messages in order to build
     * side links when viewing a particular email associated with an issue.
     *
     * @param   int $issue_id The issue ID
     * @param   int $sup_id The email ID
     * @return  array Information on the next and previous messages
     */
    public static function getIssueSides($issue_id, $sup_id)
    {
        $stmt = 'SELECT
                    sup_id,
                    sup_ema_id
                 FROM
                    `support_email`
                 WHERE
                    sup_iss_id=?
                 ORDER BY
                    sup_id ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        $email_ids = array_keys($res);
        $index = array_search($sup_id, $email_ids);
        if (!empty($email_ids[$index + 1])) {
            $next = $email_ids[$index + 1];
        }
        if (!empty($email_ids[$index - 1])) {
            $previous = $email_ids[$index - 1];
        }

        return [
            'next' => [
                'sup_id' => @$next,
                'ema_id' => @$res[$next],
            ],
            'previous' => [
                'sup_id' => @$previous,
                'ema_id' => @$res[$previous],
            ],
        ];
    }

    /**
     * Method used to get the sender of a given set of emails.
     *
     * @param   int[] $sup_ids The email IDs
     * @return  array The 'From:' headers for those emails
     */
    public static function getSender($sup_ids)
    {
        $stmt = 'SELECT
                    sup_from
                 FROM
                    `support_email`
                 WHERE
                    sup_id IN (' . DB_Helper::buildList($sup_ids) . ')';
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $sup_ids);
        } catch (DatabaseException $e) {
            return [];
        }

        if (empty($res)) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to restore the specified support emails from
     * 'removed' to 'active'.
     *
     * @return  int 1 if the update worked, -1 otherwise
     */
    public static function restoreEmails()
    {
        $items = $_POST['item'];
        $list = DB_Helper::buildList($items);
        $stmt = "UPDATE
                    `support_email`
                 SET
                    sup_removed=0
                 WHERE
                    sup_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to get the list of support email entries that are
     * set as 'removed'.
     *
     * @return  array The list of support emails
     */
    public static function getRemovedList()
    {
        $stmt = 'SELECT
                    sup_id,
                    sup_date,
                    sup_subject,
                    sup_from
                 FROM
                    `support_email`,
                    `email_account`
                 WHERE
                    ema_prj_id=? AND
                    ema_id=sup_ema_id AND
                    sup_removed=1';
        $params = [Auth::getCurrentProject()];
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to remove all support email entries associated with
     * a specified list of support email accounts.
     *
     * @param   array $ids The list of support email accounts
     * @return  bool
     */
    public static function removeEmailByAccounts($ids)
    {
        if (count($ids) < 1) {
            return true;
        }

        $stmt = 'DELETE FROM
                    `support_email`
                 WHERE
                    sup_ema_id IN (' . DB_Helper::buildList($ids) . ')';
        try {
            DB_Helper::getInstance()->query($stmt);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to build the server URI to connect to.
     *
     * @param   array $info The email server information
     * @return  string The server URI to connect to
     */
    public static function getServerURI($info)
    {
        $server_uri = $info['ema_hostname'] . ':' . $info['ema_port'] . '/' . strtolower($info['ema_type']);
        if (stripos($info['ema_type'], 'imap') !== false) {
            $folder = $info['ema_folder'];
        } else {
            $folder = 'INBOX';
        }

        return '{' . $server_uri . '}' . $folder;
    }

    /**
     * Method used to connect to the provided email server.
     *
     * @param   array $info The email server information
     * @return  resource The email server connection
     */
    public static function connectEmailServer($info)
    {
        $mbox = @imap_open(self::getServerURI($info), $info['ema_username'], $info['ema_password']);
        if ($mbox === false) {
            $error = @imap_last_error();
            Logger::app()->error("Error while connecting to the email server - {$error}");
        }

        return $mbox;
    }

    /**
     * Method used to get the total number of emails in the specified
     * mailbox.
     *
     * @param   resource $mbox The mailbox
     * @return  int The number of emails
     */
    public static function getTotalEmails($mbox)
    {
        return @imap_num_msg($mbox);
    }

    /**
     * Method used to get new emails from the mailbox.
     *
     * @param  resource $mbox The mailbox
     * @return array array of new message numbers
     */
    public static function getNewEmails($mbox)
    {
        return @imap_search($mbox, 'UNSEEN UNDELETED UNANSWERED');
    }

    /**
     * Bounce message to sender.
     *
     * @param ImapMessage $mail
     * @param RoutingException $e error to bounce
     */
    private static function bounceMessage(ImapMessage $mail, RoutingException $e)
    {
        // open text template
        $tpl = new Template_Helper();
        $tpl->setTemplate('notifications/bounced_email.tpl.text');
        $tpl->assign([
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'date' => $mail->getMailDate(),
            'subject' => $mail->subject,
            'from' => $mail->from,
            'to' => $mail->to,
            'cc' => $mail->cc,
        ]);

        $sender_email = $mail->getSender();
        $usr_id = User::getUserIDByEmail($sender_email);
        // change the current locale
        if ($usr_id) {
            Language::set(User::getLang($usr_id));
        }

        // TRANSLATORS: %s: APP_SHORT_NAME
        $subject = ev_gettext('%s: Postmaster notify: see transcript for details', APP_SHORT_NAME);

        $builder = new MailBuilder();
        $builder->addTextPart($tpl->getTemplateContents())
            ->getMessage()
            ->setSubject($subject)
            ->setTo($sender_email);

        Mail_Queue::queue($builder, $sender_email);

        if ($usr_id) {
            Language::restore();
        }
    }

    /**
     * Method used to get the information about a specific message
     * from a given mailbox.
     *
     * XXX this function does more than that.
     *
     * @param   ImapMessage $mail The Mail object
     * @param   array $info The support email account information
     */
    public static function processMailMessage(ImapMessage $mail, $info)
    {
        $logger = Logger::app();

        AuthCookie::setAuthCookie(APP_SYSTEM_USER_ID);

        $message_id = $mail->messageId;

        // check if the current message was already seen
        if ($info['ema_get_only_new'] && $mail->isSeen()) {
            $logger->debug("Skip $message_id: processing only new mails and already Seen.");

            return;
        }

        // if message_id already exists, return immediately -- nothing to do
        if (self::exists($message_id) || Note::exists($message_id)) {
            $logger->debug("Skip $message_id: Already exists as email or note.");

            return;
        }

        // pass in $mail object so it can be modified
        $workflow = Workflow::preEmailDownload($mail->getProjectId(), $mail);
        if ($workflow === -1) {
            $logger->debug("Skip $message_id: Skipped by workflow");

            return;
        }

        // route emails if necessary
        if ($info['ema_use_routing'] == 1) {
            try {
                $routed = Routing::route($mail);
            } catch (RoutingException $e) {
                $logger->debug("Skip $message_id: RoutingException: {$e->getMessage()}");

                // "if leave copy of emails on IMAP server" is "off",
                // then we can bounce on the message
                // otherwise proper would be to create table -
                // eventum_bounce: bon_id, bon_message_id, bon_error
                if (!$info['ema_leave_copy']) {
                    self::bounceMessage($mail, $e);
                    $mail->deleteMessage();
                }

                return;
            }

            // the mail was routed
            if ($routed === true) {
                $logger->debug("Routed $message_id");

                if (!$info['ema_leave_copy']) {
                    $logger->debug("$message_id: Delete from IMAP/POP3");
                    $mail->deleteMessage();
                }

                return;
            }

            // no match for issue-, note-, draft- routing,
            // continue to allow routing and issue auto creating from same account,
            // but it will download email, store it in database and do nothing with it
            // if it does not match support@ address.
            // by "do nothing" it is meant that the mail will be downloaded each time
            // the mails are processed from imap account.
        }

        /** @var string $sender_email */
        $sender_email = $mail->getSender();

        $t = [
            'ema_id' => $mail->getEmailAccountId(),
            'date' => Date_Helper::convertDateGMT($mail->getMailDate()),
            // these below are likely unused by Support::insertEmail
            'message_id' => $mail->messageId,
            'from' => $mail->from,
            'to' => $mail->to,
            'cc' => $mail->cc,
            'subject' => $mail->subject,
            'body' => $mail->getMessageBody(),
            'full_email' => $mail->getRawContent(),
            // the following items are not inserted, but useful in some methods
//            'headers' => $mail->getHeadersArray(),
        ];

        $info['date'] = $t['date'];
        $should_create_array = self::createIssueFromEmail($mail, $info);
        $should_create_issue = $should_create_array['should_create_issue'];

        if (!empty($should_create_array['issue_id'])) {
            $t['issue_id'] = $should_create_array['issue_id'];

            // figure out if we should change to a different email account
            $iss_prj_id = Issue::getProjectID($t['issue_id']);
            if ($info['ema_prj_id'] != $iss_prj_id) {
                $new_ema_id = Email_Account::getEmailAccount($iss_prj_id);
                if (!empty($new_ema_id)) {
                    $t['ema_id'] = $new_ema_id;
                }
            }
        }
        if (!empty($should_create_array['customer_id'])) {
            $t['customer_id'] = $should_create_array['customer_id'];
        }
        if (empty($t['issue_id'])) {
            $t['issue_id'] = 0;
        } else {
            $prj_id = Issue::getProjectID($t['issue_id']);
            AuthCookie::setAuthCookie(APP_SYSTEM_USER_ID);
            AuthCookie::setProjectCookie($prj_id);
        }
        if ($should_create_array['type'] === 'note') {
            // assume that this is not a valid note
            $res = -1;

            if ($t['issue_id'] != 0) {
                // check if this is valid user
                $usr_id = User::getUserIDByEmail($sender_email);
                if (!empty($usr_id)) {
                    $role_id = User::getRoleByUser($usr_id, $prj_id);
                    if ($role_id > User::ROLE_CUSTOMER) {
                        // actually a valid user so insert the note

                        AuthCookie::setAuthCookie($usr_id);
                        AuthCookie::setProjectCookie($prj_id);

                        $users = Project::getUserEmailAssocList($prj_id, 'active', User::ROLE_CUSTOMER);
                        $user_emails = Misc::lowercase(array_values($users));
                        $users = array_flip($users);

                        $addresses = [];

                        $to_addresses = AddressHeader::fromString($mail->to)->getEmails();
                        if ($to_addresses) {
                            $addresses = $to_addresses;
                        }
                        $cc_addresses = AddressHeader::fromString($mail->cc)->getEmails();
                        if ($cc_addresses) {
                            $addresses = array_merge($addresses, $cc_addresses);
                        }

                        $cc_users = [];
                        foreach ($addresses as $email) {
                            if (in_array(strtolower($email), $user_emails)) {
                                $cc_users[] = $users[strtolower($email)];
                            }
                        }

                        // XXX FIXME, this is not nice thing to do
                        $_POST = [
                            'title' => Mail_Helper::removeExcessRe($t['subject']),
                            'note' => $t['body'],
                            'note_cc' => $cc_users,
                            'add_extra_recipients' => 'yes',
                            'message_id' => $t['message_id'],
                            'parent_id' => $should_create_array['parent_id'],
                        ];
                        $res = Note::insertFromPost($usr_id, $t['issue_id']);

                        // need to handle attachments coming from notes as well
                        if ($res != -1) {
                            self::extractAttachments($t['issue_id'], $mail, true, $res);
                        }
                    }
                }
            }
        } else {
            // check if we need to block this email
            if ($should_create_issue == true || !self::blockEmailIfNeeded($mail, $t['issue_id'])) {
                if ($t['issue_id']) {
                    Mail_Helper::rewriteThreadingHeaders($mail, $t['issue_id'], 'email');
                }

                // make variable available for workflow to be able to detect whether this email created new issue
                $t['should_create_issue'] = $should_create_array['should_create_issue'];

                $sup_id = self::insertEmail($mail, $t);
                $res = 1;
                // only extract the attachments from the email if we are associating the email to an issue
                if (!empty($t['issue_id'])) {
                    self::extractAttachments($t['issue_id'], $mail);

                    // notifications about new emails are always external
                    $internal_only = false;
                    $assignee_only = false;
                    // special case when emails are bounced back, so we don't want a notification to customers about those
                    if ($mail->isBounceMessage()) {
                        // broadcast this email only to the assignees for this issue
                        $internal_only = true;
                        $assignee_only = true;
                    } elseif ($should_create_issue == true) {
                        // if a new issue was created, only send a copy of the email to the assignee (if any), don't resend to the original TO/CC list
                        $assignee_only = true;
                        $internal_only = true;
                    }

                    if (Workflow::shouldAutoAddToNotificationList($info['ema_prj_id'])) {
                        self::addExtraRecipientsToNotificationList($info['ema_prj_id'], $t, $should_create_issue);
                    }

                    if (self::isAllowedToEmail($t['issue_id'], $sender_email)) {
                        $t['internal_only'] = $internal_only;
                        $t['assignee_only'] = $assignee_only;
                        $t['sup_id'] = $sup_id;
                        $t['usr_id'] = Auth::getUserID();
                        Notification::notifyNewEmail($mail, $t);
                    }

                    // try to get usr_id of sender, if not, use system account
                    $addr = $mail->getSender();
                    $usr_id = User::getUserIDByEmail($addr) ?: APP_SYSTEM_USER_ID;

                    // mark this issue as updated if only if this email wasn't used to open it
                    if (!$should_create_issue) {
                        if ((!empty($t['customer_id'])) && ($t['customer_id'] != 'NULL') && ((empty($usr_id)) || (User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER))) {
                            Issue::markAsUpdated($t['issue_id'], 'customer action');
                        } else {
                            if ((!empty($usr_id)) && (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER)) {
                                Issue::markAsUpdated($t['issue_id'], 'staff response');
                            } else {
                                Issue::markAsUpdated($t['issue_id'], 'user response');
                            }
                        }
                    }
                    // log routed email
                    History::add($t['issue_id'], $usr_id, 'email_routed', 'Email routed from {from}', [
                        'from' => $mail->from,
                    ]);
                }
            } else {
                $res = 1;
            }
        }

        if ($res > 0) {
            $mail->deleteMessage();
        }
    }

    /**
     * Creates a new issue from an email if appropriate.
     * Also returns if this message is related to a previous message.
     *
     * @param   MailMessage $mail The Mail object
     * @param   array $info an array of info about the email account
     * - int $ema_prj_id
     * - int $ema_id
     * - string $ema_issue_auto_creation
     * - array $ema_issue_auto_creation_options
     * - string $date email date (if set, overrides $mail->date)
     * @return  array   An array of information about the message
     */
    public static function createIssueFromEmail(MailMessage $mail, $info)
    {
        $should_create_issue = false;
        $issue_id = null;
        $associate_email = '';
        $type = 'email';
        $parent_id = '';
        $customer_id = false;
        $contact_id = false;
        $contract_id = false;
        $severity = false;
        $references = $mail->getAllReferences();

        $workflow = Workflow::getIssueIDforNewEmail($info['ema_prj_id'], $info, $mail);
        if (is_array($workflow)) {
            if (isset($workflow['customer_id'])) {
                $customer_id = $workflow['customer_id'];
            }
            if (isset($workflow['contract_id'])) {
                $contract_id = $workflow['contract_id'];
            }
            if (isset($workflow['contact_id'])) {
                $contact_id = $workflow['contact_id'];
            }
            if (isset($workflow['severity'])) {
                $severity = $workflow['severity'];
            }
            if (isset($workflow['should_create_issue'])) {
                $should_create_issue = $workflow['should_create_issue'];
            } else {
                $should_create_issue = true;
            }
        } elseif ($workflow == 'new') {
            $should_create_issue = true;
        } elseif (is_numeric($workflow)) {
            $issue_id = $workflow;
        } else {
            $setup = Setup::get();
            if ($setup['subject_based_routing']['status'] == 'enabled'
                and (preg_match("/\[#(\d+)\]( Note| BLOCKED)*/", $mail->subject, $matches))) {
                // look for [#XXXX] in the subject line
                $should_create_issue = false;
                $issue_id = $matches[1];
                if (!Issue::exists($issue_id, false)) {
                    $issue_id = '';
                } elseif (!empty($matches[2])) {
                    $type = 'note';
                }
            } else {
                // - if this email is a reply:
                if (count($references) > 0) {
                    foreach ($references as $reference_msg_id) {
                        //  -> check if the replied email exists in the database:
                        if (Note::exists($reference_msg_id)) {
                            // note exists
                            // get what issue it belongs too.
                            $issue_id = Note::getIssueByMessageID($reference_msg_id);
                            $should_create_issue = false;
                            $type = 'note';
                            $parent_id = Note::getIDByMessageID($reference_msg_id);
                            break;
                        } elseif (self::exists($reference_msg_id) || Issue::getIssueByRootMessageID($reference_msg_id) != false) {
                            // email or issue exists
                            $issue_id = self::getIssueByMessageID($reference_msg_id);
                            if (empty($issue_id)) {
                                $issue_id = Issue::getIssueByRootMessageID($reference_msg_id);
                            }
                            if (empty($issue_id)) {
                                // parent email isn't associated with issue.
                                //      --> create new issue, associate current email and replied email to this issue
                                $should_create_issue = true;
                                $associate_email = $reference_msg_id;
                            } else {
                                // parent email is associated with issue:
                                //      --> associate current email with existing issue
                                $should_create_issue = false;
                            }
                            break;
                        }
                        //  no matching note, email or issue:
                        //    => create new issue and associate current email with it
                        $should_create_issue = true;
                    }
                } else {
                    // - if this email is not a reply:
                    //  -> create new issue and associate current email with it
                    $should_create_issue = true;
                }
            }
        }

        $sender_email = $mail->getSender();

        // only create a new issue if this email is coming from a known customer
        $prj_id = $info['ema_prj_id'];
        if ($should_create_issue
            && $info['ema_issue_auto_creation_options']['only_known_customers'] == 'yes'
            && CRM::hasCustomerIntegration($prj_id)
            && !$customer_id) {
            try {
                CRM::getInstance($prj_id);
                $should_create_issue = true;
            } catch (CRMException $e) {
                $should_create_issue = false;
            }
        }

        // check whether we need to create a new issue or not
        if ($info['ema_issue_auto_creation'] == 'enabled'
            && $should_create_issue && !$mail->isBounceMessage()) {
            $options = Email_Account::getIssueAutoCreationOptions($info['ema_id']);
            AuthCookie::setAuthCookie(APP_SYSTEM_USER_ID);
            AuthCookie::setProjectCookie($prj_id);

            $options += [
                'prj_id' => $prj_id,
                'usr_id' => APP_SYSTEM_USER_ID,
                'severity' => $severity,
                'customer_id' => $customer_id,
                'contact_id' => $contact_id,
                'contract_id' => $contract_id,
            ];
            if (isset($info['date'])) {
                $options['date'] = $info['date'];
            }
            $issue_id = Issue::createFromEmail($mail, $options);

            // add sender to authorized repliers list if they are not a real user
            $sender_usr_id = User::getUserIDByEmail($sender_email, true);
            if (empty($sender_usr_id)) {
                Authorized_Replier::manualInsert($issue_id, $sender_email, false);
            }
            // associate any existing replied-to email with this new issue
            if ((!empty($associate_email)) && (!empty($reference_issue_id))) { // $reference_issue_id never defined
                $reference_sup_id = self::getIDByMessageID($associate_email);
                self::associate(APP_SYSTEM_USER_ID, $issue_id, [$reference_sup_id]);
            }
        }

        // need to check crm for customer association
        if ($sender_email) {
            // FIXME: how can $sender_email be empty?
            if (CRM::hasCustomerIntegration($prj_id) && !$customer_id) {
                // check for any customer contact association
                try {
                    $crm = CRM::getInstance($prj_id);
                    $contact = $crm->getContactByEmail($sender_email);
                    $contact_id = $contact->getContactID();
                    $contracts = $contact->getContracts([CRM_EXCLUDE_EXPIRED]);
                    if (count($contracts) > 0) {
                        $contract = $contracts[0];
                        $customer_id = $contract->getCustomerID();
                    }
                } catch (CRMException $e) {
                    $customer_id = null;
                    $contact_id = null;
                }
            }
        }

        return [
            'should_create_issue' => $should_create_issue,
            'associate_email' => $associate_email,
            'issue_id' => $issue_id,
            'customer_id' => $customer_id,
            'contact_id' => $contact_id,
            'type' => $type,
            'parent_id' => $parent_id,
        ];
    }

    /**
     * Checks if a message already is downloaded.
     *
     * @param   string $message_id The Message-ID header
     * @return  bool
     */
    public static function exists($message_id)
    {
        $sql = 'SELECT
                    count(*)
                FROM
                    `support_email`
                WHERE
                    sup_message_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$message_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res > 0) {
            return true;
        }

        return false;
    }

    /**
     * Method used to add a new support email to the system.
     *
     * @param   MailMessage $mail The Mail object
     * @param   array $email_options The support email details:
     * - int customer_id
     * - int issue_id
     * - int ema_id
     * - int date
     * - bool $closing If this email comes from closing the issue
     * - bool has_attachment used if defined, otherwise calls $mail->hasAttachments()
     * - string cc (overwrites $mail->cc) !!!
     * @return  int The support ID inserted to database
     */
    public static function insertEmail(MailMessage $mail, $email_options)
    {
        $closing = isset($email_options['closing']) ? $email_options['closing'] : false;

        $usr_id = User::getUserIDByEmail($mail->getSender());

        if (!empty($usr_id) && empty($email_options['customer_id'])) {
            $email_options['customer_id'] = User::getCustomerID($usr_id);
        }
        if (empty($email_options['customer_id'])) {
            $email_options['customer_id'] = null;
        }
        $issue_id = isset($email_options['issue_id']) ? $email_options['issue_id'] : null;

        // try to get the parent ID
        $reference_message_id = $mail->getReferenceMessageID();
        $parent_id = null;
        if ($reference_message_id) {
            $parent_id = self::getIDByMessageID($reference_message_id);
            // make sure it is in the same issue
            if ($parent_id && (!$issue_id || $issue_id != self::getIssueFromEmail($parent_id))) {
                $parent_id = null;
            }
        }

        $has_attachments = isset($email_options['has_attachment']) ? (int)$email_options['has_attachment'] : $mail->getAttachment()->hasAttachments();
        $params = [
            'sup_ema_id' => $email_options['ema_id'],
            'sup_iss_id' => $issue_id,
            'sup_customer_id' => $email_options['customer_id'],
            'sup_message_id' => $mail->messageId,
            // note: don't be tempted to use $mail->getDate()
            // because $mail could be imapmessage where we require $mail->getMailDate()
            'sup_date' => $email_options['date'],
            'sup_from' => $mail->getSender(),
            'sup_to' => $mail->to,
            'sup_cc' => $mail->cc,
            'sup_subject' => $mail->subject,
            'sup_has_attachment' => $has_attachments,
        ];

        if ($parent_id) {
            $params['sup_parent_id'] = $parent_id;
        }

        if (!empty($usr_id)) {
            $params['sup_usr_id'] = $usr_id;
        }

        if (isset($email_options['cc'])) {
            $params['sup_cc'] = $email_options['cc'];
        }

        $stmt = 'INSERT INTO `support_email` SET ' . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);

        $sup_id = DB_Helper::get_last_insert_id();

        $stmt = 'INSERT INTO
                    `support_email_body`
                 (
                    seb_sup_id,
                    seb_body,
                    seb_full_email
                 ) VALUES (
                    ?, ?, ?
                 )';
        $params = [$sup_id, $mail->getMessageBody(), $mail->getRawContent()];
        DB_Helper::getInstance()->query($stmt, $params);

        if ($issue_id) {
            $prj_id = Issue::getProjectID($issue_id);
        } elseif (!empty($email_options['ema_id'])) {
            $prj_id = Email_Account::getProjectID($email_options['ema_id']);
        } else {
            $prj_id = false;
        }

        // FIXME: $row['ema_id'] is empty when mail is sent via convert note!
        if ($prj_id !== false) {
            $email_options['sup_id'] = $sup_id;

            Workflow::handleNewEmail($prj_id, $issue_id, $mail, $email_options, $closing);
        }

        return $sup_id;
    }

    /**
     * Method used to get a specific parameter in the email listing
     * cookie.
     *
     * @param   string $name The name of the parameter
     * @return  mixed The value of the specified parameter
     */
    public static function getParam($name)
    {
        if (isset($_GET[$name])) {
            return $_GET[$name];
        } elseif (isset($_POST[$name])) {
            return $_POST[$name];
        } elseif (($profile = Search_Profile::getProfile(Auth::getUserID(), Auth::getCurrentProject(), 'email')) && (isset($profile[$name]))) {
            return $profile[$name];
        }

        return '';
    }

    /**
     * Method used to save the current search parameters in a cookie.
     *
     * TODO: Merge with Search::saveSearchParams()
     *
     * @return  array The search parameters
     */
    public static function saveSearchParams()
    {
        $sort_by = self::getParam('sort_by');
        $sort_order = self::getParam('sort_order');
        $rows = self::getParam('rows');
        $cookie = [
            'rows' => $rows ? $rows : APP_DEFAULT_PAGER_SIZE,
            'pagerRow' => self::getParam('pagerRow'),
            'hide_associated' => self::getParam('hide_associated'),
            'sort_by' => $sort_by ? $sort_by : 'sup_date',
            'sort_order' => $sort_order ? $sort_order : 'DESC',
            // quick filter form options
            'keywords' => self::getParam('keywords'),
            'sender' => self::getParam('sender'),
            'to' => self::getParam('to'),
            'ema_id' => self::getParam('ema_id'),
            'filter' => self::getParam('filter'),
        ];
        // now do some magic to properly format the date fields
        $date_fields = [
            'arrival_date',
        ];
        foreach ($date_fields as $field_name) {
            $field = self::getParam($field_name);
            if ((empty($field)) || ($cookie['filter'][$field_name] != 'yes')) {
                continue;
            }
            $end_field_name = $field_name . '_end';
            $end_field = self::getParam($end_field_name);
            @$cookie[$field_name] = [
                'Year' => $field['Year'],
                'Month' => $field['Month'],
                'Day' => $field['Day'],
                'start' => $field['Year'] . '-' . $field['Month'] . '-' . $field['Day'],
                'filter_type' => $field['filter_type'],
                'end' => $end_field['Year'] . '-' . $end_field['Month'] . '-' . $end_field['Day'],
            ];
            @$cookie[$end_field_name] = [
                'Year' => $end_field['Year'],
                'Month' => $end_field['Month'],
                'Day' => $end_field['Day'],
            ];
        }
        Search_Profile::save(Auth::getUserID(), Auth::getCurrentProject(), 'email', $cookie);

        return $cookie;
    }

    /**
     * Method used to get the list of emails to be displayed in the
     * grid layout.
     *
     * @param   array $options The search parameters
     * @param   int $current_row The current page number
     * @param   int $max The maximum number of rows per page
     * @return  array The list of issues to be displayed
     */
    public static function getEmailListing($options, $current_row = 0, $max = 5)
    {
        $prj_id = Auth::getCurrentProject();
        if ($max == 'ALL') {
            $max = 9999999;
        }
        $start = $current_row * $max;

        $stmt = 'SELECT
                    sup_id,
                    sup_ema_id,
                    sup_iss_id,
                    sup_customer_id,
                    sup_from,
                    sup_date,
                    sup_to,
                    sup_subject,
                    sup_has_attachment
                 FROM
                    (
                    `support_email`,
                    `email_account`';
        if (!empty($options['keywords'])) {
            $stmt .= ', `support_email_body` ';
        }
        $stmt .= '
                    )
                    LEFT JOIN
                        `issue`
                    ON
                        sup_iss_id = iss_id';
        $stmt .= self::buildWhereClause($options);
        $stmt .= '
                 ORDER BY
                    ' . Misc::escapeString($options['sort_by']) . ' ' . DB_Helper::orderBy($options['sort_order']);
        $total_rows = Pager::getTotalRows($stmt);
        $stmt .= '
                 LIMIT
                    ' . Misc::escapeInteger($max) . ' OFFSET ' . Misc::escapeInteger($start);
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return [
                'list' => '',
                'info' => '',
            ];
        }

        if (count($res) < 1 && $current_row > 0) {
            // if there are no results, and the page is not the first page reset page to one and reload results
            Auth::redirect("emails.php?pagerRow=0&rows=$max");
        }

        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            $customer_ids = [];
            foreach ($res as $row) {
                if ((!empty($row['sup_customer_id'])) && (!in_array($row['sup_customer_id'], $customer_ids))) {
                    $customer_ids[] = $row['sup_customer_id'];
                }
            }
            if (count($customer_ids) > 0) {
                $company_titles = $crm->getCustomerTitles($customer_ids);
            }
        }

        foreach ($res as &$row) {
            $row['sup_from'] = implode(', ', Mail_Helper::getName($row['sup_from'], true));
            if ((empty($row['sup_to'])) && (!empty($row['sup_iss_id']))) {
                $row['sup_to'] = ev_gettext('Notification List');
            }
            if (CRM::hasCustomerIntegration($prj_id)) {
                $row['customer_title'] = isset($company_titles[$row['sup_customer_id']]) ? $company_titles[$row['sup_customer_id']] : '';
            }
        }

        $total_pages = ceil($total_rows / $max);
        $last_page = $total_pages - 1;

        return [
            'list' => $res,
            'info' => [
                'current_page' => $current_row,
                'start_offset' => $start,
                'end_offset' => $start + count($res),
                'total_rows' => $total_rows,
                'total_pages' => $total_pages,
                'previous_page' => ($current_row == 0) ? '-1' : ($current_row - 1),
                'next_page' => ($current_row == $last_page) ? '-1' : ($current_row + 1),
                'last_page' => $last_page,
            ],
        ];
    }

    /**
     * Method used to get the list of emails to be displayed in the grid layout.
     *
     * @param   array $options The search parameters
     * @return  string The where clause
     */
    public static function buildWhereClause($options)
    {
        $stmt = '
                 WHERE
                    sup_removed=0 AND
                    sup_ema_id=ema_id AND
                    ema_prj_id=' . Auth::getCurrentProject();
        if (!empty($options['hide_associated'])) {
            $stmt .= ' AND sup_iss_id = 0';
        }
        if (!empty($options['keywords'])) {
            $stmt .= ' AND sup_id=seb_sup_id ';
            $stmt .= ' AND (' . Misc::prepareBooleanSearch('sup_subject', $options['keywords']);
            $stmt .= ' OR ' . Misc::prepareBooleanSearch('seb_body', $options['keywords']) . ')';
        }
        if (!empty($options['sender'])) {
            $stmt .= ' AND ' . Misc::prepareBooleanSearch('sup_from', $options['sender']);
        }
        if (!empty($options['to'])) {
            $stmt .= ' AND ' . Misc::prepareBooleanSearch('sup_to', $options['to']);
        }
        if (!empty($options['ema_id'])) {
            $stmt .= ' AND sup_ema_id=' . $options['ema_id'];
        }
        if ((!empty($options['filter'])) && ($options['filter']['arrival_date'] == 'yes')) {
            switch ($options['arrival_date']['filter_type']) {
                case 'greater':
                    $stmt .= " AND sup_date >= '" . $options['arrival_date']['start'] . "'";
                    break;
                case 'less':
                    $stmt .= " AND sup_date <= '" . $options['arrival_date']['start'] . "'";
                    break;
                case 'between':
                    $stmt .= " AND sup_date BETWEEN '" . $options['arrival_date']['start'] . "' AND '" . $options['arrival_date']['end'] . "'";
                    break;
            }
        }

        // handle 'private' issues.
        if (Auth::getCurrentRole() < User::ROLE_MANAGER) {
            $stmt .= " AND iss_access_level = 'normal'";
        }

        return $stmt;
    }

    /**
     * Method used to extract and associate attachments in an email
     * to the given issue.
     *
     * @param   int $issue_id The issue ID
     * @param   MailMessage $mail The Mail object
     * @param   bool $internal_only Whether these files are supposed to be internal only or not
     * @param   int $associated_note_id The note ID that these attachments should be associated with
     */
    public static function extractAttachments($issue_id, MailMessage $mail, $internal_only = false, $associated_note_id = null)
    {
        // figure out who should be the 'owner' of this attachment
        $sender_email = $mail->getSender();
        $usr_id = User::getUserIDByEmail($sender_email);
        $prj_id = Issue::getProjectID($issue_id);
        $unknown_user = false;
        if (!$usr_id) {
            if (CRM::hasCustomerIntegration($prj_id)) {
                // try checking if a customer technical contact has this email associated with it
                try {
                    $crm = CRM::getInstance($prj_id);
                    $contact = $crm->getContactByEmail($sender_email);
                    $usr_id = User::getUserIDByContactID($contact->getContactID());
                } catch (CRMException $e) {
                    $usr_id = null;
                }
            }
            if (!$usr_id) {
                // if we couldn't find a real customer by that email, set the usr_id to be the system user id,
                // and store the actual email address in the unknown_user field.
                $usr_id = APP_SYSTEM_USER_ID;
                $unknown_user = $sender_email;
            }
        }

        // now for the real thing
        if ($mail->getAttachment()->hasAttachments()) {
            if (empty($associated_note_id)) {
                $history_log = ev_gettext('Attachment originated from an email');
            } else {
                $history_log = ev_gettext('Attachment originated from a note');
            }

            $iaf_ids = [];
            foreach ($mail->getAttachment()->getAttachments() as $attachment) {
                $attach = Workflow::shouldAttachFile($prj_id, $issue_id, $usr_id, $attachment);
                if (!$attach) {
                    continue;
                }
                try {
                    $iaf_ids[] = Attachment::create($attachment['filename'], $attachment['filetype'], $attachment['blob'])->id;
                } catch (AttachmentException $e) {
                    continue;
                }
            }

            if ($iaf_ids) {
                if ($internal_only) {
                    $min_role = User::ROLE_USER;
                } else {
                    $min_role = User::ROLE_VIEWER;
                }
                AttachmentManager::attachFiles($issue_id, $usr_id, $iaf_ids, $min_role, $history_log,
                    ['unknown_user' => $unknown_user, 'associated_note_id' => $associated_note_id]);
            }

            // mark the note as having attachments (poor man's caching system)
            if ($associated_note_id != false) {
                Note::setAttachmentFlag($associated_note_id);
            }
        }
    }

    /**
     * Method used to silently associate a support email with an
     * existing issue.
     *
     * @param   int $usr_id The user ID of the person performing this change
     * @param   int $issue_id The issue ID
     * @param   array $sup_ids The list of email IDs to associate
     * @return  int 1 if it worked, -1 otherwise
     */
    public static function associateEmail($usr_id, $issue_id, $sup_ids)
    {
        $list = DB_Helper::buildList($sup_ids);
        $stmt = "UPDATE
                    `support_email`
                 SET
                    sup_iss_id=$issue_id
                 WHERE
                    sup_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $sup_ids);
        } catch (DatabaseException $e) {
            return -1;
        }

        foreach ($sup_ids as $sup_id) {
            $mail = self::getSupportEmail($sup_id);
            self::extractAttachments($issue_id, $mail);
        }

        Issue::markAsUpdated($issue_id, 'email');
        // save a history entry for each email being associated to this issue
        $stmt = "SELECT
                    sup_subject
                 FROM
                    `support_email`
                 WHERE
                    sup_id IN ($list)";
        $res = DB_Helper::getInstance()->getColumn($stmt, $sup_ids);

        foreach ($res as $subject) {
            History::add($issue_id, $usr_id, 'email_associated', "Email (subject: '{subject}') associated by {user}", [
                'subject' => $subject,
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to associate a support email with an existing
     * issue.
     *
     * @param   int $usr_id The user ID of the person performing this change
     * @param   int $issue_id The issue ID
     * @param   array $sup_ids The list of email IDs to associate
     * @param   bool $authorize If the senders should be added the authorized repliers list
     * @return  int 1 if it worked, -1 otherwise
     */
    public static function associate($usr_id, $issue_id, $sup_ids, $authorize = false)
    {
        $res = self::associateEmail($usr_id, $issue_id, $sup_ids);
        if ($res != 1) {
            return -1;
        }

        $stmt = 'SELECT
                    sup_id,
                    seb_full_email
                 FROM
                    `support_email`,
                    `support_email_body`
                 WHERE
                    sup_id=seb_sup_id AND
                    sup_id IN (' . DB_Helper::buildList($sup_ids) . ')';

        $res = DB_Helper::getInstance()->getAll($stmt, $sup_ids);

        $prj_id = Issue::getProjectID($issue_id);
        foreach ($res as $row) {
            $mail = MailMessage::createFromString($row['seb_full_email']);
            $t = [
                'issue_id' => $issue_id,
                'message_id' => $mail->messageId,
                'from' => $mail->from,
                'to' => $mail->to,
                'cc' => $mail->cc,
                'subject' => $mail->subject,
                'body' => $mail->getMessageBody(),
                'full_email' => $row['seb_full_email'],
                'has_attachment' => $mail->getAttachment()->hasAttachments(),
                // the following items are not inserted, but useful in some methods
                'headers' => $mail->getHeadersArray(),
            ];

            if (Workflow::shouldAutoAddToNotificationList($prj_id)) {
                self::addExtraRecipientsToNotificationList($prj_id, $t, false);
            }

            $t['sup_id'] = $row['sup_id'];
            $t['usr_id'] = $usr_id;
            Notification::notifyNewEmail($mail, $t);
            if ($authorize) {
                $sender_email = $mail->getSender();
                Authorized_Replier::manualInsert($issue_id, $sender_email, false);
            }
        }

        return 1;
    }

    /**
     * Method used to get the support email entry details.
     *
     * @param   int $sup_id The support email ID
     * @return  array The email entry details
     */
    public static function getEmailDetails($sup_id)
    {
        // $ema_id is not needed anymore and will be re-factored away in the future
        $stmt = 'SELECT
                    `support_email`.*,
                    `support_email_body`.*
                 FROM
                    `support_email`,
                    `support_email_body`
                 WHERE
                    sup_id=seb_sup_id AND
                    sup_id=?';

        $res = DB_Helper::getInstance()->getRow($stmt, [$sup_id]);
        if (!$res) {
            throw new InvalidArgumentException("Could not fetch email: $sup_id");
        }

        $mail = MailMessage::createFromString($res['seb_full_email']);

        $res['message'] = $res['seb_body'];
        $res['attachments'] = $res['sup_has_attachment'] ? $mail->getAttachment()->getAttachments() : [];
        $res['timestamp'] = Date_Helper::getUnixTimestamp($res['sup_date'], 'GMT');
        // TRANSLATORS: %1 = email subject
        $res['reply_subject'] = Mail_Helper::removeExcessRe(ev_gettext('Re: %1$s', $res['sup_subject']), true);

        if (!empty($res['sup_iss_id'])) {
            $res['reply_subject'] = Mail_Helper::formatSubject($res['sup_iss_id'], $res['reply_subject']);
        }

        return $res;
    }

    /**
     * Returns the nth note for a specific issue. The sequence starts at 1.
     *
     * @param   int $issue_id the id of the issue
     * @param   int $sequence the sequential number of the email
     * @return  array an array of data containing details about the email
     */
    public static function getEmailBySequence($issue_id, $sequence)
    {
        $offset = (int) $sequence - 1;
        $stmt = "SELECT
                    sup_id,
                    sup_ema_id
                FROM
                    `support_email`
                WHERE
                    sup_iss_id = ?
                ORDER BY
                    sup_id
                LIMIT 1 OFFSET $offset";
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return [];
        }

        if (count($res) < 1) {
            return [];
        }

        return self::getEmailDetails($res['sup_id']);
    }

    /**
     * Method used to get the list of support emails associated with
     * a given set of issues.
     *
     * @param   array $items List of issues
     * @return  array The list of support emails
     */
    public static function getListDetails($items)
    {
        $stmt = 'SELECT
                    sup_id,
                    sup_from,
                    sup_subject
                 FROM
                    `support_email`,
                    `email_account`
                 WHERE
                    ema_id=sup_ema_id AND
                    ema_prj_id=? AND
                    sup_id IN (' . DB_Helper::buildList($items) . ')';

        $params = $items;
        array_unshift($params, Auth::getCurrentProject());
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Get mail message for a given support email ID.
     *
     * @param int $sup_id The support email ID
     * @return MailMessage
     */
    public static function getSupportEmail($sup_id)
    {
        $stmt = 'SELECT
                    seb_full_email
                 FROM
                    `support_email_body`
                 WHERE
                    seb_sup_id=?';
        $full_email = DB_Helper::getInstance()->getOne($stmt, [$sup_id]);

        return MailMessage::createFromString($full_email);
    }

    /**
     * Method used to get the email message for a given support
     * email ID.
     *
     * @param   int $sup_id The support email ID
     * @return  string The email message
     */
    public static function getEmail($sup_id)
    {
        $stmt = 'SELECT
                    seb_body
                 FROM
                    `support_email_body`
                 WHERE
                    seb_sup_id=?';

        $res = DB_Helper::getInstance()->getOne($stmt, [$sup_id]);

        return $res;
    }

    /**
     * Method used to get all of the support email entries associated
     * with a given issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of support emails
     */
    public static function getEmailsByIssue($issue_id)
    {
        $stmt = "SELECT
                    sup_id,
                    sup_ema_id,
                    sup_from,
                    sup_to,
                    sup_cc,
                    sup_date,
                    UNIX_TIMESTAMP(sup_date) as date_ts,
                    sup_subject,
                    sup_has_attachment,
                    CONCAT(sup_ema_id, '-', sup_id) AS composite_id
                 FROM
                    `support_email`
                 WHERE
                    sup_iss_id=?
                 ORDER BY
                    sup_id ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        if (count($res) == 0) {
            return [];
        }

        return $res;
    }

    /**
     * Method used to update all of the selected support emails as
     * 'removed' ones.
     *
     * @return  int 1 if it worked, -1 otherwise
     */
    public static function removeEmails()
    {
        $items = $_POST['item'];
        $list = DB_Helper::buildList($items);
        $stmt = "UPDATE
                    `support_email`
                 SET
                    sup_removed=1
                 WHERE
                    sup_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to remove the association of all support emails
     * for a given issue.
     *
     * @return  int 1 if it worked, -1 otherwise
     */
    public static function removeAssociation()
    {
        $items = $_POST['item'];
        $list = DB_Helper::buildList($items);
        $stmt = "SELECT
                    sup_iss_id
                 FROM
                    `support_email`
                 WHERE
                    sup_id IN ($list)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt, $items);

        $stmt = "UPDATE
                    `support_email`
                 SET
                    sup_iss_id=0
                 WHERE
                    sup_id IN ($list)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        Issue::markAsUpdated($issue_id);
        // save a history entry for each email being associated to this issue
        $stmt = "SELECT
                    sup_id,
                    sup_subject
                 FROM
                    `support_email`
                 WHERE
                    sup_id IN ($list)";
        $subjects = DB_Helper::getInstance()->getPair($stmt, $items);

        $usr_id = Auth::getUserID();
        foreach ($items as $item) {
            History::add($issue_id, $usr_id, 'email_disassociated', "Email (subject: '{subject}') disassociated by {user}", [
                'subject' => $subjects[$item],
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Checks whether the given email address is allowed to send emails in the
     * issue ID.
     *
     * @param   int $issue_id The issue ID
     * @param   string $sender_email The email address
     * @return  bool
     */
    public static function isAllowedToEmail($issue_id, $sender_email)
    {
        $prj_id = Issue::getProjectID($issue_id);

        // check the workflow
        $workflow_can_email = Workflow::canEmailIssue($prj_id, $issue_id, $sender_email);
        if ($workflow_can_email != null) {
            return $workflow_can_email;
        }

        $is_allowed = true;
        $sender_usr_id = User::getUserIDByEmail($sender_email, true);
        if (empty($sender_usr_id)) {
            if (CRM::hasCustomerIntegration($prj_id)) {
                // check for a customer contact with several email addresses
                $crm = CRM::getInstance($prj_id);
                try {
                    $contract = $crm->getContract(Issue::getContractID($issue_id));
                    $contact_emails = array_keys($contract->getContactEmailAssocList());
                    $contact_emails = Misc::lowercase($contact_emails);
                } catch (CRMException $e) {
                    $contact_emails = [];
                }
                if ((!in_array(strtolower($sender_email), $contact_emails)) &&
                        (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email))) {
                    $is_allowed = false;
                }
            } else {
                if (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email)) {
                    $is_allowed = false;
                }
            }
        } else {
            // check if this user is not a customer and
            // also not in the assignment list for the current issue and
            // also not in the authorized repliers list
            // also not the reporter
            $details = Issue::getDetails($issue_id);
            if ($sender_usr_id == $details['iss_usr_id']) {
                $is_allowed = true;
            } elseif ((User::isPartner($sender_usr_id)) && (in_array(User::getPartnerID($sender_usr_id), Partner::getPartnerCodesByIssue($issue_id)))) {
                $is_allowed = true;
            } elseif ((!Issue::canAccess($issue_id, $sender_usr_id)) && (!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email))) {
                $is_allowed = false;
            } elseif ((!Authorized_Replier::isAuthorizedReplier($issue_id, $sender_email)) &&
                    (!Issue::isAssignedToUser($issue_id, $sender_usr_id)) &&
                    (User::getRoleByUser($sender_usr_id, Issue::getProjectID($issue_id)) != User::ROLE_CUSTOMER)) {
                $is_allowed = false;
            }
        }

        return $is_allowed;
    }

    /**
     * Method used to build mail from web-based message.
     *
     * @param   int $issue_id The issue ID
     * @param   string $from The sender of this message
     * @param   string $to The primary recipient of this message
     * @param   string $cc The extra recipients of this message
     * @param string $subject
     * @param   string $body The message body
     * @param   string $in_reply_to The message-id that we are replying to
     * @param   array $iaf_ids Array with attachment file id-s
     * @return MailMessage
     */
    public static function buildMail($issue_id, $from, $to, $cc, $subject, $body, $in_reply_to, $iaf_ids = null)
    {
        $builder = new MailBuilder();
        $builder->addTextPart($body);

        $message = $builder->getMessage();
        $message->setSubject($subject);
        $message->setFrom($from);
        if ($to) {
            $message->setTo($to);
        }

        if ($iaf_ids) {
            foreach ($iaf_ids as $iaf_id) {
                $attachment = AttachmentManager::getAttachment($iaf_id);
                $builder->addAttachment($attachment);
            }
        }

        if ($cc) {
            $ccs = AddressHeader::fromString($cc)->getAddressList();
            $message->addCc($ccs);
        }

        $m = $builder->toMailMessage();

        // if there is no existing in-reply-to header, get the root message for the issue
        if (!$in_reply_to && $issue_id) {
            $in_reply_to = Issue::getRootMessageID($issue_id);
        }

        if ($in_reply_to) {
            $m->setInReplyTo($in_reply_to);
        }

        return $m;
    }

    /**
     * Method used to send emails directly from the sender to the
     * recipient. This will not re-write the sender's email address
     * to issue-xxxx@ or whatever.
     *
     * @param MailMessage $mail The Mail object
     * @param array $options
     * - int $issue_id The issue ID
     * - string $from The sender of this message
     * - string $to The primary recipient of this message
     * - string $cc The extra recipients of this message
     * - int $sender_usr_id the ID of the user sending this message
     * - array $iaf_ids an array with attachment information
     */
    public static function sendDirectEmail(MailMessage $mail, $options = [])
    {
        $issue_id = $options['issue_id'];
        $iaf_ids = $options['iaf_ids'];
        $from = $options['from'];
        $to = isset($options['to']) ? $options['to'] : $mail->to;
        $cc = isset($options['cc']) ? $options['cc'] : $mail->cc;
        $sender_usr_id = isset($options['sender_usr_id']) ? $options['sender_usr_id'] : null;
        $subject = $mail->subject;
        $message_id = $mail->messageId;
        $body = $mail->getContent();

        $prj_id = Issue::getProjectID($issue_id);
        $subject = Mail_Helper::formatSubject($issue_id, $subject);
        $recipients = AddressHeader::fromString($cc)->getEmails();
        $recipients[] = $to;

        // create emails for each recipient
        foreach ($recipients as $recipient) {
            $builder = new MailBuilder();
            $builder
                ->addTextPart($body)
                ->getMessage()
                ->setSubject($subject)
                ->setTo($recipient)
                ->setFrom($from);

            foreach ($iaf_ids as $iaf_id) {
                $attachment = AttachmentManager::getAttachment($iaf_id);
                $builder->addAttachment($attachment);
            }

            $mail = $builder->toMailMessage();

            if ($issue_id) {
                // add the warning message to the current message' body, if needed
                $wm = new WarningMessage($mail);
                $recipient_email = Mail_Helper::getEmailAddress($recipient);
                $wm->add($issue_id, $recipient_email);

                $add_headers = [
                    'Message-Id' => $message_id,
                ];
                $mail->addHeaders($add_headers);

                // skip users who don't have access to this issue (but allow non-users and users without access to this project) to get emails
                $recipient_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($recipient), true);
                if ((((!empty($recipient_usr_id)) && ((!Issue::canAccess($issue_id, $recipient_usr_id)) && (User::getRoleByUser($recipient_usr_id, $prj_id) != null)))) ||
                        (empty($recipient_usr_id)) && (Issue::getAccessLevel($issue_id) != 'normal')) {
                    continue;
                }
            }

            if (User::getRoleByUser(User::getUserIDByEmail(Mail_Helper::getEmailAddress($from)), Issue::getProjectID($issue_id)) == User::ROLE_CUSTOMER) {
                $type = 'customer_email';
            } else {
                $type = 'other_email';
            }

            $options = [
                'save_email_copy' => true,
                'issue_id' => $issue_id,
                'type' => $type,
                'sender_usr_id' => $sender_usr_id,
            ];

            Mail_Queue::queue($mail, $recipient, $options);
        }
    }

    /**
     * @param int $issue_id
     * @param string $type type of email
     * @param string $from
     * @param string $to
     * @param string $cc
     * @param string $subject
     * @param string $body
     * @param array $options optional parameters
     * - (int) parent_sup_id
     * - (array) iaf_ids attachment file ids
     * - (bool) add_unknown
     * - (bool) add_cc_to_ar
     * - (int) ema_id
     * @return int 1 if it worked, -1 otherwise
     */
    public static function sendEmail($issue_id, $type, $from, $to, $cc, $subject, $body, $options = [])
    {
        if ($to === null) {
            // BTW, $to = '' is ok
            Logger::app()->error('"To:" can not be NULL');

            return -1;
        }

        $parent_sup_id = isset($options['parent_sup_id']) ? $options['parent_sup_id'] : null;
        $iaf_ids = isset($options['iaf_ids']) ? (array) $options['iaf_ids'] : [];
        $add_unknown = isset($options['add_unknown']) ? (bool) $options['add_unknown'] : false;
        $add_cc_to_ar = isset($options['add_cc_to_ar']) ? (bool) $options['add_cc_to_ar'] : false;
        $ema_id = isset($options['ema_id']) ? (int) $options['ema_id'] : null;

        $usr_id = Auth::getUserID();
        $prj_id = Issue::getProjectID($issue_id);

        // get ID of whoever is sending this.
        $sender_usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($from)) ?: false;

        $internal_only = false;

        // process any files being uploaded
        // from ajax upload, attachment file ids
        if ($iaf_ids) {
            // FIXME: is it correct to use sender from post data?
            // FIXME: Should we upload attachments even if the email is blocked?
            $attach_usr_id = $sender_usr_id ?: $usr_id;
            AttachmentManager::attachFiles($issue_id, $attach_usr_id, $iaf_ids, User::ROLE_VIEWER, 'Attachment originated from outgoing email');
        }

        // if we are replying to an existing email, set the In-Reply-To: header accordingly
        $in_reply_to = $parent_sup_id ? self::getMessageIDByID($parent_sup_id) : false;
        $subject = Mail_Helper::removeExcessRe($subject, true);
        $mail = self::buildMail($issue_id, $from, $to, $cc, $subject, $body, $in_reply_to, $iaf_ids);

        // email blocking should only be done if this is an email about an associated issue
        if ($issue_id) {
            $user_info = User::getNameEmail($usr_id);
            // check whether the current user is allowed to send this email to customers or not
            if (!self::isAllowedToEmail($issue_id, $user_info['usr_email'])) {
                // add the message body as a note
                $note = Mail_Helper::getCannedBlockedMsgExplanation() . $mail->getContent();
                $note_options = [
                    'full_message' => $mail->getRawContent(),
                    'is_blocked' => true,
                ];
                Note::insertNote($usr_id, $issue_id, $mail->subject, $note, $note_options);

                $email_details = [
                    'from' => $mail->from,
                    'to' => $mail->to,
                    'cc' => $mail->cc,
                    'subject' => $mail->subject,
                    // we pass as reference, as that may save some memory
                    'body' => &$body,
                ];
                Workflow::handleBlockedEmail($prj_id, $issue_id, $email_details, 'web');

                return 1;
            }
        }

        // only send a direct email if the user doesn't want to add the Cc'ed people to the notification list
        if ($add_unknown && $issue_id) {
            // add the recipients to the notification list of the associated issue
            foreach ($mail->getAddresses() as $address) {
                if ($address && !Notification::isIssueRoutingSender($issue_id, $address)) {
                    $actions = Notification::getDefaultActions($issue_id, $address, 'add_unknown_user');
                    Notification::subscribeEmail($usr_id, $issue_id, Mail_Helper::getEmailAddress($address), $actions);
                }
            }
        } else {
            // Usually when sending out emails associated to an issue, we would
            // simply insert the email in the table and call the Notification::notifyNewEmail() method,
            // but on this case we need to actually send the email to the recipients that are not
            // already in the notification list for the associated issue, if any.
            // In the case of replying to an email that is not yet associated with an issue, then
            // we are always directly sending the email, without using any notification list
            // functionality.
            if ($issue_id) {
                // send direct emails only to the unknown addresses, and leave the rest to be
                // catched by the notification list
                $fixed_from = Notification::getFixedFromHeader($issue_id, $mail->from, 'issue');

                // build the list of unknown recipients
                $unknowns = [];
                foreach ($mail->getAddresses() as $address) {
                    if (!Notification::isSubscribedToEmails($issue_id, $address)) {
                        $unknowns[] = $address;
                    }
                }

                if ($unknowns) {
                    // NOTE: email "name" field may have gotten lost when using $mail->getAddresses()
                    // if that's relevant use AddressList functionality to preserve "name" field
                    $to = array_shift($unknowns);

                    // send direct emails
                    $options = [
                        'issue_id' => $issue_id,
                        'from' => $fixed_from,
                        'to' => $to,
                        'cc' => implode(', ', $unknowns),
                        'iaf_ids' => $iaf_ids,
                        'sender_usr_id' => $sender_usr_id,
                    ];
                    self::sendDirectEmail($mail, $options);
                }
            } else {
                // send direct emails to all recipients, since we don't have an associated issue
                $project_info = Project::getOutgoingSenderAddress(Auth::getCurrentProject());
                // use the project-related outgoing email address, if there is one
                if (!empty($project_info['email'])) {
                    $from = Mail_Helper::getFormattedName(User::getFullName($usr_id), $project_info['email']);
                } else {
                    // otherwise, use the real email address for the current user
                    $from = User::getFromHeader($usr_id);
                }
                // send direct emails
                $options = [
                    'issue_id' => $issue_id,
                    'from' => $from,
                    'iaf_ids' => $iaf_ids,
                ];
                self::sendDirectEmail($mail, $options);
            }
        }

        if ($add_cc_to_ar) {
            foreach ($mail->getAddresses('Cc') as $recipient) {
                Authorized_Replier::manualInsert($issue_id, $recipient);
            }
        }

        $email_options = [
            // FIXME: use actual null, not string 'null'
            'customer_id' => 'NULL',
            'issue_id' => $issue_id,
            'ema_id' => $ema_id,
            'date' => Date_Helper::convertDateGMT($mail->getDate()),
            // these below are likely unused by Support::insertEmail
            'message_id' => $mail->messageId,
            'from' => $mail->from,
            'to' => $mail->to,
            'cc' => $mail->cc,
            'subject' => $mail->subject,
            'body' => $mail->getContent(),
            'full_email' => $mail->getRawContent(),
        ];

        // associate this new email with a customer, if appropriate
        if (Auth::getCurrentRole() == User::ROLE_CUSTOMER) {
            if ($issue_id) {
                $crm = CRM::getInstance($prj_id);
                try {
                    $contact = $crm->getContact(User::getCustomerContactID($usr_id));
                    $issue_contract = $crm->getContract(Issue::getContractID($issue_id));
                    if ($contact->canAccessContract($issue_contract)) {
                        $email_options['customer_id'] = $issue_contract->getCustomerID();
                    }
                } catch (CRMException $e) {
                }
            } else {
                $customer_id = User::getCustomerID($usr_id);
                if ($customer_id && $customer_id != -1) {
                    $email_options['customer_id'] = $customer_id;
                }
            }
        }

        $email_options['has_attachment'] = $iaf_ids ? 1 : 0;
        $email_options['headers'] = $mail->getHeadersArray();

        $sup_id = self::insertEmail($mail, $email_options);

        if ($issue_id) {
            $email_options['internal_only'] = $internal_only;
            $email_options['type'] = $type;
            $email_options['sup_id'] = $sup_id;
            $email_options['usr_id'] = $usr_id;
            Notification::notifyNewEmail($mail, $email_options);

            // mark this issue as updated
            $has_customer = $email_options['customer_id'] && $email_options['customer_id'] != 'NULL';
            if ($has_customer && (!$usr_id || User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER)) {
                Issue::markAsUpdated($issue_id, 'customer action');
            } else {
                if ($sender_usr_id && User::getRoleByUser($sender_usr_id, $prj_id) > User::ROLE_CUSTOMER) {
                    Issue::markAsUpdated($issue_id, 'staff response');
                } else {
                    Issue::markAsUpdated($issue_id, 'user response');
                }
            }

            History::add($issue_id, $usr_id, 'email_sent', 'Outgoing email sent by {user}', [
                'user' => User::getFullName($usr_id),
            ]);
        }

        return 1;
    }

    /**
     * Method used to get the message-id associated with a given support
     * email entry.
     *
     * @param   int $sup_id The support email ID
     * @return  int The email ID
     */
    public static function getMessageIDByID($sup_id)
    {
        $stmt = 'SELECT
                    sup_message_id
                 FROM
                    `support_email`
                 WHERE
                    sup_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$sup_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the support ID associated with a given support
     * email message-id.
     *
     * @param   string $message_id The message ID
     * @return  int The email ID
     */
    public static function getIDByMessageID($message_id)
    {
        if (!$message_id) {
            return false;
        }
        $stmt = 'SELECT
                    sup_id
                 FROM
                    `support_email`
                 WHERE
                    sup_message_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$message_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return $res;
    }

    /**
     * Method used to get the issue ID associated with a given support
     * email message-id.
     *
     * @param   string $message_id The message ID
     * @return  int The issue ID
     */
    public static function getIssueByMessageID($message_id)
    {
        if (!$message_id) {
            return false;
        }
        $stmt = 'SELECT
                    sup_iss_id
                 FROM
                    `support_email`
                 WHERE
                    sup_message_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$message_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the issue ID associated with a given support
     * email entry.
     *
     * @param   int $sup_id The support email ID
     * @return  int The issue ID
     */
    public static function getIssueFromEmail($sup_id)
    {
        $stmt = 'SELECT
                    sup_iss_id
                 FROM
                    `support_email`
                 WHERE
                    sup_id=?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$sup_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Returns the message-id of the parent email.
     *
     * @param   string $msg_id The message ID
     * @return  string The message id of the parent email or false
     */
    public static function getParentMessageIDbyMessageID($msg_id)
    {
        $sql = 'SELECT
                    parent.sup_message_id
                FROM
                    `support_email` child,
                    `support_email` parent
                WHERE
                    parent.sup_id = child.sup_parent_id AND
                    child.sup_message_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($sql, [$msg_id]);
        } catch (DatabaseException $e) {
            return false;
        }

        if (empty($res)) {
            return false;
        }

        return $res;
    }

    /**
     * Returns the number of emails sent by a user in a time range.
     *
     * @param   string $usr_id The ID of the user
     * @param   int $start The timestamp of the start date
     * @param   int $end The timestamp of the end date
     * @param   bool $associated if this should return emails associated with issues or non associated emails
     * @return  int the number of emails sent by the user
     */
    public static function getSentEmailCountByUser($usr_id, $start, $end, $associated)
    {
        $usr_info = User::getNameEmail($usr_id);
        $stmt = 'SELECT
                    COUNT(sup_id)
                 FROM
                    `support_email`,
                    `email_account`
                 WHERE
                    ema_id = sup_ema_id AND
                    ema_prj_id = ? AND
                    sup_date BETWEEN ? AND ? AND
                    sup_from LIKE ? AND
                    sup_iss_id ';
        if ($associated == true) {
            $stmt .= '!= 0';
        } else {
            $stmt .= '= 0';
        }

        $params = [Auth::getCurrentProject(), $start, $end, "%{$usr_info['usr_email']}%"];
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Returns the projectID based on the email account
     *
     * @param   int $ema_id the id of the email account
     * @return  int the ID of the of the project
     */
    public static function getProjectByEmailAccount($ema_id)
    {
        static $returns;

        if (!empty($returns[$ema_id])) {
            return $returns[$ema_id];
        }

        $stmt = 'SELECT
                    ema_prj_id
                 FROM
                    `email_account`
                 WHERE
                    ema_id = ?';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, [$ema_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        $returns[$ema_id] = $res;

        return $res;
    }

    /**
     * Moves an email from one account to another.
     *
     * @param   int $sup_id the ID of the message
     * @param   int $current_ema_id the ID of the account the message is currently in
     * @param   int $new_ema_id the ID of the account to move the message too
     * @return  int -1 if there was error moving the message, 1 otherwise
     */
    public static function moveEmail($sup_id, $current_ema_id, $new_ema_id)
    {
        $email = self::getEmailDetails($sup_id);
        if (!empty($email['sup_iss_id'])) {
            return -1;
        }

        $info = Email_Account::getDetails($new_ema_id);
        $mail = self::getSupportEmail($sup_id);

        // handle auto creating issues (if needed)
        $should_create_array = self::createIssueFromEmail($mail, $info);
        $issue_id = $should_create_array['issue_id'];
        $customer_id = $should_create_array['customer_id'];

        if (empty($issue_id)) {
            $issue_id = 0;
        }
        if (empty($customer_id)) {
            $customer_id = 'NULL';
        }

        $sql = 'UPDATE
                    `support_email`
                SET
                    sup_ema_id = ?,
                    sup_iss_id = ?,
                    sup_customer_id = ?
                WHERE
                    sup_id = ? AND
                    sup_ema_id = ?';
        $params = [$new_ema_id, $issue_id, $customer_id, $sup_id, $current_ema_id];
        try {
            DB_Helper::getInstance()->query($sql, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        $row = [
            'sup_id' => $email['sup_id'],
            'customer_id' => $customer_id,
            'issue_id' => $issue_id,
            'ema_id' => $new_ema_id,
            'message_id' => $email['sup_message_id'],
            'date' => $email['timestamp'],
            'from' => $email['sup_from'],
            'to' => $email['sup_to'],
            'cc' => $email['sup_cc'],
            'subject' => $email['sup_subject'],
            'body' => $email['seb_body'],
            'full_email' => $email['seb_full_email'],
            'has_attachment' => $email['sup_has_attachment'],
        ];

        $prj_id = self::getProjectByEmailAccount($new_ema_id);
        Workflow::handleNewEmail($prj_id, $issue_id, $mail, $row);

        return 1;
    }

    /**
     * Check if this email needs to be blocked and if so, block it.
     *
     * @param MailMessage $mail The Mail object
     * @param int $issue_id
     * @return bool
     */
    public static function blockEmailIfNeeded(MailMessage $mail, $issue_id)
    {
        if (!$issue_id) {
            return false;
        }

        $prj_id = Issue::getProjectID($issue_id);
        $sender_email = $mail->getSender();

        if ($mail->isVacationAutoResponder() || $mail->isBounceMessage() || !self::isAllowedToEmail($issue_id, $sender_email)) {
            // avoid having this type of message re-open the issue
            if ($mail->isVacationAutoResponder()) {
                $closing = true;
                $notify = false;
            } else {
                $closing = false;
                $notify = true;
            }

            $options = [
                'unknown_user' => $sender_email,
                'log' => false,
                'closing' => $closing,
                'send_notification' => $notify,
                'is_blocked' => true,
                'full_message' => $mail->getRawContent(),
                'message_id' => $mail->messageId,
            ];

            $body = Mail_Helper::getCannedBlockedMsgExplanation() . $mail->getContent();
            $usr_id = Auth::getUserID();
            $res = Note::insertNote($usr_id, $issue_id, $mail->subject, $body, $options);

            // associate the email attachments as internal-only files on this issue
            if ($res != -1) {
                self::extractAttachments($issue_id, $mail, true, $res);
            }

            $email_details = [];
            $email_details['issue_id'] = $issue_id;
            $email_details['from'] = $sender_email;

            // XXX: review and remove unneeded ones
            // these are from 01c7db33
            $email_details['full_message'] = $options['full_message'];
            $email_details['title'] = $mail->subject;
            $email_details['note'] = $mail->getContent();
            $email_details['message_id'] = $options['message_id'];

            // avoid having this type of message re-open the issue
            if ($mail->isVacationAutoResponder()) {
                $email_type = 'vacation-autoresponder';
            } else {
                $email_type = 'routed';
            }
            Workflow::handleBlockedEmail($prj_id, $issue_id, $email_details, $email_type);

            // try to get usr_id of sender, if not, use system account
            $usr_id = User::getUserIDByEmail($sender_email, true) ?: APP_SYSTEM_USER_ID;
            History::add($issue_id, $usr_id, 'email_blocked', "Email from '{from}' blocked", [
                'from' => $sender_email,
            ]);

            return true;
        }

        return false;
    }

    public static function addExtraRecipientsToNotificationList($prj_id, $email, $is_auto_created = false)
    {
        if ((empty($email['to'])) && (!empty($email['sup_to']))) {
            $email['to'] = $email['sup_to'];
        }
        if ((empty($email['cc'])) && (!empty($email['sup_cc']))) {
            $email['cc'] = $email['sup_cc'];
        }

        $project_details = Project::getDetails($prj_id);
        $addresses_not_too_add = explode(',', strtolower($project_details['prj_mail_aliases']));
        array_push($addresses_not_too_add, $project_details['prj_outgoing_sender_email']);

        $addresses = [];

        $to_addresses = AddressHeader::fromString(@$email['to'])->getEmails();
        if ($to_addresses) {
            $addresses = $to_addresses;
        }
        $cc_addresses = AddressHeader::fromString($email['cc'])->getEmails();
        if ($cc_addresses) {
            $addresses = array_merge($addresses, $cc_addresses);
        }

        $subscribers = Notification::getSubscribedEmails($email['issue_id']);
        foreach ($addresses as $address) {
            $address = strtolower($address);
            if ((!in_array($address, $subscribers)) && (!in_array($address, $addresses_not_too_add))) {
                Notification::subscribeEmail(Auth::getUserID(), $email['issue_id'], $address, Notification::getDefaultActions($email['issue_id'], $address, 'add_extra_recipients'));
                if ($is_auto_created) {
                    Notification::notifyAutoCreatedIssue($prj_id, $email['issue_id'], $email['from'], $email['date'], $email['subject'], $address);
                }
            }
        }
    }

    /**
     * Returns the sequential number of the specified email ID.
     *
     * @param   int $sup_id The email ID
     * @return  int The sequence number of the email
     */
    public static function getSequenceByID($sup_id)
    {
        if (empty($sup_id)) {
            return '';
        }

        try {
            DB_Helper::getInstance()->query('SET @sup_seq = 0');
        } catch (DatabaseException $e) {
            return 0;
        }

        $issue_id = self::getIssueFromEmail($sup_id);
        $sql = 'SELECT
                    sup_id,
                    @sup_seq := @sup_seq+1
                FROM
                    `support_email`
                WHERE
                    sup_iss_id = ?
                ORDER BY
                    sup_id ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($sql, [$issue_id]);
        } catch (DatabaseException $e) {
            return 0;
        }

        return @$res[$sup_id];
    }
}
