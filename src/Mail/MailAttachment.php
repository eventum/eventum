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

use Eventum\Mail\Helper\DecodePart;
use Zend\Mail;
use Zend\Mail\Header\ContentType;
use Zend\Mail\Storage;
use Zend\Mail\Storage\Message;
use Zend\Mime\Part;

class MailAttachment
{
    /** @var MailMessage|Storage\Part\PartInterface */
    private $message;

    public function __construct(MailMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Return true if mail has attachments,
     * inline text messages are not accounted as attachments.
     *
     * TODO: handle application/pgp-signature, application/ms-tnef?
     *
     * @see https://github.com/eventum/eventum/blob/v3.2.1/lib/eventum/class.mime_helper.php#L740-L753
     *
     * @return  bool
     */
    public function hasAttachments()
    {
        $have_multipart = $this->message->isMultipart() && $this->message->countParts() > 0;
        if (!$have_multipart) {
            return false;
        }

        foreach ($this->message as $part) {
            if ($this->isAttachment($part)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get attachments with 'filename', 'cid', 'filetype', 'blob' array elements
     *
     * @return array
     */
    public function getAttachments()
    {
        $attachments = [];

        /** @var MailMessage $part */
        foreach ($this->message as $part) {
            $headers = $part->getHeaders();

            /** @var ContentType $ct */
            $ct = $headers->get('Content-Type');
            $type = $ct->getType();

            // multipart/alternative should have text/plain and text/html, none of them are "attachments"
            if ($type === 'multipart/alternative') {
                continue;
            }

            if ($type === 'multipart/related') {
                // get attachments from multipart/related
                $subpart = new self($part);

                // only include non text/html
                // this will resemble previous eventum behavior
                // whether that's correct is another topic
                foreach ($subpart->getAttachments() as $attachment) {
                    if ($attachment['filetype'] === 'text/html') {
                        continue;
                    }
                    $attachments[] = $attachment;
                }

                // don't add related part itself
                continue;
            }

            if (!$this->isAttachment($part)) {
                continue;
            }

            // attempt to extract filename
            // 1. try Content-Type: name parameter
            // 2. try Content-Disposition: filename parameter
            // 3. as last resort use Untitled with extension from mime-type subpart
            $filename = $ct->getParameter('name');
            if (!$filename) {
                try {
                    $filename = $part->getHeaderField('Content-Disposition', 'filename');
                } catch (Mail\Exception\InvalidArgumentException $e) {
                }
            }
            if (!$filename) {
                $parts = explode('/', $type);
                $filename = ev_gettext('Untitled.%s', end($parts));
            }

            $cid = $headers->has('Content-Id') ? $headers->get('Content-Id')->getFieldValue() : null;

            $attachments[] = [
                'filename' => $filename,
                'cid' => $cid,
                'filetype' => $type,
                'blob' => (new DecodePart($part))->decode(),
            ];
        }

        return $attachments;
    }

    /**
     * @param MailMessage $part
     * @return bool
     */
    private function isAttachment(MailMessage $part)
    {
        $is_attachment = false;
        $disposition = $filename = null;

        $ctype = $part->getHeaderField('Content-Type');
        if ($part->getHeaders()->has('Content-Disposition')) {
            $disposition = $part->getHeaderField('Content-Disposition');
            $filename = $part->getHeaderField('Content-Disposition', 'filename');
            $is_attachment = $disposition === 'attachment' || $filename;
        }

        if (in_array($ctype, ['text/plain', 'text/html', 'text/enriched'], true)) {
            return $is_attachment;
        }

        if ($ctype === 'multipart/related') {
            // multipart/related may have subparts (inline html)
            return (new self($part))->hasAttachments();
        }

        // avoid treating forwarded messages as attachments
        if ($disposition === 'inline' && $ctype !== 'message/rfc822') {
            return true;
        }

        // handle inline images
        $type = current(explode('/', $ctype));
        $is_attachment |= $type === 'image';

        return $is_attachment;
    }
}
