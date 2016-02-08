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
use Zend\Mail\Headers;

/*
 * Update mail queue table by adding Message-ID column
 */

/** @var Closure $log */
/** @var AdapterInterface $db */

$logger = Logger::getInstance('db');

$db->query("alter table {{%mail_queue}} add maq_message_id varchar(255) DEFAULT NULL AFTER maq_subject");

$res = $db->getAll(
    // TODO: process only status pending?
    "select maq_id,maq_headers from {{%mail_queue}} where maq_message_id is null"
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

    $headers = Headers::fromString($row['maq_headers']);
    $message_id = $headers->get('Message-Id');
    if (!$message_id) {
        var_dump($row['maq_headers']);
        $logger->info(
            "skipped maq_id={$row['maq_id']}, no message-id header"
        );
        continue;
    }
    $message_id = $message_id->getFieldValue();

    $logger->info(
        "updated maq_id={$row['maq_id']}", array('maq_id' => $row['maq_id'], 'message_id' => $message_id)
    );

    $db->query('UPDATE {{%mail_queue}} SET maq_message_id=? WHERE maq_id=?', array($message_id, $row['maq_id']));
    $changed++;

    if ($current % 5000 == 0) {
        $p = round($current / $total * 100, 2);
        $log("... updated $current rows, $p%");
    }
}

$count = count($res);
$logger->info("Updated $changed out of $count entries");
