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
use Laminas\Mail;
use Laminas\Mime;

/**
 * Helper to combine of Mail\Message, Mime\Message and MailMessage
 */
class MailBuilder
{
    private const ENCODING = 'UTF-8';

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

    public function getMessage(): Mail\Message
    {
        return $this->message;
    }

    /**
     * Add inline text part to message
     */
    public function addTextPart(string $text): self
    {
        $this->mime->addPart(MimePart::createTextPart($text));

        return $this;
    }

    /**
     * Add $attachment object as attachment to message
     */
    public function addAttachment(Attachment $attachment): self
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
     */
    public function toMailMessage(): MailMessage
    {
        $this->message->setBody($this->mime);

        return MailMessage::createFromMessage($this->message);
    }
}
