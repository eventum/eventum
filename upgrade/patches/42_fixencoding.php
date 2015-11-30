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

/*
 * Update database fields with fixEncoding instead doing that runtime
 */

/** @var DbInterface $db */

$logger = Logger::getInstance('db');

$res = $db->getAll(
    "SELECT sup_id,sup_subject,sup_from,sup_to,sup_cc FROM {{%support_email}} WHERE concat(sup_subject,sup_from,sup_to,sup_cc) LIKE '%=?%'"
);

$changed = 0;
foreach ($res as $idx => $row) {
    $params = array();
    foreach ($row as $k => $v) {
        $params[$k] = Mime_Helper::fixEncoding($v);
    }

    if ($row == $params) {
        $logger->warning("sup_id={$row['sup_id']} no changes", array('sup_id' => $row['sup_id'], 'old' => $row));
        continue;
    }
    $logger->info(
        "updated sup_id={$row['sup_id']}", array('sup_id' => $row['sup_id'], 'old' => $row, 'new' => $params)
    );

    $params[] = $row['sup_id'];
    $db->query('UPDATE {{%support_email}} SET ' . DB_Helper::buildSet($row) . ' WHERE sup_id=?', $params);
    $changed++;
}

$count = count($res);
$logger->info("Updated $changed out of $count entries");
