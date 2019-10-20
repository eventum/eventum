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

use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\Filesystem\Filesystem;

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
     * Return $input array containing only keys in $preserveKeys.
     * @param array $input
     * @param array $preserveKeys
     * @return array
     */
    public static function filterKeys($input, $preserveKeys = [])
    {
        return array_intersect_key($input, array_flip($preserveKeys));
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
        return mb_strlen($data, '8bit');
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
    public static function lowercase($mixed, $encoding = 'UTF-8')
    {
        $converter = static function ($str) use ($encoding) {
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
        $converter = static function ($str) {
            return trim($str);
        };

        return self::walk($mixed, $converter);
    }

    /**
     * Method used to get the title given to the current installation of Eventum.
     *
     * @return  string The installation title
     * @deprecated since 3.8.0; use Setup::getToolCaption()
     */
    public static function getToolCaption(): string
    {
        return Setup::getToolCaption();
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
    public static function array_map_deep(&$in_array, $in_func, $in_args = [], $in_index = 1): array
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
        $val = (float)$val;
        switch ($last) {
            // The 'G' modifier is available since PHP 5.1.0
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'g':
                $val *= 1024;
            /** @noinspection PhpMissingBreakStatementInspection */
            // no break
            case 'm':
                $val *= 1024;
            // no break
            case 'k':
                $val *= 1024;
        }

        // try to return int if it fits, otherwise float
        return $val > PHP_INT_MAX ? $val : (int)$val;
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
    public static function stripInput(&$value): void
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                self::stripInput($v);
            }

            return;
        }

        // strip control chars, backspace and delete (including \r)
        $value = preg_replace('/[\x00-\x08\x0b-\x1f\x7f]/', '', $value);

        // strip unicode chars over 3 bytes
        $value = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $value);
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
     * @param   string|string[] $input The original string or array
     * @return  string|string[] The escaped (or not) string
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
     * @deprecated since 3.8.0 this method does nothing
     */
    public static function activateLinks($text, $class = 'link')
    {
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
    public static function formatReply($str): string
    {
        $lines = explode("\n", str_replace("\r", '', $str));
        $lines = array_map(static function ($s) {
            return self::indent($s);
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

    public static function htmlentities($var): string
    {
        return htmlentities($var, ENT_QUOTES, 'UTF-8');
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
        $generator = @$factory->getMediumStrengthGenerator();

        return $generator->generate($size);
    }

    /**
     * Wrapper to call unserialize safe way.
     * This specifies that no classes may be instantiated.
     *
     * @param string $data
     * @param array $allowedClasses
     * @return mixed
     */
    public static function unserialize($data, $allowedClasses = [])
    {
        return unserialize($data, ['allowed_classes' => $allowedClasses ?: false]);
    }

    /**
     * @param string $path
     * @return string return path for fluent access
     */
    public static function ensureDir(string $path): string
    {
        $fs = new Filesystem();
        $fs->mkdir($path, 02775);

        return $path;
    }

    /**
     * Processes a message according to PSR-3 rules
     *
     * It replaces {foo} with the value from $context['foo']
     *
     * @param string $message
     * @param array|string $context
     * @return string
     */
    public static function processTokens(string $message, $context): string
    {
        $processor = new PsrLogMessageProcessor();

        // handle raw data from database (json encoded)
        if (!is_array($context)) {
            $context = json_decode($context, true);
        }

        $record = [
            'message' => $message,
            'context' => $context,
        ];
        $record = $processor($record);

        return $record['message'];
    }

    /**
     * Method used to output the headers and the binary data for
     * an attachment file.
     *
     * This method never returns to caller.
     *
     * @param   $data
     * @param   $filename
     * @param   $filetype
     * @param   $filesize
     * @param   bool $force_inline If the file should be forced to render in the browser
     */
    public static function outputDownload($data, $filename, $filesize, $filetype, $force_inline = false): void
    {
        if ($force_inline == true) {
            header('Content-Type: text/plain');

            if (stripos($filetype, 'gzip') !== false) {
                header('Content-Encoding: gzip');
            }
            header('Content-Disposition: inline; filename="' . urlencode($filename) . '"');
            header('Content-Length: ' . $filesize);
            echo $data;
            exit;
        }

        if (empty($filetype)) {
            $filetype = 'application/octet-stream';
        }
        if (empty($filename)) {
            $filename = ev_gettext('Untitled');
        }
        $filename = rawurlencode($filename);
        $disposition = self::getAttachmentDisposition($filetype);
        header('Content-Type: ' . $filetype);
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"; filename*=UTF-8''{$filename}");
        header("Content-Length: {$filesize}");
        echo $data;
        exit;
    }

    /**
     * Returns how the download should be displayed.
     *
     * @param string $filetype
     * @return string inline|attachment
     */
    public static function getAttachmentDisposition($filetype)
    {
        $parts = explode('/', $filetype, 2);
        if (count($parts) < 2) {
            return 'attachment';
        }

        list($type) = $parts;

        // display inline images and text documents
        if (in_array($type, ['image', 'text'])) {
            return 'inline';
        }

        return 'attachment';
    }
}
