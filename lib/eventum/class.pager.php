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
        }

        return $temp;
    }
}
