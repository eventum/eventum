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
// @(#) $Id: s.class.error_handler.php 1.14 03/12/31 17:29:00-00:00 jpradomaia $
//

include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.setup.php");

@define("REPORT_ERROR_FILE", true);

/**
 * Class to manage all tasks related to error conditions of the site, such as
 * logging facilities or alert notifications to the site administrators.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Error_Handler
{
    /**
     * Logs the specified error
     *
     * @access public
     * @param  mixed $error_msg The error message
     * @param  string $script The script name where the error happened
     * @param  integer $line The line number where the error happened
     */
    function logError($error_msg = "", $script = "", $line = "")
    {
        if (REPORT_ERROR_FILE) {
            Error_Handler::logToFile($error_msg, $script, $line);
        }
        $setup = Setup::load();
        if (@$setup['email_error']['status'] == 'enabled') {
            // if there's no db_api object, then we cannot
            // possibly queue up the error emails
            if (!is_null(@$GLOBALS["db_api"])) {
                Error_Handler::_notify($error_msg, $script, $line);
            }
        }
    }


    /**
     * Notifies site administrators of the error condition
     *
     * @access private
     * @param  mixed $error_msg The error message
     * @param  string $script The script name where the error happened
     * @param  integer $line The line number where the error happened
     */
    function _notify($error_msg = "unknown", $script = "unknown", $line = "unknown")
    {
        global $HTTP_SERVER_VARS;

        $setup = Setup::load();
        $notify_list = trim($setup['email_error']['addresses']);
        if (empty($notify_list)) {
            return false;
        }
        $notify_list = str_replace(';', ',', $notify_list);
        $notify_list = explode(',', $notify_list);

        $subject = APP_SITE_NAME . " - Error found! - " . date("m/d/Y H:i:s");
        $msg = "Hello,\n\n";
        $msg .= "An error was found at " . date("m/d/Y H:i:s") . " (" . time() . ") on line '" . $line . "' of script " . "'$script'.\n\n";
        $msg .= "The error message passed to us was:\n\n";
        if ((is_array($error_msg)) && (count($error_msg) > 1)) {
            $msg .= "'" . $error_msg[0] . "'\n\n";
            $msg .= "A more detailed error message follows:\n\n";
            $msg .= "'" . $error_msg[1] . "'\n\n";
        } else {
            $msg .= "'$error_msg'\n\n";
        }
        @$msg .= "That happened on page '" . $HTTP_SERVER_VARS["PHP_SELF"] . "' from IP Address '" . getenv("REMOTE_ADDR") . "' coming from the page (referrer) '" . getenv("HTTP_REFERER") . "'.\n\n";
        @$msg .= "The user agent given was '" . $HTTP_SERVER_VARS['HTTP_USER_AGENT'] . "'.\n\n";
        $msg .= "Sincerely yours,\nAutomated Error_Handler Class";
        // only try to include the backtrace if we are on PHP 4.3.0 or later
        if (version_compare(phpversion(), "4.3.0", ">=")) {
            $msg .= "\n\nA backtrace is available:\n\n";
            ob_start();
            $backtrace = debug_backtrace();
            // remove the two entries related to the error handling stuff itself
            array_shift($backtrace);
            array_shift($backtrace);
            // now we can print it out
            print_r($backtrace);
            $contents = ob_get_contents();
            $msg .= $contents;
            ob_end_clean();
        }
        // avoid triggering an email notification about a query that 
        // was bigger than max_allowed_packet (usually 16 megs on 3.23 
        // client libraries)
        if (strlen($msg) > 16777216) {
            return false;
        }
        foreach ($notify_list as $notify_email) {
            $mail = new Mail_API;
            $mail->setTextBody($msg);
            $mail->send($setup['smtp']['from'], $notify_email, $subject);
        }
    }


    /**
     * Logs the error condition to a specific file
     *
     * @access public
     * @param  mixed $error_msg The error message
     * @param  string $script The script name where the error happened
     * @param  integer $line The line number where the error happened
     */
    function logToFile($error_msg = "unknown", $script = "unknown", $line = "unknown")
    {
        global $HTTP_SERVER_VARS;

        $msg = "[" . date("D M d H:i:s Y") . "] ";
        $msg .= "An error was found on line '" . $line . "' of script " . "'$script'.\n\n";
        $msg .= "The error message passed to us was:\n\n";
        if ((is_array($error_msg)) && (count($error_msg) > 1)) {
            $msg .= "'" . $error_msg[0] . "'\n\n";
            $msg .= "A more detailed error message follows:\n\n";
            $msg .= "'" . $error_msg[1] . "'\n\n";
        } else {
            $msg .= "'$error_msg'\n\n";
        }
        // only try to include the backtrace if we are on PHP 4.3.0 or later
        if (version_compare(phpversion(), "4.3.0", ">=")) {
            $msg .= "\n\nA backtrace is available:\n\n";
            ob_start();
            $backtrace = debug_backtrace();
            // remove the two entries related to the error handling stuff itself
            array_shift($backtrace);
            array_shift($backtrace);
            // now we can print it out
            print_r($backtrace);
            $contents = ob_get_contents();
            $msg .= $contents;
            ob_end_clean();
        }
        $fp = @fopen(APP_ERROR_LOG, "a");
        @fwrite($fp, $msg);
        @fclose($fp);
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Error_Handler Class');
}
?>