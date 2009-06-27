<?php
// create database entries for all projects in the columns_to_display table
// so all projects have fields that show up on the list issue page.

require_once dirname(__FILE__) . '/../init.php';

$projects = Project::getAll();
foreach ($projects as $prj_id => $prj_title) {
    echo "Setting Display columns for $prj_title<br />";
    Display_Column::setupNewProject($prj_id);
}

?>
Done. You can now control which columns are displayed on the list issues page through the administration page.
