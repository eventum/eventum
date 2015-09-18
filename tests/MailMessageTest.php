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

    public function testCreateFromString()
    {
        $raw = file_get_contents(__DIR__ . '/data/in-reply-to.txt');
        $message = MailMessage::createFromString($raw);
        $this->assertInstanceOf('MailMessage', $message);

        // test that getting back raw content works
        // NOTE: the result is not always identical, however this is saved from this same method before manually verifying result is okay
        $content = $message->getRawContent();
        $this->assertEquals($raw, $content);
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
}
