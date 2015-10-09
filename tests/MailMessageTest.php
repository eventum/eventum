<?php

class MailMessageTest extends PHPUnit_Framework_TestCase
{

    public function testMissingMessageId()
    {
        $headers = "X-foo: 1";
        $body = "nada";
        $message = new MailMessage(array('headers' => $headers, 'content' => $body));

        $message_id = $message->getMessageId();
        $exp = "<eventum.56uh2ycutz8kcg.clqtuo3skl4w0gc@eventum.example.org>";
        $this->assertEquals($exp, $message_id);
    }

    public function testDuplicateMessageId()
    {

    }

    public function testIsBounceMessage()
    {
        $message = MailMessage::createFromFile(__DIR__ . '/data/bug684922.txt');
        $this->assertFalse($message->isBounceMessage());
    }

    public function testHasAttachments()
    {

        $headers = "X-foo: 1";
        $body = "nada";
        $message = new MailMessage(array('headers' => $headers, 'content' => $body));
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
        $this->assertEquals('<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>', $value->getFieldValue());
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

        $address = $message->getFromHeader();
        $this->assertInstanceOf('Zend\Mail\Address', $address);
        $this->assertEquals('IT <help@localhost>', $address->toString());
        $this->assertEquals('help@localhost', $address->getEmail());
        $this->assertEquals('IT', $address->getName());
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
        $subject = $message->getSubject();

        $this->assertEquals('Re: Re: Re[2]: meh', $subject->getFieldValue());
        $subject->setSubject(Mail_Helper::removeExcessRe($subject->getFieldValue()));
        // Note: the method will still keep one 'Re'
        $this->assertEquals('Re: meh', $subject->getFieldValue());
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
            'From',
            'Message-ID',
            'Subject',
            'MIME-Version',
            'Content-Type',
        );
        $this->assertSame($exp, $after);
    }
}
