#!/usr/bin/php
<?php
/**
 * A script that changes user id to another one in whole database
 *
 * This can be used to merge users.
 *
 * Run the script, review output and execute it with MySQL CLI.
 */

use Eventum\Db\Adapter\AdapterInterface;

if (!isset($argv[2])) {
    throw new InvalidArgumentException('Invalid usage');
}

list($source_usr_id, $target_usr_id) = array_slice($argv, 1, 2);

require __DIR__ . '/../init.php';

echo "# change usr_id=$source_usr_id to usr_id=$target_usr_id\n";

/** @var AdapterInterface $db */
$db = DB_Helper::getInstance();

function replace(AdapterInterface $db, $table, $prefix, $source_usr_id, $target_usr_id)
{
    $column = ($prefix ? "${prefix}_" : '') . 'usr_id';
    $res = $db->getOne("select count(*) from {{%{$table}}} where {$column}=?", array($source_usr_id));
    if (!$res) {
        // no records, skip table
        return;
    }
    echo $db->getOne("select 'update {{%{$table}}} set $column={$target_usr_id} where {$column}={$source_usr_id};\n'");
}

$tables = array(
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
);

foreach ($tables as $table => $prefix) {
    replace($db, $table, $prefix, $source_usr_id, $target_usr_id);
}
