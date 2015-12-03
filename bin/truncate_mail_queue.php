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

// if requested, clear the lock
if (in_array('--fix-lock', $argv)) {
    if (Lock::release('truncate_mail_queue')) {
        echo "The lock file was removed successfully.\n";
    }
    exit(0);
}

if (!Lock::acquire('truncate_mail_queue')) {
    $pid = Lock::getProcessID('truncate_mail_queue');
    fwrite(STDERR, "ERROR: There is already a process (pid=$pid) of this script running.");
    fwrite(STDERR, "If this is not accurate, you may fix it by running this script with '--fix-lock' as the only parameter.\n");
    exit(1);
}

Mail_Queue::truncate();

Lock::release('truncate_mail_queue');
