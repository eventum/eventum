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
use Zend\Mail\Headers;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Address;
use Zend\Mail\AddressList;
use Zend\Mail\Header\Subject;
use Zend\Mail\Header\ContentType;
use Zend\Mail\Header\ContentTransferEncoding;
use Zend\Mail\Header\To;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Header\MessageId;
use Zend\Mime;

class MailMessage extends Message
{
    /**
     * Public constructor
     *
     * Generates MessageId header in case it is missing:
     *
     * @param array $params
     */
    public function __construct(array $params)
    {
        parent::__construct($params);

        // set messageId if that is missing
        // FIXME: do not set this for "child" messages (attachments)
        if (!$this->headers->has('Message-Id')) {
            $headers = rtrim($this->headers->toString(), Headers::EOL);
            $messageId = Mail_Helper::generateMessageID($headers, $this->getContent());
            $header = new MessageId();
            $this->headers->addHeader($header->setId(trim($messageId, "<>")));
        }
    }

    /**
     * Create Mail object from raw email message.
     *
     * @param string $raw The full email message
     * @return MailMessage
     */
    public static function createFromString($raw)
    {
        $message = new self(array('raw' => $raw));
        return $message;
    }

    /**
     * Create Mail object from specified filename
     *
     * @param string $filename Path to the file to read in
     * @return MailMessage
     */
    public static function createFromFile($filename)
    {
        $message = new self(array('file' => $filename));

        return $message;
    }

    /**
     * Method to read email from imap extension and return Zend Mail Message object.
     *
     * This is bridge while migrating to Zend Mail package supporting reading from imap extension functions.
     *
     * @param resource $mbox
     * @param integer $num
     * @return MailMessage
     */
    public static function createFromImap($mbox, $num)
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

        return $message;
    }

    /**
     * Return Message-Id Value
     *
     * @return string
     */
    public function getMessageId()
    {
        $header = $this->getHeader('Message-Id');
        if ($header instanceof ArrayIterator) {
            $header = current($header);
        }
        return $header->getFieldValue();
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
     * Assemble email into raw format.
     *
     * @return string
     */
    public function getRawContent()
    {
        return $this->headers->toString() . Headers::EOL . $this->getContent();
    }

    /**
     * Return true if mail has attachments
     *
     * @return  boolean
     */
    public function hasAttachments()
    {
        return $this->isMultipart() && $this->countParts() > 0;
    }

    /**
     * Get attachments with 'filename', 'cid', 'filetype', 'blob' array elements
     *
     * @return array
     */
    public function getAttachments()
    {
        $attachments = array();

        /** @var MailMessage $attachment */
        foreach ($this as $attachment) {
            $headers = $attachment->headers;

            $ct = $headers->get('Content-Type');
            // attempt to extract filename
            // 1. try Content-Type: name parameter
            // 2. try Content-Disposition: filename parameter
            // 3. as last resort use Untitled with extension from mime-type subpart
            /** @var ContentType $ct */
            $filename = $ct->getParameter('name')
                ?: $attachment->getHeaderField('Content-Disposition', 'filename')
                    ?: ev_gettext('Untitled.%s', end(explode('/', $ct->getType())));

            // get body.
            // have to decode ourselves or use something like Mime\Message::createFromMessage
            $body = $attachment->getContent();
            /** @var ContentTransferEncoding $cte */
            $cte = $headers->get('Content-Transfer-Encoding');
            switch ($cte->getTransferEncoding()) {
                case 'quoted-printable':
                    $body = quoted_printable_decode($body);
                    break;
                case 'base64':
                    $body = base64_decode($body);
                    break;
                case '7bit':
                case '8bit':
                case 'binary':
                    // these need no transformation
                    break;
                default:
                    throw new LogicException("Unsupported Content-Transfer-Encoding: '{$cte->getTransferEncoding()}'");
            }

            $attachments[] = array(
                'filename' => $filename,
                'cid' => $headers->get('Content-Id')->getFieldValue(),
                'filetype' => $ct->getType(),
                'blob' => $body,
            );
        }

        return $attachments;
    }

    /**
     * Returns the referenced message-id for a given reply.
     *
     * @return string|null
     */
    public function getReferenceMessageId()
    {
        if ($this->headers->has('In-Reply-To')) {
            return $this->headers->get('In-Reply-To')->getFieldValue();
        }

        if (!$this->headers->has('References')) {
            return null;
        }

        $references = explode(' ', $this->headers->get('References')->getFieldValue());

        // return the first message-id in the list of references
        return trim($references[0]);
    }

    /**
     * Returns the message IDs of all emails this message references.
     *
     * @return string[] An array of message-ids
     */
    public function getAllReferences()
    {
        $references = array();

        if ($this->headers->has('In-Reply-To')) {
            $references[] = $this->headers->get('In-Reply-To')->getFieldValue();
        }

        if ($this->headers->has('References')) {
            $values = explode(' ', $this->headers->get('References')->getFieldValue());
            $references = array_merge($references, $values);
        }

        return array_unique($references);
    }

    /**
     * Get email addresses from specified headers, default from "To:" and "Cc:".
     *
     * @param array $headers
     * @return string[]
     */
    public function getAddresses($headers = array('To', 'Cc'))
    {
        if (!$headers) {
            throw new InvalidArgumentException('No header field specified');
        }

        $addresses = array();
        foreach ((array)$headers as $header) {
            if (!$this->headers->has($header)) {
                continue;
            }

            /** @var AbstractAddressList $addresslist */
            $addresslist = $this->headers->get($header);
            foreach ($addresslist->getAddressList() as $address) {
                $addresses[] = $address->getEmail();
            }
        }

        return array_unique($addresses);
    }

    /**
     * Shortcut to get lowercased mail sender email address.
     *
     * @return string
     */
    public function getSender()
    {
        return strtolower($this->getFromHeader()->getEmail());
    }

    /**
     * Access the address list of the To header
     *
     * @return AddressList
     * @see Zend\Mail\Message::getTo
     */
    public function getTo()
    {
        return $this->getAddressListFromHeader('to', '\Zend\Mail\Header\To');
    }

    /**
     * Retrieve list of CC recipients
     *
     * @return AddressList
     * @see Zend\Mail\Message::getCc
     */
    public function getCc()
    {
        return $this->getAddressListFromHeader('cc', '\Zend\Mail\Header\Cc');
    }

    /**
     * Get From header. In case multiple headers present, return just first one.
     *
     * @return Address
     */
    public function getFromHeader()
    {
        if (!$this->headers->has('From')) {
            return null;
        }

        // take long path to get multiple headers to single item
        // and multiple addresses from addresslist to single one.

        $header = $this->getHeader('From');

        if (!$header instanceof HeaderInterface) {
            $header = iterator_to_array($header);
            // take first header
            $header = current($header);
        }

        $addressList = $header->getAddressList();
        $addressList = iterator_to_array($addressList);

        $value = current($addressList);
        return $value;
    }

    /**
     * Get Subject header object.
     *
     * @return Subject
     */
    public function getSubject()
    {
        return $this->headers->get('Subject');
    }

    /**
     * Set To: header
     *
     * @param string $value
     */
    public function setTo($value)
    {
        $this->setAddressListHeader('To', $value);
    }

    /**
     * Set From: header
     *
     * @param string $value
     */
    public function setFrom($value)
    {
        $this->setAddressListHeader('From', $value);
    }

    /**
     * Set AddressList type header a value
     *
     * @param string $name
     * @param string $value
     */
    public function setAddressListHeader($name, $value)
    {
        /** @var AbstractAddressList $header */
        $header = $this->getHeader($name);
        $addresslist = new AddressList();
        $addresslist->addFromString($value);
        $header->setAddressList($addresslist);
    }

    /**
     * Set many headers at once
     *
     * Expects an array (or Traversable object) of name/value pairs.
     *
     * @param array|Traversable $headerlist
     */
    public function setHeaders(array $headerlist)
    {
        // NOTE: could use addHeaders() but that blows if value is not mime encoded. wtf
        //$this->headers->addHeaders($headerlist);

        $headers = $this->headers;
        foreach ($headerlist as $name => $value) {
            /** @var $header GenericHeader */
            if ($headers->has($name)) {
                $header = $headers->get($name);
                $header->setFieldValue($value);
            } else {
                $header = new GenericHeader($name, $value);
                $this->headers->addHeader($header);
            }
        }
    }

    /**
     * Convenience method to remove address from $header AddressList.
     *
     * @param string $header A header name, like 'To', or 'Cc'.
     * @param string $address An email address to remove
     * @return bool
     */
    public function removeFromAddressList($header, $address)
    {
        if (!$this->headers->has($header)) {
            return false;
        }

        /** @var AbstractAddressList $h */
        $h = $this->headers->get($header);
        $addressList = $h->getAddressList();

        return $addressList->delete($address);
    }

    /**
     * Return true if message is \Seen, \Deleted or \Answered
     *
     * @return bool
     */
    public function isSeen()
    {
        return
            $this->hasFlag(Zend\Mail\Storage::FLAG_SEEN) ||
            $this->hasFlag(Zend\Mail\Storage::FLAG_DELETED) ||
            $this->hasFlag(Zend\Mail\Storage::FLAG_ANSWERED);
    }

    /**
     * Checks whether the given headers are from a vacation auto-responder message or not.
     *
     * @return bool
     */
    public function isVacationAutoResponder()
    {
        // has 'auto-submitted' header?
        if ($this->headers->has('auto-submitted')) {
            return true;
        }

        // return true if 'x-vacationmessage' is set and not empty
        if (!$this->headers->has('x-vacationmessage')) {
            return false;
        }

        return $this->headers->get('x-vacationmessage') != '';
    }

    /**
     * Method used to check whether the current sender of the email is the
     * mailer daemon responsible for dealing with bounces.
     *
     * @return bool
     */
    public function isBounceMessage()
    {
        $email = $this->getSender();

        return substr($email, 0, 14) == 'mailer-daemon@';
    }

    /**
     * Strips out email headers that should not be sent over to the recipient
     * of the routed email. The 'Received:' header was sometimes being used to
     * validate the sender of the message, and because of that some emails were
     * not being delivered correctly.
     *
     * FIXME: think of better method name
     * @see Mail_Helper::stripHeaders
     */
    public function stripHeaders()
    {
        $headers = $this->headers;

        // process exact matches
        $ignore_headers = array(
            'to',
            'cc',
            'bcc',
            'return-path',
            'received',
            'Disposition-Notification-To',
        );
        foreach ($ignore_headers as $name) {
            if ($headers->has($name)) {
                $headers->removeHeader($name);
            }
        }

        // process patterns
        array_walk($headers->toArray(), function ($value, $name) use ($headers) {
            if (preg_match('/^resent.*/i', $name)) {
                $headers->removeHeader($name);
            }
        });
    }

    /**
     * Set Body of a message.
     *
     * IMPORTANT: it should not contain any multipart changes,
     * as then everything will blow up as it is not parsed again.
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Deep clone handling.
     *
     * Zend Does not handle this, do it ourselves.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    /**
     * Retrieve a header by name
     *
     * If not found, instantiates one based on $headerClass.
     *
     * @param  string $headerName
     * @param  string $headerClass
     * @return HeaderInterface|\ArrayIterator header instance or collection of headers
     * @see Zend\Mail\Message::getHeaderByName
     */
    protected function getHeaderByName($headerName, $headerClass)
    {
        $headers = $this->headers;
        if ($headers->has($headerName)) {
            $header = $headers->get($headerName);
        } else {
            $header = new $headerClass();
            $headers->addHeader($header);
        }
        return $header;
    }

    /**
     * Retrieve the AddressList from a named header
     *
     * Used with To, From, Cc, Bcc, and ReplyTo headers. If the header does not
     * exist, instantiates it.
     *
     * @param  string $headerName
     * @param  string $headerClass
     * @throws DomainException
     * @return AddressList
     * @see Zend\Mail\Message::getAddressListFromHeader
     */
    protected function getAddressListFromHeader($headerName, $headerClass)
    {
        $header = $this->getHeaderByName($headerName, $headerClass);
        if (!$header instanceof AbstractAddressList) {
            throw new DomainException(sprintf(
                'Cannot grab address list from header of type "%s"; not an AbstractAddressList implementation',
                get_class($header)
            ));
        }
        return $header->getAddressList();
    }
}