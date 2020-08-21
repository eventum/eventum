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

use Eventum\Diff\Differ;
use Eventum\Test\TestCase;

class DiffTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testDiff(string $old, string $new, array $expected): void
    {
        $differ = new Differ();
        $diff = $differ->diff($old, $new);
        $this->assertEquals($expected, $diff);
    }

    public function dataProvider(): array
    {
        return [
            [
                'a', 'b',
                [
                    '@@ -1 +1 @@',
                    '-a',
                    '+b',
                ],
            ],
        ];
    }
}
