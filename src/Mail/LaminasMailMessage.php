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

use Laminas\Mail\Headers;
use Laminas\Mail\Message;

/**
 * See MailMessage::toMessage for long story
 *
 * @internal
 */
class LaminasMailMessage extends Message
{
    public function forceHeaders(Headers $headers): void
    {
        $this->headers = $headers;
    }
}
