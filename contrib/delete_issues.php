#!/usr/bin/php
<?php
/**
 * A script that produces SQL statements to permanently delete issue data
 *
 * Run the script, review output and execute it with MySQL CLI.
 */

use Eventum\Db\Adapter\AdapterInterface;

if (!isset($argv[1])) {
    throw new InvalidArgumentException('Invalid usage');
}

list($issue_id) = $argv[1];

require __DIR__ . '/../init.php';

echo "# delete issue $issue_id\n";

/** @var AdapterInterface $db */
$db = DB_Helper::getInstance();

function check_delete(AdapterInterface $db, $table, $column, $issue_id)
{
    $query = "select count(*) from {{%{$table}}} where {$column}=?";
    $res = $db->getOne($query, array($issue_id));
    if (!$res) {
        // no records, skip table
        return;
    }

    echo $db->getOne("select 'delete from {{%{$table}}} where {$column}={$issue_id};'"), "\n";
}

$tables = array(
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
);

foreach ($tables as $table => $prefix) {
    check_delete($db, $table, $prefix, $issue_id);
}
