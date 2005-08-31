<?php
include_once("../config.inc.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "db_access.php");

Auth::checkAuthentication(APP_COOKIE);

if (!empty($_REQUEST['iss_id'])) {
    $fields = Custom_Field::getListByIssue(Auth::getCurrentProject(), $_REQUEST['iss_id']);
} else {
    $fields = Custom_Field::getListByProject(Auth::getCurrentProject(), $_REQUEST['form_type']);
}

$data = array();
foreach ($fields as $field) {
    $backend = Custom_Field::getBackend($field['fld_id']);
    if ((is_object($backend)) && (is_subclass_of($backend, "Dynamic_Custom_Field_Backend"))) {
        $field['structured_data'] = $backend->getStructuredData();
        $data[] = $field;
    }
}

$tpl = new Template_API();
$tpl->setTemplate("js/dynamic_custom_field.tpl.js");
$tpl->assign("fields", $data);
$tpl->displayTemplate();
?>

