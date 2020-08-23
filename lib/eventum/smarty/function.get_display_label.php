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

function smarty_function_get_display_label($params, &$smarty)
{
    $print_result = true;
    $show = ev_gettext('show');
    $hide = ev_gettext('hide');
    extract($params, EXTR_SKIP);

    $cookie_name = 'visibility_' . $element_name;
    if (!empty($_COOKIE[$cookie_name])) {
        if ($_COOKIE[$cookie_name] == 'none') {
            $html_result = $show;
        } else {
            $html_result = $hide;
        }
    } else {
        $html_result = $hide;
    }

    // automatically hide the table if there is nothing to be displayed
    if (isset($total)) {
        if ($total < 1) {
            $html_result = $show;
        }
    }

    if ($print_result) {
        echo $html_result;
    } else {
        return $html_result;
    }
}
