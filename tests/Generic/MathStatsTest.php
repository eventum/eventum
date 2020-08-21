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

use Eventum\Report\Stats;
use Eventum\Test\TestCase;
use Generator;

class MathStatsTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function test(array $expected, array $input): void
    {
        $stats = new Stats();
        $result = $stats->getStats($input);
        $this->assertEquals($expected, $result);
    }

    public function dataProvider(): Generator
    {
        yield [
            [
                'total' => 135.0,
                'avg' => 15.0,
                'median' => 14,
                'max' => 21,
            ],
            [13, 18, 13, 14, 13, 16, 14, 21, 13],
        ];
    }
}
