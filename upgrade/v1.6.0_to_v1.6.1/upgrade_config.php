<?php
// upgrade the config.inc.php file

require_once dirname(__FILE__) . '/../init.php';

// make backup copy
$backup_file = APP_PATH . "/config.inc.pre_1_6_1.php";
if (copy(APP_PATH . "/config.inc.php", $backup_file) == false) {
    echo "Unable to create backup copy of config.inc.php. Please check your base directory is writeable and try again.";
    exit(1);
}

$config_contents = file_get_contents(APP_PATH . "/setup/config.inc.php");
$config_backup = $config_contents;
$config_contents = str_replace("%{APP_PATH}%", APP_PATH, $config_contents);
$config_contents = str_replace("%{APP_SQL_DBHOST}%", APP_SQL_DBHOST, $config_contents);
$config_contents = str_replace("%{APP_SQL_DBNAME}%", APP_SQL_DBNAME, $config_contents);
$config_contents = str_replace("%{APP_SQL_DBUSER}%", APP_SQL_DBUSER, $config_contents);
$config_contents = str_replace("%{APP_SQL_DBPASS}%", APP_SQL_DBPASS, $config_contents);
$config_contents = str_replace("%{APP_TABLE_PREFIX}%", APP_TABLE_PREFIX, $config_contents);
$config_contents = str_replace("%{APP_HOSTNAME}%", APP_HOSTNAME, $config_contents);
$config_contents = str_replace("%{APP_RELATIVE_URL}%", APP_RELATIVE_URL, $config_contents);
if (APP_ENABLE_FULLTEXT == true) {
    $fulltext = 'true';
} else {
    $fulltext = 'false';
}
$config_contents = str_replace("'%{APP_ENABLE_FULLTEXT}%'", $fulltext, $config_contents);
$config_contents = str_replace("%{APP_VERSION}%", "1.6.1", $config_contents);
if (stristr(APP_BASE_URL, 'https://') !== false) {
    $protocol_type = 'https://';
} else {
    $protocol_type = 'http://';
}
$config_contents = str_replace("%{PROTOCOL_TYPE}%", $protocol_type, $config_contents);

$res = file_put_contents(APP_PATH . '/config.inc.php', $config_contents);
if (!$res) {
    echo "Could not write file 'config.inc.php'. The permissions on the file should be set as to allow the user that the web server runs as to open it. Please correct this problem and try again.";
    exit(1);
}
?>
Done. Your configuration file (config.inc.php) has been upgraded to version 1.6.1.<br />
A backup copy has been made in the file <i>'<?php echo $backup_file; ?>'</i>.
