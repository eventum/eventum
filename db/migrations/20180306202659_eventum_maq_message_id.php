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
use Eventum\Mail\MailMessage;
use Eventum\Monolog\Logger;
use Psr\Log\LoggerInterface;
use Zend\Mail\Headers;

class EventumMaqMessageId extends AbstractMigration
{
    /** @var LoggerInterface */
    private $logger;

    public function up()
    {
        $this->logger = Logger::getInstance('db');

        $maq_ids = $this->getQueueIds();
        $total = count($maq_ids);
        $current = $changed = 0;

        if (!$total) {
            // nothing to do
            $this->writeln("Total $total rows, nothing to do");

            return;
        }

        $this->writeln("Total $total rows, this may take time. Please be patient.");
        foreach ($maq_ids as $maqId) {
            $current++;

            try {
                $messageId = $this->getMessageId($maqId);
            } catch (Exception $e) {
                $this->logger->info(
                    "skipped maq_id={$maqId}, exception: {$e->getMessage()}"
                );

                continue;
            }

            if (!$messageId) {
                continue;
            }

            $this->setMessageId($maqId, $messageId);
            $this->logger->info(
                "updated maq_id={$maqId}", ['maq_id' => $maqId, 'message_id' => $messageId]
            );
            $changed++;

            if ($current % 5000 == 0) {
                $p = round($current / $total * 100, 2);
                $this->writeln("... updated $current rows, $p%");
            }
        }

        $this->logger->info("Updated $changed out of $total entries");
    }

    private function getMessageId($maqId)
    {
        $textHeaders = $this->getHeaders($maqId);

        try {
            $headers = Headers::fromString($textHeaders);
            $messageId = $headers->get('Message-Id');
            if ($messageId) {
                return $messageId->getFieldValue();
            }
        } catch (Exception $e) {
            // will fallback and retry with MailMessage
            $this->logger->debug($e->getMessage());
        }

        // Message-Id header missing, load whole email, and let SanitizeHeaders build it
        $body = $this->getBody($maqId);

        return MailMessage::createFromHeaderBody($textHeaders, $body)->messageId;
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

    private function getBody($maq_id)
    {
        $sql = "SELECT maq_body FROM `mail_queue` WHERE maq_id=$maq_id";

        $rows = $this->queryColumn($sql, 'maq_body');

        return $rows[0];
    }

    private function setMessageId($maq_id, $messageId)
    {
        // NOTE: no method to quote from phinx,
        // but $messageId should be sql safe after it came from Zend\Mail
        $this->query("UPDATE `mail_queue` SET maq_message_id='{$messageId}' WHERE maq_id={$maq_id}");
    }
}
