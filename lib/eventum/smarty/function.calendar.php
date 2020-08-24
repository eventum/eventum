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

function _get_month_view($params)
{
    extract($params, EXTR_SKIP);

    // $values[1-31] -> values to be printed in each cell
    // $month -> month
    // $year -> year

    $time = time();
    if (!isset($month)) {
        $month = date('n', $time);
    }
    if (!isset($year)) {
        $year = date('Y', $time);
    }

    $total_days = date('t', mktime(0, 0, 0, $month, 1, $year));
    $first_day = date('w', mktime(0, 0, 0, $month, 1, $year));
    $current_day = 1;
    $current_week = 1;

    $html_result = '<table cellspacing="1" width="100%"';
    if (isset($table_class)) {
        $html_result .= ' class="' . $table_class . '"';
    }
    $html_result .= '>';
    if (!isset($header_class)) {
        $header_class = '';
    } else {
        $header_class = 'class="' . $header_class . '"';
    }
    if (!isset($cell_class)) {
        $cell_class = '';
    } else {
        $cell_class = 'class="' . $cell_class . '"';
    }
    if (!isset($faded_class)) {
        $faded_class = '';
    } else {
        $faded_class = 'class="' . $faded_class . '"';
    }
    // create the table header
    $html_result .= sprintf('<tr>
                      <th width="14%%" %s>Sunday</th>
                      <th width="14%%" %s>Monday</th>
                      <th width="14%%" %s>Tuesday</th>
                      <th width="14%%" %s>Wednesday</th>
                      <th width="14%%" %s>Thursday</th>
                      <th width="14%%" %s>Friday</th>
                      <th width="14%%" %s>Saturday</th>
                    </tr>', $header_class, $header_class, $header_class, $header_class, $header_class, $header_class, $header_class);
    // loops through the days
    while ($current_day <= $total_days) {
        $html_result .= '<tr>';
        for ($i = 0; $i < 7; $i++) {
            if (($current_week == 1 && $i >= $first_day) ||
                    ($current_week > 1 && $current_day <= $total_days)) {
                $html_result .= sprintf('<td valign="top" %s>', $cell_class);
                $html_result .= '<div style="position:relative; left: 0; top: 0; z-index:1;">' . $current_day . '</div>';
                if ((isset($values)) && (!empty($values[$current_day]))) {
                    $html_result .= $values[$current_day];
                } else {
                    $html_result .= '&nbsp;';
                }
                $html_result .= '</td>';
                $current_day++;
            } else {
                $html_result .= sprintf('<td %s>&nbsp;</td>', $faded_class);
            }
        }
        $html_result .= '</tr>';
        $current_week++;
    }
    $html_result .= '</table>';

    return $html_result;
}

function _get_week_view($params)
{
    extract($params, EXTR_SKIP);
    // $values[1-31] -> values to be printed in each cell
    // $week -> 1 - 5
    // $month -> month
    // $year -> year

    $time = time();
    if (!isset($week)) {
        $week = 1;
    }
    if (!isset($month)) {
        $month = date('n', $time);
    }
    if (!isset($year)) {
        $year = date('Y', $time);
    }
    $total_days = date('t', mktime(0, 0, 0, $month, 1, $year));
    $first_day = date('w', mktime(0, 0, 0, $month, 1, $year));
    $current_day = 1;
    $current_week = 1;

    $html_result = '<table cellspacing="1" width="100%"';
    if (isset($table_class)) {
        $html_result .= ' class="' . $table_class . '"';
    }
    $html_result .= '>';
    if (!isset($header_class)) {
        $header_class = '';
    } else {
        $header_class = 'class="' . $header_class . '"';
    }
    if (!isset($cell_class)) {
        $cell_class = '';
    } else {
        $cell_class = 'class="' . $cell_class . '"';
    }
    if (!isset($faded_class)) {
        $faded_class = '';
    } else {
        $faded_class = 'class="' . $faded_class . '"';
    }
    // create the table header
    $html_result .= sprintf('<tr>
                      <th width="14%%" %s>Sunday</th>
                      <th width="14%%" %s>Monday</th>
                      <th width="14%%" %s>Tuesday</th>
                      <th width="14%%" %s>Wednesday</th>
                      <th width="14%%" %s>Thursday</th>
                      <th width="14%%" %s>Friday</th>
                      <th width="14%%" %s>Saturday</th>
                    </tr>', $header_class, $header_class, $header_class, $header_class, $header_class, $header_class, $header_class);
    // loops through the days
    $found_week = 0;
    $clear_days = 0;
    while ($current_day <= $total_days) {
        for ($i = 0; $i < 7; $i++) {
            if ($current_week == $week) {
                $found_week = 1;
                if (($current_week == 1 && $i >= $first_day) ||
                        ($current_week > 1 && $current_day <= $total_days)) {
                    $week_html .= sprintf('<td valign="top" %s>', $cell_class);
                    $week_html .= '<div style="position:relative; left: 0; top: 0; z-index:1;">' . ($current_day - $clear_days) . '</div>';
                    if ((isset($values)) && (!empty($values[$current_day - $clear_days]))) {
                        $week_html .= $values[$current_day - $clear_days];
                    } else {
                        $week_html .= '&nbsp;';
                    }
                    $week_html .= '</td>';
                } else {
                    $clear_days++;
                    $week_html .= sprintf('<td %s>&nbsp;</td>', $faded_class);
                }
            } else {
                if (($current_week == 1 && $i >= $first_day) ||
                        ($current_week > 1 && $current_day <= $total_days)) {
                    // boo
                } else {
                    $clear_days++;
                }
            }
            $current_day++;
        }
        if ($found_week) {
            $html_result .= '<tr>' . $week_html . '</tr>';
            break;
        }
        $current_week++;
    }
    $html_result .= '</table>';

    return $html_result;
}

function smarty_function_calendar($params, &$smarty)
{
    $print_result = true;
    extract($params, EXTR_SKIP);

    if ($view == 'month') {
        $html_result = _get_month_view($params);
    } elseif ($view == 'week') {
        $html_result = _get_week_view($params);
    }

    if ($print_result) {
        echo $html_result;
    } else {
        return $html_result;
    }
}

/* vim: set expandtab: */
