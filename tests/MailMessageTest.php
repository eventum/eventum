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
}
