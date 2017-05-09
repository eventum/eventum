<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

/**
 * Class to hold methods and algorithms that woudln't fit in other classes, such
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
            $bar = [];
        }
        $diffs = [];
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
        }

        return $callback($mixed);
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

        return $setup['tool_caption'] ?: APP_NAME;
    }

    /**
     * Method used to simulate array_map()'s functionality in a deeply nested
     * array. The PHP built-in function does not allow that.
     *
     * @param   array $in_array The array to run the function against
     * @param   string $in_func The function to run
     * @param   array $in_args The array of arguments to pass to the function
     * @param   int $in_index Internal parameter to specify which index of the array we are currently mapping
     * @return  array The mapped array
     */
    public static function array_map_deep(&$in_array, $in_func, $in_args = [], $in_index = 1)
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
     * @param   int $bytes The filesize to format
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
        }
        $mbytes = ($bytes / 1024) / 1024;

        return sprintf('%.1f', round($mbytes, 1)) . ' MiB';
    }

    /**
     * Method used to parse a size with qualifier to bytes.
     *
     * The available options are K (for Kilobytes), M (for Megabytes) and G
     * (for Gigabytes; available since PHP 5.1.0).
     *
     * @param   string $val The size to format
     * @return  int size in bytes
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
     * @param   mixed $input the original input
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
     * @param   string $input The original string or array
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
        $boolean = [];
        $pieces = explode(' ', $value);
        foreach ($pieces as $piece) {
            $boolean[] = "$field LIKE '%" . self::escapeString($piece) . "%'";
        }

        return '(' . implode(' OR ', $boolean) . ')';
    }

    /**
     * Method used to get the list of files contained in a specific
     * directory with their absolute paths.
     *
     * @param   string $directory The path to list the files from
     * @return  array The list of files
     */
    public static function getFileList($directory)
    {
        $files = [];
        $dir = @opendir($directory);
        while ($item = @readdir($dir)) {
            if (($item == '.') || ($item == '..') || ($item == 'CVS') || ($item == 'SCCS')) {
                continue;
            }
            $files[] = "$directory/$item";
        }

        return $files;
    }

    /**
     * Method used to format the given number of minutes in a string showing
     * the number of hours and minutes (02:30)
     *
     * @param   int $minutes The number of minutes to format
     * @param   bool $omit_days if days should not be used, hours will just show up as greater than 24
     * @param   bool $omit_empty if true, values that are "00" will be omitted
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

        $mail_pat = '/(^|\s+)([-+a-z0-9_.]+@(?:[-a-z0-9_.]{2,63}\.)+[a-z]{2,6})/i';
        $text = preg_replace($mail_pat, '$1<a title="open mailto:$2 in a new window" class="' . $class . '" href="mailto:$2" target="_$2">$2</a>', $text);

        return $text;
    }

    /**
     * Method used to indent a given string.
     *
     * @param   string $str The string to be indented
     * @return  string The indented string
     */
    public static function indent($str)
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
        $lines = array_map(function ($s) {
            return Misc::indent($s);
        }, $lines);

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
     * @return  bool
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
          <td class="default"><span style="font-weight: bold; font-size: 160%; color: red;">Configuration Error:</span></td>
        </tr>
        <tr>
          <td class="default">
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
     * @param   bool $value The boolean value
     * @return  string either 'Yes' or 'No'
     */
    public static function getBooleanDisplayValue($value)
    {
        if ($value == true) {
            return ev_gettext('Yes');
        }

        return ev_gettext('No');
    }

    /**
     * @return string
     */
    public static function removeNewLines($str, $no_space = false)
    {
        if ($no_space) {
            $replacement = '';
        } else {
            $replacement = ' ';
        }

        return str_replace(["\n", "\r"], $replacement, $str);
    }

    public static function htmlentities($var)
    {
        return htmlentities($var, ENT_QUOTES, APP_CHARSET);
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
     * @see https://github.com/ircmaxell/RandomLib#factory-getlowstrengthgenerator
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
     * @see https://github.com/Seldaek/monolog/blob/master/src/Monolog/Processor/PsrLogMessageProcessor.php
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
            $context = [];
        }

        // handle raw data from database (json encoded)
        if (!is_array($context)) {
            $context = json_decode($context, true);
        }

        $replacements = [];
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
