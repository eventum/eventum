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

use Lock;

abstract class Command
{
    protected $lock_name;

    protected $SAPI_CLI;

    protected function configure()
    {
    }

    protected function execute()
    {
    }

    public function run()
    {
        // setup constant to be used globally
        $this->SAPI_CLI = 'cli' == php_sapi_name();

        $this->configure();

        if ($this->lock_name) {
            $this->lock($this->lock_name);
        }

        $this->execute();

        if ($this->lock_name) {
            $this->unlock($this->lock_name);
        }
    }

    /**
     * acquire a lock to prevent multiple scripts from running at the same time.
     */
    protected function lock($lockname)
    {
        global $argv;

        // if requested, clear the lock
        if (in_array('--fix-lock', $argv)) {
            if (Lock::release($lockname)) {
                $this->msg("Removed lock file '$lockname'.");
                exit(0);
            }
            exit(1);
        }

        $locked = Lock::acquire($lockname);

        if (!$locked) {
            // acquire a lock to prevent multiple scripts from
            // running at the same time
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
        }
    }

    public function unlock($lockname)
    {
        Lock::release($lockname);
    }

    /**
     * Display status message.
     *
     * Respects calling context:
     * - for CLI output is displayed to STDOUT,
     * - for Web newlines are converted to HTML linebreaks.
     */
    protected function msg()
    {
        $args = func_get_args();
        // let messages be newline terminated
        $args[] = '';
        $msg = implode("\n", $args);

        if ($this->SAPI_CLI) {
            fwrite(STDOUT, $msg);
        } else {
            $msg = nl2br($msg);
            echo $msg;
        }
    }

    /**
     * Display fatal error message and exit program.
     *
     * Respects calling context:
     * - for CLI output is displayed to STDERR,
     * - for Web newlines are converted to HTML linebreaks.
     */
    protected function fatal()
    {
        $args = func_get_args();
        // let messages be newline terminated
        $args[] = '';
        $msg = implode("\n", $args);

        if ($this->SAPI_CLI) {
            fwrite(STDERR, 'ERROR: ' . $msg);
        } else {
            $msg = '<b>ERROR</b>: ' . nl2br($msg);
            echo $msg;
        }

        exit(1);
    }
}
