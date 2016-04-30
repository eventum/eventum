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
        $this->assertEquals('', $message_body);
    }

    public function testParseHeaders()
    {
        $maq_recipient = 'Elan Ruusamäe <glen@delfi.ee>';
        // encode it
        $maq_recipient = Mime_Helper::encodeAddress($maq_recipient);
        // encoding twice does no harm
        $maq_recipient = Mime_Helper::encodeAddress($maq_recipient);

        // call private parseRecipients
        $recipients = Mail::parseRecipients($maq_recipient);
        $this->assertFalse(Misc::isError($recipients), Misc::isError($recipients) ? $recipients->getMessage() : '');
        // success
        $this->assertEquals(['glen@delfi.ee'], $recipients);
    }
}
