<?php

class MailParseTest extends PHPUnit_Framework_TestCase
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
}
