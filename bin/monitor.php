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

require_once __DIR__ . '/../init.php';

// Nagios compatible exit codes
define('STATE_OK', 0);
define('STATE_WARNING', 1);
define('STATE_CRITICAL', 2);
define('STATE_UNKNOWN', 3);
define('STATE_DEPENDENT', 4);

// the owner, group and filesize settings should be changed to match the correct permissions on your server.
$required_files = array(
    APP_CONFIG_PATH . '/config.php' => array(
        'check_owner'      => true,
        'owner'            => 'apache',
        'check_group'      => true,
        'group'            => 'apache',
        'check_permission' => true,
        'permission'       => 640,
    ),
    APP_CONFIG_PATH . '/setup.php' => array(
        'check_owner'      => true,
        'owner'            => 'apache',
        'check_group'      => true,
        'group'            => 'apache',
        'check_permission' => true,
        'permission'       => 660,
        'check_filesize'   => true,
        'filesize'         => 1024,
    ),
);

$required_directories = array(
    APP_PATH . '/misc/routed_emails' => array(
        'check_permission' => true,
        'permission'       => 770,
    ),
    APP_PATH . '/misc/routed_notes' => array(
        'check_permission' => true,
        'permission'       => 770,
    ),
);

$opt = getopt('q');
$quiet = isset($opt['q']);

$errors = 0;
// load prefs
$setup = Setup::get();
$prefs = $setup['monitor'];

$errors += Monitor::checkDatabase();
$errors += Monitor::checkMailQueue();
$errors += Monitor::checkMailAssociation();

if ($prefs['diskcheck']['status'] == 'enabled') {
    $errors += Monitor::checkDiskspace($prefs['diskcheck']['partition']);
}
if ($prefs['paths']['status'] == 'enabled') {
    $errors += Monitor::checkRequiredFiles($required_files);
    $errors += Monitor::checkRequiredDirs($required_directories);
}
if ($prefs['ircbot']['status'] == 'enabled') {
    $errors += Monitor::checkIRCBot();
}

if ($errors) {
    // propagate status code to shell
    exit(STATE_CRITICAL);
}

if (!$quiet) {
    echo ev_gettext('OK: No errors found'), "\n";
}
exit(STATE_OK);
