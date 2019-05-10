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
use Mime_Helper;
use Support;

/**
 * Tests Mime_Decode so it could be dropped
 *
 * @see https://github.com/eventum/eventum/pull/256#issuecomment-300879398
 */
class MimeDecodeTest extends TestCase
{
    public function testFieldValues(): void
    {
        $message = $this->readDataFile('bug684922.txt');
        $mail = MailMessage::createFromString($message);

        $this->assertEquals('Some Guy <abcd@origin.com>', $mail->from);
        $this->assertEquals('PD: My: Gołblahblah', $mail->subject);
    }

    public function testSupportBuildMail(): void
    {
        $issue_id = null;
        $from = 'rööts <me@localhost>';
        $reason = 'reason';
        $subject = 'Issue closed comments';
        $cc = '';
        $to = '';
        $mail = Support::buildMail(
            $issue_id, $from,
            $to, $cc, $subject, $reason, ''
        );

        $this->assertEquals($reason, $mail->getContent());
        $this->assertEquals($reason, $mail->getMessageBody());
        $this->assertEquals($from, $mail->from);
        $this->assertEquals('', $mail->to);
        $this->assertEquals('', $mail->cc);
        $this->assertEquals($subject, $mail->subject);
    }

    /**
     * Mime_Helper::decode()->body extracts main message body if no parts present
     */
    public function testBuildMail(): void
    {
        $issue_id = null;
        $from = 'Elan Ruusamäe <root@localhost>';
        $to = '';
        $cc = '';
        $subject = 'söme messidž';
        $body = "Hello, bödi tekst\n\nBye";
        $in_reply_to = '';
        $iaf_ids = [];

        $mail = Support::buildMail($issue_id, $from, $to, $cc, $subject, $body, $in_reply_to, $iaf_ids);

        $this->assertEquals($body, $mail->getMessageBody());
    }

    public function testBuildMailSave(): void
    {
        // this is mail saved by Support::buildMail
        $content = $this->readDataFile('saved_mail.txt');
        $mail = MailMessage::createFromString($content);
        $this->assertNotEmpty($mail);
    }
}
