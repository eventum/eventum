<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmts = array();

$stmts[] = "CREATE FULLTEXT INDEX ft_issue ON eventum_issue (iss_summary, iss_description)";
$stmts[] = "CREATE FULLTEXT INDEX ft_support_email ON eventum_support_email_body (seb_body)";
$stmts[] = "CREATE FULLTEXT INDEX ft_note ON eventum_note (not_title,not_note)";
$stmts[] = "CREATE FULLTEXT INDEX ft_time_tracking ON eventum_time_tracking (ttr_summary)";
$stmts[] = "CREATE FULLTEXT INDEX ft_phone_support ON eventum_phone_support (phs_description)";

foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($res)) {
        echo "<pre>";var_dump($res);echo "</pre>";
        exit(1);
    }
}

?>
done