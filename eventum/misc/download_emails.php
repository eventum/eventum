<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.download_emails.php 1.4 03/04/15 14:50:39-00:00 jpm $
//
include("../config.inc.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "db_access.php");

// check for the required parameters
if (@$HTTP_SERVER_VARS['argv'][1] == '--fix-lock') {
    $setup = Setup::load();
    $setup['downloading_emails'] = 'no';
    Setup::save($setup);
    echo "The lock key was fixed successfully.\n";
    exit;
} else {
    if (@count($HTTP_SERVER_VARS['argv']) != 4) {
        echo "Error: Wrong number of parameters given. Expected parameters related to the email account:\n";
        echo " 1 - username\n";
        echo " 2 - hostname\n";
        echo " 3 - mailbox\n";
        echo "Example: php -q download_emails.php bobby silly.org INBOX\n";
        exit;
    }
}

// check if there is another instance of this script already running
$setup = Setup::load();
if (@$setup['downloading_emails'] == 'yes') {
    echo "Error: Another instance of the script is still running. If this is not accurate, you may fix it by running this script with '--fix-lock' as the only parameter.\n";
    exit;
} else {
    $setup['downloading_emails'] = 'yes';
    Setup::save($setup);
}

$account_id = Support::getAccountID($HTTP_SERVER_VARS["argv"][1], $HTTP_SERVER_VARS["argv"][2], $HTTP_SERVER_VARS["argv"][3]);
if ($account_id == 0) {
    echo "Error: Could not find a email account with the parameter provided. Please verify your email account settings and try again.\n";
    exit;
}
$account = Support::getDetails($account_id);
$mbox = Support::connectEmailServer($account);
if ($mbox == false) {
    echo "Error: Could not connect to the email server. Please verify your email account settings and try again.\n";
    exit;
} else {
    $total_emails = Support::getTotalEmails($mbox);
    if ($total_emails > 0) {
        for ($i = 1; $i <= $total_emails; $i++) {
            Support::getEmailInfo($mbox, $account, $i);
        }
    }
    Support::clearErrors();
}

// clear the "lock" key
$setup = Setup::load();
$setup['downloading_emails'] = 'no';
Setup::save($setup);
?>
