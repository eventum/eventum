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
//

/**
 * Class to hold methods and algorythms that woudln't fit in other classes, such
 * as functions to work around PHP bugs or incompatibilities between separate
 * PHP configurations.
 */
class Misc
{
    /**
     * Method used to simulate the correct behavior of array_diff().
     *
     * @param   array $foo The first array
     * @param   array $bar The second array
     * @return  array The different values
     */
    public static function arrayDiff($foo, $bar)
    {
        if (!is_array($bar)) {
            $bar = array();
        }
        $diffs = array();
        $foo_values = array_values($foo);
        $bar_values = array_values($bar);
        if (count($foo_values) > count($bar_values)) {
            $total = count($foo_values);
            $first = &$foo_values;
            $second = &$bar_values;
        } else {
            $total = count($bar_values);
            $first = &$bar_values;
            $second = &$foo_values;
        }
        for ($i = 0; $i < $total; $i++) {
            if ((!empty($first[$i])) && (!@in_array($first[$i], $second))) {
                $diffs[] = $first[$i];
            }
            if ((!empty($second[$i])) && (!@in_array($second[$i], $first))) {
                $diffs[] = $second[$i];
            }
        }

        return $diffs;
    }

    /**
     * Retrieves values for fieldName from specified array.
     *
     * @param string $fieldName value to collect
     * @param array|object $array array or object to search
     * @return array new array containing the fieldName values from original array
     */
    public static function collect($fieldName, $array)
    {
        $result = array();
        if (empty($array)) {
            return $result;
        }

        foreach ($array as $object) {
            if (is_object($object) && isset($object->$fieldName)) {
                array_push($result, $object->$fieldName);
            } elseif (is_array($object) && isset($object[$fieldName])) {
                array_push($result, $object[$fieldName]);
            }
        }

        return $result;
    }

    /*
     * Merge two arrays so that $a contains all keys that $b would
     */

    public static function array_extend($a, $b)
    {
        foreach ($b as $k => $v) {
            if (is_array($v)) {
                if (!isset($a[$k])) {
                    $a[$k] = $v;
                } else {
                    $a[$k] = self::array_extend($a[$k], $v);
                }
            } else {
                $a[$k] = $v;
            }
        }

        return $a;
    }

    /**
     * Return bytes count of $data, even in the presence of
     * mbstring.func_overload
     *
     * @param string $data the string we're measuring
     * @return int
     */
    public static function countBytes($data)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($data, '8bit');
        }

        return strlen($data);
    }

    /**
     * Process string with callback function. Input can be string or array of strings
     *
     * @param string|string[] $mixed
     * @param callable $callback
     * @return string|string[]
     */
    private static function walk($mixed, $callback)
    {
        if (!$mixed) {
            return $mixed;
        }

        if (is_array($mixed)) {
            foreach ($mixed as $i => $item) {
                $mixed[$i] = $callback($item);
            }

            return $mixed;
        } else {
            return $callback($mixed);
        }
    }

    /**
     * Lowercase string, it can be array of strings
     *
     * @param string|string[] $mixed
     * @param string $encoding The string encoding. Default UTF-8.
     * @return string|string[]
     */
    public static function lowercase($mixed, $encoding = APP_CHARSET)
    {
        $converter = function ($str) use ($encoding) {
            return mb_convert_case($str, MB_CASE_LOWER, $encoding);
        };

        return self::walk($mixed, $converter);
    }

    /**
     * Removes leading and trailing whitespace from input.
     *
     * @param string|string[] $mixed
     * @return string|string[]
     */
    public static function trim($mixed)
    {
        $converter = function ($str) {
            return trim($str);
        };

        return self::walk($mixed, $converter);
    }

    /**
     * Method used to get the title given to the current installation of Eventum.
     *
     * @return  string The installation title
     */
    public static function getToolCaption()
    {
        $setup = Setup::get();

        return !empty($setup['tool_caption']) ? $setup['tool_caption'] : APP_NAME;
    }

    /**
     * Method used to print a prompt asking the user for information.
     *
     * @param   string $message The message to print
     * @param   string $default_value The default value to be used if the user just press <enter>
     * @return  string The user response
     */
    public function prompt($message, $default_value)
    {
        echo $message;
        if ($default_value !== false) {
            echo " [default: $default_value] -> ";
        } else {
            echo ' [required] -> ';
        }
        flush();
        $input = self::getInputLine();
        if (empty($input)) {
            if ($default_value === false) {
                die("ERROR: Required parameter was not provided!\n");
            } else {
                return $default_value;
            }
        } else {
            return $input;
        }
    }

    /**
     * Method used to get a line from the standard input.
     *
     * @return  string The standard input value
     */
    private static function getInputLine()
    {
        return trim(fgets(STDIN));
    }

    /**
     * Method used to check the spelling of a given text.
     *
     * @param   string $text The text to check the spelling against
     * @return  array Information about the mispelled words, if any
     */
    public static function checkSpelling($text)
    {
        $temptext = tempnam('/tmp', 'spelltext');
        if ($fd = fopen($temptext, 'w')) {
            $textarray = explode("\n", $text);
            fwrite($fd, "!\n");
            foreach ($textarray as $value) {
                // adding the carat to each line prevents the use of aspell commands within the text...
                fwrite($fd, "^$value\n");
            }
            fclose($fd);
            $return = shell_exec("cat $temptext | /usr/bin/aspell -a");
            unlink($temptext);
        }
        $lines = explode("\n", $return);
        // remove the first line that is only the aspell copyright banner
        array_shift($lines);
        // remove all blank lines
        foreach ($lines as $key => $value) {
            if (empty($value)) {
                unset($lines[$key]);
            }
        }
        $lines = array_values($lines);

        $misspelled_words = array();
        $spell_suggestions = array();
        foreach ($lines as $line) {
            if (substr($line, 0, 1) == '&') {
                // found suggestions for this word
                $first_part = substr($line, 0, strpos($line, ':'));
                $pieces = explode(' ', $first_part);
                $misspelled_word = $pieces[1];
                $last_part = substr($line, strpos($line, ':') + 2);
                $suggestions = explode(', ', $last_part);
            } elseif (substr($line, 0, 1) == '#') {
                // found no suggestions for this word
                $pieces = explode(' ', $line);
                $misspelled_word = $pieces[1];
                $suggestions = array();
            } else {
                // no spelling mistakes could be found
                continue;
            }
            // prevent duplicates...
            if (in_array($misspelled_word, $misspelled_words)) {
                continue;
            }
            $misspelled_words[] = $misspelled_word;
            $spell_suggestions[$misspelled_word] = $suggestions;
        }

        return array(
            'total_words' => count($misspelled_words),
            'words' => $misspelled_words,
            'suggestions' => $spell_suggestions,
        );
    }

    /**
     * Method used to replace all special whitespace characters (\n,
     * \r and \t) by their string equivalents. It is usually used in
     * JavaScript code.
     *
     * @param   string $str The string to be escaped
     * @return  string The escaped string
     */
    public static function escapeWhitespace($str)
    {
        $str = str_replace("\n", '\n', $str);
        $str = str_replace("\r", '\r', $str);
        $str = str_replace("\t", '\t', $str);

        return $str;
    }

    /**
     * Method used to simulate array_map()'s functionality in a deeply nested
     * array. The PHP built-in function does not allow that.
     *
     * @param   array $in_array The array to run the function against
     * @param   string $in_func The function to run
     * @param   array $in_args The array of arguments to pass to the function
     * @param   integer $in_index Internal parameter to specify which index of the array we are currently mapping
     * @return  array The mapped array
     */
    public static function array_map_deep(&$in_array, $in_func, $in_args = array(), $in_index = 1)
    {
        // fix people from messing up the index of the value
        if ($in_index < 1) {
            $in_index = 1;
        }
        foreach (array_keys($in_array) as $key) {
            // we need a reference, not a copy, normal foreach won't do
            $value = &$in_array[$key];
            // we need to copy args because we are doing
            // manipulation on it farther down
            $args = $in_args;
            if (is_array($value)) {
                self::array_map_deep($value, $in_func, $in_args, $in_index);
            } else {
                array_splice($args, $in_index - 1, $in_index - 1, $value);
                $value = call_user_func_array($in_func, $args);
            }
        }

        return $in_array;
    }

    /**
     * Method used to format a filesize in bytes to the appropriate string,
     * showing 'Kb' and 'Mb'.
     *
     * @param   integer $bytes The filesize to format
     * @return  string The formatted filesize
     */
    public static function formatFileSize($bytes)
    {
        $kb = 1024;
        $mb = 1024 * 1024;
        if ($bytes <= $kb) {
            return "$bytes bytes";
        } elseif (($bytes > $kb) && ($bytes <= $mb)) {
            $kbytes = $bytes / 1024;

            return sprintf('%.1f', round($kbytes, 1)) . ' KiB';
        } else {
            $mbytes = ($bytes / 1024) / 1024;

            return sprintf('%.1f', round($mbytes, 1)) . ' MiB';
        }
    }

    /**
     * Method used to parse a size with qualifier to bytes.
     *
     * The available options are K (for Kilobytes), M (for Megabytes) and G
     * (for Gigabytes; available since PHP 5.1.0).
     *
     * @param   string $val The size to format
     * @return  integer size in bytes
     */
    public static function return_bytes($val)
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $val *= 1024;
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }

        return $val;
    }

    /**
     * The Util:: class provides generally useful methods of different kinds.
     *
     * $Horde: framework/Util/Util.php,v 1.366 2004/03/30 17:03:58 jan Exp $
     *
     * Copyright 1999-2004 Chuck Hagenbuch <chuck@horde.org>
     * Copyright 1999-2004 Jon Parise <jon@horde.org>
     *
     * See the enclosed file COPYING for license information (LGPL). If you
     * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
     *
     * @author  Chuck Hagenbuch <chuck@horde.org>
     * @author  Jon Parise <jon@horde.org>
     * @version $Revision: 1.366 $
     * @since   Horde 3.0
     * @package Horde_Util
     */
    public static function dispelMagicQuotes(&$var)
    {
        static $magic_quotes;

        if (!isset($magic_quotes)) {
            $magic_quotes = get_magic_quotes_gpc();
        }

        if ($magic_quotes) {
            if (!is_array($var)) {
                $var = stripslashes($var);
            } else {
                array_walk($var, array('Misc', 'dispelMagicQuotes'));
            }
        }

        return $var;
    }

    /**
     * Clean input from control characters (low bits in ASCII table).
     *
     * In case of UTF-8 encoding, strip also Unicode characters over 3 bytes
     * as MySQL 'utf8' encoding does not support it and truncates input in place of such Unicode character.
     *
     * As a better solution, since of MySQL 5.5.3, there exists
     * {@link http://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html utf8mb4} encoding
     *
     * @param string|array $value input to modify in place
     * @author Elan Ruusamäe <glen@delfi.ee>
     */
    public static function stripInput(&$value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                self::stripInput($v);
            }

            return;
        }

        // strip control chars, backspace and delete (including \r)
        $value = preg_replace('/[\x00-\x08\x0b-\x1f\x7f]/', '', $value);

        static $is_utf8;
        if (!isset($is_utf8)) {
            $is_utf8 = strtolower(APP_CHARSET) == 'utf-8' || strtolower(APP_CHARSET) == 'utf8';
        }

        if ($is_utf8) {
            // strip unicode chars over 3 bytes
            $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);
        }
    }

    /**
     * Method used to escape a string before using it in a query.
     *
     * @param   string|array $input The original string
     * @return  string|array The escaped (or not) string
     * @deprecated Using this is bad design, must use placeholders in query
     */
    public static function escapeString($input, $add_quotes = false)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::escapeString($value, $add_quotes);
            }
        } else {
            $input = DB_Helper::escapeString($input, $add_quotes);
        }

        return $input;
    }

    /**
     * Accepts a value and cleans it to only contain numeric values
     *
     * @param   mixed $input The original input.
     * @return  mixed The input converted to an integer
     * @deprecated Using this is bad design, must use placeholders in query
     */
    public static function escapeInteger($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::escapeInteger($value);
            }
        } else {
            settype($input, 'integer');
        }

        return $input;
    }

    /**
     * Method used to strip HTML from a string or array
     *
     * @param   string $str The original string or array
     * @return  string The escaped (or not) string
     */
    public static function stripHTML($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::stripHTML($value);
            }
        } else {
            $input = filter_var($input, FILTER_SANITIZE_SPECIAL_CHARS);
        }

        return $input;
    }

    /**
     * Method used to prepare a set of fields and values for a boolean search
     *
     * @param   string $field The field name
     * @param   string $value The value for that field
     * @return  string The prepared boolean search string
     */
    public static function prepareBooleanSearch($field, $value)
    {
        $boolean = array();
        $pieces = explode(' ', $value);
        foreach ($pieces as $piece) {
            $boolean[] = "$field LIKE '%" . self::escapeString($piece) . "%'";
        }

        return '(' . implode(' OR ', $boolean) . ')';
    }

    /**
     * Method used to get the full list of files contained in a specific
     * directory.
     *
     * @param   string $directory The path to list the files from
     * @return  array The list of files
     */
    public static function getFileList($directory)
    {
        $files = array();
        $dir = @opendir($directory);
        while ($item = @readdir($dir)) {
            if (($item == '.') || ($item == '..') || ($item == 'CVS') || ($item == 'SCCS')) {
                continue;
            }
            $files[] = $item;
        }

        return $files;
    }

    /**
     * Method used to format the given number of minutes in a string showing
     * the number of hours and minutes (02:30)
     *
     * @param   integer $minutes The number of minutes to format
     * @param   boolean $omit_days If days should not be used, hours will just show up as greater then 24.
     * @param   boolean $omit_empty If true, values that are "00" will be omitted.
     * @return  string The formatted time
     */
    public static function getFormattedTime($minutes, $omit_days = false, $omit_empty = false)
    {
        $hours = $minutes / 60;
        if ((!empty($minutes)) && ($minutes < 6)) {
            $return = sprintf('%02dm', $minutes);
        } elseif ($hours > 24 && $omit_days == false) {
            $return = sprintf('%dd %dh %dm (%dh %dm)', floor($minutes / 24 / 60), floor($minutes / 60) % 24, $minutes % 60, floor($minutes / 60), $minutes % 60);
        } else {
            $return = sprintf('%dh %dm', floor($minutes / 60), $minutes % 60);
        }
        if ($omit_empty) {
            $chunks = explode(' ', $return);
            foreach ($chunks as $index => $chunk) {
                preg_match("/(\d*)\S/i", $chunk, $matches);
                if ($matches[1] == '00') {
                    unset($chunks[$index]);
                }
            }
            $return = implode(' ', $chunks);
        }

        return $return;
    }

    /**
     * Method used to parse the given string for references to URLs and create
     * real links out of those.
     *
     * @param   string $text The text to search against
     * @param   string $class The CSS class to use on the actual links
     * @return  string The parsed string
     */
    public static function activateLinks($text, $class = 'link')
    {
        $range = '[-\w+@=?.%/:&;~|,#\[\]]+';
        // FIXME: handle the base of email addresses surrounded by <>, i.e.
        // Bryan Alsdorf <bryan@askmonty.org>
        $text = preg_replace("'(\w+)://($range)(\.)?'", '<a title="open $1://$2 in a new window" class="' . $class . '" href="$1://$2" target="_$2">$1://$2</a>', $text);
        $text = preg_replace("'(\s+)(www\.$range)(\.\s|\s)'", '$1<a title="open http://$2 in a new window" class="' . $class . '" href="http://$2" target="_$2">$2</a>$3', $text);

        $mail_pat = '/([-+a-z0-9_.]+@(?:[-a-z0-9_.]{2,63}\.)+[a-z]{2,6})/i';
        $text = preg_replace($mail_pat, '<a title="open mailto:$1 in a new window" class="' . $class . '" href="mailto:$1" target="_$1">$1</a>', $text);

        return $text;
    }

    /**
     * Method used to indent a given string.
     *
     * @param   string $str The string to be indented
     * @return  string The indented string
     */
    public function indent($str)
    {
        return '> ' . $str;
    }

    /**
     * Method used to format the reply of someone's email that is available in
     * the system.
     *
     * @param   string $str The string to be formatted
     * @return  string the formatted string
     */
    public static function formatReply($str)
    {
        $lines = explode("\n", str_replace("\r", '', $str));
        $lines = array_map(function ($s) { return Misc::indent($s); }, $lines);

        return implode("\n", $lines);
    }

    /**
     * Format "On ... Wrote:" reply preamble. Helper for translations.
     *
     * @param string $date
     * @param string $sender
     * @return string
     */
    public static function formatReplyPreamble($date, $sender)
    {
        $date = Date_Helper::getFormattedDate($date);

        // TRANSLATORS: %1: date, %2: sender
        $line = ev_gettext('On %1$s, %2$s wrote:', $date, $sender);

        return "\n\n\n$line\n>\n";
    }

    /**
     * Method used to check whether the given directory is writable by the
     * web server user or not.
     *
     * @param   string $file The full path to the directory
     * @return  boolean
     */
    public static function isWritableDirectory($file)
    {
        clearstatcache();
        if (!file_exists($file)) {
            if (!@mkdir($file)) {
                return false;
            }
        }
        clearstatcache();
        if (!is_writable($file)) {
            if (!stristr(PHP_OS, 'win')) {
                // let's try to change the permissions ourselves
                @chmod($file, 0755);
                clearstatcache();
                if (!is_writable($file)) {
                    return false;
                }
            } else {
                return false;
            }
        }
        if (stristr(PHP_OS, 'win')) {
            // need to check whether we can really create files in this directory or not
            // since is_writable() is not trustworthy on windows platforms
            if (is_dir($file)) {
                $fp = @fopen($file . '/dummy.txt', 'w');
                if (!$fp) {
                    return false;
                }
                @fwrite($fp, 'test');
                @fclose($fp);
                // clean up after ourselves
                @unlink($file . '/dummy.txt');
            }
        }

        return true;
    }

    /**
     * Highlights quoted replies. Relies on a smarty plugin written by
     * Joscha Feth, joscha@feth.com, www.feth.com
     *
     * @param   string $text The text to highlight
     * @return  string The highlighted text
     */
    public static function highlightQuotedReply($text)
    {
        require_once APP_INC_PATH . '/smarty/modifier.highlight_quoted.php';

        return smarty_modifier_highlight_quoted($text);
    }

    /**
     * Method used to display a nice error message when one (or more) of the
     * system requirements for Eventum is not found.
     *
     * @param array $errors The list of errors
     * @param string $title HTML page title
     * @return string
     */
    public static function displayRequirementErrors($errors, $title = 'Configuration Error')
    {
        $rel_path = APP_RELATIVE_URL;
        $messages = implode("\n<br>\n", $errors);
        echo '<html>
<head>
<style type="text/css">
<!--
.default {
  font-family: Verdana, Arial, Helvetica, sans-serif;
  font-style: normal;
  font-weight: normal;
  font-size: 70%;
}
-->
</style>
<title>', $title, '</title>
</head>
<body>

<br /><br />

<table width="600" bgcolor="#003366" border="0" cellspacing="0" cellpadding="1" align="center">
  <tr>
    <td>
      <table bgcolor="#FFFFFF" width="100%" cellspacing="1" cellpadding="2" border="0">
        <tr>
          <td><img src="', $rel_path, 'images/icons/error.gif" hspace="2" vspace="2" border="0" align="left"></td>
          <td width="100%" class="default"><span style="font-weight: bold; font-size: 160%; color: red;">Configuration Error:</span></td>
        </tr>
        <tr>
          <td colspan="2" class="default">
            <br />
            <b>The following problems were found:</b>
            <br /><br />
            ', $messages, '
            <br /><br />
            <b>Please resolve the issues described above. For file permission errors, please provide the appropriate permissions to the user that the web server run as to write in the directories and files specified above.</b>
            <br /><br />
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

</body>
</html>';
    }

    /**
     * Changes a boolean value to either "Yes" or "No".
     *
     * @param   boolean $value The boolean value
     * @return  string Either 'Yes' or 'No'.
     */
    public static function getBooleanDisplayValue($value)
    {
        if ($value == true) {
            return ev_gettext('Yes');
        } else {
            return ev_gettext('No');
        }
    }

    public static function removeNewLines($str, $no_space = false)
    {
        if ($no_space) {
            $replacement = '';
        } else {
            $replacement = ' ';
        }

        return str_replace(array("\n", "\r"), $replacement, $str);
    }

    public static function htmlentities($var)
    {
        return htmlentities($var, ENT_QUOTES, APP_CHARSET);
    }

    const MSG_INFO = 'info';
    const MSG_WARNING = 'warning';
    const MSG_ERROR = 'error';
    const MSG_HTML_BOX = 'html_box';
    const MSG_NOTE_BOX = 'note_box';

    public static function setMessage($msg, $type = self::MSG_INFO)
    {
        $messages = Session::get('messages', array());
        $messages[] = array(
            'text' => $msg,
            'type' => $type,
        );
        Session::set('messages', $messages);
    }

    public static function getMessages()
    {
        $messages = Session::get('messages', array());
        Session::set('messages', array());

        return $messages;
    }

    public static function mapMessages($result, $map)
    {
        foreach ($map as $val => $info) {
            if ($result == $val) {
                self::setMessage($info[0], $info[1]);

                return;
            }
        }
    }

    public static function displayNotifiedUsers($notify_list)
    {
        if (count($notify_list) > 0) {
            $update_tpl = new Template_Helper();
            $update_tpl->setTemplate('include/notified_list.tpl.html');
            $update_tpl->assign('notify_list', $notify_list);
            self::setMessage($update_tpl->getTemplateContents(false), self::MSG_HTML_BOX);
        }
    }

    /**
     * Shortcut method to check if if an element is set in the array and if not
     * return a default value.
     *
     * @param  array $array The array to check if the element is in
     * @param  string $var_name The name of the element to check for
     * @param  mixed $default The default value to return if the element is not set
     * @return mixed
     */
    public static function ifSet($array, $var_name, $default = null)
    {
        if (isset($array[$var_name])) {
            return $array[$var_name];
        } else {
            return $default;
        }
    }

    public static function arrayToQueryString($array, $parent_name = false)
    {
        $qs = '';
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $qs .= self::arrayToQueryString($val, $key);
            } else {
                if ($parent_name != false) {
                    $key = $parent_name . '[' . $key . ']';
                }
                $qs .= '&' . $key . '=' . urlencode($val);
            }
        }

        return $qs;
    }

    /**
     * Method used to get the standard input.
     *
     * @return  string The standard input value
     */
    public static function getInput($is_one_liner = false)
    {
        static $return;

        if (!empty($return)) {
            return $return;
        }

        $terminator = "\n";

        $stdin = fopen('php://stdin', 'r');
        $input = '';
        while (!feof($stdin)) {
            $buffer = fgets($stdin, 256);
            $input .= $buffer;
            if (($is_one_liner) && (strstr($input, $terminator))) {
                break;
            }
        }
        fclose($stdin);
        $return = $input;

        return $input;
    }

    public static function displayErrorMessage($msg)
    {
        self::setMessage($msg, self::MSG_ERROR);
        $tpl = new Template_Helper();
        $tpl->setTemplate('error_message.tpl.html');
        $tpl->displayTemplate();
        exit;
    }

    /**
     * Base 64 encodes all elements of an array.
     *
     * @param   array $data The values to encode
     * @return  array The array of encoded values.
     */
    public static function base64_encode($data)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::base64_encode($v);
            }
        } else {
            $data = base64_encode($data);
        }

        return $data;
    }

    /**
     * Tell whether a value is a PEAR error.
     *
     * @param   mixed $data the value to test
     * @param   int $code if $data is an error object, return true
     *                        only if $code is a string and
     *                        $obj->getMessage() == $code or
     *                        $code is an integer and $obj->getCode() == $code
     * @return  bool    true if parameter is an error
     */
    public static function isError($data, $code = null)
    {
        if (!$data instanceof PEAR_Error) {
            return false;
        }

        if ($code === null) {
            return true;
        }

        if (is_string($code)) {
            return $data->getMessage() == $code;
        }

        return $data->getCode() == $code;
    }

    /**
     * Generate a random byte string of the requested size.
     *
     * Uses Medium Strength Generator
     *
     * @link https://github.com/ircmaxell/RandomLib#factory-getlowstrengthgenerator
     *
     * @param int $size
     * @return string
     */
    public static function generateRandom($size = 32)
    {
        $factory = new RandomLib\Factory();
        $generator = $factory->getMediumStrengthGenerator();

        return $generator->generate($size);
    }

    /**
     * Processes a message according to PSR-3 rules
     *
     * It replaces {foo} with the value from $context['foo']
     *
     * @see \Monolog\Processor\PsrLogMessageProcessor()
     * @link https://github.com/Seldaek/monolog/blob/master/src/Monolog/Processor/PsrLogMessageProcessor.php
     * @param string $message
     * @param  array $context
     * @return string
     */
    public static function processTokens($message, $context)
    {
        // shortcut out
        if (false === strpos($message, '{')) {
            return $message;
        }

        // handle empty context
        if (!$context) {
            $context = array();
        }

        // handle raw data from database (json encoded)
        if (!is_array($context)) {
            $context = json_decode($context, true);
        }

        $replacements = array();
        foreach ($context as $key => $val) {
            if (is_null($val) || is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replacements['{' . $key . '}'] = $val;
            } elseif (is_object($val)) {
                $replacements['{' . $key . '}'] = '[object ' . get_class($val) . ']';
            } else {
                $replacements['{' . $key . '}'] = '[' . gettype($val) . ']';
            }
        }

        $message = strtr($message, $replacements);

        return $message;
    }
}
