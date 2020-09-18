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

use Email_Account;
use Eventum\Mail\Imap\ImapConnection;
use Support;

class ExpungeEmails
{
    /** @var array ImapConnection */
    private $connections = [];

    public function expunge(array $res): void
    {
        foreach ($res as $row) {
            $connection = $this->getConnection($row['sup_ema_id']);

            // don't remove emails from the imap/pop3 server if the email
            // account is set to leave a copy of the messages on the server
            if (!$connection->getOptions()['ema_leave_copy']) {
                // now try to find the UID of the current message-id
                foreach ($connection->searchText($row['sup_message_id']) as $match) {
                    // if the current message also matches the message-id header, then remove it!
                    if ($match->imapheaders->message_id === $row['sup_message_id']) {
                        $connection->deleteMessage($match);
                    }
                }
            }

            // remove the email record from the table
            Support::removeEmail($row['sup_id']);
        }
    }

    private function getConnection(int $accountId): ImapConnection
    {
        if (!isset($this->connections[$accountId])) {
            $account = Email_Account::getDetails($accountId, true);
            $this->connections[$accountId] = new ImapConnection($account);
        }

        return $this->connections[$accountId];
    }
}
