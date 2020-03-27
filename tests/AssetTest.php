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

use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

class AssetTest extends TestCase
{
    public function testAsset(): void
    {
        $manifestPath = $this->getDataFile('mix-manifest.json');
        $relativeUrl = '/example';
        $versionStrategy = new JsonManifestVersionStrategy($manifestPath);
        $package = new PathPackage($relativeUrl, $versionStrategy);

        // returns /dist/main.js?id=ecfe06d840525bff34b2 because leading slash
        $this->assertEquals('/dist/main.js?id=ecfe06d840525bff34b2', $package->getUrl('/dist/main.js'));

        // returns "/example/dist/main.js" because no match in manifest
        $this->assertEquals('/example/dist/main.js', $package->getUrl('dist/main.js'));
    }
}
