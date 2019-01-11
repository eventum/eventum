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

use Eventum\Mail\ImapMessage;
use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;

class LoadEmailTest extends TestCase
{
    public function testLoadBrokenReferences1()
    {
        $raw = $this->readDataFile('kallenote.eml');
        $mail = MailMessage::createFromString($raw);
        $this->assertTrue($mail->getHeaders()->has('In-Reply-To'));
    }

    public function testLoadCCHeader()
    {
        $raw = $this->readDataFile('92367.txt');
        $mail = MailMessage::createFromString($raw);
        $this->assertTrue($mail->getHeaders()->has('X-Broken-Header-CC'));
    }

    /**
     * The 91f7937b.txt contains broken `Sender` header.
     * MailMessage::createFromString fixes this by renaming header.
     */
    public function testLoad91f7937b()
    {
        $raw = $this->readDataFile('91f7937b.txt');

        // test with MailMessage
        $mail = MailMessage::createFromString($raw);
        $headers = $mail->getHeaders();
        $this->assertTrue($headers->has('X-Broken-Header-Sender'));

        // test with ImapMessage
        $parameters = ImapMessage::createParameters($raw);
        $mail = new ImapMessage($parameters);
        $headers = $mail->getHeaders();
        $this->assertTrue($headers->has('X-Broken-Header-Sender'));
    }

    public function testLoadOddMboxHeader()
    {
        $raw = $this->readDataFile('from_nocolon.txt');
        $mail = MailMessage::createFromString($raw);
        $this->assertTrue($mail->getHeaders()->has('X-Broken-Header-Mbox'));
    }
}
