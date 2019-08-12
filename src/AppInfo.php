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

namespace Eventum;

use Eventum\Config\Paths;
use PackageVersions\Versions;

final class AppInfo
{
    private const URL = 'https://github.com/' . self::NAME;
    private const NAME = Versions::ROOT_PACKAGE_NAME;

    /** @var Version */
    private $version;

    public function __construct()
    {
        $version = $this->createVersion();
        if ($version) {
            $this->version = $version;
        }
    }

    private function createVersion(): ?Version
    {
        $versionString = Versions::getVersion(self::NAME);
        $version = new Version($versionString);
        if ($version->reference) {
            return $version;
        }

        if (!$versionString = $this->getGitVersion()) {
            return null;
        }
        $version = new Version($versionString);
        if ($version->reference) {
            return $version;
        }

        return null;
    }

    public static function getInstance(): self
    {
        static $appInfo;

        return $appInfo ?: $appInfo = new static();
    }

    public function getVersion(): ?string
    {
        if (!$version = $this->version) {
            return null;
        }

        // a release
        if ($version->version) {
            return $version->version;
        }

        // detached head
        if (!$version->branch) {
            return $this->formatHash($version->hash);
        }

        // a branch
        $hash = $this->formatHash($version->hash);

        return "{$version->reference}-g{$hash}";
    }

    public function getVersionLink(): ?string
    {
        if (!$version = $this->version) {
            return null;
        }

        if ($version->version) {
            $link = "releases/tag/{$version->version}";
        } else {
            $link = "commit/{$version->hash}";
        }

        return self::URL . '/' . $link;
    }

    private function formatHash(?string $hash): ?string
    {
        if (!$hash) {
            return null;
        }

        return substr($hash, 0, 9);
    }

    private function getGitVersion(): ?string
    {
        putenv(sprintf('GIT_DIR=%s/.git', Paths::APP_PATH));
        $versionString = shell_exec('git describe --tags --match=v* --abbrev=40');
        putenv('GIT_DIR=');

        if (preg_match('/(?P<version>.+)-g(?P<hash>[\da-f]+)$/', $versionString, $m)) {
            return sprintf('dev-%s@%s', $m['version'], $m['hash']);
        }

        return null;
    }
}
