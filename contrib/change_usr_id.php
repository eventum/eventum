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
 * A script that changes user id to another one in whole database
 *
 * This can be used to merge users.
 *
 * Run the script, review output and execute it with MySQL CLI.
 */

use Eventum\Db\Adapter\AdapterInterface;

if (!isset($argv[2])) {
    throw new InvalidArgumentException(sprintf('Usage: %s source_usr_id target_usr_id', $argv[0]));
}

list($source_usr_id, $target_usr_id) = array_slice($argv, 1, 2);

require __DIR__ . '/../init.php';

echo "# change usr_id=$source_usr_id to usr_id=$target_usr_id\n";

/** @var AdapterInterface $db */
$db = DB_Helper::getInstance();

function replace(AdapterInterface $db, $table, $prefix, $source_usr_id, $target_usr_id)
{
    $column = ($prefix ? "${prefix}_" : '') . 'usr_id';
    $query = "select count(*) from `{$table}` where {$column}=?";
    $res = $db->getOne($query, [$source_usr_id]);
    if (!$res) {
        // no records, skip table
        return;
    }
    // check that target usr id is not in use
    $res = $db->getOne($query, [$target_usr_id]);
    if ($res) {
        echo "# WARNING: target usr_id=$target_usr_id in use in $table: $res records\n";
    }

    echo $db->getOne("select 'update `{$table}` set $column={$target_usr_id} where {$column}={$source_usr_id};\n'");
}

$tables = [
    'email_draft' => 'emd',
    'issue' => 'iss',
    'issue_attachment' => 'iat',
    'issue_history' => 'his',
    'issue_user' => 'isu',
    'issue_user_replier' => 'iur',
    'note' => 'not',
    'phone_support' => 'phs',
    //'project' => 'prj', // prj_lead_usr_id - dont need to change this time
    'project_user' => 'pru',
    'round_robin_user' => 'rru',
    'search_profile' => 'sep',
    'subscription' => 'sub',
    'time_tracking' => 'ttr',
    'user' => '', // change this note empty "prefix"
    'user_alias' => 'ual',
    'user_preference' => 'upr',
    'user_project_preference' => 'upp',
];

foreach ($tables as $table => $prefix) {
    replace($db, $table, $prefix, $source_usr_id, $target_usr_id);
}
