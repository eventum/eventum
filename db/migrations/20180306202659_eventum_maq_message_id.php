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

use Eventum\Db\AbstractMigration;
use Eventum\Monolog\Logger;
use Zend\Mail\Headers;

class EventumMaqMessageId extends AbstractMigration
{
    public function up()
    {
        $logger = Logger::getInstance('db');

        $maq_ids = $this->getQueueIds();
        $total = count($maq_ids);
        $current = $changed = 0;

        if (!$total) {
            // nothing to do
            $this->writeln("Total $total rows, nothing to do");

            return;
        }

        $this->writeln("Total $total rows, this may take time. Please be patient.");
        foreach ($maq_ids as $maq_id) {
            $current++;

            $maq_headers = $this->getHeaders($maq_id);

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

            $this->setMessageId($maq_id, $message_id);
            $changed++;

            if ($current % 5000 == 0) {
                $p = round($current / $total * 100, 2);
                $this->writeln("... updated $current rows, $p%");
            }
        }

        $logger->info("Updated $changed out of $total entries");
    }

    private function getQueueIds()
    {
        // TODO: process only status pending?
        $sql = 'SELECT maq_id FROM `mail_queue` WHERE maq_message_id IS NULL';

        return $this->queryColumn($sql, 'maq_id');
    }

    private function getHeaders($maq_id)
    {
        $sql = "SELECT maq_headers FROM `mail_queue` WHERE maq_id=$maq_id";

        $rows = $this->queryColumn($sql, 'maq_headers');

        return $rows[0];
    }

    private function setMessageId($maq_id, $messageId)
    {
        // NOTE: no method to quote from phinx,
        // but $messageId should be sql safe after it came from Zend\Mail
        $this->query("UPDATE `mail_queue` SET maq_message_id='{$messageId}' WHERE maq_id={$maq_id}");
    }
}
