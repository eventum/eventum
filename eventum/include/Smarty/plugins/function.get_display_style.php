<?php
function smarty_function_get_display_style($params, &$smarty)
{
    global $HTTP_COOKIE_VARS;

    $print_result = true;
    extract($params);

    if (!empty($HTTP_COOKIE_VARS["visibility_" . $element_name])) {
        $html_result = 'style="display:' . $HTTP_COOKIE_VARS["visibility_" . $element_name] . ';"';
    }
    
    if ($print_result) {
        print $html_result;
    } else {
        return $html_result;
    }
}
?>