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

// {get_textarea_size page=$js_script_name field="description" default="60"}
function smarty_function_get_textarea_size($params, &$smarty)
{
    $print_result = true;
    extract($params, EXTR_SKIP);

    $cookie_name = 'textarea_' . $page . '_' . $field;
    if (!empty($_COOKIE[$cookie_name])) {
        $html_result = $_COOKIE[$cookie_name];
    } else {
        if (empty($default)) {
            $default = 60;
        }
        $html_result = $default;
    }

    if ($print_result) {
        echo $html_result;
    } else {
        return $html_result;
    }
}
