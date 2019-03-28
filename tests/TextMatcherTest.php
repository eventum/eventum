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

use Eventum\TextMatcher\IssueMatcher;
use Eventum\TextMatcher\NoteMatcher;
use Generator;

class TextMatcherTest extends TestCase
{
    /**
     * @dataProvider issueDataProvider
     */
    public function testIssueMatch($message, $expected): void
    {
        $issueMatcher = new IssueMatcher('http://eventum.example.lan/');
        $result = iterator_to_array($issueMatcher->match($message));
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider noteDataProvider
     */
    public function testNoteMatch($message, $expected): void
    {
        $issueMatcher = new NoteMatcher('http://eventum.example.lan/');
        $result = iterator_to_array($issueMatcher->match($message));
        $this->assertEquals($expected, $result);
    }

    public function noteDataProvider(): Generator
    {
        yield 'no match' => [
            '',
            [],
        ];
    }

    public function issueDataProvider(): Generator
    {
        yield 'no match' => [
            '',
            [],
        ];

        yield 'match link and text' => [
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
        ];
    }
}
