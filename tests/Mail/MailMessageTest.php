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

use Date_Helper;
use Eventum\Mail\MailBuilder;
use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;
use Mail_Helper;
use Mail_Queue;
use Mime_Helper;
use PHPUnit_Framework_Error_Notice;
use Routing;
use Setup;
use Zend;
use Zend\Mail\AddressList;

/**
 * @group mail
 */
class MailMessageTest extends TestCase
{
    public function testMissingMessageId()
    {
        $raw = "X-foo: 1\r\n\r\nnada";
        $message = MailMessage::createFromString($raw);
        $message_id = $message->messageId;

        /**
         * due bad conversion in Mail_Helper::generateMessageID(),
         * the result is different on 32bit systems.
         *
         * @see Mail_Helper::generateMessageID()
         */
        if (PHP_INT_SIZE == 4) {
            $exp = '<eventum.md5.68gm8417ga.clqtuo3sklwsgok@eventum.example.org>';
        } else {
            $exp = '<eventum.md5.68gm8417ga.clqtuo3skl4w0gc@eventum.example.org>';
        }
        $this->assertEquals($exp, $message_id);
    }

    public function testDuplicateMessageId()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-msgid.txt');
        $message_id = $message->messageId;
        $exp = '<81421718b55935a2f5105705f8baf571@lookout.example.org>';
        $this->assertEquals($exp, $message_id);
    }

    public function testHeaderLine()
    {
        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Subject: [#66] Re: LVM-i bänner',
        ];
        $message = MailMessage::createFromHeaderBody($headers, 'kõk');
        $this->assertEquals('[#66] Re: LVM-i bänner', $message->subject);

        $headers = 'Subject:[#83566...';
        $message = MailMessage::createFromString($headers);
        $this->assertEquals('[#83566...', $message->subject);

        $headers = "Content-Type: text/plain; charset=UTF-8\r\nSubject: Re: LVM-i =?utf-8?b?YsOkbm5lcg==?=";
        $message = MailMessage::createFromString($headers);
        $this->assertEquals('Re: LVM-i bänner', $message->subject);
    }

    public function testMissingSubject()
    {
        $raw = "Message-ID: 1\r\n\r\n";
        $message = MailMessage::createFromString($raw);
        $this->assertSame('', $message->subject);
    }

    public function testDateHeader()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-msgid.txt');
        $date = Date_Helper::convertDateGMT($message->date);
        $exp = '2012-12-16 20:21:05';
        $this->assertEquals($exp, $date);
    }

    public function testFrom()
    {
        /**
         * Test what gives similar output:
         * $email = imap_headerinfo($mbox, $i);
         * print_r($email);
         * [fromaddress] => Gemius Monitoring 24/7 <monitoring@gemius.com>
         */

        $message = MailMessage::createFromFile(__DIR__ . '/../data/bug684922.txt');
        $from = $message->from;
        $this->assertEquals('Some Guy <abcd@origin.com>', $from);

        // or simplier variants:
        $this->assertEquals('Some Guy <abcd@origin.com>', $message->from);
        $this->assertEquals('Us <our@email.com>', $message->to);
        $this->assertEquals('', $message->cc);

        // TODO test with duplicate from as well:
//        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');
    }

    public function testGetToCc()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');

        $recipients = [];
        foreach ($message->getTo() as $address) {
            $recipients[] = $address->getEmail();
        }
        foreach ($message->getCc() as $address) {
            $recipients[] = $address->getEmail();
        }

        $recipients = array_unique($recipients);

        $exp = 'issue-73358@eventum.example.org,abcd@origin.com,our@email.com';
        $res = implode(',', $recipients);
        $this->assertEquals($exp, $res);

        // note it does not return the original header, but what ZF_Mail has encoded it back
        $exp = "Some Guy <abcd@origin.com>,\r\n Us <our@email.com>";
        $this->assertEquals($exp, $message->cc);

        $exp = '<issue-73358@eventum.example.org>';
        $res = array_map(
            function (\Zend\Mail\Address $a) {
                return $a->toString();
            }, iterator_to_array($message->getTo())
        );
        $this->assertEquals($exp, implode(',', $res));
    }

    public function testMultipleToHeaders()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');

        $to = $message->getTo();
        $this->assertInstanceOf('Zend\Mail\AddressList', $to);
        $this->assertEquals('issue-73358@eventum.example.org', $message->to);

        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-msgid.txt');
        $to = $message->getTo();
        $this->assertInstanceOf('Zend\Mail\AddressList', $to);
        $this->assertEquals("support@example.org,\r\n support-2@example.org", $message->to);
    }

    public function testIsBounceMessage()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/bug684922.txt');
        $this->assertFalse($message->isBounceMessage());
    }

    public function testReferenceMessageId()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/in-reply-to.txt');
        $reference_id = $message->getReferenceMessageId();
        $this->assertEquals('<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>', $reference_id);

        $message = MailMessage::createFromFile(__DIR__ . '/../data/bug684922.txt');
        $reference_id = $message->getReferenceMessageId();
        $this->assertEquals('<4d36173add8b60.67944236@origin.com>', $reference_id);
    }

    public function testReferences()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/../data/in-reply-to.txt');

        $references = $mail->getAllReferences();
        $exp = [
            '<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>',
            '<CAAaem7eL8Tz0LBqncnX6O+SVBPBXZe+-YFmfdCPfuAFLfjk2YQ@4.example.org>',
            '<55D2DF21.4090409@3.example.org>',
            '<CAAaem7eobVjOPoZSUbG34AXkwVhoK19HpW+Bc-VgXRqmCc_3FA@4.example.org>',
            '<55D44224.6050503@3.example.org>',
            '<CAAaem7fhEfPyksxO45NKph7VQ=F-4r2KwP2P3hzQB0yT=Z-Okg@4.example.org>',
            '<55DEC5DF.8030103@3.example.org>',
            '<CAG5u9y8dtK1-9Dx3uvetcJOENiYM6yT7N0kmiT8kLyQYahnKeA@4.example.org>',
            '<55DED903.9080304@3.example.org>',
            '<55E59719.5010303@3.example.org>',
            '<CAG5u9y9xfjVDL4nb=dGpZG2vpEuUgCTaNbxsVxh_Nd5MLRrJrQ@4.example.org>',
            '<55E59AF9.7040903@3.example.org>',
            '<CAG5u9y9384O3zSZcp4DZEkXt5Fjh3Ga+0wvQnKg5CUTpyeFbOw@4.example.org>',
            '<CAG5u9y-V1Qfwfv0jQzpspQ=6ak55vD1=9y6B4kqkcj1sR5fXHQ@4.example.org>',
            '<55E6A4E4.4060201@3.example.org>',
        ];
        $this->assertSame($exp, $references);
    }

    /**
     * @covers Mail_Helper::rewriteThreadingHeaders()
     */
    public function testRewriteThreadingHeaders()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/../data/in-reply-to.txt');
        $msg_id = $mail->getReferenceMessageId();
        $exp = '<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>';
        $this->assertEquals($exp, $msg_id);

        $this->assertEquals($exp, $mail->InReplyTo);

        $mail->setInReplyTo('foo-bar-123');
        $value = 'foo-bar-123';
        $this->assertEquals($value, $mail->InReplyTo);

        $references = [1, $msg_id];
        $mail->setReferences($references);
        $exp = '1 <CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>';
        $this->assertEquals($exp, $mail->References);
    }

    /**
     * @test that the result can be assembled after adding generic header!
     */
    public function testInReplyTo()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/../data/multipart-text-html.txt');
        $mail->setInReplyTo('fu!');
        $mail->getRawContent();
    }

    public function testGetAddresses()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/bug684922.txt');
        $addresses = $message->getAddresses();
        $exp = [
            'our@email.com',
        ];
        $this->assertEquals($exp, $addresses);
    }

    public function testGetRawContent()
    {
        $raw = $this->readDataFile('in-reply-to.txt');
        $message = MailMessage::createFromString($raw);

        // test that getting back raw content works
        // NOTE: the result is not always identical, however this is saved from this same method before manually verifying result is okay
        $content = $message->getRawContent();

        $raw = preg_split("/\r?\n/", $raw);
        $content = preg_split("/\r?\n/", $content);
        $this->assertSame($raw, $content);
    }

    public function testRemoveHeader()
    {
        // test if header exists
        $message = MailMessage::createFromFile(__DIR__ . '/../data/in-reply-to.txt');
        $headers = $message->getHeaders();

        $this->assertTrue($headers->has('In-Reply-To'));
        $value = $headers->get('In-Reply-To');
        $this->assertEquals(
            '<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>', $value->getFieldValue()
        );
        $headers->removeHeader('In-Reply-To');
        $this->assertFalse($headers->has('In-Reply-To'));

        // test if header already does not exist
        $message = MailMessage::createFromFile(__DIR__ . '/../data/bug684922.txt');
        $headers = $message->getHeaders();

        $this->assertFalse($headers->has('In-Reply-To'));
        // this should not throw
        $headers->removeHeader('In-Reply-To');
    }

    public function testDuplicateFrom()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');

        $from = $message->from;
        $this->assertEquals('IT <help@localhost>', $from);

        $address = $message->getFrom();
        $this->assertInstanceOf('Zend\Mail\Address', $address);
        $this->assertEquals('IT <help@localhost>', $address->toString());
        $this->assertEquals('help@localhost', $address->getEmail());
        $this->assertEquals('IT', $address->getName());
    }

    public function testMissingFrom()
    {
        // test with no From header
        $raw = "X-foo: 1\r\n\r\nnada";
        $message = MailMessage::createFromString($raw);
        $headers = $message->getHeaders();
        $this->assertTrue($headers->has('From'));
        $this->assertSame(null, $message->getFrom());
    }

    public function testRemoveCc()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');

        $cc = implode(',', $message->getAddresses('Cc'));
        $this->assertEquals('abcd@origin.com,our@email.com', $cc);

        $message->removeFromAddressList('Cc', 'our@email.com');

        $cc = implode(',', $message->getAddresses('Cc'));
        $this->assertEquals('abcd@origin.com', $cc);
    }

    public function testReplaceSubject()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');

        $subject = $message->subject;
        $this->assertEquals('Re: Re: Re[2]: meh', $subject);
        $message->setSubject(Mail_Helper::removeExcessRe($subject));

        // Note: the method will still keep one 'Re'
        $this->assertEquals('Re: meh', $message->subject);
    }

    /**
     * Checks whether the given headers are from a vacation
     * auto-responder message or not.
     */
    public function testIsVacationAutoResponder()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');
        $this->assertFalse($mail->isVacationAutoResponder());

        $mail = MailMessage::createFromFile(__DIR__ . '/../data/cron.txt');
        $this->assertTrue($mail->isVacationAutoResponder());
    }

    public function testStripHeaders()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');
        $before = array_keys($mail->getHeaders()->toArray());

        $mail->stripHeaders();

        $after = array_keys($mail->getHeaders()->toArray());
        $this->assertNotSame($before, $after);

        $exp = [
            'Date',
            'Message-ID',
            'Subject',
            'MIME-Version',
            'Content-Type',
            'From',
        ];
        $this->assertSame($exp, $after);
    }

    public function testSetTo()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');

        $to = 'root@example.org';
        $mail->setTo($to);
        $this->assertEquals($to, $mail->to);

        $to = '"test to" <root@example.org>';
        $mail->setTo($to);
        $this->assertEquals($to, $mail->to);
    }

    public function testHeadersCloning()
    {
        $this->markTestSkipped('cloning does not work');

        $mail = MailMessage::createFromFile(__DIR__ . '/../data/duplicate-from.txt');
        $clone = clone $mail;

        $to = 'root@example.org';
        $clone->setTo($to);
        $this->assertEquals($to, $clone->to);
        $this->assertNotEquals($to, $mail->to);

        $this->assertNotEquals($mail->getRawContent(), $clone->getRawContent());
    }

    public function testAddHeaders()
    {
        $raw = "Message-ID: <33@JON>\nX-Eventum-Level: 1\n\nBody";
        $mail = MailMessage::createFromString($raw);

        $this->assertEquals('1', $mail->getHeader('X-Eventum-Level')->getFieldValue());

        $headers = [
            'X-Eventum-Group-Issue' => 'something 123 143',
            'X-Eventum-Group-Replier' => 'mõmin',
            'X-Eventum-Group-Assignee' => 'UUser1, juusõr2',
            'X-Eventum-Customer' => 'cust om er',
            'X-Eventum-Level' => 10,
            'X-Eventum-Assignee' => 'foo, bar',
            'X-Eventum-Category' => 'Title Cat',
            'X-Eventum-Project' => 'prjnma',
            'X-Eventum-Priority' => 'kümme',
            'X-Eventum-CustomField-Foo' => 'maha kali',
            'X-Eventum-Type' => 'elisabeth bathory',

            'precedence' => 'bulk', // the 'classic' way, works with e.g. the unix 'vacation' tool
            'Auto-submitted' => 'auto-generated', // the RFC 3834 way
        ];
        $mail->addHeaders($headers);

        $exp = implode(
            "\r\n", [
                'Message-ID: <33@JON>',
                'X-Eventum-Level: 10',
                'Subject: ',
                'X-Eventum-Group-Issue: something 123 143',
                'X-Eventum-Group-Replier: =?UTF-8?Q?m=C3=B5min?=',
                'X-Eventum-Group-Assignee: =?UTF-8?Q?UUser1,=20juus=C3=B5r2?=',
                'X-Eventum-Customer: cust om er',
                'X-Eventum-Assignee: foo, bar',
                'X-Eventum-Category: Title Cat',
                'X-Eventum-Project: prjnma',
                'X-Eventum-Priority: =?UTF-8?Q?k=C3=BCmme?=',
                'X-Eventum-CustomField-Foo: maha kali',
                'X-Eventum-Type: elisabeth bathory',
                'Precedence: bulk',
                'Auto-Submitted: auto-generated',
                '',
            ]
        );
        $this->assertEquals($exp, $mail->getHeaders()->toString());
    }

    public function testAddHeadersMultiValue()
    {
        $raw = "Message-ID: <33@JON>\n\n\nbody";
        $mail = MailMessage::createFromString($raw);

        // References is single value header, must join to string
        // but do not worry about wordwrap
        $references = ['a', 'b', str_repeat('de de', 40)];
        $add_headers = [
            'References' => implode(' ', $references),
        ];

        $mail->addHeaders($add_headers);
        $raw = $mail->getRawContent();
        $this->assertContains("dede\r\n dede", $raw, 'value has been wrapped');
    }

    /**
     * test different access modes or X-Priority header.
     */
    public function testXPriority()
    {
        $content = $this->readDataFile('duplicate-msgid.txt');
        $mail = MailMessage::createFromString($content);

        $this->assertEquals('3', $mail->XPriority);
        $this->assertEquals('3', $mail->xpriority);
        $this->assertEquals('3', $mail->x_priority);
        $this->assertEquals('3', $mail->{'x-priority'});
        $this->assertEquals('3', $mail->{'X-Priority'});

        // this is how optional headers are to be handled
        $has_priority = $mail->getHeaders()->has('XX-Priority');
        $priority = $has_priority ? $mail->xpriority : null;
        $this->assertEquals(null, $priority);
    }

    /**
     * @see Notification::notifyAccountDetails
     * @group db
     */
    public function testSendSimpleMail()
    {
        $text_message = 'text message';
        $info = [
            'usr_full_name' => 'Some User',
            'usr_email' => 'nobody@example.org',
        ];
        $subject = 'Your User Account Details';

        $smtp = [
            'from' => 'root@example.org',
        ];
        Setup::set(['smtp' => $smtp]);

        $from = Setup::get()->smtp->from;
        $to = Mail_Helper::getFormattedName($info['usr_full_name'], $info['usr_email']);

        // the same but with ZF
        $mail = MailMessage::createNew();
        $mail->setSubject($subject);
        $mail->setFrom($from);
        $mail->setTo($to);
        $mail->setContent($text_message);
        Mail_Queue::queue($mail, $to);
    }

    /**
     * Test mail sending with ZendFramework Mail (MailMessage)
     *
     * @group db
     */
    public function testMailSendZF()
    {
        $text_message = 'tere';
        $issue_id = 1;
        $from = 'Eventum <support@example.org>';
        $recipient = 'Eventum <support@example.org>';
        $subject = '[#1] Issue Created';
        $msg_id = '<eventum@eventum.example.org>';

        $raw = "Message-ID: $msg_id\n\n\n$text_message";
        $mail = MailMessage::createFromString($raw);
        $mail->setSubject($subject);
        $mail->setFrom($from);
        $mail->setTo($recipient);

        // add($recipient, $headers, $body, $save_email_copy = 0, $issue_id = false, $type = '', $sender_usr_id = false, $type_id = false)
        Mail_Queue::queue($mail, $recipient);
    }

    public function testMailFromHeaderBody()
    {
        $headers = [
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'Message-ID' => '<eventum.md5.55kfn795r.3buu2ivsffcw8@localhost>',
            'In-Reply-To' => '<eventum.4zwt0q24d.y3mpoo@localhost:8002>',
            'References' => '<eventum.4zwt0q24d.y3mpoo@localhost:8002>',
            'From' => '"Admin User " <note-3@eventum.example.org>',
            'To' => '"Admin User" <admin@example.com>',
            'Subject' => '[#3] Note: Re: example issue title',
        ];
        $body = 'lala';
        MailMessage::createFromHeaderBody($headers, $body);

        // does not like empty headers
        $headers['Cc'] = '';
        $mail = MailMessage::createFromHeaderBody($headers, $body);

        // does not like not-encoded headers
        $mail->setSubject('[#3] Note: new ää');
        $headers = $mail->getHeadersArray();
        MailMessage::createFromHeaderBody($headers, $body);
    }

    public function testSendPlainMail()
    {
        $text_message = 'zzzxx';
        $issue_id = 1;
        $from = '"Admin User " <note-3@eventum.example.org>';
        $to = '"Admin User" <admin@example.com>';
        $subject = '[#3] Note: Re: pläh';
        $type = 'assignment';

        // using zend\mail
        $mail = MailMessage::createNew();
        $mail->setContent($text_message);
        $mail->setSubject($subject);
        $mail->setFrom($from);
        $mail->setTo($to);
        $headers = Mail_Helper::getBaseThreadingHeaders($issue_id);
        // do not overwrite message-id
        unset($headers['Message-ID']);
        $mail->addHeaders($headers);
        $options = [
            'save_email_copy' => true,
            'issue_id' => $issue_id,
            'type' => $type,
        ];
        Mail_Queue::queue($mail, $to, $options);

        $mail = new \Zend\Mail\Message();
        $mail->setBody('This is the text of the email.');
        $mail->setFrom($from);
        $mail->setTo($to);
        $mail->setSubject($subject);
        $mail->setEncoding('UTF-8');

        $transport = new \Zend\Mail\Transport\Sendmail();
        $transport->setCallable(
            function ($to, $subject, $body, $headers, $params) {
                //error_log("to[$to] subject[$subject] body[$body] headers[$headers] params[$params]");
            }
        );
        $transport->send($mail);
    }

    public function testReSetMessageId()
    {
        $mail = MailMessage::createNew();
        $headers = [];
        $headers['Message-ID'] = Mail_Helper::generateMessageID();
        $mail->addHeaders($headers);
    }

    public function testZFPlainMail()
    {
        $text_message = 'zzzxx';
        $issue_id = 1;
        $from = '"Admin User " <note-3@eventum.example.org>';
        $to = '"Admin User" <admin@example.com>';
        $subject = '[#3] Note: Re: pläh';
        $type = 'assignment';

        // use mail builder
        $builder = new MailBuilder();
        $builder
            ->addTextPart($text_message)
            ->getMessage()
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject);
        $mail = $builder->toMailMessage();

        $options = [
            'save_email_copy' => true,
            'issue_id' => $issue_id,
            'type' => $type,
        ];
        Mail_Queue::queue($mail, $to, $options);
    }

    /**
     * a test showing a valid header can not be loaded from string using Headers::fromString method
     * due underlying fail in iconv_mime_encode
     *
     * @see https://github.com/zendframework/zend-mail/issues/64
     */
    public function testParseHeaders()
    {
        $header
            = "Subject: [#77675] New Issue:xxxxxxxxx xxxxxxx xxxxxxxx xxxxxxxxxxxxx xxxxxxxxxx xxxxxxxx, =?utf-8?b?dMOkaHRhZWc=?= xx.xx, xxxx\r\n";
        try {
            /** @see \Zend\Mail\Header\HeaderWrap::canBeEncoded */
            \Zend\Mail\Headers::fromString($header);
        } catch (PHPUnit_Framework_Error_Notice $e) {
            error_log($e->getMessage());
        }

        // the above fails with:
        // "iconv_mime_encode(): Unknown error (7)"
        // because it iconv_mime_encode fails:
        $value = '[#77675] New Issue:xxxxxxxxx xxxxxxx xxxxxxxx xxxxxxxxxxxxx xxxxxxxxxx xxxxxxxx, tähtaeg xx.xx, xxxx';
        try {
            // it fails with line length exactly 76, but suceeds with anything else, like 75 or 77
            $v = iconv_mime_encode(
                'x-test', $value, ['scheme' => 'Q', 'line-length' => '76', 'line-break-chars' => ' ']
            );
        } catch (PHPUnit_Framework_Error_Notice $e) {
            error_log($e->getMessage());
        }

        // this works too
        $v = \Zend\Mail\Header\HeaderWrap::mimeEncodeValue($value, 'UTF-8');
//        var_dump($v);
    }

    /**
     * @see https://github.com/eventum/eventum/issues/155
     */
    public function testMboxHeader()
    {
        $full_message = $this->readDataFile('from_nocolon.txt');
        $this->assertNotEquals('MIME-Version', substr($full_message, 0, 12));
        Routing::removeMboxHeader($full_message);
        $this->assertEquals('MIME-Version', substr($full_message, 0, 12));
    }

    /**
     * @see Mail_Queue::send for getMergedList handling
     */
    public function testRecipientConcat()
    {
        $recipients = [
            '"Some Öne" <Some.One@example.org>',
            'root@example.org',
            'Root Mäe <root2@example.org>',
        ];

        $addresslist = new AddressList();
        foreach ($recipients as $recipient) {
            if (Mime_Helper::is8bit($recipient)) {
                $recipient = Mime_Helper::encode($recipient);
            }

            $addresslist->addFromString($recipient);
        }

        $headers = "Message-ID: <eventum.md5.1bddof5dyu68w08.3117t0idh7ggk4@localhost>\n";
        $headers .= "From: root@example.org\n";
        $body = 'body';

        $m = MailMessage::createFromHeaderBody($headers, $body);
        $m->setTo($addresslist);

        $exp = "\"Some =?utf-8?b?w5ZuZSI=?= <Some.One@example.org>,\r\n" .
            " root@example.org,\r\n" .
            ' Root =?utf-8?b?TcOkZQ==?= <root2@example.org>';
        $this->assertEquals($exp, $m->to);
    }
}
