<?php
// avoid setup redirecting us
define('INSTALL_PATH', realpath(dirname(__FILE__) . '/../../..'));
if (!file_exists(INSTALL_PATH . '/config/config.php')) {
	die("Can't find config.php from ". INSTALL_PATH . "/config. Did you forgot to copy config from old install?");
}

require_once INSTALL_PATH . '/init.php';
include_once APP_INC_PATH . 'class.custom_field.php';
include_once APP_INC_PATH . 'db_access.php';

// get all custom fields with type of date or integer
$sql = "SELECT
            fld_id
        FROM
            " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "custom_field
        WHERE
            fld_type IN ('date', 'integer')";
$res = DB_Helper::getInstance()->getCol($sql);
if (PEAR::isError($res)) {
	echo 'ERROR: ', $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
if (count($res) > 0) {
    foreach ($res as $fld_id) {
        echo "Updating field: $fld_id<br />";
        Custom_Field::updateValuesForNewType($fld_id);
    }
    echo "<hr>\nAll fields updated";
} else {
    echo "No fields to update";
}
exit(0);
