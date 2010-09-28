<?php
function smarty_function_get_display_style($params, &$smarty)
{
    $print_result = true;
    extract($params);

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
        print $html_result;
    } else {
        return $html_result;
    }
}
