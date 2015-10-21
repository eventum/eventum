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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+


/**
 * Class to manage all tasks related to error conditions of the site, such as
 * logging facilities or alert notifications to the site administrators.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 * @author Elan Ruusamäe <glen@delfi.ee>
 */

class Error_Handler
{
    /**
     * Logs the error condition to a specific file and if asked and possible
     * queue error in mail queue for reporting.
     *
     * @param  mixed $error_msg The error message
     * @param  string $script The script name where the error happened
     * @param  integer $line The line number where the error happened
     * @param  boolean $notify_error Whether error should be notified by email.
     */
    public static function logError($error_msg = 'unknown', $script = 'unknown', $line = 0, $notify_error = true)
    {
        $msg = & self::_createErrorReport($error_msg, $script, $line);

        if (is_resource(APP_ERROR_LOG)) {
            fwrite(APP_ERROR_LOG, date('[D M d H:i:s Y] '));
            fwrite(APP_ERROR_LOG, $msg);
        } else {
            file_put_contents(APP_ERROR_LOG, array(date('[D M d H:i:s Y] '), $msg), FILE_APPEND);
        }

        // if there's no database connection, then we cannot possibly queue up the error emails
        $dbh = DB_Helper::getInstance();
        if ($notify_error === false || !$dbh) {
            return;
        }

        $setup = Setup::get();
        if (isset($setup['email_error']['status']) && $setup['email_error']['status'] == 'enabled') {
            $notify_list = trim($setup['email_error']['addresses']);
            if (empty($notify_list)) {
                return;
            }
            self::_notify($msg, $setup['smtp']['from'], $notify_list);
        }
    }

    /**
     * Notifies site administrators of the error condition
     *
     * @param  string $notify_msg The formatted error message
     * @param  string $notify_from Sender of the email
     * @param  string $notify_list Email addresses to whom send the error report.
     */
    private static function _notify(&$notify_msg, $notify_from, $notify_list)
    {
        $backtrace = debug_backtrace();
        array_splice($backtrace, 0, 2);
        foreach ($backtrace as $frame) {
            // avoid recursion?
            if (isset($frame['class']) && $frame['class'] == __CLASS__) {
                return;
            }
        }

        $time = time();
        $date = date('Y-m-d H:i:s', $time);
        $msg = "Hello,\n\n";
        $msg .= $notify_msg;

        // this checks that we're not running from commandline (cron for example)
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $proto = 'http';
            if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
                $proto .= 's';
            }
            $url = "$proto://{$_SERVER['HTTP_HOST']}{$_SERVER['SCRIPT_NAME']}";
            if (isset($_SERVER['QUERY_STRING'])) {
                $url .= "?{$_SERVER['QUERY_STRING']}";
            }

            $msg .= "URL: {$url}\n";
            $msg .= "IP: {$_SERVER['REMOTE_ADDR']}\n";

            $login = Auth::getUserLogin();
            if ($login) {
                $msg .= "User: $login\n";
            }

            if (!empty($_SERVER['HTTP_REFERER'])) {
                $msg  .= "Referer: {$_SERVER['HTTP_REFERER']}\n";
            }
            if (!empty($_SERVER['HTTP_USER_AGENT'])) {
                $msg .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\n";
            }
            $msg .= "\n";
        }
        $msg .= "-- \nSincerely yours,\nAutomated Error_Handler Class";

        $max_allowed_packet = DB_Helper::getMaxAllowedPacket();

        // skip error details of an email notification about a query that
        // was bigger than max_allowed_packet + 1024
        if (strlen($msg) > $max_allowed_packet + 1024) {
            return;
        }

        $notify_list = str_replace(';', ',', $notify_list);
        $notify_list = explode(',', $notify_list);
        $subject = APP_SITE_NAME . ' - Error found! - ' . $date;

        foreach ($notify_list as $notify_email) {
            $mail = new Mail_Helper();
            $mail->setTextBody($msg);
            $mail->send($notify_from, $notify_email, $subject, 0, false, 'error');
        }
    }

    /**
     * Formats backtrace
     *
     * @param  array    $backtrace The backtrace to format
     * @return string   A nicely formatted backtrace.
     */
    private static function format_backtrace($backtrace = null)
    {
        if ($backtrace == null) {
            $backtrace = debug_backtrace();
        }

        // we process backtrace to truncate large blobs
        $cutoff = 1024;

        $msg = '';
        foreach ($backtrace as $e) {
            if (!isset($e['file'])) {
                // this is empty when run in phpunit
                $e['file'] = '(inline)';
                $e['line'] = '0';
            }
            // backtrace frame contains: [file] [line] [function] [class] [type] [args]
            $f = $e['file'];
            $f = str_replace(APP_INC_PATH, 'APP_INC_PATH', $f);
            $f = str_replace(APP_PATH, 'APP_PATH', $f);

            $fn = $e['function'];
            if (isset($e['class'])) {
                $fn = $e['class']. $e['type']. $fn;
            }
            $a = '';
            if ($e['args']) {
                $z = array();
                foreach ($e['args'] as $x) {
                    if (is_string($x)) {
                        if (strlen($x) > $cutoff) {
                            $z[] = sprintf("(string )'%.{$cutoff}s'...", $x);
                        } else {
                            $z[] = sprintf("(string )'%s'", $x);
                        }
                    } elseif (is_object($x)) {
                        $z[] = 'Object '. get_class($x);
                    } elseif (is_array($x)) {
                        $z[] = 'Array('. count($x) .')';
                    } elseif (is_bool($x)) {
                        $z[] = '(bool ) '.$x ? 'true' : 'false';
                    } else {
                        $z[] = '(' . gettype($x). ' )' . $x;
                    }
                }
                $a = implode(', ', $z);
            }
            $msg .= sprintf("%s:%d\n  %s(%s)\n", $f, $e['line'], $fn, $a);
        }

        return $msg;
    }

    /**
     * Creates error report.
     *
     * @param  mixed $error_msg The error message
     * @param  string $script The script name where the error happened
     * @param  integer $line The line number where the error happened
     */
    private static function &_createErrorReport(&$error_msg, $script, $line)
    {
        $msg = "An error was found on $script:$line\n\n";

        $msg .= "The error message passed to us was:\n\n";
        if ((is_array($error_msg)) && (count($error_msg) > 1)) {
            $msg .= "'" . $error_msg[0] . "'\n\n";
            $msg .= "A more detailed error message follows:\n\n";
            $error_msg = $error_msg[1];
        }

        if (strlen($error_msg) > 2048) {
            $msg .= "'" . substr($error_msg, 0, 2048) . "' ...";

            // try to find native code from DB error
            // [nativecode=1153 ** Got a packet bigger than 'max_allowed_packet' bytes]'
            $nativecode = strstr($error_msg, '[nativecode=');
            if ($nativecode) {
                $msg .= ' ' . $nativecode;
            }
            $msg .= "\n";
        } else {
            $msg .= "'" . $error_msg . "'\n";
        }

        $msg .= "\nA backtrace is available:\n\n";
        $backtrace = debug_backtrace();

        // remove the two entries related to the error handling stuff itself
        array_splice($backtrace, 0, 2);

        $msg .= self::format_backtrace($backtrace);
        $msg .= "\n\n";

        return $msg;
    }
}
