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

use Eventum\Mail\MailMessage;
use Eventum\Test\TestCase;

class TextMessageTest extends TestCase
{
    /**
     * Test that HTML entities used in text/html part get decoded
     */
    public function testParseHtmlEntities()
    {
        $full_message = $this->readDataFile('encoding.txt');

        $mail = MailMessage::createFromString($full_message);
        $text = $mail->getMessageBody();
        $this->assertEquals(
            "\npöördumise töötaja.\n<b>Võtame</b> töösse võimalusel.\npöördumisele süsteemis\n\n", $text
        );
    }

    public function testBug684922()
    {
        $message = $this->readDataFile('bug684922.txt');
        $mail = MailMessage::createFromString($message);

        $this->assertEquals('', $mail->getMessageBody());
    }

    /**
     * test $structure->body getting textual mail body from multipart message
     */
    public function testGetMailBody()
    {
        $filename = $this->getDataFile('multipart-text-html.txt');
        $mail = MailMessage::createFromFile($filename);
        $body = $mail->getMessageBody();
        $this->assertEquals("Commit in MAIN\n", $body);
    }

    /**
     * root mail: multipart/mixed
     * first part: multipart/alternative
     */
    public function testMultiPartAlternativeAttachment()
    {
        $filename = $this->getDataFile('multipart-mixed-alternative.eml');
        $mail = MailMessage::createFromFile($filename);
        $body = $mail->getMessageBody();
        $this->assertEquals("No one has ever seen God.\n", $body);
    }
}
