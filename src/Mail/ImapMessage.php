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
use Laminas\Mail\Header\GenericHeader;
use Laminas\Mail\Storage;
use Symfony\Component\EventDispatcher\GenericEvent;

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
     * Method to create email from data from extension and return Zend Mail Message object.
     *
     * This is bridge while migrating to Zend Mail package supporting reading from imap extension functions.
     */
    public static function createFromImapResource(ImapResource $resource): ImapMessage
    {
        // fill with "\Seen", "\Deleted", "\Answered", ... etc
        $knownFlags = [
            'recent' => Storage::FLAG_RECENT,
            'flagged' => Storage::FLAG_FLAGGED,
            'answered' => Storage::FLAG_ANSWERED,
            'deleted' => Storage::FLAG_DELETED,
            'seen' => Storage::FLAG_SEEN,
            'draft' => Storage::FLAG_DRAFT,
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

        $event = new GenericEvent($message, $parameters);
        EventManager::dispatch(SystemEvents::MAIL_LOADED_IMAP, $event);

        return $message;
    }

    /**
     * @param string $raw
     * @param array $flags
     * @return array
     */
    public static function createParameters($raw, array $flags = []): array
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
}
