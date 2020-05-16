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
use Eventum\Mail\Helper\MailLoader;
use Eventum\Mail\Imap\ImapResource;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Storage as ZendMailStorage;

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
     * Server parameters for IMAP connection
     *
     * @var array
     */
    public $info;

    /**
     * Method to create email from data from extension and return Zend Mail Message object.
     *
     * This is bridge while migrating to Zend Mail package supporting reading from imap extension functions.
     */
    public static function createFromImapResource(ImapResource $resource): ImapMessage
    {
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
            if ($resource->overview->$flag) {
                $flags[] = $value;
            }
        }

        $parameters = self::createParameters("{$resource->headers}\r\n\r\n{$resource->content}", $flags);
        $message = new self($parameters);

        // set MailDate to $message object, as it's not available in message headers, only in IMAP itself
        // this likely "message received date"
        $header = new GenericHeader('X-IMAP-UnixDate', $resource->imapheaders->udate);
        $message->getHeaders()->addHeader($header);

        $message->num = $resource->num;
        $message->info = $resource->info;

        $event = new GenericEvent($message, $parameters);
        EventManager::dispatch(SystemEvents::MAIL_LOADED_IMAP, $event);

        return $message;
    }

    /**
     * @deprecated removed in 3.8.12
     */
    public static function createFromImap($mbox, $num, $info): ImapMessage
    {
        throw new RuntimeException('This method no longer exists');
    }

    /**
     * @param string $raw
     * @param array $flags
     * @return array
     */
    public static function createParameters($raw, $flags = [])
    {
        MailLoader::splitMessage($raw, $headers, $content);

        return ['root' => true, 'headers' => $headers, 'content' => $content, 'flags' => $flags];
    }

    /**
     * Get date this message was sent.
     * Uses IMAP Date, and fallbacks to Date header.
     *
     * @return DateTime
     */
    public function getMailDate(): DateTime
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
     * Get Project Id associated with this email account
     *
     * @return int
     */
    public function getProjectId(): int
    {
        return $this->info['ema_prj_id'];
    }

    /**
     * Get The email account ID
     *
     * @return int
     */
    public function getEmailAccountId(): int
    {
        return $this->info['ema_id'];
    }
}
