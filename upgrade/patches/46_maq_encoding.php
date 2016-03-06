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

use Eventum\Db\Adapter\AdapterInterface;
use Eventum\Monolog\Logger;

/*
 * Update mail queue table fields with fixEncoding instead doing that runtime
 */

/** @var Closure $log */
/** @var AdapterInterface $db */

$logger = Logger::getInstance('db');

$res = $db->getAll(
    "SELECT maq_id,maq_recipient,maq_subject FROM {{%mail_queue}} WHERE concat(maq_recipient,maq_subject) LIKE '%=?%'"
);

$total = count($res);
$current = $changed = 0;

if (!$total) {
    // nothing to do
    return;
}

$log("Total $total rows, this may take time. Please be patient.");
foreach ($res as $row) {
    $current++;

    $params = array();
    foreach ($row as $k => $v) {
        $params[$k] = Mime_Helper::decodeQuotedPrintable($v);
    }

    if ($row == $params) {
        $logger->warning("maq_id={$row['maq_id']} no changes", array('maq_id' => $row['maq_id'], 'old' => $row));
        continue;
    }

    $logger->info(
        "updated maq_id={$row['maq_id']}", array('maq_id' => $row['maq_id'], 'old' => $row, 'new' => $params)
    );

    $params[] = $row['maq_id'];
    $db->query('UPDATE {{%mail_queue}} SET ' . DB_Helper::buildSet($row) . ' WHERE maq_id=?', $params);
    $changed++;

    if ($current % 5000 == 0) {
        $p = round($current / $total * 100, 2);
        $log("... updated $current rows, $p%");
    }
}

$count = count($res);
$logger->info("Updated $changed out of $count entries");
