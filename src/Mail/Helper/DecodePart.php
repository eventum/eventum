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
use LogicException;
use Zend\Mail\Header\ContentTransferEncoding;

class DecodePart
{
    /** @var MailMessage */
    private $part;

    public function __construct(MailMessage $part)
    {
        $this->part = $part;
    }

    /**
     * get body.
     * have to decode ourselves or use something like Mime\Message::createFromMessage
     *
     * @return string
     */
    public function decode()
    {
        $body = $this->part->getContent();
        /** @var ContentTransferEncoding $header */
        $header = $this->part->getHeaders()->get('Content-Transfer-Encoding');
        // assume 8bit if no header
        $transferEncoding = $header ? $header->getTransferEncoding() : '8bit';

        switch ($transferEncoding) {
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
                throw new LogicException("Unsupported Content-Transfer-Encoding: '{$transferEncoding}'");
        }

        return $body;
    }
}
