<?php

// {get_textarea_size page=$js_script_name field="description" default="60"}

function smarty_function_get_textarea_size($params, &$smarty)
{
    global $HTTP_COOKIE_VARS;

    $print_result = true;
    extract($params);

    $cookie_name = "textarea_" . $page . "_" . $field;
    if (!empty($HTTP_COOKIE_VARS[$cookie_name])) {
        $html_result = $HTTP_COOKIE_VARS[$cookie_name];
    } else {
        if (empty($default)) {
            $default = 60;
        }
        $html_result = $default;
    }
    
    if ($print_result) {
        print $html_result;
    } else {
        return $html_result;
    }
}
?>