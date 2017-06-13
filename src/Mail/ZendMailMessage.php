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

use Zend\Mail\Headers;
use Zend\Mail\Message;

/**
 * See MailMessage::toMessage for long story
 *
 * @internal
 */
class ZendMailMessage extends Message
{
    public function forceHeaders(Headers $headers)
    {
        $this->headers = $headers;
    }
}
