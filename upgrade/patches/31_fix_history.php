<?php

/**
 * Fix bad history keyword (which was fixed in 3e95aa4)
 */
/** @var DbInterface $db */
/** @var Closure $log */

$res = $db->getAll("select his_id,his_context from {{%issue_history}} where his_summary='Note routed from {user}' and his_context like '%from%:%'");

foreach ($res as $idx => $row) {
    $context = json_decode($row['his_context'], 1);
    $context['user'] = $context['from'];
    unset($context['from']);

    $context = json_encode($context);
    $params = array($context, $row['his_id']);
    $db->query('UPDATE {{%issue_history}} SET his_context=? WHERE his_id=?', $params);
}
$count = count($res);
$log("Updated $count entries");
