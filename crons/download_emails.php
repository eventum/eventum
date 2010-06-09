#!/usr/bin/php
<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// @(#) $Id: download_emails.php 3872 2009-04-13 20:51:59Z glen $

ini_set("memory_limit", "1024M");

require_once dirname(__FILE__).'/../init.php';

// setup constant to be used globally
define('SAPI_CLI', 'cli' == php_sapi_name());

/**
 * Display status message.
 *
 * Respects calling context:
 * - for CLI output is displayed to STDOUT,
 * - for Web newlines are converted to HTML linebreaks.
 */
function msg() {
    $args = func_get_args();
    // let messages be newline terminated
    $args[] = "";
    $msg = implode("\n", $args);

    if (SAPI_CLI) {
        fwrite(STDOUT, $msg);
    } else {
        $msg = nl2br($msg);
        echo $msg;
    }
}

/**
 * Display fatal error message and exit program.
 *
 * Respects calling context:
 * - for CLI output is displayed to STDERR,
 * - for Web newlines are converted to HTML linebreaks.
 */
function fatal() {
    $args = func_get_args();
    // let messages be newline terminated
    $args[] = "";
    $msg = implode("\n", $args);

    if (SAPI_CLI) {
        fwrite(STDERR, 'ERROR: '.$msg);
    } else {
        $msg = '<b>ERROR</b>: '.nl2br($msg);
        echo $msg;
    }

    exit(1);
}

/**
 * Get parameters needed for this script.
 *
 * for CLI mode these are take from command line arguments
 * for Web mode those are taken as named _GET parameters.
 *
 * @return  array   $config
 */
function getParams() {
    // some defaults,
    $config = array(
        'fix-lock' => false,
        'username' => null,
        'hostname' => null,
        'mailbox' => null,
    );

    if (SAPI_CLI) {
        global $argc, $argv;
        // --fix-lock may be only the last argument (first or fourth)
        if ($argv[$argc - 1] == '--fix-lock') {
            // no other args are allowed
            $config['fix-lock'] = true;
        }

        if ($argc > 1) {
            $config['username'] = $argv[1];
        }
        if ($argc > 2) {
            $config['hostname'] = $argv[2];
        }
        if ($argc > 3) {
            $config['mailbox'] = $argv[3];
        }
    } else {
        foreach (array_keys($config) as $key) {
            if (isset($_GET[$key])) {
                $config[$key] = $_GET[$key];
            }
        }
    }
    return $config;
}

// we need the IMAP extension for this to work
if (!function_exists('imap_open')) {
    fatal(
        "Eventum requires the IMAP extension in order to download messages saved on a IMAP/POP3 mailbox.",
        "Please refer to the PHP manual for more details about how to enable the IMAP extension."
    );
}

// argv/argc needs to be enabled in CLI mode
if (SAPI_CLI && ini_get('register_argc_argv') == 'Off') {
    fatal(
        "Eventum requires the ini setting register_argc_argv to be enabled to run this script.",
        "Please refer to the PHP manual for more details on how to change this ini setting."
    );
}

$config = getParams();

// check for the required parameters
if (!$config['fix-lock'] && (empty($config['username']) || empty($config['hostname']))) {
    if (SAPI_CLI) {
        fatal(
            "Wrong number of parameters given. Expected parameters related to the email account:",
            " 1 - username",
            " 2 - hostname",
            " 3 - mailbox (only required if IMAP account)",
            "Example: php download_emails.php user example.com INBOX"
        );
    } else {
        fatal(
            "Wrong number of parameters given. Expected parameters related to email account:",
            "download_emails.php?username=<i>username</i>&hostname=<i>hostname</i>&mailbox=<i>mailbox</i>"
        );
    }
}

// get the account ID early since we need it also for unlocking.
$account_id = Email_Account::getAccountID($config['username'], $config['hostname'], $config['mailbox']);
if (!$account_id && !$config['fix-lock']) {
    fatal(
        "Could not find a email account with the parameter provided.",
        "Please verify your email account settings and try again."
    );
}

if ($config['fix-lock']) {
    // if there is no account id, unlock all accounts
    if (!$account_id) {
        $prj_ids = array_keys(Project::getAll());
        foreach ($prj_ids as $prj_id) {
            $ema_ids = Email_Account::getAssocList($prj_id);
            foreach ($ema_ids as $ema_id => $ema_title) {
                $lockfile = 'download_emails_' . $ema_id;
                Lock::release($lockfile);
                msg("Removed lock file '$lockfile'.");
            }
        }
    } else {
        $lockfile = 'download_emails_' . $account_id;
        Lock::release($lockfile);
        msg("Removed lock file '$lockfile'.");
    }
    exit(0);
}

// check if there is another instance of this script already running
if (!Lock::acquire('download_emails_' . $account_id)) {
    if (SAPI_CLI) {
        fatal(
            "Another instance of the script is still running for the specified account.",
            "If this is not accurate, you may fix it by running this script with '--fix-lock'",
            "as the 4th parameter or you may unlock ALL accounts by running this script with '--fix-lock'",
            "as the only parameter."
        );
    } else {
        fatal(
            "Another instance of the script is still running for the specified account. ",
            "If this is not accurate, you may fix it by running this script with 'fix-lock=1'",
            "in the query string or you may unlock ALL accounts by running this script with 'fix-lock=1'",
            "as the only parameter."
        );
    }
    exit;
}

// clear the lock in all cases of termination
function cleanup_lock() {
    global $account_id;
    Lock::release('download_emails_' . $account_id);
}
register_shutdown_function('cleanup_lock');

$account = Email_Account::getDetails($account_id);
$mbox = Support::connectEmailServer($account);
if ($mbox == false) {
    $uri = Support::getServerURI($account);
    $login = $account['ema_username'];
    $error = imap_last_error();
    fatal(
        "$error\n",
        "Could not connect to the email server '$uri' with login: '$login'.",
        "Please verify your email account settings and try again."
    );
}

$total_emails = Support::getTotalEmails($mbox);

if ($total_emails > 0) {
    for ($i = 1; $i <= $total_emails; $i++) {
        Support::getEmailInfo($mbox, $account, $i);
    }
}
imap_expunge($mbox);
Support::clearErrors();
