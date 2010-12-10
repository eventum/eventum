<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class Lock
{
    /**
     * Creates a lock file for the given name.
     *
     * @access  public
     * @param   string $name The name of this lock file
     * @return  boolean
     */
    public static function acquire($name)
    {
        $pid = self::getProcessID($name);
        if (!empty($pid)) {
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
     * @access  public
     * @param   string $name The name of this lock file
     * @return  void
     */
    public static function release($name)
    {
        $pid_file = self::getProcessFilename($name);
        if (file_exists($pid_file)) {
            unlink($pid_file);
        }
    }


    /**
     * Returns the full path to the file that keeps the process
     * ID of the running script.
     *
     * @access  private
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
     * @access  public
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
