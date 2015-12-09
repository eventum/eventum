#!/usr/bin/php
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

ini_set('memory_limit', '1024M');

require_once __DIR__ . '/../init.php';

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
function getParams()
{
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
    if (Lock::release('process_mail_queue')) {
        echo "The lock file was removed successfully.\n";
    }
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
