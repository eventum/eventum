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

use Eventum\LinkFilter\LinkFilter;
use Eventum\Test\TestCase;
use Link_Filter;

/**
 * @group db
 */
class LinkFilterTest extends TestCase
{
    /** @var LinkFilter */
    private static $linkFilter;

    public static function setUpBeforeClass(): void
    {
        self::$linkFilter = Link_Filter::getLinkFilter();
    }

    /**
     * @dataProvider dataTestIssueLinking
     * @see          Link_Filter::proccessText
     */
    public function testIssueLinking(string $text, string $exp): void
    {
        $text = self::$linkFilter->replace($text);
        $this->assertRegExp($exp, $text);
    }

    public function dataTestIssueLinking(): array
    {
        return [
            0 => [
                'issue #1',
                ';<a title="issue 1.*" class="" href="view\.php\?id=1">issue #1</a>;',
            ],
            1 => [
                'Issue: 1',
                ';<a title="issue 1.*" class="" href="view\.php\?id=1">Issue: 1</a>;',
            ],
            2 => [
                'issue 1',
                ';<a title="issue 1.*" class="" href="view\.php\?id=1">issue 1</a>;',
            ],
            3 => [
                'test issue 1 test',
                ';test <a title="issue 1.*" class="" href="view\.php\?id=1">issue 1</a> test;',
            ],
        ];
    }
}
