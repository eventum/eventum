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

use Link_Filter;
use Misc;

/**
 * @group db
 */
class ActivateLinksTest extends TestCase
{
    public function setUp()
    {
        Link_Filter::markdownEnabled(true);
    }

    /**
     * @dataProvider ActivateLinksData
     * @see Misc::activateLinks
     */
    public function testActivateLinks($text, $exp)
    {
        $this->assertEquals($exp, Misc::activateLinks($text));
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
}
