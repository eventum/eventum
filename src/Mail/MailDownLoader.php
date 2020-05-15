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
use Eventum\Mail\Exception\InvalidMessageException;
use Generator;

class MailDownLoader
{
    use LoggerTrait;

    /** @var resource */
    private $mbox;
    /** @var array */
    private $options;
    /** @var bool */
    private $onlyNewMails;

    public function __construct($mbox, array $options)
    {
        $this->mbox = $mbox;
        $this->options = $options;
        $this->onlyNewMails = $options['ema_get_only_new'];
    }

    public function getMails(): Generator
    {
        foreach ($this->getMessageIndexes() as $i) {
            $mail = $this->createMail($i);
            if (!$mail) {
                continue;
            }

            yield $mail;
        }
    }

    private function getMessageIndexes(): Generator
    {
        if ($this->onlyNewMails) {
            $emails = $this->getNewEmails();
            if (!is_array($emails)) {
                return;
            }

            yield from new ArrayIterator($emails);
        } else {
            $total_emails = $this->getTotalEmails();
            if ($total_emails <= 0) {
                return;
            }

            for ($i = 1; $i <= $total_emails; $i++) {
                yield $i;
            }
        }
    }

    private function createMail(int $i): ?ImapMessage
    {
        try {
            $this->debug('Create mail', ['num' => $i]);

            return ImapMessage::createFromImap($this->mbox, $i, $this->options);
        } catch (InvalidMessageException $e) {
            $this->error($e->getMessage(), ['num' => $i, 'e' => $e]);

            return null;
        }
    }

    /**
     * Method used to get new emails from the mailbox.
     *
     * @param resource $mbox The mailbox
     * @return array array of new message numbers
     */
    private function getNewEmails()
    {
        return imap_search($this->mbox, 'UNSEEN UNDELETED UNANSWERED');
    }

    /**
     * Method used to get the total number of emails in the specified
     * mailbox.
     *
     * @return  int The number of emails
     */
    private function getTotalEmails()
    {
        return @imap_num_msg($this->mbox);
    }
}
