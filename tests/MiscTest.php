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

namespace Eventum\Test;

use Misc;

/**
 * Test class for Misc.
 */
class MiscTest extends TestCase
{
    /**
     * @dataProvider caseData
     */
    public function testLowercase($str, $exp)
    {
        $res = Misc::lowercase($str);
        $this->assertSame($exp, $res);
    }

    /**
     * Method used to strip HTML from a string or array
     *
     * @param   string $str The original string or array
     * @return  string The escaped (or not) string
     * @dataProvider StripHTMLData
     */
    public function testStripHTML($str, $exp)
    {
        $this->assertEquals($exp, Misc::stripHTML($str));
    }

    /**
     * @param   string $str The original string or array
     * @return  string The escaped (or not) string
     * @dataProvider StripInputData
     */
    public function testStripInput($str, $exp)
    {
        Misc::stripInput($str);
        $this->assertEquals($exp, $str);
    }

    /**
     * @dataProvider ActivateLinksData
     * @see Misc::activateLinks
     */
    public function testActivateLinks($text, $exp)
    {
        $this->assertEquals($exp, Misc::activateLinks($text));
    }

    public function StripHTMLData()
    {
        return [
            ['plain', 'plain'],
            ['<b>bold</b>', '&#60;b&#62;bold&#60;/b&#62;'],
            [['<b>bold</b>'], ['&#60;b&#62;bold&#60;/b&#62;']],
        ];
    }

    public function StripInputData()
    {
        return [
            ['plain', 'plain'],
            // nothing bad happens with empty array
            [[], []],
            // ctrl char: \r
            [
                ['a' => "a\r\nb"],
                ['a' => "a\nb"],
            ],
            // some emoji
            [
                ['a' => self::unichr(0x1F6B2) . self::unichr(0x1F4A8)],
                ['a' => ''],
            ],
        ];
    }

    public function caseData()
    {
        return [
            [null, null],
            [[], []],
            ['', ''],

            ['A', 'a'],

            [['AA', 'B'], ['aa', 'b']],
            [['z' => 'AA', 3 => 'B'], ['z' => 'aa', 3 => 'b']],
        ];
    }

    public function ActivateLinksData()
    {
        return [
            [
                'http://google.com',
                '<a title="open http://google.com in a new window" class="link" href="http://google.com" target="_google.com">http://google.com</a>',
            ],
            [
                ' a link in the middle of some text http://google.com test test',
                ' a link in the middle of some text <a title="open http://google.com in a new window" class="link" href="http://google.com" target="_google.com">http://google.com</a> test test',
            ],
            [
                'test@example.com',
                '<a title="open mailto:test@example.com in a new window" class="link" href="mailto:test@example.com" target="_test@example.com">test@example.com</a>',
            ],
            [
                'blah test@example.com foo',
                'blah <a title="open mailto:test@example.com in a new window" class="link" href="mailto:test@example.com" target="_test@example.com">test@example.com</a> foo',
            ],
            [
                'curl -T myfile ftp://anonymous:nopassword@ftp.example.com/uploads/',
                'curl -T myfile <a title="open ftp://anonymous:nopassword@ftp.example.com/uploads/ in a new window" class="link" href="ftp://anonymous:nopassword@ftp.example.com/uploads/" target="_anonymous:nopassword@ftp.example.com/uploads/">ftp://anonymous:nopassword@ftp.example.com/uploads/</a>',
            ],
        ];
    }

    /**
     * Return unicode char by its code
     *
     * @see http://php.net/manual/en/function.chr.php#88611
     * @param int $u
     * @return string
     */
    private function unichr($u)
    {
        return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
    }
}
