<?php
// upgrade the config.inc.php file

define('APP_PATH', realpath(dirname(__FILE__) . '/../../../') . '/');
define('APP_CONFIG_PATH', APP_PATH . '/config/');

if (!is_writable(APP_PATH . '/config/')) {
    echo "Error: '" . APP_PATH . "/config/' is not writeable. Please change
            this directory to be writeable by the webserver.";
    exit(1);
}

// make backup copy
$backup_file = APP_PATH . "/config/config.inc.pre_2_0.php";
if (copy(APP_PATH . "/config.inc.php", $backup_file) == false) {
    echo "Unable to create backup copy of config.inc.php. Please check your config/ directory is writeable and try again.";
    exit(1);
}

// read old file and parse out needed values
$old_config = file_get_contents(APP_PATH . "/config.inc.php");

$config_contents = implode("", file(APP_PATH . "/setup/config.php"));
$config_contents = str_replace("%{APP_SQL_DBHOST}%", get_old_value('APP_SQL_DBHOST'), $config_contents);
$config_contents = str_replace("%{APP_SQL_DBNAME}%", get_old_value('APP_SQL_DBNAME'), $config_contents);
$config_contents = str_replace("%{APP_SQL_DBUSER}%", get_old_value('APP_SQL_DBUSER'), $config_contents);
$config_contents = str_replace("%{APP_SQL_DBPASS}%", get_old_value('APP_SQL_DBPASS'), $config_contents);
$config_contents = str_replace("%{APP_TABLE_PREFIX}%", get_old_value('APP_TABLE_PREFIX'), $config_contents);
$config_contents = str_replace("%{APP_HOSTNAME}%", get_old_value('APP_HOSTNAME'), $config_contents);
$config_contents = str_replace("%{APP_RELATIVE_URL}%", get_old_value('APP_RELATIVE_URL'), $config_contents);
$config_contents = str_replace("%{CHARSET}%", get_old_value('APP_CHARSET'), $config_contents);
$config_contents = str_replace("'%{APP_ENABLE_FULLTEXT}%'", get_old_value('APP_ENABLE_FULLTEXT'), $config_contents);
if (stristr(get_old_value('APP_BASE_URL'), 'https://') !== false) {
    $protocol_type = 'https://';
} else {
    $protocol_type = 'http://';
}
$config_contents = str_replace("%{PROTOCOL_TYPE}%", $protocol_type, $config_contents);
$res = file_put_contents(APP_PATH . '/config/config.php', $config_contents);
if (!$res) {
    echo "Could not write file 'config.inc.php'. The permissions on the file should be set as to allow the user that the web server runs as to open it. Please correct this problem and try again.";
    exit(1);
}

if (copy(APP_PATH . "/setup.conf.php", APP_CONFIG_PATH . "/setup.php") == false) {
	echo "Unable to copy '" . APP_PATH . "/setup.conf.php' to '" .APP_CONFIG_PATH . "/setup.php'";
	exit(1);
}
if (copy(APP_PATH . "/include/private_key.php", APP_CONFIG_PATH . "/private_key.php") == false) {
	echo "Unable to copy '" . APP_PATH . "/include/private_key.php' to '" .APP_CONFIG_PATH . "/private_key.php'";
	exit(1);
}

function get_old_value($name)
{
    GLOBAL $old_config;

    preg_match("/@?define\(\"" . $name . "\", (.*)\);/", $old_config, $matches);
    return trim($matches[1], '"');
}

?>
<h1>Done</h1>

<p>Eventum 2.0 stores configuration differently then previous versions. All configuration
files are now located in the 'config/' sub directory. Once you have tested that Eventum is
smoothly you may remove the following configuration files:
<ul>
    <li>config.inc.pnp</li>
    <li>setup.conf.php</li>
    <li>include/private_key.php</li>
</ul>

Your old configuration has been backup in the file <i>'<?php echo $backup_file; ?>'</i>.
</p>

<a href="database_changes.php">Perform database changes</a>.<br />
