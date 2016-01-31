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
use Lock;
use Project;
use Support;

class DownloadEmailsCommand extends Command
{
    protected function execute()
    {
        $config = $this->getParams();

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

        // check for the required parameters
        if (!$config['fix-lock'] && (empty($config['username']) || empty($config['hostname']))) {
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

        // get the account ID early since we need it also for unlocking.
        $account_id = Email_Account::getAccountID(
            $config['username'], $config['hostname'], $config['mailbox']
        );
        if (!$account_id && !$config['fix-lock']) {
            $this->fatal(
                'Could not find a email account with the parameter provided.',
                'Please verify your email account settings and try again.'
            );
        }

        if ($config['fix-lock']) {
            // if there is no account id, unlock all accounts
            if (!$account_id) {
                $prj_ids = array_keys(Project::getAll());
                foreach ($prj_ids as $prj_id) {
                    $ema_ids = Email_Account::getAssocList($prj_id);
                    foreach ($ema_ids as $ema_id => $ema_title) {
                        $lockfile = 'download_emails_' . $ema_id;
                        if (Lock::release($lockfile)) {
                            $this->msg("Removed lock file '$lockfile'.");
                        }
                    }
                }
            } else {
                $lockfile = 'download_emails_' . $account_id;
                if (Lock::release($lockfile)) {
                    $this->msg("Removed lock file '$lockfile'.");
                }
            }
            exit(0);
        }

        // check if there is another instance of this script already running
        if (!Lock::acquire('download_emails_' . $account_id)) {
            if ($this->SAPI_CLI) {
                $this->fatal(
                    'Another instance of the script is still running for the specified account.',
                    "If this is not accurate, you may fix it by running this script with '--fix-lock'",
                    "as the 4th parameter or you may unlock ALL accounts by running this script with '--fix-lock'",
                    'as the only parameter.'
                );
            } else {
                $this->fatal(
                    'Another instance of the script is still running for the specified account. ',
                    "If this is not accurate, you may fix it by running this script with 'fix-lock=1'",
                    "in the query string or you may unlock ALL accounts by running this script with 'fix-lock=1'",
                    'as the only parameter.'
                );
            }
            exit;
        }

        // clear the lock in all cases of termination
        function cleanup_lock()
        {
            global $account_id;
            Lock::release('download_emails_' . $account_id);
        }

        register_shutdown_function('cleanup_lock');

        $account = Email_Account::getDetails($account_id);
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

        Support::closeEmailServer($mbox);
        Support::clearErrors();
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
        $config = array(
            'fix-lock' => false,
            'username' => null,
            'hostname' => null,
            'mailbox' => null,
        );

        if ($this->SAPI_CLI) {
            global $argc, $argv;
            // --fix-lock may be only the last argument (first or fourth)
            if ($argv[$argc - 1] == '--fix-lock') {
                // no other args are allowed
                $config['fix-lock'] = true;
            }

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
