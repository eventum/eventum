<?php
/**
 * Set required fields to match old default configuration
 */

$db->query("UPDATE {{%project_field_display}} SET pfd_required = 1 WHERE pfd_field IN('category', 'priority', 'severity')");

$setup = Setup::load();

if (isset($setup['allow_unassigned_issues']) && $setup['allow_unassigned_issues'] != 'yes') {
    $db->query("UPDATE {{%project_field_display}} SET pfd_required = 1 WHERE pfd_field = 'assignment'");
}



