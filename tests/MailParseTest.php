<?php

class MailParseTest extends TestCase
{
    /**
     * Test that HTML entities used in text/html part get decoded
     */
    public function testParseHtmlEntities()
    {
        $file = __DIR__ . '/data/encoding.txt';
        $full_message = file_get_contents($file);
        $this->assertNotEmpty($full_message);

        $structure = Mime_Helper::decode($full_message, true, true);
        $this->assertEquals(
            "\npöördumise töötaja.\n<b>Võtame</b> töösse võimalusel.\npöördumisele süsteemis\n\n", $structure->body
        );
    }

    public function testBug684922()
    {
        $file = __DIR__ . '/data/bug684922.txt';
        $message = file_get_contents($file);
        $this->assertNotEmpty($message);

        $structure = Mime_Helper::decode($message, true, true);
        $message_body = $structure->body;
        $this->assertEquals("", $message_body);
    }
}
