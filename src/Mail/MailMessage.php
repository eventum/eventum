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

use DomainException;
use Eventum\Mail\Helper\MimePart;
use Eventum\Mail\Helper\SanitizeHeaders;
use InvalidArgumentException;
use LogicException;
use Mime_Helper;
use Zend\Mail;
use Zend\Mail\Address;
use Zend\Mail\AddressList;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\ContentTransferEncoding;
use Zend\Mail\Header\ContentType;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\MessageId;
use Zend\Mail\Header\MultipleHeadersInterface;
use Zend\Mail\Header\Subject;
use Zend\Mail\Headers;
use Zend\Mail\Storage as ZendMailStorage;
use Zend\Mail\Storage\Message;
use Zend\Mime;

/**
 * Class MailMessage
 *
 * @property-read string $messageId a Message-Id header value
 * @property-read string $from a From header value
 * @property-read string $to a To header value
 * @property-read string $cc a Cc header value
 * @property-read string $subject a Subject header value
 */
class MailMessage extends Message
{
    /**
     * Namespace for Header classes
     */
    const HEADER_NS = '\\Zend\\Mail\\Header\\';

    /**
     * Public constructor
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        parent::__construct($params);

        // do not do this for "child" messages (attachments)
        if (!empty($params['root'])) {
            $helper = new SanitizeHeaders();
            $helper($this);
        }
    }

    /**
     * Create Empty Mail object
     *
     * @return MailMessage
     */
    public static function createNew()
    {
        $message = new self(['root' => true]);

        return $message;
    }

    /**
     * Create Mail object from raw email message.
     *
     * @param string $raw The full email message
     * @return MailMessage
     */
    public static function createFromString($raw)
    {
        $message = new self(['root' => true, 'raw' => $raw]);

        return $message;
    }

    /**
     * Create Mail object from headers array and body string
     *
     * @param array $headers
     * @param string $content
     * @return MailMessage
     */
    public static function createFromHeaderBody($headers, $content)
    {
        foreach ($headers as $k => $v) {
            // Zend\Mail does not like empty headers, "Cc:" for example
            if ($v === '') {
                unset($headers[$k]);
            }

            // also it doesn't like 8bit headers
            if (Mime_Helper::is8bit($v)) {
                $headers[$k] = Mime_Helper::encode($v);
            }
        }

        $message = new self(['root' => true, 'headers' => $headers, 'content' => $content]);

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
        $message = new self(['root' => true, 'file' => $filename]);

        return $message;
    }

    /**
     * Create from Zend\Mail\Message object
     *
     * @param Mail\Message $message
     * @return MailMessage
     */
    public static function createFromMessage(Mail\Message $message)
    {
        $message = self::createFromString($message->toString());

        return $message;
    }

    /**
     * Assemble email into raw format including headers.
     *
     * @return string
     */
    public function getRawContent()
    {
        return $this->headers->toString() . Headers::EOL . $this->getContent();
    }

    /**
     * Return true if mail has attachments,
     * inline text messages are not accounted as attachments.
     *
     * @return  boolean
     */
    public function hasAttachments()
    {
        $have_multipart = $this->isMultipart() && $this->countParts() > 0;
        if (!$have_multipart) {
            return false;
        }

        $has_attachments = false;

        // check what really the attachments are
        foreach ($this as $part) {
            $ctype = $part->getHeaderField('Content-Type');
            $disposition = $part->getHeaderField('Content-Disposition');
            $filename = $part->getHeaderField('Content-Disposition', 'filename');
            $is_attachment = $disposition == 'attachment' || $filename;

            if (in_array($ctype, ['text/plain', 'text/html', 'text/enriched'])) {
                $has_attachments |= $is_attachment;
            } else {
                // avoid treating forwarded messages as attachments
                $is_attachment |= ($disposition == 'inline' && $ctype != 'message/rfc822');
                // handle inline images
                $type = current(explode('/', $ctype));
                $is_attachment |= $type == 'image';

                $has_attachments |= $is_attachment;
            }
        }

        return (bool)$has_attachments;
    }

    /**
     * Get attachments with 'filename', 'cid', 'filetype', 'blob' array elements
     *
     * @return array
     */
    public function getAttachments()
    {
        $attachments = [];

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

            $attachments[] = [
                'filename' => $filename,
                'cid' => $headers->get('Content-Id')->getFieldValue(),
                'filetype' => $ct->getType(),
                'blob' => $body,
            ];
        }

        return $attachments;
    }

    /**
     * Returns the text message body.
     *
     * @return string The message body
     * @see Mime_Helper::getMessageBody()
     */
    public function getMessageBody()
    {
        $parts = [];
        foreach ($this as $part) {
            $ctype = $part->getHeaderField('Content-Type');
            $disposition = $part->getHeaderField('Content-Disposition');
            $filename = $part->getHeaderField('Content-Disposition', 'filename');
            $is_attachment = $disposition == 'attachment' || $filename;

            $charset = $part->getHeaderField('Content-Type', 'charset');

            switch ($ctype) {
                case 'text/plain':
                    if (!$is_attachment) {
                        $format = $part->getHeaderField('Content-Type', 'format');
                        $delsp = $part->getHeaderField('Content-Type', 'delsp');

                        $text = Mime_Helper::convertString($part->getContent(), $charset);
                        if ($format == 'flowed') {
                            $text = Mime_Helper::decodeFlowedBodies($text, $delsp);
                        }
                        $parts['text'][] = $text;
                    }
                    break;

                case 'text/html':
                    if (!$is_attachment) {
                        $parts['html'][] = Mime_Helper::convertString($part->getContent(), $charset);
                    }
                    break;

                // special case for Apple Mail
                case 'text/enriched':
                    if (!$is_attachment) {
                        $parts['html'][] = Mime_Helper::convertString($part->getContent(), $charset);
                    }
                    break;

                default:
                    // avoid treating forwarded messages as attachments
                    $is_attachment |= ($disposition == 'inline' && $ctype != 'message/rfc822');
                    // handle inline images
                    $type = current(explode('/', $ctype));
                    $is_attachment |= $type == 'image';

                    if (!$is_attachment) {
                        $parts['text'][] = $part->getContent();
                    }
            }
        }

        // now we have $parts with type 'text' and type 'html'
        if (isset($parts['text'])) {
            return implode("\n\n", $parts['text']);
        }

        if (isset($parts['html'])) {
            $str = implode("\n\n", $parts['html']);

            // hack for inotes to prevent content from being displayed all on one line.
            $str = str_replace('</DIV><DIV>', "\n", $str);
            $str = str_replace(['<br>', '<br />', '<BR>', '<BR />'], "\n", $str);
            // XXX: do we also need to do something here about base64 encoding?
            $str = strip_tags($str);

            // convert html entities. this should be done after strip tags
            $str = html_entity_decode($str, ENT_QUOTES, APP_CHARSET);

            return $str;
        }

        return null;
    }

    public function addMimePart($content, $type = Mime\Mime::TYPE_TEXT, $charset = APP_CHARSET)
    {
        $part = new Mime\Part($content);
        $part
            ->setType($type)
            ->setCharset($charset);

        // parts start from 1 somewhy,
        // and no easy wait to know how many parts there are
        if (isset($this->parts[1])) {
            $this->parts[] = $part;
        } else {
            $this->parts[1] = $part;
        }

        return $part;
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
        $references = [];

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
     * Set In-Reply-To header value.
     * The header is added if missing, otherwise value is replaced
     *
     * @param string $value
     */
    public function setInReplyTo($value)
    {
        /** @var GenericHeader $header */
        $header = $this->getHeaderByName('In-Reply-To');
        $header->setFieldValue($value);
    }

    /**
     * Set References header value.
     *
     * @param string|string[] $value
     */
    public function setReferences($value)
    {
        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        /** @var GenericHeader $header */
        $header = $this->getHeaderByName('References');
        $header->setFieldValue($value);
    }

    /**
     * Get email addresses from specified headerBag, default from "To:" and "Cc:".
     *
     * @param array $headers
     * @return string[]
     */
    public function getAddresses($headers = ['To', 'Cc'])
    {
        if (!$headers) {
            throw new InvalidArgumentException('No header field specified');
        }

        $addresses = [];
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
        return strtolower($this->getFrom()->getEmail());
    }

    /**
     * Get Address object for From header
     *
     * @return Address
     */
    public function getFrom()
    {
        $addresslist = $this->getAddressListFromHeader('from', '\Zend\Mail\Header\From');

        // obtain first address from addresses list
        $addresses = current($addresslist);
        $address = current($addresses);

        return $address ?: null;
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
     * Get the message Subject header object
     *
     * @return Subject
     */
    protected function getSubject()
    {
        // NOTE: Subject header is always present,
        // so it's safe to call this without checking for header presence
        return $this->getHeader('Subject');
    }

    /**
     * Set the message subject header value, return Subject object
     *
     * @return Subject
     */
    public function setSubject($subject)
    {
        return $this->getSubject()->setSubject($subject);
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
     * @param array|\Traversable $headerlist
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
                if ($header instanceof MessageId) {
                    /** @var MessageId $header */
                    $header->setId($value);
                } else {
                    $header->setFieldValue($value);
                }
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
        return $this->hasFlag(ZendMailStorage::FLAG_SEEN)
        || $this->hasFlag(ZendMailStorage::FLAG_DELETED)
        || $this->hasFlag(ZendMailStorage::FLAG_ANSWERED);
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
     *
     * @see Mail_Helper::stripHeaders
     */
    public function stripHeaders()
    {
        $headers = $this->headers;

        // process exact matches
        $ignore_headers = [
            'to',
            'cc',
            'bcc',
            'return-path',
            'received',
            'Disposition-Notification-To',
        ];
        foreach ($ignore_headers as $name) {
            if ($headers->has($name)) {
                $headers->removeHeader($name);
            }
        }

        // process patterns
        array_walk(
            $headers->toArray(), function ($value, $name) use ($headers) {
                if (preg_match('/^resent.*/i', $name)) {
                    $headers->removeHeader($name);
                }
            }
        );
    }

    /**
     * Set Body of a message.
     *
     * IMPORTANT: it should not contain any multipart changes,
     * as then everything will blow up as it is not parsed again.
     */
    public function setContent($content)
    {
        if ($content instanceof Mime\Message) {
            // if it's mime message,
            // build new Mail\Message and obtain it's content
            // NOTE: this is only partially correct
            // as main mail headers need to be adjusted as well
            $message = new Mail\Message();
            $message->setBody($content);

            // this is copied from Zend\Mail\Message::setBody

            // Get headers, and set Mime-Version header
            $headers = $this->getHeaders();
            $this->getHeaderByName('mime-version', self::HEADER_NS . 'MimeVersion');

            // Multipart content headers
            if ($content->isMultiPart()) {
                $mime = $content->getMime();
                /** @var ContentType $header */
                $header = $this->getHeaderByName('content-type', self::HEADER_NS . 'ContentType');
                $header->setType('multipart/mixed');
                $header->addParameter('boundary', $mime->boundary());
            } else {
                // MIME single part headers
                $parts = $content->getParts();
                if (!empty($parts)) {
                    /** @var \Zend\Mime\Part $part */
                    $part = array_shift($parts);
                    $headers->addHeaders($part->getHeadersArray("\r\n"));
                }
            }
            $this->content = $message->getBodyText();

            return;
        }

        $this->content = $content;
    }

    /**
     * Create Mime Message with text part and set as content
     *
     * @param string $content
     */
    public function setTextPart($content)
    {
        $body = new Mime\Message();
        $body->addPart(MimePart::createTextPart($content));
        $this->setContent($body);
    }

    /**
     * Returns true if message content has been set
     *
     * @return bool
     */
    public function hasContent()
    {
        return $this->content !== null;
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
     * @param string $headerName
     * @param string $headerClass Header Class name, defaults to GenericHeader
     * @return HeaderInterface|\ArrayIterator header instance or collection of headers
     * @see Zend\Mail\Message::getHeaderByName
     */
    public function getHeaderByName($headerName, $headerClass = 'GenericHeader')
    {
        // add namespace if called without namespace
        if ($headerClass[0] != '\\') {
            $headerClass = self::HEADER_NS . $headerClass;
        }

        $headers = $this->headers;
        if ($headers->has($headerName)) {
            $header = $headers->get($headerName);
        } else {
            $header = new $headerClass();
            if ($header instanceof GenericHeader) {
                $header->setFieldName($headerName);
            }
            $headers->addHeader($header);
        }

        return $header;
    }

    /**
     * Get headers as array.
     *
     * this is same as $this->getHeaders()->toArray(), but allows specifying encoding
     * thus this defaults to FORMAT_ENCODED
     *
     * @param bool $format
     * @return array
     * @see Headers::toArray
     * @see https://github.com/zendframework/zend-mail/pull/61
     */
    public function getHeadersArray($format = HeaderInterface::FORMAT_ENCODED)
    {
        $headers = [];
        /* @var $header HeaderInterface */
        foreach ($this->getHeaders() as $header) {
            if ($header instanceof MultipleHeadersInterface) {
                $name = $header->getFieldName();
                if (!isset($headers[$name])) {
                    $headers[$name] = [];
                }
                $headers[$name][] = $header->getFieldValue($format);
            } else {
                $headers[$header->getFieldName()] = $header->getFieldValue($format);
            }
        }

        return $headers;
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
            throw new DomainException(
                sprintf(
                    'Cannot grab address list from header of type "%s"; not an AbstractAddressList implementation',
                    get_class($header)
                )
            );
        }

        return $header->getAddressList();
    }
}
