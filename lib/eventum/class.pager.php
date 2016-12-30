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

use Eventum\Db\DatabaseException;

/**
 * Class to manage paginated links on the frontend pages.
 */
class Pager
{
    /**
     * Returns the total number of rows for a specific query. It is used to
     * calculate the total number of pages of data.
     *
     * @param   string $stmt The SQL statement
     * @return  int The total number of rows
     */
    public static function getTotalRows($stmt)
    {
        $stmt = str_replace("\n", '', $stmt);
        $stmt = str_replace("\r", '', $stmt);
        if (stristr($stmt, 'GROUP BY')) {
            // go the extra mile and try to use the grouped by column in the count() call
            preg_match("/.*\s+GROUP BY\s+(\w*)\s+.*/i", $stmt, $matches);
            if (!empty($matches[1])) {
                $stmt = preg_replace('/SELECT (.*?) FROM /si', 'SELECT COUNT(DISTINCT ' . $matches[1] . ') AS total_rows FROM ', $stmt);
            }
        } else {
            $stmt = preg_replace('/SELECT (.*?) FROM /si', 'SELECT COUNT(*) AS total_rows FROM ', $stmt);
        }
        // remove any order by clauses
        $stmt = preg_replace("/(.*)(ORDER BY\s+\w+\s+\w+)(?:,\s+\w+\s+\w+)*(.*)/si", '\\1\\3', $stmt);
        try {
            $rows = DB_Helper::getInstance()->getAll($stmt);
        } catch (DatabaseException $e) {
            return 0;
        }

        if (empty($rows)) {
            return 0;
        }

        // the query above works only if there is no left join or any other complex queries
        if (count($rows) == 1) {
            return $rows[0]['total_rows'];
        }

        return count($rows);
    }

    /**
     * Returns the query string to be used on the paginated links
     *
     * @return  string The query string
     */
    private function _buildQueryString()
    {
        $query_str = '';
        // gotta check manually here
        $params = $_GET;
        while (list($key, $value) = each($params)) {
            if ($key != 'pagerRow') {
                $query_str .= '&' . $key . '=' . urlencode($value);
            }
        }

        return $query_str;
    }

    /**
     * Returns an array with the paginated links, one in each item.
     *
     * @param   int $row Current page number (starts from zero)
     * @param   int $total_rows Total number of rows, as returned by Pager::getTotalRows()
     * @param   int $per_page Maximum number of rows per page
     * @param   string $show_links An option to show 'Next'/'Previous' links, page numbering links or both ('sides', 'pages' or 'all')
     * @param   string $show_blank An option to show 'Next'/'Previous' strings even if there are no appropriate next or previous pages
     * @param   array $link_str The strings to be used instead of the default 'Next >>' and '<< Previous'
     * @return  array The list of paginated links
     * @see     getTotalRows()
     * @deprecated method not used?
     */
    public static function getLinks($row, $total_rows, $per_page, $show_links = 'all', $show_blank = 'off', $link_str = -1)
    {
        // check for emptyness
        if ((empty($total_rows)) || (empty($per_page))) {
            return [];
        }
        if ($link_str == -1) {
            $link_str = [
                'previous' => '&lt;&lt; ' . ev_gettext('Previous'),
                'next'     => ev_gettext('Next') . ' &gt;&gt;',
            ];
        }
        $extra_vars = self::_buildQueryString();
        $file = $_SERVER['SCRIPT_NAME'];
        $number_of_pages = ceil($total_rows / $per_page);
        $subscript = 0;
        for ($current = 0; $current < $number_of_pages; $current++) {
            // if we need to show all links, or the 'side' links,
            // let's add the 'Previous' link as the first item of the array
            if ((($show_links == 'all') || ($show_links == 'sides')) && ($current == 0)) {
                if ($row != 0) {
                    $array[0] = '<A HREF="' . $file . '?pagerRow=' . ($row - 1) . $extra_vars . '">' . $link_str['previous'] . '</A>';
                } elseif (($row == 0) && ($show_blank == 'on')) {
                    $array[0] = $link_str['previous'];
                }
            }

            // check to show page numbering links or not
            if (($show_links == 'all') || ($show_links == 'pages')) {
                if ($row == $current) {
                    // if we only have one page worth of rows, we should show the '1' page number
                    if (($current == ($number_of_pages - 1)) && ($number_of_pages == 1) && ($show_blank == 'off')) {
                        $array[0] = '<b>' . ($current > 0 ? ($current + 1) : 1) . '</b>';
                    } else {
                        $array[++$subscript] = '<b>' . ($current > 0 ? ($current + 1) : 1) . '</b>';
                    }
                } else {
                    $array[++$subscript] = '<A HREF="' . $file . '?pagerRow=' . $current . $extra_vars . '">' . ($current + 1) . '</A>';
                }
            }

            // only add the 'Next' link to the array if we are on the last iteration of this loop
            if ((($show_links == 'all') || ($show_links == 'sides')) && ($current == ($number_of_pages - 1))) {
                if ($row != ($number_of_pages - 1)) {
                    $array[++$subscript] = '<A HREF="' . $file . '?pagerRow=' . ($row + 1) . $extra_vars . '">' . $link_str['next'] . '</A>';
                } elseif (($row == ($number_of_pages - 1)) && ($show_blank == 'on')) {
                    $array[++$subscript] = $link_str['next'];
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
     * @param   array $array The full list of paginated links
     * @param   int $current The current page number
     * @param   int $target_size The maximum number of paginated links
     * @return  array The list of paginated links
     * @see     getLinks()
     * @deprecated method not used?
     */
    public static function getPortion($array, $current, $target_size = 20)
    {
        $size = count($array);
        if (($size <= 2) || ($size < $target_size)) {
            $temp = $array;
        } else {
            if (($current + $target_size) > $size) {
                $temp = array_slice($array, $size - $target_size);
            } else {
                $temp = array_slice($array, $current, $target_size);
                if ($size >= $target_size) {
                    array_push($temp, $array[$size - 1]);
                }
            }
            if ($current > 0) {
                array_unshift($temp, $array[0]);
            }
        }
        // extra check to make sure
        if (count($temp) == 0) {
            return '';
        } else {
            return $temp;
        }
    }
}
