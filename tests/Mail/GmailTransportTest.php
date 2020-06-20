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
use Eventum\Mail\MailTransport;
use Eventum\ServiceContainer;
use Eventum\Test\TestCase;
use Setup;

/**
 * Class GmailTransportTest
 *
 * Test that smtp.gmail.com works and doesn't error out with:
 * "Must issue a STARTTLS command first"
 *
 * @group mail
 * @see https://github.com/eventum/eventum/issues/308
 */
class GmailTransportTest extends TestCase
{
    public function testGmailTransport(): void
    {
        $this->configureSmtp();

        $mail = new MailTransport();
        $address = 'glen@delfi.ee';
        $headers = [];
        $body = 'test';
        $message = MailMessage::createFromHeaderBody($headers, $body);
        $mail->send($address, $message);
    }

    private function configureSmtp(): void
    {
        $smtpSetup = ServiceContainer::getConfig()['tests.smtp'];
        if (!$smtpSetup) {
            $this->markTestSkipped('configure tests.smtp for test');
        }

        $smtp = [
            'from' => 'xyz@domain.cz',
            'host' => 'smtp.gmail.com',
            'port' => '587',
            'ssl' => 'tls',
            'auth' => true,
            'username' => $smtpSetup['username'],
            'password' => $smtpSetup['password'],
            'type' => 'smtp',
        ];

        Setup::set(['smtp' => $smtp]);
    }
}
