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

/**
 * @group db
 */
class LinkFilterTest extends TestCase
{
    /**
     * @dataProvider testIssueLinking_data
     * @see          Link_Filter::proccessText
     */
    public function testIssueLinking($text, $exp)
    {
        $filters = Link_Filter::getFilters();

        foreach ((array)$filters as $filter) {
            list($pattern, $replacement) = $filter;
            // replacement may be a callback, provided by workflow
            if (is_callable($replacement)) {
                $text = preg_replace_callback($pattern, $replacement, $text);
            } else {
                $text = preg_replace($pattern, $replacement, $text);
            }
        }

        $this->assertRegExp($exp, $text);
    }

    public function testIssueLinking_data()
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
