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

use PackageVersions\Versions;

final class AppInfo
{
    private const URL = 'https://github.com/' . self::NAME;
    private const NAME = Versions::ROOT_PACKAGE_NAME;
    private const VERSION = '3.7.0-dev';

    /** @var Version */
    private $version;

    public function __construct()
    {
        $versionString = Versions::getVersion(self::NAME);
        $this->version = new Version($versionString);
    }

    public static function getInstance(): self
    {
        static $appInfo;

        return $appInfo ?: $appInfo = new static();
    }

    public function getVersion(): string
    {
        if ($this->version->version) {
            return $this->version->version;
        }

        $hash = $this->formatHash($this->version->hash);

        return "{$this->version->reference}-g{$hash}";
    }

    public function getVersionLink(): string
    {
        if ($this->version->version) {
            $link = "releases/tag/{$this->version->version}";
        } else {
            $link = "commit/{$this->version->hash}";
        }

        return self::URL . '/' . $link;
    }

    private function formatHash(string $hash): ?string
    {
        if (!$hash) {
            return null;
        }

        return substr($hash, 0, 9);
    }

    private function getGitHash($version)
    {
        // Try $version match:
        // "Eventum 2.3.3-148-g78b3368"
        // "Eventum 2.4.0-pre1-285-g298325e"
        if (preg_match('/^[\d.]+(?:-[^-]+)(?:-\d+)?-g(?P<hash>[0-9a-f]+)$/', $version, $m)) {
            return $m['hash'];
        }

        // if version ends with "-dev", try look into VCS
        if (substr($version, -4) === '-dev') {
            return $this->getHashFromGit();
        }

        // probably release version
        return null;
    }

    private function getHashFromGit()
    {
        $gitDir = __DIR__ . '/../.git';

        if (!file_exists($file = $gitDir . '/HEAD')) {
            return null;
        }

        $hash = file_get_contents($file);
        // it can be branch:
        // "ref: refs/heads/master"
        // or some tag:
        // "fc334abadfd480820071c1415723c7de0216eb6f"
        if (strpos($hash, 'ref:') === 0) {
            $refname = explode(': ', $hash)[1];
            if (!file_exists($file = $gitDir . '/' . trim($refname))) {
                return null;
            }
            $hash = file_get_contents($file);
        }

        return $this->formatHash($hash);
    }
}
