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

namespace Eventum\Mail\Helper;

use Horde_Text_Flowed;
use Laminas\Mail\Storage\Part\PartInterface;
use LogicException;
use Mime_Helper;

/**
 * Creates textual representation of the message body.
 */
class TextMessage
{
    /** @var PartInterface */
    private $message;
    /** @var PartInterface[] */
    private $alttext = [];
    /** @var PartInterface[] */
    private $text = [];
    /** @var PartInterface[] */
    private $html = [];

    public function __construct(PartInterface $message)
    {
        $this->message = $message;
    }

    public function getMessageBody(): string
    {
        $isMultipart = $this->message->isMultipart();

        foreach ($this->message as $part) {
            $this->processPart($part);
        }

        // if no parts were extracted, process main message itself
        if (!$isMultipart && !$this->hasText()) {
            $this->processPart($this->message);
        }

        // alternative text present but no main text, fill it
        if ($this->alttext && !$this->text) {
            $this->text = $this->alttext;
        }

        if ($this->text) {
            return $this->getText();
        }

        if ($this->html) {
            return $this->getHtml();
        }

        if (!$isMultipart) {
            throw new LogicException('Should not be reached');
        }

        return '';
    }

    private function hasText(): bool
    {
        return $this->html || $this->text || $this->alttext;
    }

    /**
     * @param PartInterface $part
     */
    private function processPart($part): void
    {
        $headers = $part->getHeaders();
        $hasContentType = $headers->has('Content-Type');
        $hasDisposition = $headers->has('Content-Disposition');
        $contentType = $hasContentType ? $part->getHeaderField('Content-Type') : null;
        $disposition = $hasDisposition ? $part->getHeaderField('Content-Disposition') : null;
        $filename = $hasDisposition ? $part->getHeaderField('Content-Disposition', 'filename') : null;
        $is_attachment = $disposition === 'attachment' || $filename;
        $charset = $hasContentType ? $part->getHeaderField('Content-Type', 'charset') : null;
        $content = (new DecodePart($part))->decode();

        switch ($contentType) {
            case 'multipart/related':
                // multipart/related is likely a container for html with image multiparts
                // see https://tools.ietf.org/html/rfc2387
                //
                // from multipart related, extract body if text parts are missing.
                $this->alttext[] = (new self($part))->getMessageBody();

                break;

            case 'multipart/alternative':
                $this->text[] = (new self($part))->getMessageBody();
                break;

            case 'text/plain':
                if (!$is_attachment) {
                    $format = $part->getHeaderField('Content-Type', 'format');

                    $content = Mime_Helper::convertString($content, $charset);
                    if ($format === 'flowed') {
                        $delsp = $part->getHeaderField('Content-Type', 'delsp');
                        $flowed = new Horde_Text_Flowed($content, 'UTF-8');
                        $flowed->setDelSp($delsp === 'yes');
                        $content = $flowed->toFixed();
                    }
                    $this->text[] = $content;
                }
                break;

            case 'text/html':
                if (!$is_attachment) {
                    $this->html[] = Mime_Helper::convertString($content, $charset);
                }
                break;

            // special case for Apple Mail
            case 'text/enriched':
                if (!$is_attachment) {
                    $this->html[] = Mime_Helper::convertString($content, $charset);
                }
                break;

            default:
                // avoid treating forwarded messages as attachments
                $is_attachment |= ($disposition === 'inline' && $contentType !== 'message/rfc822');
                // handle inline images
                $type = current(explode('/', $contentType));
                $is_attachment |= $type === 'image';

                if (!$is_attachment) {
                    $this->text[] = $content;
                }
        }
    }

    private function getText(): string
    {
        return trim(implode("\n\n", $this->text));
    }

    private function getHtml(): string
    {
        $str = implode("\n\n", $this->html);

        // hack for inotes to prevent content from being displayed all on one line.
        $str = str_replace(['</DIV><DIV>', '<br>', '<br />', '<BR>', '<BR />'], "\n", $str);
        $str = strip_tags($str);

        // convert html entities. this should be done after strip tags
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');

        return trim($str);
    }
}
