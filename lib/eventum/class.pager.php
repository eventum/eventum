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


/**
 * Class to manage paginated links on the frontend pages.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Pager
{
    /**
     * Returns the total number of rows for a specific query. It is used to
     * calculate the total number of pages of data.
     *
     * @access  public
     * @param   string $stmt The SQL statement
     * @return  int The total number of rows
     */
    function getTotalRows($stmt)
    {
        $stmt = str_replace("\n", "", $stmt);
        $stmt = str_replace("\r", "", $stmt);
        if (stristr($stmt, 'GROUP BY')) {
            // go the extra mile and try to use the grouped by column in the count() call
            preg_match("/.*\s+GROUP BY\s+(\w*)\s+.*/i", $stmt, $matches);
            if (!empty($matches[1])) {
                $stmt = preg_replace("/SELECT (.*?) FROM /sei", "'SELECT COUNT(DISTINCT " . $matches[1] . ") AS total_rows FROM '", $stmt);
            }
        } else {
            $stmt = preg_replace("/SELECT (.*?) FROM /sei", "'SELECT COUNT(*) AS total_rows FROM '", $stmt);
        }
        // remove any order by clauses
        $stmt = preg_replace("/(.*)(ORDER BY\s+\w+\s+\w+)[,\s+\w+\s+\w+]*(.*)/sei", "'\\1\\3'", $stmt);
        $rows = DB_Helper::getInstance()->getAll($stmt, DB_FETCHMODE_ASSOC);
        if (PEAR::isError($rows)) {
            Error_Handler::logError(array($rows->getMessage(), $rows->getDebugInfo()), __FILE__, __LINE__);
            return 0;
        } elseif (empty($rows)) {
            return 0;
        } else {
            // the query above works only if there is no left join or any other complex queries
            if (count($rows) == 1) {
                return $rows[0]["total_rows"];
            } else {
                return count($rows);
            }
        }
    }


    /**
     * Returns the query string to be used on the paginated links
     *
     * @access  private
     * @return  string The query string
     */
    function _buildQueryString()
    {
        $query_str = "";
        // gotta check manually here
        $params = $_GET;
        while (list($key, $value) = each($params)) {
            if ($key != "pagerRow") {
                $query_str .= "&" . $key . "=" . urlencode($value);
            }
        }
        return $query_str;
    }


    /**
     * Returns an array with the paginated links, one in each item.
     *
     * @access  public
     * @param   int $row Current page number (starts from zero)
     * @param   int $total_rows Total number of rows, as returned by Pager::getTotalRows()
     * @param   int $per_page Maximum number of rows per page
     * @param   string $show_links An option to show 'Next'/'Previous' links, page numbering links or both ('sides', 'pages' or 'all')
     * @param   string $show_blank An option to show 'Next'/'Previous' strings even if there are no appropriate next or previous pages
     * @param   array $link_str The strings to be used instead of the default 'Next >>' and '<< Previous'
     * @return  array The list of paginated links
     * @see     getTotalRows()
     */
    function getLinks($row, $total_rows, $per_page, $show_links = "all", $show_blank = "off", $link_str = -1)
    {
        // check for emptyness
        if ((empty($total_rows)) || (empty($per_page))) {
            return array();
        }
        if ($link_str == -1) {
            $link_str = array(
                "previous" => "&lt;&lt; " . ev_gettext("Previous"),
                "next"     => ev_gettext("Next") . " &gt;&gt;"
            );
        }
        $extra_vars = self::_buildQueryString();
        $file = $_SERVER["SCRIPT_NAME"];
        $number_of_pages = ceil($total_rows / $per_page);
        $subscript = 0;
        for ($current = 0; $current < $number_of_pages; $current++) {
            // if we need to show all links, or the 'side' links,
            // let's add the 'Previous' link as the first item of the array
            if ((($show_links == "all") || ($show_links == "sides")) && ($current == 0)) {
                if ($row != 0) {
                    $array[0] = '<A HREF="' . $file . '?pagerRow=' . ($row - 1) . $extra_vars . '">' . $link_str["previous"] . '</A>';
                } elseif (($row == 0) && ($show_blank == "on")) {
                    $array[0] = $link_str["previous"];
                }
            }

            // check to show page numbering links or not
            if (($show_links == "all") || ($show_links == "pages")) {
                if ($row == $current) {
                    // if we only have one page worth of rows, we should show the '1' page number
                    if (($current == ($number_of_pages - 1)) && ($number_of_pages == 1) && ($show_blank == "off")) {
                        $array[0] = "<b>" . ($current > 0 ? ($current + 1) : 1) . "</b>";
                    } else {
                        $array[++$subscript] = "<b>" . ($current > 0 ? ($current + 1) : 1) . "</b>";
                    }
                } else {
                    $array[++$subscript] = '<A HREF="' . $file . '?pagerRow=' . $current . $extra_vars . '">' . ($current + 1) . '</A>';
                }
            }

            // only add the 'Next' link to the array if we are on the last iteration of this loop
            if ((($show_links == "all") || ($show_links == "sides")) && ($current == ($number_of_pages - 1))) {
                if ($row != ($number_of_pages - 1)) {
                    $array[++$subscript] = '<A HREF="' . $file . '?pagerRow=' . ($row + 1) . $extra_vars . '">' . $link_str["next"] . '</A>';
                } elseif (($row == ($number_of_pages - 1)) && ($show_blank == "on")) {
                    $array[++$subscript] = $link_str["next"];
                }
            }
        }
        return $array;
    }


    /**
     * Returns a portion of an array of links, as returned by the Pager::getLinks()
     * function. This is especially useful for preventing a huge list of links
     * on the paginated list.
     *
     * @access  public
     * @param   array $array The full list of paginated links
     * @param   int $current The current page number
     * @param   int $target_size The maximum number of paginated links
     * @return  array The list of paginated links
     * @see     getLinks()
     */
    function getPortion($array, $current, $target_size = 20)
    {
        $size = count($array);
        if (($size <= 2) || ($size < $target_size)) {
            $temp = $array;
        } else {
            $temp = array();
            if (($current + $target_size) > $size) {
                $temp = array_slice($array, $size - $target_size);
            } else {
                $temp = array_slice($array, $current, $target_size);
                if ($size >= $target_size) {
                    array_push($temp, $array[$size-1]);
                }
            }
            if ($current > 0) {
                array_unshift($temp, $array[0]);
            }
        }
        // extra check to make sure
        if (count($temp) == 0) {
            return "";
        } else {
            return $temp;
        }
    }
}
