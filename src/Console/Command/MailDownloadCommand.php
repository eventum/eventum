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

use Email_Account;
use Eventum\ConcurrentLock;
use Eventum\Mail\ImapMessage;
use InvalidArgumentException;
use RuntimeException;
use Support;

class MailDownloadCommand
{
    const DEFAULT_COMMAND = 'mail:download';
    const USAGE = self::DEFAULT_COMMAND . ' [username] [hostname] [mailbox] [--limit=] [--no-lock]';

    /**
     * Limit amount of emails to process.
     * Default unlimited: 0
     *
     * @var int
     */
    private $limit = 0;

    /**
     * @param string $username
     * @param string $hostname
     * @param string $mailbox
     */
    public function execute($username, $hostname, $mailbox, $limit = 0, $noLock = false)
    {
        $account_id = $this->getAccountId($username, $hostname, $mailbox);
        $this->limit = $limit;

        if (!$noLock) {
            $lock = new ConcurrentLock('download_emails_' . $account_id);
            $lock->synchronized(
                function () use ($account_id) {
                    $this->processEmails($account_id);
                }
            );
        } else {
            $this->processEmails($account_id);
        }
    }

    /**
     * @param int $account_id
     */
    private function processEmails($account_id)
    {
        $account = Email_Account::getDetails($account_id, true);
        $mbox = $this->getConnection($account);
        $limit = 0;

        // if we only want new emails
        if ($account['ema_get_only_new']) {
            $emails = Support::getNewEmails($mbox);

            if (is_array($emails)) {
                foreach ($emails as $i) {
                    $mail = ImapMessage::createFromImap($mbox, $i, $account);
                    Support::processMailMessage($mail, $account);
                    if ($this->limit && $limit++ > $this->limit) {
                        break;
                    }
                }
            }
        } else {
            $total_emails = Support::getTotalEmails($mbox);

            if ($total_emails > 0) {
                for ($i = 1; $i <= $total_emails; $i++) {
                    $mail = ImapMessage::createFromImap($mbox, $i, $account);
                    Support::processMailMessage($mail, $account);
                    if ($this->limit && ++$limit >= $this->limit) {
                        break;
                    }
                }
            }
        }

        $this->closeConnection($mbox);
    }

    /**
     * Get email account id from parameters
     *
     * @param string $username
     * @param string $hostname
     * @param string $mailbox
     * @throws InvalidArgumentException
     * @return int
     */
    private function getAccountId($username, $hostname, $mailbox)
    {
        // get the account ID early since we need it also for unlocking.
        $account_id = Email_Account::getAccountID($username, $hostname, $mailbox);

        if (!$account_id) {
            throw new InvalidArgumentException(
                "Could not find a email account with the parameter provided.\n" .
                'Please verify your email account settings and try again.'
            );
        }

        return $account_id;
    }

    /**
     * Get IMAP connection handle
     *
     * @param array $account
     * @throws RuntimeException
     * @return resource
     */
    private function getConnection($account)
    {
        if (!function_exists('imap_open')) {
            throw new RuntimeException(
                "Eventum requires the IMAP extension in order to download messages saved on a IMAP/POP3 mailbox.\n" .
                "See Prerequisites on the Wiki https://github.com/eventum/eventum/wiki/Prerequisites\n" .
                'Please refer to the PHP manual for more details about how to install and enable the IMAP extension.'
            );
        }

        $mbox = Support::connectEmailServer($account);
        if ($mbox === false) {
            $uri = Support::getServerURI($account);
            $login = $account['ema_username'];
            $error = imap_last_error();

            throw new RuntimeException(
                "$error\n" .
                "Could not connect to the email server '$uri' with login: '$login'." .
                'Please verify your email account settings and try again.'

            );
        }

        return $mbox;
    }

    /**
     * @param resource $mbox
     */
    private function closeConnection($mbox)
    {
        $this->closeEmailServer($mbox);
        $this->clearErrors();
    }

    /**
     * Method used to close the existing connection to the email
     * server.
     *
     * @param   resource $mbox The mailbox
     */
    private function closeEmailServer($mbox)
    {
        imap_expunge($mbox);
        imap_close($mbox);
    }

    /**
     * Method used to clear the error stack as required by the IMAP PHP extension.
     */
    private function clearErrors()
    {
        imap_errors();
    }
}
