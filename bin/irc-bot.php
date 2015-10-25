#!/usr/bin/php
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

ini_set('memory_limit', '1024M');

require_once __DIR__ . '/../init.php';

if (!file_exists(APP_CONFIG_PATH . '/irc_config.php')) {
    fwrite(STDERR, "ERROR: No config specified. Please see setup/irc_config.php for config information.\n\n");
    exit(1);
}

require_once APP_CONFIG_PATH . '/irc_config.php';

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

// acquire a lock to prevent multiple scripts from
// running at the same time
if (!Lock::acquire('irc_bot', $check)) {
    echo 'Error: Another instance of the script is still running. ',
    "If this is not accurate, you may fix it by running this script with '--fix-lock' ",
    "as the only parameter.\n";
    exit;
}

// NB: must require this in global context
// otherise $SMARTIRC_nreplycodes from defines.php is not initialized
require_once 'Net/SmartIRC/defines.php';

$config = array(
    'hostname' => $irc_server_hostname,
    'port' => $irc_server_port,

    'nickname' => $nickname,
    'realname' => $realname,

    'username' => $username,
    'password' => $password,

    'channels' => $irc_channels,
    'default_category' => APP_EVENTUM_IRC_CATEGORY_DEFAULT,

    'logfile' => APP_IRC_LOG,

    /**
     * @see Net_SmartIRC::setDebugLevel
     */
    'debuglevel' => 'notice',
);

$bot = new Eventum_Bot($config);
$bot->run();

// release the lock
Lock::release('irc_bot');
