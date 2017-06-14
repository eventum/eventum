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

namespace Eventum\Test\Mail;

use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;
use Mail_Helper;

class ForwardedRoutingTest extends TestCase
{
    /**
     * Test that message forwarded by Thunderbird gets new issue
     * i.e if mail has Matching In-Reply-To header, but also X-Forwarded-Message-Id header
     * the email is not associated by new issue created
     */
    public function testForwardedMailRouting()
    {
        $full_message = file_get_contents(__DIR__ . '/data/thunderbird-forwarded.txt');
        $message = MailMessage::createFromString($full_message);

        $headers = $message->getHeaders();

        $references = Mail_Helper::getAllReferences($headers->toString());
        $this->assertEmpty($references);
    }
}
