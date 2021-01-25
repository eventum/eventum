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
    private const UNICODE_NBSP = "\xC2\xA0";

    /**
     * @dataProvider dataProvider
     */
    public function testTextMessage($dataFile, $expectedText): void
    {
        $content = $this->readDataFile($dataFile);
        $mail = MailMessage::createFromString($content);
        $textBody = $mail->getMessageBody();
        $this->assertEquals($expectedText, $textBody);
    }

    public function dataProvider(): array
    {
        return [
            'Test that HTML entities used in text/html part get decoded' => [
                'encoding.txt',
                "pöördumise töötaja.\n<b>Võtame</b> töösse võimalusel.\npöördumisele süsteemis",
            ],
            'testBug684922' => [
                'bug684922.txt',
                '',
            ],
            'test $structure->body getting textual mail body from multipart message' => [
                'multipart-text-html.txt',
                'Commit in MAIN',
            ],

            'test with multipart/mixed mail with multipart/alternative attachment' => [
                'multipart-mixed-alternative.eml',
                'No one has ever seen God.',
            ],
            'process multipart/related, add it unless plain text content already present' => [
                'multipart-related.eml',
                "Labas,\n\nsu pšventėmis :)",
            ],
            'test downloading html emails extracts body from source' => [
                'htmltext_emailsource.eml',
                "This is a sample email to test Eventum html parsing.\n\n" . self::UNICODE_NBSP,
            ],
            'mail with no mime headers, should be plain text' => [
                'email-106251.txt',
                "here\nbe\ndragons",
            ],
            'pull request #477' => [
                'gnus511.txt',
                'Body text',
            ],
            'bug #478' => [
                'message-chopped.eml',
                $this->readDataFile('message-chopped.txt'),
            ],
            'html-part-encoding' => [
                'html-part-encoding.txt',
                "no encoding\n\n\nplain encoding\n\n\nquøted-prïntâble\n\n\nbase64",
            ],
        ];
    }
}
