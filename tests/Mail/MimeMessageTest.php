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

use Eventum\Mail\Helper\MailBuilder;
use Eventum\Mail\Helper\MimePart;
use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;
use Zend\Mail\Header\MessageId;
use Zend\Mime;

class MimeMessageTest extends TestCase
{
    /** @var MailMessage */
    private $mail;

    private $from;
    private $to;
    private $subject;
    private $message_id;
    private $date;

    public function setUp()
    {
        $this->from = '"Admin User " <note-3@eventum.example.org>';
        $this->to = '"Admin User" <admin@example.com>';
        $this->subject = '[#3] Note: Re: pläh';
        $this->message_id = 'eventum.md5.5bh5b2b2k.1odx18yqps5xd@eventum.example.org';
        $this->date = 'Wed, 19 Jul 2017 18:15:33 GMT';

        $mail = MailMessage::createNew()
            ->setFrom($this->from)
            ->setSubject($this->subject)
            ->setTo($this->to)
            ->setDate($this->date);

        /** @var MessageId $header */
        $header = $mail->getHeaderByName('Message-Id');
        $header->setId($this->message_id);

        $this->mail = $mail;
    }

    public function testMimeMessageText()
    {
        $body = "Hello, bödi tekst\n\nBye\n";

        $mime = new Mime\Message();
        $mime->addPart(MimePart::createTextPart($body));
        $this->mail->setContent($mime);

        $exp = [];
        $exp[] = 'Message-ID: <eventum.md5.5bh5b2b2k.1odx18yqps5xd@eventum.example.org>';
        $exp[] = 'From: "Admin User " <note-3@eventum.example.org>';
        $exp[] = 'Subject: =?UTF-8?Q?[#3]=20Note:=20Re:=20pl=C3=A4h?=';
        $exp[] = 'To: "Admin User" <admin@example.com>';
        $exp[] = 'Date: Wed, 19 Jul 2017 18:15:33 GMT';
        $exp[] = 'MIME-Version: 1.0';
        $exp[] = 'Content-Type: text/plain;';
        $exp[] = ' charset="UTF-8"';
        $exp[] = 'Content-Transfer-Encoding: 8bit';
        $exp[] = '';
        $exp[] = "Hello, bödi tekst\n\nBye";
        $this->assertSame($exp, explode("\r\n", $this->mail->getRawContent()));
    }

    public function testMimeMessageAttachment()
    {
        $body = "Hello, bödi tekst\n\nBye\n";

        $mime = new Mime\Message();

        $part = MimePart::createTextPart($body);
        $mime->addPart($part);

        $part = MimePart::createAttachmentPart('testing123', 'text/plain', 'filename.txt');
        $mime->addPart($part);

        $this->mail->setContent($mime);

        $exp = [];
        $exp[] = 'Message-ID: <eventum.md5.5bh5b2b2k.1odx18yqps5xd@eventum.example.org>';
        $exp[] = 'From: "Admin User " <note-3@eventum.example.org>';
        $exp[] = 'Subject: =?UTF-8?Q?[#3]=20Note:=20Re:=20pl=C3=A4h?=';
        $exp[] = 'To: "Admin User" <admin@example.com>';
        $exp[] = 'Date: Wed, 19 Jul 2017 18:15:33 GMT';
        $exp[] = 'MIME-Version: 1.0';
        $exp[] = 'Content-Type: text/plain;';
        $exp[] = ' charset="UTF-8"';
        $exp[] = 'Content-Transfer-Encoding: 8bit';
        $exp[] = '';
        $exp[] = "Hello, bödi tekst\n\nBye";
        $this->assertSame($exp, explode("\r\n", $this->mail->getRawContent()));
    }

    public function testMailBuilder()
    {
        $body = "Hello, bödi tekst\n\nBye\n";

        $builder = new MailBuilder();
        $builder->addTextPart($body);

        $message = $builder->getMessage();
        $message->setSubject($this->subject);
        $message->setFrom($this->from);
        if ($this->to) {
            $message->setTo($this->to);
        }

        // textual attachment
        $attachment = [
            'iaf_file' => 'lamp€1',
            'iaf_filetype' => 'application/octet-stream',
            'iaf_filename' => 'test2123.txt',
        ];
        $builder->addAttachment($attachment);

        // binary
        $attachment = [
            'iaf_file' => "\x1b\xff\xff\xcf",
            'iaf_filetype' => 'application/octet-stream',
            'iaf_filename' => 'test2123.txt',
        ];
        $builder->addAttachment($attachment);

        $mail = $builder->toMailMessage();

        // it's reusable
        $m = MailMessage::createFromString($mail->getRawContent());
        $this->assertNotEmpty($m);
    }
}
