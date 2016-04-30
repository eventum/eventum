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
use Zend\Mail\Headers;

/*
 * Update mail queue table by adding Message-ID column
 */

/** @var Closure $log */
/** @var AdapterInterface $db */

$process_messages = function () use ($db, $log) {
    $logger = Logger::getInstance('db');

    // TODO: process only status pending?
    $maq_ids = $db->getColumn(
        'SELECT maq_id FROM {{%mail_queue}} WHERE maq_message_id IS NULL'
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

        $maq_headers = $db->getOne('SELECT maq_headers FROM {{%mail_queue}} WHERE maq_id=?', [$maq_id]);

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
            "updated maq_id={$maq_id}", ['maq_id' => $maq_id, 'message_id' => $message_id]
        );

        $db->query('UPDATE {{%mail_queue}} SET maq_message_id=? WHERE maq_id=?', [$message_id, $maq_id]);
        $changed++;

        if ($current % 5000 == 0) {
            $p = round($current / $total * 100, 2);
            $log("... updated $current rows, $p%");
        }
    }

    $logger->info("Updated $changed out of $total entries");
};

$db->query('ALTER TABLE {{%mail_queue}} ADD maq_message_id VARCHAR(255) DEFAULT NULL AFTER maq_subject');

// Lock mail queue table for the patch run
// as the patch is likely deployed with new code,
// and new code will not work ok if message_id field is not yet filled properly
$db->query('LOCK TABLES {{%mail_queue}} WRITE');
$process_messages();
$db->query('UNLOCK TABLES');
