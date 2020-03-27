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
use Setup;
use Symfony\Component\Asset\PackageInterface;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\JsonManifestVersionStrategy;

class Asset
{
    /** @var PackageInterface */
    private $package;

    public function __construct(string $manifestPath, string $relativeUrl)
    {
        $versionStrategy = new JsonManifestVersionStrategy($manifestPath);
        $this->package = new PathPackage($relativeUrl, $versionStrategy);
    }

    public static function create(): self
    {
        $manifestPath = Paths::APP_PUBLIC_PATH . '/mix-manifest.json';
        $relativeUrl = Setup::getRelativeUrl();

        return new self($manifestPath, $relativeUrl);
    }

    public function getUrl(string $path): string
    {
        return $this->package->getUrl($path);
    }
}
