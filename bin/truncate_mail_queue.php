#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
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
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+

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
