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

namespace Eventum\Mail;

use ArrayIterator;
use Eventum\Logger\LoggerTrait;
use Eventum\Mail\Imap\ImapConnection;
use Eventum\Mail\Imap\ImapResource;
use Generator;

class MailDownLoader
{
    use LoggerTrait;

    /** @var ImapConnection */
    private $connection;
    /** @var bool */
    private $onlyNewMails;

    public function __construct(ImapConnection $connection, array $options)
    {
        $this->connection = $connection;
        $this->onlyNewMails = $options['ema_get_only_new'];
    }

    /**
     * @return ImapResource[]|Generator
     */
    public function getMails(): Generator
    {
        foreach ($this->getMessageIndexes() as $i) {
            yield $this->connection->getMessage($i);
        }
    }

    private function getMessageIndexes(): Generator
    {
        if ($this->onlyNewMails) {
            $emails = $this->connection->getNewEmails();
            if (!is_array($emails)) {
                return;
            }

            yield from new ArrayIterator($emails);
        } else {
            $total_emails = $this->connection->getTotalEmails();
            if ($total_emails <= 0) {
                return;
            }

            for ($i = 1; $i <= $total_emails; $i++) {
                yield $i;
            }
        }
    }
}
