<?php

global $dbconfig;

$res = db_getOne("SELECT count(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE
            TABLE_NAME = '{$dbconfig['table_prefix']}project' AND
            TABLE_SCHEMA = '{$dbconfig['database']}' AND
            COLUMN_NAME = 'prj_mail_aliases'");

if ($res != 1) {
    $queries[] = "ALTER TABLE {{%project}} ADD COLUMN prj_mail_aliases varchar(255)";
}
