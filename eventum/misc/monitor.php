<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008 MySQL AB                        |
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
//
// @(#) $Id: monitor.php 3555 2008-03-15 16:45:34Z glen $

require_once(dirname(__FILE__) . "/../init.php");
require_once(APP_INC_PATH . "class.monitor.php");

// the disk partition in which eventum is stored in
$partition = '/';

Monitor::checkDiskspace($partition);

// the owner, group and filesize settings should be changed to match the correct permissions on your server.
$required_files = array(
    APP_PATH . 'config/config.php' => array(
        'check_owner'      => true,
        'owner'            => 'apache',
        'check_group'      => true,
        'group'            => 'apache',
        'check_permission' => true,
        'permission'       => 755,
    ),
    APP_PATH . 'config/setup.php' => array(
        'check_owner'      => true,
        'owner'            => 'apache',
        'check_group'      => true,
        'group'            => 'apache',
        'check_permission' => true,
        'permission'       => 750,
        'check_filesize'   => true,
        'filesize'         => 1024
    ),
);
Monitor::checkConfiguration($required_files);
Monitor::checkDatabase();
Monitor::checkMailQueue();
Monitor::checkIRCBot();
