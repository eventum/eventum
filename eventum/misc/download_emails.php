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
include_once(APP_INC_PATH . "db_access.php");

// Expected parameters related to the email account:
// 1 - username
// 2 - hostname
// 3 - mailbox
//
// Example: php -q download_emails.php bobby silly.org INBOX
$account_id = Support::getAccountID($HTTP_SERVER_VARS["argv"][1], $HTTP_SERVER_VARS["argv"][2], $HTTP_SERVER_VARS["argv"][3]);
if ($account_id == 0) {
    echo "Error: Could not find a email account with the parameter provided. Please verify your email account settings and try again.\n";
    flush();
}
$account = Support::getDetails($account_id);
$mbox = Support::connectEmailServer($account);
if ($mbox == false) {
    echo "Error: Could not connect to the email server. Please verify your email account settings and try again.\n";
    flush();
} else {
    $total_emails = Support::getTotalEmails($mbox);
    if ($total_emails > 0) {
        for ($i = 1; $i <= $total_emails; $i++) {
            Support::getEmailInfo($mbox, $account, $i);
        }
    }
    Support::clearErrors();
}
?>
