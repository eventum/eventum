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

use Eventum\Version;
use Generator;

class VersionTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */

    public function testVersions($versionString, $parsed)
    {
        $version = new Version($versionString);
        $this->assertEquals($parsed, (array)$version);
    }

    public function dataProvider(): Generator
    {
        yield 'tag' => [
            '3.7.0@859ccf532731b653c5af71f4151f173bc8fd1d42',
            [
                'version' => '3.7.0',
                'hash' => '859ccf532731b653c5af71f4151f173bc8fd1d42',
                'branch' => null,
            ],
        ];
        yield 'branch' => [
            'dev-package-versions@859ccf532731b653c5af71f4151f173bc8fd1d42',
            [
                'version' => 'dev-package-versions',
                'hash' => '859ccf532731b653c5af71f4151f173bc8fd1d42',
                'branch' => 'package-versions',
            ],
        ];
        yield 'detached' => [
            'dev-bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6@bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6',
            [
                'version' => 'dev-bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6',
                'hash' => 'bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6',
                'branch' => null,
            ],
        ];
    }
}
