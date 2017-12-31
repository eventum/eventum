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

class AttachmentTest extends TestCase
{
    public function testHasAttachments()
    {
        $raw = "Message-ID: <33@JON>X-foo: 1\r\n\r\nada";
        $message = MailMessage::createFromString($raw);
        $has_attachments = $message->countParts();
        $multipart = $message->isMultipart();
        $this->assertFalse($multipart);
        $this->assertEquals(0, $has_attachments);
        $this->assertFalse($message->getAttachment()->hasAttachments());

        $datafile = $this->getDataFile('bug684922.txt');
        $message = MailMessage::createFromFile($datafile);
        $multipart = $message->isMultipart();
        $this->assertTrue($multipart);
        $has_attachments = $message->countParts();
        $this->assertEquals(2, $has_attachments);
        $this->assertTrue($message->getAttachment()->hasAttachments());

        // this one does not have "Attachments" even it is multipart
        $datafile = $this->getDataFile('multipart-text-html.txt');
        $message = MailMessage::createFromFile($datafile);
        $this->assertFalse($message->getAttachment()->hasAttachments());
    }

    /**
     * Ensure email with text/plain attachment does not throw InvalidArgumentException
     *
     * Uncaught Exception Zend\Mail\Storage\Exception\InvalidArgumentException:
     * "Header with Name Content-Disposition or content-disposition not found"
     */
    public function testHasAttachmentPlain()
    {
        $content = $this->readDataFile('attachment-bug.txt');
        $message = MailMessage::createFromString($content);
        $attachments = $message->getAttachment();
        $this->assertTrue($attachments->hasAttachments());
    }

    public function testGetAttachments()
    {
        $raw = $this->readDataFile('bug684922.txt');

        $mail = MailMessage::createFromString($raw);
        $attachment = $mail->getAttachment();
        $this->assertTrue($attachment->hasAttachments());
        $att2 = $attachment->getAttachments();

        $this->assertCount(2, $att2);
        $att = $att2[0];
        $this->assertArrayHasKey('filename', $att);
        $this->assertArrayHasKey('cid', $att);
        $this->assertArrayHasKey('filetype', $att);
        $this->assertArrayHasKey('blob', $att);
    }

    /**
     * Multipart/related contains attachment.
     * Current implementation sees 2 attachments, should see 3.
     */
    public function testMultipartRelatedAttachments()
    {
        $content = $this->readDataFile('102232.txt');

        $mail = MailMessage::createFromString($content);
        $attachment = $mail->getAttachment();

        $this->assertTrue($attachment->hasAttachments());
        $attachments = $attachment->getAttachments();
        // just 1 attachment from related multipart
        $this->assertCount(1, $attachments);
    }

    public function testAttachmentWithDeliveryStatus()
    {
        $content = $this->readDataFile('attachment-bug.txt');
        $mail = MailMessage::createFromString($content);
        $attachment = $mail->getAttachment();
        $this->assertTrue($attachment->hasAttachments());
        $attachments = $attachment->getAttachments();
        $this->assertCount(1, $attachments);
        $this->assertEquals('message/delivery-status', $attachments[0]['filetype']);
    }

    /**
     * it should not account multipart/alternative as an attachment
     */
    public function testMultipartAlternative()
    {
        $content = $this->readDataFile('multipart-mixed-alternative-attachment.eml');
        $mail = MailMessage::createFromString($content);
        $attachment = $mail->getAttachment();

        $this->assertTrue($attachment->hasAttachments());
        $attachments = $attachment->getAttachments();
        $this->assertCount(1, $attachments);
    }

    /**
     * process multipart/related,
     * should not consider text/plain and multipart/related as attachments.
     */
    public function testMultipartRelatedWithText()
    {
        $content = $this->readDataFile('multipart-related.eml');
        $mail = MailMessage::createFromString($content);
        $attachment = $mail->getAttachment();

        $this->assertTrue($attachment->hasAttachments());
        $attachments = $attachment->getAttachments();
        $this->assertCount(1, $attachments);
    }
}
