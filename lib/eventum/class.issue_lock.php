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

use Eventum\Config\Paths;
use Eventum\ServiceContainer;

/**
 * Manages issue specific locks
 */
class Issue_Lock
{
    /**
     * Creates a lock file for the given name.
     * Returns FALSE if lock couldn't be created (lock already exists)
     *
     * @param int $issue_id Issue Id what is being locked
     * @param string $usr_id User Id who locked the issue
     * @return bool
     */
    public static function acquire($issue_id, $usr_id)
    {
        $setup = ServiceContainer::getConfig();
        $lock_ttl = $setup['issue_lock'];
        $expires = time() + $lock_ttl;

        if (self::isLocked($issue_id)) {
            $info = self::getInfo($issue_id);
            // allow lock, if locked by user himself
            if ($info['usr_id'] != $usr_id) {
                return false;
            }
        }

        $lockfile = self::getLockFilename($issue_id);
        $info = [
            'usr_id' => $usr_id,
            'expires' => $expires,
        ];
        $fp = fopen($lockfile, 'w');
        flock($fp, LOCK_EX);
        fwrite($fp, serialize($info));
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    /**
     * Removes the process file to allow other instances of this
     * script to run.
     *
     * @param   int $issue_id Issue id
     * @return  bool
     */
    public static function release($issue_id)
    {
        $lockfile = self::getLockFilename($issue_id);
        if (file_exists($lockfile)) {
            return unlink($lockfile);
        }

        return false;
    }

    /**
     * Get information about issue lock
     * @param int $issue_id Issue id
     * @return bool|array
     */
    public static function getInfo($issue_id)
    {
        $lockfile = self::getLockFilename($issue_id);
        if (!file_exists($lockfile)) {
            return false;
        }
        $info = file_get_contents($lockfile);
        if ($info === false) {
            return false;
        }

        return Misc::unserialize($info);
    }

    /**
     * Checks if the locker is in LOCKED stage
     *
     * @param $issue_id
     * @return bool TRUE - if locked, otherwise - FALSE
     */
    private static function isLocked($issue_id)
    {
        $lockfile = self::getLockFilename($issue_id);

        clearstatcache();
        if (!is_file($lockfile)) {
            return false;
        }

        $info = self::getInfo($issue_id);
        if (!isset($info['expires'])) {
            // lock corrupted, so don't know
            return false;
        }

        $expires = $info['expires'];
        $now = time();
        $stale = $expires <= $now;

        return !$stale;
    }

    /**
     * Returns the full path to the lock file.
     *
     * @param   int $issue_id Issue id
     * @return  string The full path of the process file
     */
    private static function getLockFilename($issue_id): string
    {
        return Paths::APP_LOCKS_PATH . '/issue_' . $issue_id . '.lock';
    }
}
