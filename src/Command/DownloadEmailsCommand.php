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
use Eventum\Mail\ImapMessage;
use Support;

class DownloadEmailsCommand extends Command
{
    /** @var array */
    private $config;

    /** @var int */
    private $account_id;

    protected function configure()
    {
        // we need the IMAP extension for this to work
        if (!function_exists('imap_open')) {
            $this->fatal(
                'Eventum requires the IMAP extension in order to download messages saved on a IMAP/POP3 mailbox.',
                'See Prerequisites on the Wiki https://github.com/eventum/eventum/wiki/Prerequisites',
                'Please refer to the PHP manual for more details about how to install and enable the IMAP extension.'
            );
        }

        // argv/argc needs to be enabled in CLI mode
        if ($this->SAPI_CLI && ini_get('register_argc_argv') == 'Off') {
            $this->fatal(
                'Eventum requires the ini setting register_argc_argv to be enabled to run this script.',
                'Please refer to the PHP manual for more details on how to change this ini setting.'
            );
        }

        $config = $this->getParams();

        // check for the required parameters
        if (empty($config['username']) || empty($config['hostname'])) {
            if ($this->SAPI_CLI) {
                $this->fatal(
                    'Wrong number of parameters given. Expected parameters related to the email account:',
                    ' 1 - username',
                    ' 2 - hostname',
                    ' 3 - mailbox (only required if IMAP account)',
                    'Example: php download_emails.php user example.com INBOX'
                );
            } else {
                $this->fatal(
                    'Wrong number of parameters given. Expected parameters related to email account:',
                    'download_emails.php?username=<i>username</i>&hostname=<i>hostname</i>&mailbox=<i>mailbox</i>'
                );
            }
        }

        $this->config = $config;

        $this->account_id = $this->getAccountId();

        $this->lockname = 'download_emails_' . $this->account_id;
    }

    protected function execute()
    {
        $account = Email_Account::getDetails($this->account_id, true);
        $mbox = Support::connectEmailServer($account);
        if ($mbox == false) {
            $uri = Support::getServerURI($account);
            $login = $account['ema_username'];
            $error = imap_last_error();
            $this->fatal(
                "$error\n",
                "Could not connect to the email server '$uri' with login: '$login'.",
                'Please verify your email account settings and try again.'
            );
        }

        // if we only want new emails
        if ($account['ema_get_only_new']) {
            $emails = Support::getNewEmails($mbox);

            if (is_array($emails)) {
                foreach ($emails as $i) {
                    $mail = ImapMessage::createFromImap($mbox, $i, $account);
                    Support::processMailMessage($mail, $account);
                }
            }
        } else {
            $total_emails = Support::getTotalEmails($mbox);

            if ($total_emails > 0) {
                for ($i = 1; $i <= $total_emails; $i++) {
                    $mail = ImapMessage::createFromImap($mbox, $i, $account);
                    Support::processMailMessage($mail, $account);
                }
            }
        }

        Support::closeEmailServer($mbox);
        Support::clearErrors();
    }

    private function getAccountId()
    {
        $config = $this->config;

        // get the account ID early since we need it also for unlocking.
        $account_id = Email_Account::getAccountID(
            $config['username'], $config['hostname'], $config['mailbox']
        );
        if (!$account_id) {
            $this->fatal(
                'Could not find a email account with the parameter provided.',
                'Please verify your email account settings and try again.'
            );
        }

        return $account_id;
    }

    /**
     * Get parameters needed for this script.
     *
     * for CLI mode these are take from command line arguments
     * for Web mode those are taken as named _GET parameters.
     *
     * @return  array   $config
     */
    private function getParams()
    {
        // some defaults,
        $config = [
            'username' => null,
            'hostname' => null,
            'mailbox' => null,
        ];

        if ($this->SAPI_CLI) {
            global $argc, $argv;
            if ($argc > 1) {
                $config['username'] = $argv[1];
            }
            if ($argc > 2) {
                $config['hostname'] = $argv[2];
            }
            if ($argc > 3) {
                $config['mailbox'] = $argv[3];
            }
        } else {
            foreach (array_keys($config) as $key) {
                if (isset($_GET[$key])) {
                    $config[$key] = $_GET[$key];
                }
            }
        }

        return $config;
    }
}
