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

namespace Eventum\Console\Command;

use Eventum\ConcurrentLock;
use Mail_Queue;

class MailQueueProcessCommand
{
    const DEFAULT_COMMAND = 'mail-queue:process';
    const USAGE = self::DEFAULT_COMMAND;

    /** @var string */
    private $lock_name = 'process_mail_queue';

    public function execute()
    {
        $lock = new ConcurrentLock($this->lock_name);
        $lock->synchronized(
            function () {
                $this->processMailQueue();
            }
        );
    }

    private function processMailQueue()
    {
        // handle only pending emails
        $limit = 50;
        Mail_Queue::send(Mail_Queue::STATUS_PENDING, $limit);

        // handle emails that we tried to send before, but an error happened...
        $limit = 50;
        Mail_Queue::send(Mail_Queue::STATUS_ERROR, $limit);
    }
}
