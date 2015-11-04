<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

class Lock
{
    /**
     * Creates a lock file for the given name.
     *
     * @param   string $name The name of this lock file
     * @param   bool $check If we should check if the process exists in addition to check for a lock file
     * @return  boolean
     */
    public static function acquire($name, $check = false)
    {
        $pid = self::getProcessID($name);
        if (!empty($pid)) {
            // Test asks us to check if the process is still running
            if ($check) {
                if (function_exists('posix_kill')) {
                    $exists = posix_kill($pid, 0);
                } else {
                    $retval = 0;
                    $out = array();
                    exec('kill -s 0 ' . $pid, $out, $retval);
                    $exists = $retval == 0;
                }
                if ($exists) {
                    return false;
                }
            }

            return false;
        }

        // create the pid file
        $fp = fopen(self::getProcessFilename($name), 'w');
        flock($fp, LOCK_EX);
        fwrite($fp, getmypid());
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    /**
     * Removes the process file to allow other instances of this
     * script to run.
     *
     * @param   string $name The name of this lock file
     * @return  boolean
     */
    public static function release($name)
    {
        $pid_file = self::getProcessFilename($name);
        if (file_exists($pid_file)) {
            return unlink($pid_file);
        }

        return false;
    }

    /**
     * Returns the full path to the file that keeps the process
     * ID of the running script.
     *
     * @param   string $name The name of this lock file
     * @return  string The full path of the process file
     */
    private static function getProcessFilename($name)
    {
        return APP_LOCKS_PATH . '/'. $name . '.pid';
    }

    /**
     * Returns the process ID of the script, if any.
     *
     * @param   string $name The name of this lock file
     * @return  integer The process ID of the script
     */
    public static function getProcessID($name)
    {
        static $pids;

        // poor man's cache system
        if (!empty($pids[$name])) {
            return $pids[$name];
        }

        $pid_file = self::getProcessFilename($name);
        if (!file_exists($pid_file)) {
            return 0;
        } else {
            $pids[$name] = trim(file_get_contents($pid_file));

            return $pids[$name];
        }
    }
}
