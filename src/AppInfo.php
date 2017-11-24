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

class AppInfo
{
    const URL = 'https://github.com/eventum/eventum';
    const VERSION = '3.4.0-dev';

    /** @var string */
    private $version;

    /** @var string */
    private $hash;

    public function __construct()
    {
        $this->version = self::VERSION;
        $this->hash = $this->getGitHash($this->version);
        // append VCS version if not yet there
        if ($this->hash && !preg_match('/-g[0-9a-f]+$/', $this->version)) {
            $this->version = "{$this->version}-g{$this->hash}";
        }
    }

    public static function getInstance()
    {
        static $appInfo;

        return $appInfo ?: new static();
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function getVersionLink()
    {
        if ($this->hash) {
            return self::URL . "/commit/{$this->hash}";
        }

        return null;
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
            list(, $refname) = explode(': ', $hash);
            if (!file_exists($file = $gitDir . '/' . trim($refname))) {
                return null;
            }
            $hash = file_get_contents($file);
        }

        return substr($hash, 0, 7);
    }
}
