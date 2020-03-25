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

namespace Eventum\Templating;

use Eventum\Config\Paths;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

class Asset
{
    /** @var Package */
    private $package;

    public function __construct()
    {
        $manifestPath = Paths::APP_PATH . '/mix-manifest.json';
        $this->package = new Package(new JsonManifestVersionStrategy($manifestPath));
    }

    public function asset(string $path): string
    {
        return $this->package->getUrl($path);
    }
}
