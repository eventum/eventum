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

use Eventum\IssueMatcher;

class IssueMatcherTest extends TestCase
{
    /** @var IssueMatcher */
    private $matcher;

    public function setUp(): void
    {
        $this->matcher = new IssueMatcher('http://eventum.example.lan/');
    }

    /**
     * @dataProvider dataProvider
     */
    public function testMatching($message, $expected): void
    {
        $result = $this->matcher->match($message);
        $this->assertEquals($expected, $result);
    }

    public function dataProvider(): array
    {
        return [
            'no match' => [
                '',
                [],
            ],
            'match link and text' => [
                '
issue updated, issue 123
link: http://eventum.example.lan/view.php?id=64
[issue 123]: http://eventum.example.lan/view.php?id=64
fresh modify.
',
                [
                    [
                        'text' => 'issue 123',
                        'textOffset' => 16,
                        'issueId' => 123,
                    ],
                    [
                        'text' => 'http://eventum.example.lan/view.php?id=64',
                        'textOffset' => 32,
                        'issueId' => 64,
                    ],
                    [
                        'text' => 'issue 123',
                        'textOffset' => 75,
                        'issueId' => 123,
                    ],
                    [
                        'text' => 'http://eventum.example.lan/view.php?id=64',
                        'textOffset' => 87,
                        'issueId' => 64,
                    ],
                ],
            ],
        ];
    }
}
