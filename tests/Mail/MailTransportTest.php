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
use Eventum\Test\TestCase;
use stdClass;
use Zend\Mail\Headers;
use Zend\Mail\Protocol;
use Zend\Mail\Transport;

/**
 * Class MailTransportTest
 *
 * @group mail
 */
class MailTransportTest extends TestCase
{
    /**
     * Converting MailMessage to Mail\Message in Transport\SMTP
     * caused ASCII encoding on headers
     * which failed the toString call later.
     */
    public function testMessageObject()
    {
        list($recipient, $headers, $body) = $this->loadMailTrace('zf-mail-591ca27fb27c2.json');

        $message = MailMessage::createFromHeaderBody((array)$headers, $body)->toMessage();

        // this logic is from Smtp::send
        // $headers = $this->prepareHeaders($message);
        /** @see \Zend\Mail\Transport\Smtp::send() */

        $headers = clone $message->getHeaders();
        $headers->removeHeader('Bcc');

        // should not be QP encoded with UTF-8
        $res = $headers->get('Message-Id')->toString();
        $this->assertEquals('Message-ID: <eventum.md5.5as5i4vw4.2uxbmbcboc8wk@eventum.example.org>', $res);

        // should be QP encoded with UTF-8
        $res = $headers->get('To')->toString();
        $this->assertEquals('To: =?UTF-8?Q?Elan=20Ruusam=C3=A4e?= <glen@example.org>', $res);

        // toString should not throw
        $res = $headers->toString();
        $this->assertNotEmpty($res);
    }

    public function testSingleRecipient()
    {
        $recipient = 'root@localhost';
        $from = 'noreply@localhost';
        $headers = ['Subject: lol', "From: $from"];
        $body = 'nothing';

        $mail = MailMessage::createFromHeaderBody($headers, $body);

        $res = $this->send($recipient, $mail);

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
    private function send($recipient, $mail)
    {
        $mta = $this->getMailTransport();
        $mta->send($recipient, $mail);

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

    /**
     * Load saved Mail\Transport trace file
     *
     * @param string $traceFile
     * @return array
     */
    private function loadMailTrace($traceFile)
    {
        $contents = $this->readDataFile($traceFile);
        $this->assertJson($contents);
        $data = json_decode($contents);
        $this->assertNotEmpty($data);

        return $data;
    }
}
