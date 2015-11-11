<?php

/*
 * Add minimum role to history table and update old history entries
 * https://github.com/eventum/eventum/pull/88
 */

/** @var DbInterface $db */

$db->query('alter table {{%issue_history}} add `his_min_role` tinyint(1) NOT NULL DEFAULT ?', array(User::ROLE_VIEWER));

$res = $db->getAll('select htt_id, htt_role from {{%history_type}}');

foreach ($res as $idx => $row) {
    $params = array($row['htt_role'], $row['htt_id']);
    $db->query('UPDATE {{%issue_history}} SET his_min_role=? WHERE his_htt_id=?', $params);
}
