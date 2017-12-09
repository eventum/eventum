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

use Eventum\Mail\Helper\WarningMessage;
use Eventum\Mail\MailBuilder;
use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;
use Zend\Mail\Exception\InvalidArgumentException;

class WarningMessageTest extends TestCase
{
    public function testAddToPlainText()
    {
        $issue_id = 1;
        $email = 'root@localhost';

        $mail = MailMessage::createFromString("X-Foo: 1\r\n\r\nHello. Bääm");

        $this->runAddAndRemoveTests($mail, $issue_id, $email);
    }

    public function testMailBuilderText()
    {
        $issue_id = 1;
        $recipient = $from = 'root@localhost';
        $body = $subject = 'Hello. Bääm';

        $builder = new MailBuilder();
        $builder
            ->addTextPart($body)
            ->getMessage()
            ->setSubject($subject)
            ->setTo($recipient)
            ->setFrom($from);

        $mail = $builder->toMailMessage();
        $this->runAddAndRemoveTests($mail, $issue_id, $recipient);
    }

    /**
     * NOT YET
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Multipart not supported
     */
    public function testMultipart()
    {
        $issue_id = 1;
        $email = 'root@localhost';

        $mail = MailMessage::createFromFile(__DIR__ . '/../data/bug684922.txt');
        $wm = $this->getWarningMessage($mail);

        // adding should be visible
        $raw1 = $mail->getRawContent();
        $wm->add($issue_id, $email);
        $raw2 = $mail->getRawContent();
        $this->assertNotEquals($raw1, $raw2);

        // removing should get back to original
        $wm->remove();
        $raw3 = $mail->getRawContent();
        $this->assertEquals($raw1, $raw3);
    }

    public function testAddWarningMessagePlain()
    {
        $issue_id = 1;
        $recipient = 'admin@example.com';
        $body = 'here be dragons';

        // add the warning message to the current message' body, if needed
        $m = MailMessage::createFromHeaderBody([], $body);

        $wm = $this->getWarningMessage($m);
        $wm->add($issue_id, $recipient);

        $fixed_body = $m->getContent();
        $this->assertContains($body, $fixed_body);
    }

    public function testAddWarningMessageMultipart()
    {
        $issue_id = 1;
        $recipient = 'admin@example.com';

        $this->markTestSkipped('multipart yet not supported');

        $content = $this->readDataFile('attachment-bug.txt');
        $m = MailMessage::createFromString($content);

        $wm = $this->getWarningMessage($m);
        $wm->add($issue_id, $recipient);

        $fixed_body = $m->getContent();
        $this->assertContains('Your reply will be sent to the notification list', $fixed_body);
    }

    private function runAddAndRemoveTests(MailMessage $mail, $issue_id, $email)
    {
        $wm = $this->getWarningMessage($mail);

        // adding should be visible
        $raw1 = $mail->getRawContent();
        $wm->add($issue_id, $email);
        $raw2 = $mail->getRawContent();
        $this->assertNotEquals($raw1, $raw2);

        // removing should get back to original
        $wm->remove();
        $raw3 = $mail->getRawContent();
        $this->assertEquals($raw1, $raw3);
    }

    /**
     * @param MailMessage $mail
     * @param bool $shouldAddWarningMessage
     * @return WarningMessage|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWarningMessage(MailMessage $mail, $shouldAddWarningMessage = true)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $stub */
        $stub = $this->getMockBuilder(WarningMessage::class)
            ->setConstructorArgs([$mail])
            ->setMethods(['enabled', 'isAllowedToEmail', 'shouldAddWarningMessage'])
            ->getMock();

        $stub->method('enabled')
            ->willReturn(true);
        $stub->method('isAllowedToEmail')
            ->willReturn(true);
        $stub->method('shouldAddWarningMessage')
            ->willReturn($shouldAddWarningMessage);

        return $stub;
    }
}
