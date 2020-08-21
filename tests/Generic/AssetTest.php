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

use Eventum\Templating\Asset;
use Eventum\Test\TestCase;

class AssetTest extends TestCase
{
    /** @var Asset */
    private $asset;

    public function setUp(): void
    {
        $manifestPath = $this->getDataFile('mix-manifest.json');
        $relativeUrl = '/example';
        $this->asset = new Asset($manifestPath, $relativeUrl);
    }

    public function testAsset(): void
    {
        $this->assertEquals(
            '/dist/main.js',
            $this->asset->getUrl('/dist/main.js'),
            'Absolute paths is not found from manifest and is not added prefix'
        );

        $this->assertEquals(
            '/example/dist/main.js?id=ecfe06d840525bff34b2',
            $this->asset->getUrl('dist/main.js'),
            'Relative path is found from manifest and path is prefixed'
        );
    }
}
