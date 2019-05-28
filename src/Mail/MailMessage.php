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
use DomainException;
use Ds\Set;
use Eventum\Mail\Exception\InvalidMessageException;
use Eventum\Mail\Helper\MailLoader;
use Eventum\Mail\Helper\SanitizeHeaders;
use Eventum\Mail\Helper\TextMessage;
use InvalidArgumentException;
use Zend\Mail;
use Zend\Mail\Address;
use Zend\Mail\AddressList;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\Cc;
use Zend\Mail\Header\Date;
use Zend\Mail\Header\From;
use Zend\Mail\Header\GenericHeader;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\InReplyTo;
use Zend\Mail\Header\MessageId;
use Zend\Mail\Header\MultipleHeadersInterface;
use Zend\Mail\Header\References;
use Zend\Mail\Header\Subject;
use Zend\Mail\Header\To;
use Zend\Mail\Headers;
use Zend\Mail\Storage;
use Zend\Mail\Storage\Message;

/**
 * Class MailMessage
 *
 * @property-read string $messageId a Message-Id header value
 * @property-read string $from a From header value
 * @property-read string $to a To header value
 * @property-read string $cc a Cc header value
 * @property-read string $date a Date header value
 * @property-read string $subject a Subject header value
 * @property-read string $inReplyTo a In-Reply-To header value
 * @property-read string $references a References header value
 */
class MailMessage extends Message
{
    const ENCODING = APP_CHARSET;

    /** @var MailAttachment */
    private $attachment;

    /**
     * Public constructor
     *
     * @param array $params
     */
    public function __construct(array $params = [])
    {
        try {
            parent::__construct($params);
        } catch (Mail\Exception\InvalidArgumentException $e) {
            throw InvalidMessageException::create($e, $params);
        }

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

        // ensure encoding is set
        $message->getHeaders()->setEncoding(self::ENCODING);

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
        MailLoader::splitMessage($raw, $headers, $content);

        return new self(['root' => true, 'headers' => $headers, 'content' => $content]);
    }

    /**
     * Create Mail object from headers array (or string) and body string
     *
     * @param string|array $headers
     * @param string $content
     * @return MailMessage
     */
    public static function createFromHeaderBody($headers, $content)
    {
        if (is_string($headers)) {
            // create from string which is more relax for broken mails
            return self::createFromString($headers . "\r\n\r\n" . $content);
        }

        if (is_array($headers)) {
            MailLoader::encodeHeaders($headers);
        }

        return new self(['root' => true, 'headers' => $headers, 'content' => $content]);
    }

    /**
     * Create Mail object from specified filename
     *
     * @param string $filename Path to the file to read in
     * @return MailMessage
     */
    public static function createFromFile($filename)
    {
        return new self(['root' => true, 'file' => $filename]);
    }

    /**
     * Create from Zend\Mail\Message object
     *
     * @param Mail\Message $message
     * @return MailMessage
     */
    public static function createFromMessage(Mail\Message $message)
    {
        return self::createFromString($message->toString());
    }

    /**
     * Convert to Mail\Message
     *
     * @return Mail\Message
     */
    public function toMessage()
    {
        /**
         * Round 1:
         *
         * Converting MailMessage to Mail\Message in Transport\SMTP
         * caused ASCII encoding on headers, which failed the toString call later.
         *
         * A fix:
         *  $message->setEncoding('UTF-8');
         *
         * Round 2.
         * however that caused all headers be UTF-8 encoded, so Message-Id header become like:
         *  Message-ID: =?UTF-8?Q?<eventum.md5.5as5i4vw4.2uxbmbcboc8wk@eventum.example.org>?=
         *
         * Solution:
         *   Serialize to text and back in.
         *
         * Round 3.
         * Serialize loaded in everything back in ASCII, back to square one.
         *
         * Solution:
         *   Create wrapper for Mail\Message to set Headers without re-encoding them.
         */

        $message = new ZendMailMessage();
        $message->forceHeaders($this->getHeaders());
        $message->setBody($this->getContent());

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
     * Return Attachment object related to current Mail Message
     *
     * @return MailAttachment
     */
    public function getAttachment()
    {
        if (!$this->attachment) {
            $this->attachment = new MailAttachment($this);
        }

        return $this->attachment;
    }

    /**
     * Return true if mail has attachments,
     * inline text messages are not accounted as attachments.
     *
     * @return  bool
     * @deprecated
     */
    public function hasAttachments()
    {
        return $this->getAttachment()->hasAttachments();
    }

    /**
     * Get attachments with 'filename', 'cid', 'filetype', 'blob' array elements
     *
     * @return array
     * @deprecated
     */
    public function getAttachments()
    {
        return $this->getAttachment()->getAttachments();
    }

    /**
     * Returns the text message body.
     *
     * @return string|null The message body
     */
    public function getMessageBody(): ?string
    {
        return (new TextMessage($this))->getMessageBody();
    }

    /**
     * Returns the referenced message-id for a given reply.
     */
    public function getReferenceMessageId(): ?string
    {
        if ($this->headers->has('In-Reply-To')) {
            /** @var InReplyTo $header */
            $header = $this->headers->get('In-Reply-To');
            $ids = new Set($header->getIds());

            return sprintf('<%s>', $ids->first());
        }

        if (!$this->headers->has('References')) {
            return null;
        }

        /** @var References $header */
        $header = $this->headers->get('References');
        $references = new Set($header->getIds());

        return sprintf('<%s>', $references->first());
    }

    /**
     * Returns the message IDs of all emails this message references.
     *
     * @return string[] An array of message-ids
     */
    public function getAllReferences(): array
    {
        // if X-Forwarded-Message-Id is present, assume this is forwarded email and this is root email
        // thus all references must be cleared
        if ($this->headers->has('X-Forwarded-Message-Id')) {
            return [];
        }

        $references = new Set();

        if ($this->headers->has('In-Reply-To')) {
            /** @var InReplyTo $header */
            $header = $this->headers->get('In-Reply-To');
            $references->add(...$header->getIds());
        }

        if ($this->headers->has('References')) {
            /** @var References $header */
            $header = $this->headers->get('References');
            $references->add(...$header->getIds());
        }

        // Eventum uses "<>" internally, but these got removed in zend 2.10
        $result = new Set();
        foreach ($references as $reference) {
            $result->add(sprintf('<%s>', $reference));
        }

        return $result->toArray();
    }

    /**
     * Set In-Reply-To header value.
     * The header is added if missing, otherwise value is replaced
     *
     * @param string $value
     */
    public function setInReplyTo(string $value): void
    {
        /** @var InReplyTo $header */
        $header = $this->getHeaderByName('In-Reply-To', InReplyTo::class);
        $header->setIds([$value]);
    }

    /**
     * Set References header value.
     *
     * @param string|string[] $value
     */
    public function setReferences($value): void
    {
        if (!is_array($value)) {
            $value = [$value];
        }

        /** @var References $header */
        $header = $this->getHeaderByName('References', References::class);
        $header->setIds($value);
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
        /** @var From $from */
        $from = $this->getHeader('from');

        // return null not false if header missing
        return $from->getAddressList()->rewind() ?: null;
    }

    /**
     * Access the address list of the To header
     *
     * @return AddressList
     * @see \Zend\Mail\Message::getTo
     */
    public function getTo()
    {
        return $this->getAddressListFromHeader('to', To::class);
    }

    /**
     * Retrieve list of CC recipients
     *
     * @return AddressList
     * @see \Zend\Mail\Message::getCc
     */
    public function getCc()
    {
        return $this->getAddressListFromHeader('cc', Cc::class);
    }

    /**
     * Get Date as DateTime object
     *
     * @return DateTime
     */
    public function getDate()
    {
        return new DateTime($this->date);
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
        /** @var Subject $subject */
        $subject = $this->getHeader('Subject');

        return $subject;
    }

    /**
     * Set the message subject header value
     *
     * @param string $subject
     * @return $this
     */
    public function setSubject($subject)
    {
        $this->getSubject()->setSubject($subject);

        return $this;
    }

    /**
     * Set To: header
     *
     * @param string|AddressList $value
     * @return $this
     */
    public function setTo($value)
    {
        $this->setAddressListHeader('To', $value);

        return $this;
    }

    /**
     * Set From: header
     *
     * @param string|AddressList $value
     * @return $this
     */
    public function setFrom($value)
    {
        $this->setAddressListHeader('From', $value);

        return $this;
    }

    /**
     * Set Date: header
     *
     * @param string $value
     * @return $this
     */
    public function setDate($value = null)
    {
        $value = $value ?: Date_Helper::getRFC822Date(time());

        // can not update 'Date' header, so remove it
        if ($this->headers->has('Date')) {
            $this->headers->removeHeader('Date');
        }

        $header = new Date($value);
        $this->headers->addHeader($header);

        return $this;
    }

    /**
     * Set AddressList type header a value
     *
     * @param string $name
     * @param string|AddressList $value
     */
    public function setAddressListHeader($name, $value): void
    {
        /** @var AbstractAddressList $header */
        $header = $this->getHeader($name);
        if ($value instanceof AddressList) {
            $addresslist = $value;
        } else {
            $addresslist = new AddressList();
            $addresslist->addFromString($value);
        }
        $header->setAddressList($addresslist);
    }

    /**
     * Set many headers at once
     *
     * Expects an array (or Traversable object) of name/value pairs.
     *
     * @param array|\Traversable $headerlist
     */
    public function addHeaders(array $headerlist): void
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
     * @param string $header a header name, like 'To', or 'Cc'
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
        return $this->hasFlag(Storage::FLAG_SEEN)
            || $this->hasFlag(Storage::FLAG_DELETED)
            || $this->hasFlag(Storage::FLAG_ANSWERED);
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

        return strpos($email, 'mailer-daemon@') === 0;
    }

    /**
     * Strips out email headers that should not be sent over to the recipient
     * of the routed email. The 'Received:' header was sometimes being used to
     * validate the sender of the message, and because of that some emails were
     * not being delivered correctly.
     *
     * FIXME: think of better method name
     */
    public function stripHeaders(): void
    {
        $headers = $this->headers;

        // process exact matches
        $ignore_headers = [
            'to',
            'cc',
            'bcc',
            'return-path',
            'received',
            'disposition-notification-to',
        ];
        foreach ($ignore_headers as $name) {
            if ($headers->has($name)) {
                $headers->removeHeader($name);
            }
        }

        // process patterns
        $array = $headers->toArray();
        array_walk(
            $array, function ($value, $name) use ($headers): void {
                if (preg_match('/^resent.*/i', $name)) {
                    $headers->removeHeader($name);
                }
            }
        );
    }

    /**
     * Set body of a message.
     *
     * NOTE: if you have multiparts, you should look into MailBuilder.
     *
     * @param string $content
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
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
     *
     * As the headers are deep objects with possibly unknown structure
     * only way to clone and ensure they are cloned, is to serialize to text.
     *
     * so, rather using clone, remove the header you are about to modify instead.
     */
    public function __clone()
    {
        $headers = Headers::fromString($this->headers->toString());

        $this->headers = $headers;
    }

    /**
     * Retrieve a header by name
     *
     * If not found, instantiates one based on $headerClass.
     *
     * @param string $headerName
     * @param string $headerClass Header Class name, defaults to GenericHeader
     * @return HeaderInterface|\ArrayIterator header instance or collection of headers
     * @see \Zend\Mail\Message::getHeaderByName
     */
    public function getHeaderByName($headerName, $headerClass = GenericHeader::class)
    {
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
     * @see \Zend\Mail\Message::getAddressListFromHeader
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
