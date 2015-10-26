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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

// This is an example config file for the IRC bot.
// This file should be renamed to irc_config.php and customized for your needs.

// The file should return array with configuration,
// You are free to use local variables if that makes config more readable for You.

// The following is the list of IRC channels that the bot should connect to,
// and the associated project name
//      Project Name -> IRC Channel(s),
//      Second Project' => array('#issues_2', '#byrocrate'),
// If you want to use IRC message categories (only applies if you have a custom workflow backend)
// the list of channels should be an associated array with the channel for the key and an array of categories
// for the value:
//      Project => array(
//          '#issues_2' =>  array(APP_EVENTUM_IRC_CATEGORY_DEFAULT, 'other')
//      )
$irc_channels = array(
    'Default Project' => '#issues',
);

return array(
    /// connection parameters
    // IRC server address
    'hostname' => 'localhost',
    'port' => 6667,
    'nickname' => 'EventumBOT',
    'realname' => 'Eventum Issue Tracking System',
    // do you need a username/password to connect to this server?
    // if so, fill in the next two variables
    'username' => '',
    'password' => '',

    // configured IRC channels
    'channels' => $irc_channels,

    /**
     * Bitwise debug level out of SMARTIRC_DEBUG_* constants
     *
     * @see Net_SmartIRC::setDebugLevel
     */
    'debuglevel' => SMARTIRC_DEBUG_NOTICE
    //            | SMARTIRC_DEBUG_CONNECTION
    //            | SMARTIRC_DEBUG_SOCKET
    //            | SMARTIRC_DEBUG_IRCMESSAGES
    //            | SMARTIRC_DEBUG_MESSAGETYPES
    //            | SMARTIRC_DEBUG_ACTIONHANDLER
    //            | SMARTIRC_DEBUG_TIMEHANDLER
    //            | SMARTIRC_DEBUG_MESSAGEHANDLER
    //            | SMARTIRC_DEBUG_CHANNELSYNCING
    //            | SMARTIRC_DEBUG_MODULES
    //            | SMARTIRC_DEBUG_USERSYNCING
    ,

    'default_category' => APP_EVENTUM_IRC_CATEGORY_DEFAULT,
);
