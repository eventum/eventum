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

use Date_Helper;
use DateTime;
use Eventum\Event\SystemEvents;
use Eventum\EventDispatcher\EventManager;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Storage as ZendMailStorage;
use Zend\Mail\Storage\Message;

/**
 * Class ImapMessage
 */
class ImapMessage extends MailMessage
{
    /**
     * message index related to imap connection
     *
     * @var int
     */
    public $num;

    /**
     * imap connection obtained from imap_open
     *
     * @var resource
     */
    public $mbox;

    /**
     * headerinfo result
     *
     * @var \stdClass
     */
    public $imapheaders;

    /**
     * Server parameters for IMAP connection
     *
     * @var array
     */
    public $info;

    /**
     * Method to read email from imap extension and return Zend Mail Message object.
     *
     * This is bridge while migrating to Zend Mail package supporting reading from imap extension functions.
     *
     * @param resource $mbox
     * @param int $num
     * @param array $info connection information about connection
     * @return ImapMessage
     */
    public static function createFromImap($mbox, $num, $info)
    {
        // check if the current message was already seen
        list($overview) = imap_fetch_overview($mbox, $num);

        $headers = imap_fetchheader($mbox, $num);
        $content = imap_body($mbox, $num);

        // fill with "\Seen", "\Deleted", "\Answered", ... etc
        $knownFlags = [
            'recent' => ZendMailStorage::FLAG_RECENT,
            'flagged' => ZendMailStorage::FLAG_FLAGGED,
            'answered' => ZendMailStorage::FLAG_ANSWERED,
            'deleted' => ZendMailStorage::FLAG_DELETED,
            'seen' => ZendMailStorage::FLAG_SEEN,
            'draft' => ZendMailStorage::FLAG_DRAFT,
        ];
        $flags = [];
        foreach ($knownFlags as $flag => $value) {
            if ($overview->$flag) {
                $flags[] = $value;
            }
        }

        $parameters = ['root' => true, 'headers' => $headers, 'content' => $content, 'flags' => $flags];
        $message = new self($parameters);

        // set MailDate to $message object, as it's not available in message headers, only in IMAP itself
        // this likely "message received date"
        $imapheaders = imap_headerinfo($mbox, $num);
        $header = new GenericHeader('X-IMAP-UnixDate', $imapheaders->udate);
        $message->getHeaders()->addHeader($header);

        $message->mbox = $mbox;
        $message->num = $num;
        $message->info = $info;
        $message->imapheaders = $imapheaders;

        $event = new GenericEvent($message, $parameters);
        EventManager::dispatch(SystemEvents::MAIL_LOADED_IMAP, $event);

        return $message;
    }

    /**
     * Get date this message was sent.
     * Uses IMAP Date, and fallbacks to Date header.
     *
     * @return DateTime
     */
    public function getMailDate()
    {
        $headers = $this->headers;
        if ($headers->has('X-IMAP-UnixDate')) {
            // does it have imap date?
            $date = $headers->get('X-IMAP-UnixDate')->getFieldValue();
        } elseif ($headers->has('Date')) {
            // fallback to date header
            $date = $headers->get('Date')->getFieldValue();
        } else {
            throw new InvalidArgumentException('No date header for mail');
        }

        return Date_Helper::getDateTime($date);
    }

    /**
     * Deletes the specified message from the IMAP/POP server
     * NOTE: YOU STILL MUST call imap_expunge($mbox) to permanently delete the message.
     */
    public function deleteMessage()
    {
        // need to delete the message from the server?
        if (!$this->info['ema_leave_copy']) {
            imap_delete($this->mbox, $this->num);
        } else {
            // mark the message as already read
            imap_setflag_full($this->mbox, $this->num, '\\Seen');
        }
    }

    /**
     * Get Project Id associated with this email account
     *
     * @return int
     */
    public function getProjectId()
    {
        return $this->info['ema_prj_id'];
    }

    /**
     * Get The email account ID
     *
     * @return int
     */
    public function getEmailAccountId()
    {
        return $this->info['ema_id'];
    }
}
