<?php
function smarty_function_get_display_label($params, &$smarty)
{
    $print_result = true;
    extract($params);

    $cookie_name = 'visibility_' . $element_name;
    if (!empty($_COOKIE[$cookie_name])) {
        if ($_COOKIE[$cookie_name] == 'none') {
            $html_result = 'show';
        } else {
            $html_result = 'hide';
        }
    } else {
        $html_result = 'hide';
    }

    // automatically hide the table if there is nothing to be displayed
    if (isset($total)) {
        if ($total < 1) {
            $html_result = 'show';
        }
    }

    if ($print_result) {
        print $html_result;
    } else {
        return $html_result;
    }
}
