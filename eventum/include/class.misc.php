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
// @(#) $Id: s.class.misc.php 1.44 04/01/26 13:34:39-06:00 joao@kickass. $
//


/**
 * Class to hold methods and algorythms that woudln't fit in other classes, such
 * as functions to work around PHP bugs or incompatibilities between separate 
 * PHP configurations.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.setup.php");

class Misc
{
    /**
     * Method used to simulate the correct behavior of array_diff().
     *
     * @access  public
     * @param   array $foo The first array
     * @param   array $bar The second array
     * @return  array The different values
     */
    function arrayDiff($foo, $bar)
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
     * Method used to get the title given to the current installation of Eventum.
     *
     * @access  public
     * @return  string The installation title
     */
    function getToolCaption()
    {
        $setup = Setup::load();
        return $setup['tool_caption'] ? $setup['tool_caption'] : APP_NAME;
    }


    /**
     * Method used to print a prompt asking the user for information.
     *
     * @access  public
     * @param   string $message The message to print
     * @param   string $default_value The default value to be used if the user just press <enter>
     * @return  string The user response
     */
    function prompt($message, $default_value)
    {
        echo $message;
        if ($default_value !== FALSE) {
            echo " [default: $default_value] -> ";
        } else {
            echo " [required] -> ";
        }
        flush();
        $input = trim(Misc::getInput(true));
        if (empty($input)) {
            if ($default_value === FALSE) {
                die("ERROR: Required parameter was not provided!\n");
            } else {
                return $default_value;
            }
        } else {
            return $input;
        }
    }


    /**
     * Method used to get the standard input.
     *
     * @access  public
     * @return  string The standard input value
     */
    function getInput($is_one_liner = FALSE)
    {
        static $return;

        if (!empty($return)) {
            return $return;
        }

        $terminator = "\n";

        $stdin = fopen("php://stdin", "r");
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


    /**
     * Method used to check the spelling of a given text.
     *
     * @access  public
     * @param   string $text The text to check the spelling against
     * @return  array Information about the mispelled words, if any
     */
    function checkSpelling($text)
    {
        $temptext = tempnam("/tmp", "spelltext");
        if ($fd = fopen($temptext, "w")) {
            $textarray = explode("\n", $text);
            fwrite($fd, "!\n");
            foreach ($textarray as $key => $value) {
                // adding the carat to each line prevents the use of aspell commands within the text...
                fwrite($fd,"^$value\n");
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
        for ($i = 0; $i < count($lines); $i++) {
            if (substr($lines[$i], 0, 1) == '&') {
                // found suggestions for this word
                $first_part = substr($lines[$i], 0, strpos($lines[$i], ':'));
                $pieces = explode(' ', $first_part);
                $misspelled_word = $pieces[1];
                $last_part = substr($lines[$i], strpos($lines[$i], ':')+2);
                $suggestions = explode(', ', $last_part);
            } elseif (substr($lines[$i], 0, 1) == '#') {
                // found no suggestions for this word
                $pieces = explode(' ', $lines[$i]);
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
            'words'       => $misspelled_words,
            'suggestions' => $spell_suggestions
        );
    }


    /**
     * Method used to get the full contents of the given file.
     *
     * @access  public
     * @param   string $full_path The full path to the file
     * @return  string The full contents of the file
     */
    function getFileContents($full_path)
    {
        if (!@file_exists($full_path)) {
            return '';
        }
        $fp = @fopen($full_path, "rb");
        if (!$fp) {
            return '';
        }
        $contents = @fread($fp, filesize($full_path));
        @fclose($fp);
        return $contents;
    }


    /**
     * Method used to replace all special whitespace characters (\n, 
     * \r and \t) by their string equivalents. It is usually used in
     * JavaScript code.
     *
     * @access  public
     * @param   string $str The string to be escaped
     * @return  string The escaped string
     */
    function escapeWhitespace($str)
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
     * @access  public
     * @param   array $in_array The array to run the function against
     * @param   string $in_func The function to run
     * @param   array $in_args The array of arguments to pass to the function
     * @param   integer $in_index Internal parameter to specify which index of the array we are currently mapping
     * @return  array The mapped array
     */
    function array_map_deep(&$in_array, $in_func, $in_args = array(), $in_index = 1)
    {
       // fix people from messing up the index of the value
       if ($in_index < 1) {
           $in_index = 1;
       }
       foreach (array_keys($in_array) as $key) {
           // we need a reference, not a copy, normal foreach won't do
           $value =& $in_array[$key];
           // we need to copy args because we are doing 
           // manipulation on it farther down
           $args = $in_args;
           if (is_array($value)) {
               Misc::array_map_deep($value, $in_func, $in_args, $in_index);
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
     * @access  public
     * @param   integer $bytes The filesize to format
     * @return  string The formatted filesize
     */
    function formatFileSize($bytes)
    {
        $kb = 1024;
        $mb = 1024 * 1024;
        if ($bytes <= $kb) {
            return "$bytes bytes";
        } elseif (($bytes > $kb) && ($bytes <= $mb)) {
            $kbytes = $bytes / 1024;
            return sprintf("%.1f", round($kbytes, 1)) . " Kb";
        } else {
            $mbytes = ($bytes / 1024) / 1024;
            return sprintf("%.1f", round($mbytes, 1)) . " Mb";
        }
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
    function dispelMagicQuotes(&$var)
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
     * Method used to escape a string before using it in a query.
     *
     * @access  public
     * @param   string $str The original string
     * @return  string The escaped (or not) string
     */
    function escapeString($str)
    {
        return $GLOBALS["db_api"]->escapeString($str);
    }


    /**
     * Method used to prepare a set of fields and values for a boolean search
     *
     * @access  public
     * @param   string $field The field name
     * @param   string $value The value for that field
     * @return  string The prepared boolean search string
     */
    function prepareBooleanSearch($field, $value)
    {
        $boolean = array();
        $pieces = explode(" ", $value);
        for ($i = 0; $i < count($pieces); $i++) {
            $boolean[] = "$field LIKE '%" . Misc::escapeString($pieces[$i]) . "%'";
        }
        return "(" . implode(" OR ", $boolean) . ")";
    }


    /**
     * Method used to get a random file from the 'daily tips' directory.
     *
     * @access  public
     * @param   object $tpl The template object
     * @return  string Random filename
     */
    function getRandomTip($tpl)
    {
        $tip_dir = $tpl->smarty->template_dir . "/tips";
        $files = Misc::getFileList($tip_dir);
        $i = rand(0, (integer)count($files));
        // some weird bug in the rand() function where sometimes the 
        // second parameter is non-inclusive makes us have to do this
        if (!isset($files[$i])) {
            return Misc::getRandomTip($tpl);
        } else {
            return $files[$i];
        }
    }


    /**
     * Method used to get the full list of files contained in a specific 
     * directory.
     *
     * @access  public
     * @param   string $directory The path to list the files from
     * @return  array The list of files
     */
    function getFileList($directory)
    {
        $files = array();
        $dir = @opendir($directory);
        while ($item = @readdir($dir)){
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
     * @access  public
     * @param   integer $minutes The number of minutes to format
     * @param   boolean $omit_days If days should not be used, hours will just show up as greater then 24.
     * @param   boolean $omit_empty If true, values that are "00" will be omitted.
     * @return  string The formatted time
     */
    function getFormattedTime($minutes, $omit_days = false, $omit_empty = false)
    {
        $hours = $minutes / 60;
        $mins = (($minutes % 60)/10);
        if ((!empty($minutes)) && ($minutes < 6 )) {
            $return = sprintf("%02dm", $minutes);
        } elseif ($hours > 24 && $omit_days == false) {
            $days = $hours / 24;
            $hours = $hours % 24;
            $hours += $mins;
            $return = sprintf("%02dd %.1fh", $days, $hours);
        } else {
            $return = round($hours,1) . 'h';
        }
        if ($omit_empty) {
            $chunks = explode(" ", $return);
            foreach ($chunks as $index => $chunk) {
                preg_match("/(\d*)\S/i", $chunk, $matches);
                if ($matches[1] == '00') {
                    unset($chunks[$index]);
                }
            }
            $return = join(" ", $chunks);
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
    function activateLinks($text, $class = "link")
    {
        $text = preg_replace("'(\w+)://([\w\+\-\@\=\?\.\%\/\:\&\;]+)(\.)?'", "<a title=\"open \\1://\\2 in a new window\" class=\"$class\" href=\"\\1://\\2\" target=\"_\\2\">\\1://\\2</a>", $text);
        $text = preg_replace("'(\s+)www.([\w\+\-\@\=\?\.\%\/\:\&\;]+)(\.\s|\s)'", "\\1<a title=\"open http://www.\\2 in a new window\" class=\"$class\" href=\"http://www.\\2\" target=\"_\\2\">www.\\2</a>\\3" , $text);
        return $text;
    }


    /**
     * Method used to indent a given string.
     *
     * @access  public
     * @param   string $str The string to be indented
     * @return  string The indented string
     */
    function indent($str)
    {
        return "> " . $str;
    }


    /**
     * Method used to format the reply of someone's email that is available in
     * the system.
     *
     * @access  public
     * @param   string $str The string to be formatted
     * @return  string the formatted string
     */
    function formatReply($str)
    {
        $lines = explode("\n", str_replace("\r", "", $str));
        // COMPAT: the next line requires PHP >= 4.0.6
        $lines = array_map(array("Misc", "indent"), $lines);
        return implode("\n", $lines);
    }


    /**
     * Method used to format a RFC 822 compliant date for the given unix 
     * timestamp.
     *
     * @access  public
     * @param   integer $ts The unix timestamp
     * @return  string The formatted date string
     */
    function formatReplyDate($ts)
    {
        // Sat, Sep 28, 2002 at 06:28:58PM -0400
        $first = date("D, M d, Y", $ts);
        $rest = date("H:i:sA O", $ts);
        return $first . " at " . $rest;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Misc Class');
}
?>
