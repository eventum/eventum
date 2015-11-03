<?php
/** @var Closure $log */

$res = $db->getOne("SELECT count(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE
            TABLE_NAME = '{$dbconfig['table_prefix']}project' AND
            TABLE_SCHEMA = '{$dbconfig['database']}' AND
            COLUMN_NAME = 'prj_mail_aliases'");

if ($res != 1) {
    $db->query('ALTER TABLE {{%project}} ADD COLUMN prj_mail_aliases varchar(255)');
}
