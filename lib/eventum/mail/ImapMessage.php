<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

use Zend\Mail\Storage\Message;
use Zend\Mail\Address;
use Zend\Mail\Header\GenericHeader;
use Zend\Mime;

/**
 * Class ImapMessage
 */
class ImapMessage extends MailMessage
{
    /**
     * message index related to imap connection
     * @var int
     */
    public $num;

    /**
     * imap connection obtained from imap_open
     * @var resource
     */
    public $mbox;

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
     * @param integer $num
     * @param array $info connection information about connection
     * @return MailMessage
     */
    public static function createFromImap($mbox, $num, $info)
    {
        // check if the current message was already seen
        list($overview) = imap_fetch_overview($mbox, $num);

        $headers = imap_fetchheader($mbox, $num);
        $content = imap_body($mbox, $num);

        // fill with "\Seen", "\Deleted", "\Answered", ... etc
        $knownFlags = array(
            'recent' => Zend\Mail\Storage::FLAG_RECENT,
            'flagged' => Zend\Mail\Storage::FLAG_FLAGGED,
            'answered' => Zend\Mail\Storage::FLAG_ANSWERED,
            'deleted' => Zend\Mail\Storage::FLAG_DELETED,
            'seen' => Zend\Mail\Storage::FLAG_SEEN,
            'draft' => Zend\Mail\Storage::FLAG_DRAFT,
        );
        $flags = array();
        foreach ($knownFlags as $flag => $value) {
            if ($overview->$flag) {
                $flags[] = $value;
            }
        }

        $message = new self(array('headers' => $headers, 'content' => $content, 'flags' => $flags));

        // set MailDate to $message object, as it's not available in message headers, only in IMAP itself
        // this likely "message received date"
        $imapheaders = imap_headerinfo($mbox, $num);
        $header = new GenericHeader('X-IMAP-UnixDate', $imapheaders->udate);
        $message->getHeaders()->addHeader($header);

        $message->mbox = $mbox;
        $message->num = $num;
        $message->infp = $info;

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
            throw new InvalidArgumentException("No date header for mail");
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
}