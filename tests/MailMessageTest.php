<?php

class MailMessageTest extends TestCase
{
    public function testMissingMessageId()
    {
        $raw = "X-foo: 1\r\n\r\nnada";
        $message = MailMessage::createFromString($raw);
        $message_id = $message->messageId;
        $exp = "<eventum.md5.68gm8417ga.clqtuo3skl4w0gc@eventum.example.org>";
        $this->assertEquals($exp, $message_id);
    }

    public function testDuplicateMessageId()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-msgid.txt');
        $message_id = $message->messageId;
        $exp = "<81421718b55935a2f5105705f8baf571@lookout.example.org>";
        $this->assertEquals($exp, $message_id);
    }

    public function testMissingSubject()
    {
        $raw = "Message-ID: 1\r\n\r\n";
        $message = MailMessage::createFromString($raw);
        $this->assertSame('', $message->subject);
    }

    public function testDateHeader()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-msgid.txt');
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

        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
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
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');

        $recipients = array();
        foreach ($message->getTo() as $address) {
            $recipients[] = $address->getEmail();
        }
        foreach ($message->getCc() as $address) {
            $recipients[] = $address->getEmail();
        }

        $recipients = array_unique($recipients);

        $exp = 'issue-73358@eventum.example.org,abcd@origin.com,our@email.com';
        $res = join(',', $recipients);
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
        $this->assertEquals($exp, join(',', $res));
    }

    public function testMultipleToHeaders() {
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');

        $to = $message->getTo();
        $this->assertInstanceOf('Zend\Mail\AddressList', $to);
        $this->assertEquals('issue-73358@eventum.example.org', $message->to);

        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-msgid.txt');
        $to = $message->getTo();
        $this->assertInstanceOf('Zend\Mail\AddressList', $to);
        $this->assertEquals("support@example.org,\r\n support-2@example.org", $message->to);
    }

    public function testIsBounceMessage()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $this->assertFalse($message->isBounceMessage());
    }

    public function testHasAttachments()
    {
        $raw = "Message-ID: <33@JON>X-foo: 1\r\n\r\nada";
        $message = MailMessage::createFromString($raw);
        $has_attachments = $message->countParts();
        $multipart = $message->isMultipart();
        $this->assertFalse($multipart);
        $this->assertEquals(0, $has_attachments);
        $this->assertFalse($message->hasAttachments());

        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $multipart = $message->isMultipart();
        $this->assertTrue($multipart);
        $has_attachments = $message->countParts();
        $this->assertEquals(2, $has_attachments);
        $this->assertTrue($message->hasAttachments());
    }

    public function testGetAttachments()
    {
        $raw = file_get_contents(__DIR__ . '/data/bug684922.txt');

        // old code
        $mail = Mime_Helper::decode($raw, true, true);
        $att1 = Mime_Helper::getAttachments($mail);
        // it returned in reverse order. wtf. but ok
        $att1 = array_reverse($att1);

        $this->assertEquals(2, count($att1));
        $att = $att1[0];
        /**
         * [filename] => smiley-money-mouth1.gif
         * [cid] => <smiley-money-mouth2.gif>
         * [filetype] => image/gif
         * [blob] =>
         */
        $this->assertArrayHasKey('filename', $att);
        $this->assertArrayHasKey('cid', $att);
        $this->assertArrayHasKey('filetype', $att);
        $this->assertArrayHasKey('blob', $att);

        // new code
        $mail = MailMessage::createFromString($raw);
        $this->assertTrue($mail->hasAttachments());
        $att2 = $mail->getAttachments();

        $this->assertEquals(2, count($att2));
        $att = $att2[0];
        $this->assertArrayHasKey('filename', $att);
        $this->assertArrayHasKey('cid', $att);
        $this->assertArrayHasKey('filetype', $att);
        $this->assertArrayHasKey('blob', $att);

        $this->assertSame($att1, $att2);
    }

    public function testReferenceMessageId()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/in-reply-to.txt');
        $reference_id = $message->getReferenceMessageId();
        $this->assertEquals('<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>', $reference_id);

        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $reference_id = $message->getReferenceMessageId();
        $this->assertEquals('<4d36173add8b60.67944236@origin.com>', $reference_id);
    }

    /**
     * @covers Mail_Helper::getAllReferences()
     */
    public function testReferences()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/data/in-reply-to.txt');

        $ref1 = Mail_Helper::getAllReferences($mail->getHeaders()->toString());
        $ref2 = $mail->getAllReferences();

        $this->assertSame(join("\n", $ref1), join("\n", $ref2));
    }

    public function testGetAddresses()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $addresses = $message->getAddresses();
        $exp = array(
            'our@email.com',
        );
        $this->assertEquals($exp, $addresses);
    }

    public function testGetRawContent()
    {
        $raw = file_get_contents(__DIR__ . '/data/in-reply-to.txt');
        $message = MailMessage::createFromString($raw);
        $this->assertInstanceOf('MailMessage', $message);

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
        $message = MailMessage::createFromFile(__DIR__ . '/data/in-reply-to.txt');
        $this->assertInstanceOf('MailMessage', $message);
        $headers = $message->getHeaders();

        $this->assertTrue($headers->has('In-Reply-To'));
        $value = $headers->get('In-Reply-To');
        $this->assertEquals(
            '<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>', $value->getFieldValue()
        );
        $headers->removeHeader('In-Reply-To');
        $this->assertFalse($headers->has('In-Reply-To'));

        // test if header already does not exist
        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $this->assertInstanceOf('MailMessage', $message);
        $headers = $message->getHeaders();

        $this->assertFalse($headers->has('In-Reply-To'));
        // this should not throw
        $headers->removeHeader('In-Reply-To');
    }

    public function testDuplicateFrom()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');
        $this->assertInstanceOf('MailMessage', $message);

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

    public function testModifyBody()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $this->assertInstanceOf('MailMessage', $message);

        $content = Mail_Helper::stripWarningMessage($message->getContent());
        $message->setContent($content);
    }

    public function testRemoveCc()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');
        $this->assertInstanceOf('MailMessage', $message);

        $cc = join(',', $message->getAddresses('Cc'));
        $this->assertEquals('abcd@origin.com,our@email.com', $cc);

        $message->removeFromAddressList('Cc', 'our@email.com');

        $cc = join(',', $message->getAddresses('Cc'));
        $this->assertEquals('abcd@origin.com', $cc);
    }

    public function testReplaceSubject()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');

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
        $mail = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');
        $this->assertFalse($mail->isVacationAutoResponder());

        $mail = MailMessage::createFromFile(__DIR__ . '/data/cron.txt');
        $this->assertTrue($mail->isVacationAutoResponder());
    }

    public function testStripHeaders()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');
        $before = array_keys($mail->getHeaders()->toArray());

        $mail->stripHeaders();

        $after = array_keys($mail->getHeaders()->toArray());
        $this->assertNotSame($before, $after);

        $exp = array(
            'Date',
            'Message-ID',
            'Subject',
            'MIME-Version',
            'Content-Type',
            'From',
        );
        $this->assertSame($exp, $after);
    }

    public function testSetTo()
    {
        $mail = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');

        $to = "root@example.org";
        $mail->setTo($to);
        $this->assertEquals($to, $mail->to);

        $to = '"test to" <root@example.org>';
        $mail->setTo($to);
        $this->assertEquals($to, $mail->to);
    }

    public function testHeadersCloning()
    {
        $this->markTestSkipped('cloning does not work');

        $mail = MailMessage::createFromFile(__DIR__ . '/data/duplicate-from.txt');
        $clone = clone $mail;

        $to = "root@example.org";
        $clone->setTo($to);
        $this->assertEquals($to, $clone->to);
        $this->assertNotEquals($to, $mail->to);

        $this->assertNotEquals($mail->getRawContent(), $clone->getRawContent());
    }

    public function testAddHeaders()
    {
        $raw = "Message-ID: <33@JON>\nX-Eventum-Level: 1\n\nBody";
        $mail = MailMessage::createFromString($raw);

        $this->assertEquals("1", $mail->getHeader('X-Eventum-Level')->getFieldValue());

        $headers = array(
            'X-Eventum-Group-Issue' => 'something 123 143',
            'X-Eventum-Group-Replier' => 'mõmin',
            'X-Eventum-Group-Assignee' => 'UUser1, juusõr2',
            'X-Eventum-Customer' => "cust om er",
            'X-Eventum-Level' => 10,
            'X-Eventum-Assignee' => "foo, bar",
            'X-Eventum-Category' => 'Title Cat',
            'X-Eventum-Project' => 'prjnma',
            'X-Eventum-Priority' => 'kümme',
            'X-Eventum-CustomField-Foo' => 'maha kali',
            'X-Eventum-Type' => 'elisabeth bathory',

            'precedence' => 'bulk', // the 'classic' way, works with e.g. the unix 'vacation' tool
            'Auto-submitted' => 'auto-generated', // the RFC 3834 way
        );
        $mail->setHeaders($headers);

        $exp = join(
            "\r\n", array(
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
                ''
            )
        );
        $this->assertEquals($exp, $mail->getHeaders()->toString());
    }
}
