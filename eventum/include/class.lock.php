<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.class.scm.php 1.14 03/12/31 17:29:01-00:00 jpradomaia $
//


class Lock
{
    /**
     * Creates a lock file for the given name.
     *
     * @access  public
     * @param   string $name The name of this lock file
     * @return  boolean
     */
    function acquire($name)
    {
        $pid = Lock::getProcessID($name);
        if (!empty($pid)) {
            return false;
        } else {
            // create the pid file
            $fp = @fopen(Lock::_getProcessFilename($name), 'w');
            @flock($fp, LOCK_EX);
            @fwrite($fp, getmypid());
            @flock($fp, LOCK_UN);
            @fclose($fp);
            return true;
        }
    }


    /**
     * Removes the process file to allow other instances of this 
     * script to run.
     *
     * @access  public
     * @param   string $name The name of this lock file
     * @return  void
     */
    function release($name)
    {
        @unlink(Lock::_getProcessFilename($name));
    }


    /**
     * Returns the full path to the file that keeps the process 
     * ID of the running script.
     *
     * @access  private
     * @param   string $name The name of this lock file
     * @return  string The full path of the process file
     */
    function _getProcessFilename($name)
    {
        return APP_LOCKS_PATH . $name . '.pid';
    }


    /**
     * Returns the process ID of the script, if any.
     *
     * @access  public
     * @param   string $name The name of this lock file
     * @return  integer The process ID of the script
     */
    function getProcessID($name)
    {
        static $pids;

        // poor man's cache system
        if (!empty($pids[$name])) {
            return $pids[$name];
        }

        $pid_file = Lock::_getProcessFilename($name);
        if (!file_exists($pid_file)) {
            return 0;
        } else {
            $pids[$name] = trim(implode('', file($pid_file)));
            return $pids[$name];
        }
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Lock Class');
}
?>