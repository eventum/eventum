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

namespace Eventum\Test;

use Eventum\Mail\MailTransport;
use Zend\Mail\Transport\File;

class MailTransportTest extends TestCase
{
    public function testSingleRecipient()
    {
        $recipient = 'root@localhost';
        $headers = ['Subject: lol'];
        $body = 'nothing';
        $mail = $this->send($recipient, $headers, $body);

        // result looks like email
        $this->assertContains('Subject: lol', $mail);
        // $recipient is not added to headers
        $this->assertNotContains($recipient, $mail);
    }

    /**
     * Send mail via Transport\File
     *
     * @param string $recipient
     * @param array $headers
     * @param string $body
     * @return string contents of the sent mail
     */
    private function send($recipient, $headers, $body)
    {
        $mta = $this->getMailTransport();
        $mta->send($recipient, $headers, $body);
        $transport = $mta->getTransport();

        $lastFile = $transport->getLastFile();
        $this->assertFileExists($lastFile);

        return file_get_contents($lastFile);
    }

    /**
     * Create MailTransport with our Transport\File transport
     *
     * @return MailTransport
     */
    private function getMailTransport()
    {
        $stub = $this->getMockBuilder(MailTransport::class)
            ->setMethods(['getTransport'])
            ->getMock();

        $stub->method('getTransport')
            ->willReturn(new File());

        return $stub;
    }
}
