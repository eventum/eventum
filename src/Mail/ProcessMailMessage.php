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

namespace Eventum\Mail;

use Auth;
use AuthCookie;
use Date_Helper;
use Email_Account;
use Eventum\Logger\LoggerTrait;
use Eventum\Mail\Exception\InvalidMessageException;
use Eventum\Mail\Exception\RoutingException;
use Eventum\Mail\Helper\AddressHeader;
use Eventum\Mail\Imap\ImapConnection;
use Eventum\Mail\Imap\ImapResource;
use History;
use Issue;
use Mail_Helper;
use Misc;
use Note;
use Notification;
use Project;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Routing;
use Setup;
use Support;
use User;
use Workflow;

/**
 * Process incoming mail message downloaded from IMAP.
 */
class ProcessMailMessage
{
    use LoggerTrait;

    /** @var bool */
    private $onlyNew;
    /** @var bool */
    private $useRouting;
    /** @var int */
    private $systemUserId;
    /** @var bool */
    private $leaveCopy;
    /** @var ImapConnection */
    private $connection;
    /** @var int */
    private $ema_id;
    /** @var int */
    private $prj_id;

    public function __construct(ImapConnection $connection, LoggerInterface $logger = null)
    {
        $info = $connection->getOptions();
        $this->logger = $logger ?: new NullLogger();
        $this->connection = $connection;
        $this->onlyNew = (bool)$info['ema_get_only_new'];
        $this->useRouting = $info['ema_use_routing'] == 1;
        $this->leaveCopy = (bool)$info['ema_leave_copy'];
        $this->systemUserId = Setup::getSystemUserId();
        $this->ema_id = (int)$info['ema_id'];
        $this->prj_id = (int)$info['ema_prj_id'];
    }

    public function process(ImapResource $resource): void
    {
        $message_id = $resource->imapheaders->message_id;
        $this->debug("Loading IMAP resource {$resource}", ['resource' => $resource]);

        // if message_id already exists, return immediately -- nothing to do
        if (Support::exists($message_id) || Note::exists($message_id)) {
            $this->debug("Skip $resource: Already exists as email or note.");

            return;
        }

        // check if the current message was already seen
        if ($this->onlyNew && $resource->isSeen()) {
            $this->debug("Skip $resource: Processing only new mails and the message is already Seen.");

            return;
        }

        try {
            $mail = ImapMessage::createFromImapResource($resource);
            $this->debug('Created mail object', ['mail' => $mail->messageId]);
        } catch (InvalidMessageException $e) {
            $this->error($e->getMessage(), ['resource' => $resource, 'e' => $e]);

            return;
        }

        // pass in $mail object so it can be modified
        if (!Workflow::preEmailDownload($this->prj_id, $mail)) {
            $this->debug("Skip $resource: Skipped by workflow");

            return;
        }

        $this->processMessage($mail);
    }

    private function processMessage(ImapMessage $mail): void
    {
        $prj_id = $this->prj_id;
        $ema_id = $this->ema_id;
        $message_id = $mail->messageId;

        AuthCookie::setAuthCookie($this->systemUserId);

        // route emails if necessary
        if ($this->useRouting) {
            try {
                $routed = Routing::route($mail);
            } catch (RoutingException $e) {
                $this->debug("Skip $message_id: RoutingException: {$e->getMessage()}");

                // "if leave copy of emails on IMAP server" is "off",
                // then we can bounce on the message
                // otherwise proper would be to create table -
                // eventum_bounce: bon_id, bon_message_id, bon_error
                if (!$this->leaveCopy) {
                    Support::bounceMessage($mail, $e);
                    $this->connection->deleteMessage($mail);
                }

                return;
            }

            // the mail was routed
            if ($routed === true) {
                $this->debug("Routed $message_id");

                if (!$this->leaveCopy) {
                    $this->debug("$message_id: Delete from IMAP/POP3");
                    $this->connection->deleteMessage($mail);
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
            'ema_id' => $ema_id,
            'date' => Date_Helper::convertDateGMT($mail->getMailDate()),
            // these below are likely unused by Support::insertEmail
            'message_id' => $mail->messageId,
            'from' => $mail->from,
            'to' => $mail->to,
            'cc' => $mail->cc,
            'subject' => $mail->subject,
            'body' => $mail->getMessageBody(),
            'full_email' => $mail->getRawContent(),
        ];

        $info = Email_Account::getDetails($ema_id);
        $info['date'] = $t['date'];
        $should_create_array = Support::createIssueFromEmail($mail, $info);
        $should_create_issue = $should_create_array['should_create_issue'];

        if (!empty($should_create_array['issue_id'])) {
            $t['issue_id'] = $should_create_array['issue_id'];

            // figure out if we should change to a different email account
            $iss_prj_id = Issue::getProjectID($t['issue_id']);
            if ($info['ema_prj_id'] != $iss_prj_id) {
                $ema_id = Email_Account::getEmailAccount($iss_prj_id);
                if (!empty($ema_id)) {
                    $t['ema_id'] = $ema_id;
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
            AuthCookie::setAuthCookie($this->systemUserId);
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
                            Support::extractAttachments($t['issue_id'], $mail, true, $res);
                        }
                    }
                }
            }
        } else {
            // check if we need to block this email
            if ($should_create_issue == true || !Support::blockEmailIfNeeded($mail, $t['issue_id'])) {
                if ($t['issue_id']) {
                    Mail_Helper::rewriteThreadingHeaders($mail, $t['issue_id'], 'email');
                }

                // make variable available for workflow to be able to detect whether this email created new issue
                $t['should_create_issue'] = $should_create_array['should_create_issue'];

                $sup_id = Support::insertEmail($mail, $t);
                $res = 1;
                // only extract the attachments from the email if we are associating the email to an issue
                if (!empty($t['issue_id'])) {
                    Support::extractAttachments($t['issue_id'], $mail);

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
                        Support::addExtraRecipientsToNotificationList($info['ema_prj_id'], $t, $should_create_issue);
                    }

                    if (Support::isAllowedToEmail($t['issue_id'], $sender_email)) {
                        $t['internal_only'] = $internal_only;
                        $t['assignee_only'] = $assignee_only;
                        $t['sup_id'] = $sup_id;
                        $t['usr_id'] = Auth::getUserID();
                        Notification::notifyNewEmail($mail, $t);
                    }

                    // try to get usr_id of sender, if not, use system account
                    $addr = $mail->getSender();
                    $usr_id = User::getUserIDByEmail($addr) ?: $this->systemUserId;

                    // mark this issue as updated if only if this email wasn't used to open it
                    if (!$should_create_issue) {
                        if ((!empty($t['customer_id'])) && ($t['customer_id'] !== 'NULL') && (empty($usr_id) || User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER)) {
                            Issue::markAsUpdated($t['issue_id'], 'customer action');
                        } else {
                            if (!empty($usr_id) && User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER) {
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
            $this->connection->deleteMessage($mail);
        }
    }
}
