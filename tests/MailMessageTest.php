<?php

class MailMessageTest extends PHPUnit_Framework_TestCase
{

    public function testMissingMessageId()
    {
        $headers = "X-foo: 1";
        $body = "nada";
        $message = new MailMessage(array('headers' => $headers, 'content' => $body));

        $message_id = $message->getMessageId();
        echo $message_id;
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

        $message = new MailMessage(array('file' => __DIR__ . '/data/bug684922.txt'));
        $multipart = $message->isMultipart();
        $this->assertTrue($multipart);
        $has_attachments = $message->countParts();
        $this->assertEquals(2, $has_attachments);
        $this->assertTrue($message->hasAttachments());
    }

    public function testReferenceMessageId() {
        $message = new MailMessage(array('file' => __DIR__ . '/data/in-reply-to.txt'));
        $reference_id = $message->getReferenceMessageId();
        $this->assertEquals('<CAG5u9y_0RRMmCf_o28KmfmyCn5UN9PVM1=avWp4wWqbHGgojsA@4.example.org>', $reference_id);

        $message = new MailMessage(array('file' => __DIR__ . '/data/bug684922.txt'));
        $reference_id = $message->getReferenceMessageId();
        $this->assertEquals('<4d36173add8b60.67944236@origin.com>', $reference_id);
    }
}
