<?php
function smarty_function_get_display_style($params, &$smarty)
{
    global $HTTP_COOKIE_VARS;

    $print_result = true;
    extract($params);

    if (!empty($HTTP_COOKIE_VARS["visibility_" . $element_name])) {
        $html_result = 'style="display:' . $HTTP_COOKIE_VARS["visibility_" . $element_name] . ';"';
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
?>