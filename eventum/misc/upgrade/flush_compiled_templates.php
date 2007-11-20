<?php
define('APP_PATH', realpath(dirname(__FILE__) . '/../..'));
if (!file_exists(APP_PATH . '/config/config.php')) {
	die("Can't find config.php from ". APP_PATH . "/config. Did you forgot to copy config from old install?");
}

require_once APP_PATH . '/init.php';

require_once(APP_INC_PATH . "class.misc.php");

$compile_dir = APP_PATH . "templates_c";
$templates = Misc::getFileList($compile_dir);
foreach ($templates as $filename) {
    unlink($compile_dir . '/' . $filename);
}

?>
done
