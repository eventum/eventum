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

use Eventum\Mail\ImapMessage;
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

    public function __toString()
    {
        $connection = $this->account;

        return sprintf('%s[%s]/%s', $connection['ema_hostname'], $connection['ema_username'], $connection['ema_folder']);
    }

    public function __destruct()
    {
        $this->closeConnection();
    }

    public function getOptions(): array
    {
        return $this->account;
    }

    public function getMessage(int $num): ImapResource
    {
        return new ImapResource($this->mbox, $num);
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
     * Deletes the specified message from the IMAP/POP server
     * NOTE: YOU STILL MUST call imap_expunge($mbox) to permanently delete the message.
     */
    public function deleteMessage(ImapMessage $mail): void
    {
        $index = $mail->num;
        // need to delete the message from the server?
        if (!$this->account['ema_leave_copy']) {
            imap_delete($this->mbox, $index);
        } else {
            // mark the message as already read
            imap_setflag_full($this->mbox, $index, '\\Seen');
        }
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
