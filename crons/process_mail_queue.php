#!/usr/bin/php
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
// +----------------------------------------------------------------------+

ini_set("memory_limit", '1024M');

require_once dirname(__FILE__).'/../init.php';

// setup constant to be used globally
define('SAPI_CLI', 'cli' == php_sapi_name());

/**
 * Get parameters needed for this script.
 *
 * for CLI mode these are take from command line arguments
 * for Web mode those are taken as named _GET parameters.
 *
 * @return  array   $config
 */
function getParams() {
    // defaults
    $config = array(
        'fix-lock' => false,
    );

    if (SAPI_CLI) {
        global $argc, $argv;
        // --fix-lock may be only the last argument
        if ($argv[$argc - 1] == '--fix-lock') {
            // no other args are allowed
            $config['fix-lock'] = true;
        }

    } else {
        foreach (array_keys($config) as $key) {
            if (isset($_GET[$key])) {
                $config[$key] = $_GET[$key];
            }
        }
    }
    return $config;
}

$config = getParams();

// if requested, clear the lock
if ($config['fix-lock']) {
    Lock::release('process_mail_queue');
    echo "The lock file was removed successfully.\n";
    exit(0);
}

if (!Lock::acquire('process_mail_queue')) {
    $pid = Lock::getProcessID('process_mail_queue');
    fwrite(STDERR, "ERROR: There is already a process (pid=$pid) of this script running.");
    fwrite(STDERR, "If this is not accurate, you may fix it by running this script with '--fix-lock' as the only parameter.\n");
    exit(1);
}

// handle only pending emails
$limit = 50;
Mail_Queue::send('pending', $limit);

// handle emails that we tried to send before, but an error happened...
$limit = 50;
Mail_Queue::send('error', $limit);

Lock::release('process_mail_queue');
