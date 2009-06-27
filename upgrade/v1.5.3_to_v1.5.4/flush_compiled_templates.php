<?php
require_once dirname(__FILE__) . '/../init.php';

$compile_dir = APP_PATH . "/templates_c";
$templates = Misc::getFileList($compile_dir);
foreach ($templates as $filename) {
    unlink($compile_dir . '/' . $filename);
}

?>
done
