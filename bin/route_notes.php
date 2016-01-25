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

/**
 * @deprecated this script is deprecated, you probably want to use process_all_emails.php script
 */

// take input from first argument if specified
// otherwise read from STDIN
if (isset($argv[1])) {
    $full_message = file_get_contents($argv[1]);
} else {
    $full_message = stream_get_contents(STDIN);
}

$return = Routing::route_notes($full_message);
if (is_array($return)) {
    echo $return[1];
    exit($return[0]);
}
