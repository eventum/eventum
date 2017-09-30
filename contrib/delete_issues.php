#!/usr/bin/php
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

/**
 * A script that produces SQL statements to permanently delete issue data
 *
 * Run the script, review output and execute it with MySQL CLI.
 *
 * $ ./delete_issues.php 1 2 3 62 666
 * would try to delete issues 1, 2, 3, 62 and 666
 */

use Eventum\Db\Adapter\AdapterInterface;

$PROGRAM = array_shift($argv);
if (!$argv) {
    throw new InvalidArgumentException('Invalid usage');
}

require __DIR__ . '/../init.php';

function check_delete(AdapterInterface $db, $tables, $issue_id)
{
    $res = $db->getOne('SELECT iss_id FROM `issue` where iss_id=?', [$issue_id]);
    if (!$res) {
        echo "# issue $issue_id does not exist\n";

        return;
    }

    echo "# delete issue $issue_id\n";
    foreach ($tables as $table => $prefix) {
        check_delete_table($db, $table, $prefix, $issue_id);
    }
}

function check_delete_table(AdapterInterface $db, $table, $column, $issue_id)
{
    $query = "select count(*) from `{$table}` where {$column}=?";
    $res = $db->getOne($query, [$issue_id]);
    if (!$res) {
        // no records, skip table
        return;
    }

    echo $db->getOne("select 'delete from `{$table}` where {$column}={$issue_id};'"), "\n";
}

$tables = [
    'issue_association' => 'isa_issue_id',
    'issue_attachment' => 'iat_iss_id',
    'issue_checkin' => 'isc_iss_id',
    'issue_history' => 'his_iss_id',
    'issue_requirement' => 'isr_iss_id',
    'issue_user' => 'isu_iss_id',

    'note' => 'not_iss_id',
    'subscription' => 'sub_iss_id',
    'support_email' => 'sup_iss_id',
    'time_tracking' => 'ttr_iss_id',
    'issue_custom_field' => 'icf_iss_id',
    'phone_support' => 'phs_iss_id',
    'reminder_requirement' => 'rer_iss_id',
    'reminder_history' => 'rmh_iss_id',
    'email_draft' => 'emd_iss_id',
    'irc_notice' => 'ino_iss_id',
    'issue_user_replier' => 'iur_iss_id',
    'mail_queue' => 'maq_iss_id',

    'issue' => 'iss_id',
];

/** @var AdapterInterface $db */
$db = DB_Helper::getInstance();

foreach ($argv as $issue_id) {
    check_delete($db, $tables, $issue_id);
}
