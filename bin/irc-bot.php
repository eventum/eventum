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
    Lock::release('irc_bot');
    echo "The lock file was removed successfully.\n";
    exit;
}

if (in_array('--check-process', $argv)) {
    $check = true;
} else {
    $check = false;
}

// NB: must require this in global context
// otherise $SMARTIRC_nreplycodes from defines.php is not initialized
require_once 'Net/SmartIRC/defines.php';

try {
    $bot = new Eventum_Bot();
} catch (InvalidArgumentException $e) {
    error_log($e->getMessage());
    error_log('Please see config/irc_config.dist.php for sample config.');
    exit(1);
}

// acquire a lock to prevent multiple scripts from
// running at the same time
if (!$bot->lock($check)) {
    error_log('Error: Another instance of the script is still running.');
    error_log("If this is not accurate, you may fix it by running this script with '--fix-lock' as the only parameter.");
    exit(1);
}

$bot->run();
$bot->unlock();
