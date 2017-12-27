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

use Eventum\Mail\MailMessage;
use Mime_Helper;
use Zend\Mail\Storage\Part;

/**
 * Creates textual representation of the message body.
 */
class TextMessage
{
    /** @var MailMessage|Part\PartInterface */
    private $message;

    public function __construct(MailMessage $message)
    {
        $this->message = $message;
    }

    public function getMessageBody()
    {
        $parts = [];
        foreach ($this->message as $part) {
            $headers = $part->getHeaders();
            $ctype = $part->getHeaderField('Content-Type');
            $hasDisposition = $headers->has('Content-Disposition');
            $disposition = $hasDisposition ? $part->getHeaderField('Content-Disposition') : null;
            $filename = $hasDisposition ? $part->getHeaderField('Content-Disposition', 'filename') : null;
            $is_attachment = $disposition === 'attachment' || $filename;

            $charset = $part->getHeaderField('Content-Type', 'charset');

            switch ($ctype) {
                case 'multipart/alternative':
                    $parts['text'][] = (new self($part))->getMessageBody();
                    break;

                case 'text/plain':
                    if (!$is_attachment) {
                        $format = $part->getHeaderField('Content-Type', 'format');
                        $delsp = $part->getHeaderField('Content-Type', 'delsp');

                        $text = Mime_Helper::convertString((new DecodePart($part))->decode(), $charset);
                        if ($format === 'flowed') {
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
                    $is_attachment |= ($disposition === 'inline' && $ctype !== 'message/rfc822');
                    // handle inline images
                    $type = current(explode('/', $ctype));
                    $is_attachment |= $type === 'image';

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
            $str = str_replace(['</DIV><DIV>', '<br>', '<br />', '<BR>', '<BR />'], "\n", $str);
            // XXX: do we also need to do something here about base64 encoding?
            $str = strip_tags($str);

            // convert html entities. this should be done after strip tags
            $str = html_entity_decode($str, ENT_QUOTES, APP_CHARSET);

            return $str;
        }

        if (!$this->message->isMultipart()) {
            // fallback to read just main part
            return (new DecodePart($this->message))->decode();
        }

        return '';
    }
}
