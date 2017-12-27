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
     * @dataProvider testCases
     */
    public function testTextMessage($dataFile, $expectedText)
    {
        $mail = MailMessage::createFromFile($this->getDataFile($dataFile));
        $this->assertEquals($expectedText, $mail->getMessageBody());
    }

    public function testCases()
    {
        return [
            'Test that HTML entities used in text/html part get decoded' => [
                'encoding.txt',
                "\npöördumise töötaja.\n<b>Võtame</b> töösse võimalusel.\npöördumisele süsteemis\n\n",
            ],
            'testBug684922' => [
                'bug684922.txt',
                '',
            ],
            'test $structure->body getting textual mail body from multipart message' => [
                'multipart-text-html.txt',
                "Commit in MAIN\n",
            ],

            'test with multipart/mixed mail with multipart/alternative attachment' => [
                'multipart-mixed-alternative.eml',
                "No one has ever seen God.\n",
            ],
        ];
    }
}
