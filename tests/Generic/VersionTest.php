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
use Eventum\Version;
use Generator;

class VersionTest extends TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testVersions(string $versionString, array $parsed): void
    {
        $version = new Version($versionString);
        $this->assertEquals($parsed, (array)$version);
    }

    public function dataProvider(): Generator
    {
        yield 'tag' => [
            'v3.7.0@859ccf532731b653c5af71f4151f173bc8fd1d42',
            [
                'reference' => 'v3.7.0',
                'version' => 'v3.7.0',
                'hash' => '859ccf532731b653c5af71f4151f173bc8fd1d42',
                'branch' => null,
            ],
        ];
        yield 'branch' => [
            'dev-package-versions@859ccf532731b653c5af71f4151f173bc8fd1d42',
            [
                'reference' => 'dev-package-versions',
                'version' => null,
                'hash' => '859ccf532731b653c5af71f4151f173bc8fd1d42',
                'branch' => 'package-versions',
            ],
        ];
        yield 'detached' => [
            'dev-bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6@bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6',
            [
                'reference' => 'dev-bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6',
                'version' => null,
                'hash' => 'bc9c1a16dd77aba02cf22b5ed95c0d7a9f06afa6',
                'branch' => null,
            ],
        ];
        yield 'rebasing' => [
            // https://github.com/Ocramius/PackageVersions/issues/84
            'No version set (parsed as 1.0.0)@',
            [
                'reference' => null,
                'version' => null,
                'hash' => null,
                'branch' => null,
            ],
        ];
    }
}
