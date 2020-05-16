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

namespace Eventum\Mail\Imap;

use RuntimeException;
use Support;

class ImapConnection
{
    /** @var array */
    private $account;
    /** @var resource */
    private $mbox;

    public function __construct(array $account)
    {
        if (!function_exists('imap_open')) {
            throw new RuntimeException(
                "Eventum requires the IMAP extension in order to download messages saved on a IMAP/POP3 mailbox.\n" .
                "See Prerequisites on the Wiki https://github.com/eventum/eventum/wiki/Prerequisites\n" .
                'Please refer to the PHP manual for more details about how to install and enable the IMAP extension.'
            );
        }

        $this->account = $account;
        $this->mbox = $this->getConnection();
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    public function getMessage(int $num): ImapResource
    {
        [$overview] = imap_fetch_overview($this->mbox, $num);

        $message = new ImapResource();
        $message->mbox = $this->mbox;
        $message->num = $num;
        $message->info = $this->account;
        $message->overview = $overview;
        $message->headers = imap_fetchheader($this->mbox, $num);
        $message->content = imap_body($this->mbox, $num);
        $message->imapheaders = imap_headerinfo($this->mbox, $num);

        return $message;
    }

    /**
     * Method used to get new emails from the mailbox.
     *
     * @return array array of new message numbers
     */
    public function getNewEmails(): array
    {
        return imap_search($this->mbox, 'UNSEEN UNDELETED UNANSWERED') ?: [];
    }

    /**
     * Method used to get the total number of emails in the specified
     * mailbox.
     *
     * @return  int The number of emails
     */
    public function getTotalEmails(): int
    {
        return imap_num_msg($this->mbox);
    }

    /**
     * Get IMAP connection handle
     *
     * @throws RuntimeException
     * @return resource
     */
    private function getConnection()
    {
        $mbox = Support::connectEmailServer($this->account);
        if ($mbox === false) {
            $uri = Support::getServerURI($this->account);
            $login = $this->account['ema_username'];
            $error = imap_last_error();

            throw new RuntimeException(
                "$error\n" .
                "Could not connect to the email server '$uri' with login: '$login'." .
                'Please verify your email account settings and try again.'
            );
        }

        return $mbox;
    }

    private function closeConnection(): void
    {
        $this->closeEmailServer();
        $this->clearErrors();
    }

    /**
     * Method used to close the existing connection to the email
     * server.
     */
    private function closeEmailServer(): void
    {
        imap_expunge($this->mbox);
        imap_close($this->mbox);
    }

    /**
     * Method used to clear the error stack as required by the IMAP PHP extension.
     */
    private function clearErrors(): void
    {
        imap_errors();
    }
}
