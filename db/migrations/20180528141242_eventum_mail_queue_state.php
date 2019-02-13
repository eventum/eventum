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

class EventumMailQueueState extends AbstractMigration
{
    public function up(): void
    {
        foreach ($this->getEntries() as $maqId) {
            $count = $this->getErrorCount($maqId);
            if ($count > Mail_Queue::MAX_RETRIES) {
                $this->writeln("Update maq_id=$maqId, set state failed after $count retries");
                $this->setFailed($maqId);
            }
        }
    }

    private function setFailed($maqId)
    {
        $status = $this->quote(Mail_Queue::STATUS_FAILED);
        $sql = "UPDATE `mail_queue` SET maq_status=$status WHERE maq_id=$maqId";

        return $this->query($sql);
    }

    private function getErrorCount($maqId)
    {
        $sql = "SELECT COUNT(*) c FROM `mail_queue_log` WHERE mql_maq_id=$maqId";

        return $this->queryOne($sql, 'c');
    }

    private function getEntries()
    {
        $statuses = implode(', ', [
            $this->quote(Mail_Queue::STATUS_ERROR),
            $this->quote(Mail_Queue::STATUS_PENDING),
        ]);

        $sql = "SELECT maq_id FROM `mail_queue` WHERE maq_status in ($statuses)";

        return $this->queryColumn($sql, 'maq_id');
    }
}
