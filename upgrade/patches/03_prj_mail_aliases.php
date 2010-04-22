<?php
$res = db_getOne("SELECT count(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE
            TABLE_NAME = '%TABLE_PREFIX%project' AND
            TABLE_SCHEMA = '%DBNAME%' AND
            COLUMN_NAME = 'prj_mail_aliases'");
if (PEAR::isError($res)) {
    echo $res->getMessage(), ': ', $res->getDebugInfo(), "\n";
    exit(1);
}
if ($res != 1) {
    $queries[] = "ALTER TABLE %TABLE_PREFIX%project ADD COLUMN prj_mail_aliases varchar(255)";
}
