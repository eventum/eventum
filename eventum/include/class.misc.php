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
                die('ERROR: Required parameter was not provided!\n');
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
     * Method used to check whether running addslashes() against a string is
     * needed or not, and running it if required.
     *
     * @access  public
     * @param   string $str The original string
     * @return  string The slashed (or not) string
     */
    function runSlashes($str)
    {
        if (@get_magic_quotes_gpc() == 1) {
            return $str;
        } else {
            return addslashes($str);
        }
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
            $boolean[] = "$field LIKE '%" . $pieces[$i] . "%'";
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
     * Method used to get the list of priorities as an associative array in the
     * style of (id => title)
     *
     * @access  public
     * @return  array The list of priorities
     */
    function getAssocPriorities()
    {
        $stmt = "SELECT
                    pri_id,
                    pri_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority";
        $res = $GLOBALS["db_api"]->dbh->getAssoc($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the full list of priorities.
     *
     * @access  public
     * @return  array The list of priorities
     */
    function getPriorities()
    {
        $stmt = "SELECT
                    pri_id,
                    pri_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority";
        $res = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to get the title for a priority ID.
     *
     * @access  public
     * @param   integer $id The priority ID
     * @return  string The priority title
     */
    function getPriorityTitle($id)
    {
        $stmt = "SELECT
                    pri_title
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "priority
                 WHERE
                    pri_id=$id";
        $res = $GLOBALS["db_api"]->dbh->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return "";
        } else {
            return $res;
        }
    }


    /**
     * Method used to format the given number of minutes in a string showing
     * the number of hours and minutes (02:30)
     *
     * @access  public
     * @param   integer $minutes The number of minutes to format
     * @return  string The formatted time
     */
    function getFormattedTime($minutes)
    {
        $hours = $minutes / 60;
        $mins = $minutes % 60;
        if ($hours > 24) {
            $days = $hours / 24;
            $hours = $hours % 24;
            return sprintf("%02dd %02dh %02dm", $days, $hours, $mins);
        } else {
            return sprintf("%02dh %02dm", $hours, $mins);
        }
    }


    /**
     * Method used as a callback with the regular expression code that parses
     * text and creates links to other issues.
     *
     * @access  public
     * @param   array $matches Regular expression matches
     * @return  string The link to the appropriate issue
     */
    function callbackIssueLinks($matches)
    {
        include_once(APP_INC_PATH . "class.issue.php");
        // check if the issue is still open
        if (Issue::isClosed($matches[5])) {
            $class = 'closed_link';
        } else {
            $class = 'link';
        }
        $issue_title = Issue::getTitle($matches[5]);
        return "<a title=\"issue " . $matches[5] . " - $issue_title\" class=\"" . $class . "\" href=\"view.php?id=" . $matches[5] . "\">" . $matches[1] . $matches[2] . $matches[3] . $matches[4] . $matches[5] . "</a>";
    }


    /**
     * Method used to parse the given string for references to issues in the
     * system, and creating links to those if any are found.
     *
     * @access  public
     * @param   string $text The text to search against
     * @param   string $class The CSS class to use on the actual links
     * @return  string The parsed string
     */
    function activateIssueLinks($text, $class = "link")
    {
        $text = preg_replace_callback("/(issue)(:)?(\s)(\#)?(\d+)/i", array('Misc', 'callbackIssueLinks'), $text);
        $text = preg_replace_callback("/(bug)(:)?(\s)(\#)?(\d+)/i", array('Misc', 'callbackIssueLinks'), $text);
        return $text;
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
