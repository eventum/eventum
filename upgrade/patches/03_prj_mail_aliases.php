<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

/** @var Closure $log */

$res = $db->getOne("SELECT count(*) as count FROM INFORMATION_SCHEMA.COLUMNS WHERE
            TABLE_NAME = '{$dbconfig['table_prefix']}project' AND
            TABLE_SCHEMA = '{$dbconfig['database']}' AND
            COLUMN_NAME = 'prj_mail_aliases'");

if ($res != 1) {
    $db->query('ALTER TABLE {{%project}} ADD COLUMN prj_mail_aliases varchar(255)');
}
