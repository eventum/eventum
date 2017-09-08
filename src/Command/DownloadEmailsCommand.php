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

use Email_Account;
use Eventum\ConcurrentLock;
use InvalidArgumentException;
use RuntimeException;
use Support;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadEmailsCommand
{
    const DEFAULT_COMMAND = 'email:download';
    const USAGE = self::DEFAULT_COMMAND . ' [username] [hostname] [mailbox]';

    /** @var OutputInterface */
    private $output;

    /** @var array */
    private $config;

    /** @var int */
    private $account_id;

    public function execute(OutputInterface $output, $username, $hostname, $mailbox)
    {
        $this->output = $output;
        $this->account_id = $this->getAccountId($username, $hostname, $mailbox);

        $lock = new ConcurrentLock('download_emails_' . $this->account_id);
        $lock->synchronized(
            function () {
                $this->processEmails();
            }
        );
    }

    private function processEmails()
    {
        $account = Email_Account::getDetails($this->account_id, true);
        $mbox = $this->getConnection($account);

        // if we only want new emails
        if ($account['ema_get_only_new']) {
            $new_emails = Support::getNewEmails($mbox);

            if (is_array($new_emails)) {
                foreach ($new_emails as $new_email) {
                    Support::getEmailInfo($mbox, $account, $new_email);
                }
            }
        } else {
            $total_emails = Support::getTotalEmails($mbox);

            if ($total_emails > 0) {
                for ($i = 1; $i <= $total_emails; $i++) {
                    Support::getEmailInfo($mbox, $account, $i);
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
        Support::closeEmailServer($mbox);
        Support::clearErrors();
    }
}
