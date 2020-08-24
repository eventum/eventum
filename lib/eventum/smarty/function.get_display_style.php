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

function smarty_function_get_display_style($params, &$smarty)
{
    $html_result = '';
    $print_result = true;
    extract($params, EXTR_SKIP);

    if (!empty($_COOKIE['visibility_' . $element_name])) {
        $html_result = 'style="display:' . $_COOKIE['visibility_' . $element_name] . ';"';
    }
    // automatically hide the table if there is nothing to be displayed
    if (isset($total)) {
        if ($total < 1) {
            $html_result = 'style="display: none;"';
        }
    }

    if ($print_result) {
        echo $html_result;
    } else {
        return $html_result;
    }
}
