#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2012 Eventum Team.                              |
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

require_once dirname(__FILE__).'/../init.php';

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
        'filesize'         => 1024
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
$setup = Setup::load();
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
    echo ev_gettext("OK: No errors found"), "\n";
}
exit(STATE_OK);
