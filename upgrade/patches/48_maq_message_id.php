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

$db->query("ALTER TABLE {{%mail_queue}} ADD maq_message_id VARCHAR(255) DEFAULT NULL AFTER maq_subject");

$maq_ids = $db->getColumn(
    // TODO: process only status pending?
    "SELECT maq_id FROM {{%mail_queue}} WHERE maq_message_id IS NULL"
);

$total = count($maq_ids);
$current = $changed = 0;

if (!$total) {
    // nothing to do
    return;
}

$log("Total $total rows, this may take time. Please be patient.");
foreach ($maq_ids as $maq_id) {
    $current++;

    $maq_headers = $db->getOne("SELECT maq_headers FROM {{%mail_queue}} WHERE maq_id=?", array($maq_id));

    try {
        $headers = Headers::fromString($maq_headers);
    } catch (Exception $e) {
        $logger->info(
            "skipped maq_id={$maq_id}, exception: {$e->getMessage()}"
        );
        continue;
    }
    $message_id = $headers->get('Message-Id');
    if (!$message_id) {
        $logger->info(
            "skipped maq_id={$maq_id}, no message-id header"
        );
        continue;
    }
    $message_id = $message_id->getFieldValue();

    $logger->info(
        "updated maq_id={$maq_id}", array('maq_id' => $maq_id, 'message_id' => $message_id)
    );

    $db->query('UPDATE {{%mail_queue}} SET maq_message_id=? WHERE maq_id=?', array($message_id, $maq_id));
    $changed++;

    if ($current % 5000 == 0) {
        $p = round($current / $total * 100, 2);
        $log("... updated $current rows, $p%");
    }
}

$count = count($res);
$logger->info("Updated $changed out of $count entries");
