<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
include_once(APP_INC_PATH . "class.lock.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "db_access.php");

ini_set("memory_limit", "256M");

// we need the IMAP extension for this to work
if (!function_exists('imap_open')) {
    echo "Error: Eventum requires the IMAP extension in order to download messages saved on a IMAP/POP3 mailbox.\n";
    echo "Please refer to the PHP manual for more details about how to enable the IMAP extension.\n";
    exit;
}

// check for the required parameters
if (@count($HTTP_SERVER_VARS['argv']) < 3 && @$HTTP_SERVER_VARS['argv'][1] != '--fix-lock') {
    echo "Error: Wrong number of parameters given. Expected parameters related to the email account:\n";
    echo " 1 - username\n";
    echo " 2 - hostname\n";
    echo " 3 - mailbox (only required if IMAP account)\n";
    echo "Example: php -q download_emails.php user example.com INBOX\n";
    exit;
}

// get the account ID since we need it for locking.
$account_id = Email_Account::getAccountID(@$HTTP_SERVER_VARS["argv"][1], @$HTTP_SERVER_VARS["argv"][2], @$HTTP_SERVER_VARS["argv"][3]);
if ($account_id == 0 && !in_array('--fix-lock', @$HTTP_SERVER_VARS['argv'])) {
    echo "Error: Could not find a email account with the parameter provided. Please verify your email account settings and try again.\n";
    exit;
}

if (in_array('--fix-lock', @$HTTP_SERVER_VARS['argv'])) {
    // if there is no account id, unlock all accounts
    if (empty($account_id)) {
        $prj_ids = array_keys(Project::getAll());
        foreach ($prj_ids as $prj_id) {
            $ema_ids = Email_Account::getAssocList($prj_id);
            foreach ($ema_ids as $ema_id => $ema_title) {
                Lock::release('download_emails_' . $ema_id);
            }
        }
    } else {
        Lock::release('download_emails_' . $account_id);
    }
    echo "The lock file was removed successfully.\n";
    exit;
}

// check if there is another instance of this script already running
if (!Lock::acquire('download_emails_' . $account_id)) {
    echo "Error: Another instance of the script is still running for the specified account. " . 
                "If this is not accurate, you may fix it by running this script with '--fix-lock' " . 
                "as the 4th parameter or you may unlock ALL accounts by running this script with '--fix-lock' " . 
                "as the only parameter.\n";
    exit;
}

$account = Email_Account::getDetails($account_id);
$mbox = Support::connectEmailServer($account);
if ($mbox == false) {
    echo "Error: Could not connect to the email server. Please verify your email account settings and try again.\n";
    Lock::release('download_emails_' . $account_id);
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

// clear the lock
Lock::release('download_emails_' . $account_id);
?>
