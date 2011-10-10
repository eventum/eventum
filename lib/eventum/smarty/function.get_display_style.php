<?php
function smarty_function_get_display_style($params, &$smarty)
{
    $html_result = '';
    $print_result = true;
    extract($params);

    if (!empty($_COOKIE['visibility_' . $element_name])) {
        $html_result = 'style="display:' . $_COOKIE['visibility_' . $element_name] . ';"';
    }

    if ($print_result) {
        print $html_result;
    } else {
        return $html_result;
    }
}
