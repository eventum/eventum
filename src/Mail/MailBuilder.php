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

use Eventum\Attachment\Attachment;
use Eventum\Mail\Helper\MimePart;
use Zend\Mail;
use Zend\Mime;

/**
 * Trivial helper to combine of Mail\Message, Mime\Message and MailMessage
 */
class MailBuilder
{
    const ENCODING = APP_CHARSET;

    /** @var Mail\Message */
    private $message;

    /** @var Mime\Message */
    private $mime;

    public function __construct()
    {
        $this->message = new Mail\Message();
        $this->message->setEncoding(self::ENCODING);

        $this->mime = new Mime\Message();
    }

    /**
     * @return Mail\Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Add inline text part to message
     *
     * @param string $text
     * @return $this
     */
    public function addTextPart($text)
    {
        $this->mime->addPart(MimePart::createTextPart($text));

        return $this;
    }

    /**
     * Add $attachment object as attachment to message
     *
     * @param Attachment $attachment
     * @return $this
     */
    public function addAttachment(Attachment $attachment)
    {
        $part = MimePart::createAttachmentPart(
            $attachment->getFileContents(),
            $attachment->filetype,
            $attachment->filename
        );
        $this->mime->addPart($part);

        return $this;
    }

    /**
     * Convert to MailMessage.
     *
     * it's not recommended to call this message more than once on same object,
     * the behavior is undefined.
     *
     * @return MailMessage
     */
    public function toMailMessage()
    {
        $this->message->setBody($this->mime);

        return MailMessage::createFromMessage($this->message);
    }
}
