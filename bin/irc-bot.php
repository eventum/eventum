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

$auth = array();

// map project_id => channel(s)

// TODO: Map old config to new config
$channels = array();
foreach ($irc_channels as $proj => $chan) {
    $proj_id = Project::getID($proj);

    // we need to map old configs with just channels to new config with categories as well
    if (!is_array($chan)) {
        // old config, one channel
        $options = array(
            $chan   =>  array(APP_EVENTUM_IRC_CATEGORY_DEFAULT),
        );
    } elseif (isset($chan[0]) and !is_array($chan[0])) {
        // old config with multiple channels
        $options = array();
        foreach ($chan as $individual_chan) {
            $options[$individual_chan] = array(APP_EVENTUM_IRC_CATEGORY_DEFAULT);
        }
    } else {
        // new format
        $options = $chan;
    }

    $channels[$proj_id] = $options;
}

$bot = new Eventum_Bot();
$irc = new Net_SmartIRC();
$irc->setLogdestination(SMARTIRC_FILE);
$irc->setLogfile(APP_IRC_LOG);
$irc->setUseSockets(true);
$irc->setAutoReconnect(true);
$irc->setAutoRetry(true);
$irc->setReceiveTimeout(600);
$irc->setTransmitTimeout(600);

$irc->registerTimehandler(3000, $bot, 'notifyEvents');

// methods that keep track of who is authenticated
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-auth', $bot, 'listAuthenticatedUsers');
$irc->registerActionhandler(SMARTIRC_TYPE_NICKCHANGE, '.*', $bot, '_updateAuthenticatedUser');
$irc->registerActionhandler(SMARTIRC_TYPE_KICK | SMARTIRC_TYPE_QUIT | SMARTIRC_TYPE_PART, '.*', $bot, '_removeAuthenticatedUser');
$irc->registerActionhandler(SMARTIRC_TYPE_LOGIN, '.*', $bot, '_joinChannels');

// real bot commands
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?help', $bot, 'listAvailableCommands');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?auth ', $bot, 'authenticate');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?clock', $bot, 'clockUser');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-clocked-in', $bot, 'listClockedInUsers');
$irc->registerActionhandler(SMARTIRC_TYPE_QUERY, '^!?list-quarantined', $bot, 'listQuarantinedIssues');

$irc->connect($irc_server_hostname, $irc_server_port);
if (empty($username)) {
    $irc->login($nickname, $realname);
} elseif (empty($password)) {
    $irc->login($nickname, $realname, 0, $username);
} else {
    $irc->login($nickname, $realname, 0, $username, $password);
}
$irc->listen();
$irc->disconnect();

// release the lock
Lock::release('irc_bot');
