<?php

use Eventum\Mail\MailMessage;

class ForwardedRoutingTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test that message forwarded by Thunderbird gets new issue
     * i.e if mail has Matching In-Reply-To header, but also X-Forwarded-Message-Id header
     * the email is not associated by new issue created
     */
    public function testForwardedMailRouting()
    {
        $full_message = file_get_contents(__DIR__ . '/data/thunderbird-forwarded.txt');
        $message = MailMessage::createFromString($full_message);

        $headers = $message->getHeaders();

        $references = Mail_Helper::getAllReferences($headers->toString());
        $this->assertEmpty($references);
    }
}
