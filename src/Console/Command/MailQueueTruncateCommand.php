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

use Mail_Queue;
use Symfony\Component\Console\Output\OutputInterface;

class MailQueueTruncateCommand
{
    const DEFAULT_COMMAND = 'mail-queue:truncate';
    const USAGE = self::DEFAULT_COMMAND . ' [-q|--quiet] [--interval=]';

    public function execute(OutputInterface $output, $quiet, $interval = '1 month')
    {
        Mail_Queue::truncate($interval);

        if (!$quiet) {
            $message = ev_gettext('Mail queue truncated by %1$s.', $interval);
            $output->writeln("<info>$message</info>");
        }
    }
}
