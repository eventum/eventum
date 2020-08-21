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

namespace Eventum\Test\Generic;

use Eventum\Test\TestCase;
use Eventum\TextMatcher\GroupMatcher;
use Eventum\TextMatcher\IssueMatcher;
use Eventum\TextMatcher\NoteMatcher;
use Eventum\TextMatcher\TextMatchInterface;
use Generator;

/**
 * @group db
 */
class TextMatcherTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testIssueMatch(TextMatchInterface $matcher, string $text, array $expected): void
    {
        $result = iterator_to_array($matcher->match($text), false);
        $this->assertEquals($expected, $result);
    }

    public function dataProvider(): Generator
    {
        $issueMatcher = new IssueMatcher('http://eventum.example.lan/');
        $noteMatcher = new NoteMatcher('http://eventum.example.lan/');
        $groupMatcher = new GroupMatcher([$issueMatcher, $noteMatcher]);

        yield 'issue no match' => [
            $issueMatcher,
            '',
            [],
        ];
        yield 'note no match' => [
            $noteMatcher,
            '',
            [],
        ];
        yield 'grup no match' => [
            $groupMatcher,
            '',
            [],
        ];

        yield 'note from issue #4' => [
            $noteMatcher,
            'http://eventum.example.lan/view_note.php?id=1',
            [
                [
                    'text' => 'http://eventum.example.lan/view_note.php?id=1',
                    'textOffset' => 0,
                    'issueId' => 2,
                    'noteId' => 1,
                ],
            ],
        ];

        yield 'note and issue' => [
            $groupMatcher,
            'http://eventum.example.lan/view_note.php?id=1 http://eventum.example.lan/view.php?id=64',
            [
                [
                    'text' => 'http://eventum.example.lan/view.php?id=64',
                    'textOffset' => 46,
                    'issueId' => 64,
                ],
                [
                    'text' => 'http://eventum.example.lan/view_note.php?id=1',
                    'textOffset' => 0,
                    'issueId' => 2,
                    'noteId' => 1,
                ],
            ],
        ];

        yield 'match link and text' => [
            $issueMatcher,
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
