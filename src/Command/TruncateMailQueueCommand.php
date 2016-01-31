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

namespace Eventum\Command;

use Mail_Queue;

class TruncateMailQueueCommand extends Command
{
    protected function configure()
    {
        $this->lock_name = 'truncate_mail_queue';
    }

    /**
     * @inheritdoc
     */
    protected function execute()
    {
        Mail_Queue::truncate();
    }
}
