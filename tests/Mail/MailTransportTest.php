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

use Eventum\Mail\MailTransport;
use Eventum\Test\TestCase;
use stdClass;
use Zend\Mail\Protocol;
use Zend\Mail\Transport;

/**
 * Class MailTransportTest
 *
 * @group mail
 */
class MailTransportTest extends TestCase
{
    public function testSingleRecipient()
    {
        $recipient = 'root@localhost';
        $from = 'noreply@localhost';
        $headers = ['Subject: lol', "From: $from"];
        $body = 'nothing';

        $res = $this->send($recipient, $headers, $body);

        // MAIL FROM<>
        $this->assertEquals($from, $res->mail);

        // RCPT TO<>
        $rcpt = $res->rcpt;
        $this->assertCount(1, $rcpt);
        $this->assertEquals($recipient, $rcpt[0]);

        // DATA
        // result looks like email
        $this->assertContains('Subject: lol', $res->data);
        // $recipient is not added to headers
        $this->assertNotContains($recipient, $res->data);
    }

    /**
     * Send mail via mocked objects
     *
     * @return object with mock results
     */
    private function send($recipient, $headers, $body)
    {
        $mta = $this->getMailTransport();
        $mta->send($recipient, $headers, $body);

        /** @var object $connection */
        $connection = $mta->getTransport()->getConnection();

        return $connection->mockResults;
    }

    /**
     * Create MailTransport mock that doesn't really send mail.
     *
     * @return MailTransport
     */
    private function getMailTransport()
    {
        $stub = $this->getMockBuilder(MailTransport::class)
            ->setMethods(['getTransport'])
            ->getMock();

        $stub->method('getTransport')
            ->willReturn($this->getTransportSmtp());

        return $stub;
    }

    /**
     * @return Transport\Smtp
     */
    private function getTransportSmtp()
    {
        $transport = $this->getMockBuilder(Transport\Smtp::class)
            ->setMethods(['getConnection', 'connect', 'mail'])
            ->getMock();

        $protocol = $this->getProtocolSmtp();
        $transport->method('getConnection')
            ->willReturn($protocol);

        $transport->method('connect')
            ->willReturn($protocol);

        return $transport;
    }

    /**
     * Setup stubs to collect parameters to object property
     *
     * @return Protocol\Smtp;
     */
    private function getProtocolSmtp()
    {
        $stub = $this->getMockBuilder(Protocol\Smtp::class)
            ->setMethods(['mail', 'rcpt', 'data'])
            ->getMock();

        $results = new stdClass();
        $results->rcpt = [];
        $results->data = null;
        $results->mail = null;

        $stub->mockResults = $results;
        $stub->method('mail')
            ->with(
                $this->callback(
                    function ($mail) use ($results) {
                        $results->mail = $mail;

                        return true;
                    }
                )
            );

        $stub->method('rcpt')
            ->with(
                $this->callback(
                    function ($to) use ($results) {
                        $results->rcpt[] = $to;

                        return true;
                    }
                )
            );

        $stub->method('data')
            ->with(
                $this->callback(
                    function ($data) use ($results) {
                        $results->data = $data;

                        return true;
                    }
                )
            );

        return $stub;
    }
}
