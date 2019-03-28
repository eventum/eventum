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

use Eventum\TextMatcher\GroupMatcher;
use Eventum\TextMatcher\IssueMatcher;
use Eventum\TextMatcher\NoteMatcher;
use Generator;

/**
 * @group db
 */
class TextMatcherTest extends TestCase
{
    /**
     * @dataProvider issueDataProvider
     */
    public function testIssueMatch($message, $expected): void
    {
        $matcher = new IssueMatcher('http://eventum.example.lan/');
        $result = iterator_to_array($matcher->match($message), false);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider noteDataProvider
     */
    public function testNoteMatch($message, $expected): void
    {
        $matcher = new NoteMatcher('http://eventum.example.lan/');
        $result = iterator_to_array($matcher->match($message), false);
        $this->assertEquals($expected, $result);
    }

    /**
     * @dataProvider groupDataProvider
     */
    public function testGroupMatcher($message, $expected): void
    {
        $issueMatcher = new IssueMatcher('http://eventum.example.lan/');
        $noteMatcher = new NoteMatcher('http://eventum.example.lan/');
        $matcher = new GroupMatcher([$issueMatcher, $noteMatcher]);

        $result = iterator_to_array($matcher->match($message), false);
        $this->assertEquals($expected, $result);
    }

    public function noteDataProvider(): Generator
    {
        yield 'no match' => [
            '',
            [],
        ];

        yield 'note from issue #4' => [
            'http://eventum.example.lan/view_note.php?id=13',
            [
                [
                    'text' => 'http://eventum.example.lan/view_note.php?id=13',
                    'textOffset' => 0,
                    'issueId' => 4,
                    'noteId' => 13,
                ],
            ],
        ];
    }

    public function groupDataProvider(): Generator
    {
        yield 'no match' => [
            '',
            [],
        ];

        yield 'note and issue' => [
            'http://eventum.example.lan/view_note.php?id=13 http://eventum.example.lan/view.php?id=64',
            [
                [
                    'text' => 'http://eventum.example.lan/view.php?id=64',
                    'textOffset' => 47,
                    'issueId' => 64,
                ],
                [
                    'text' => 'http://eventum.example.lan/view_note.php?id=13',
                    'textOffset' => 0,
                    'issueId' => 4,
                    'noteId' => 13,
                ],
            ],
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
