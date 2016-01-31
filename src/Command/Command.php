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

use Setup;
use Status;

class Command
{
    protected $SAPI_CLI;
    
    public function configure()
    {
    }

    public function run()
    {
        // setup constant to be used globally
        $this->SAPI_CLI = 'cli' == php_sapi_name();

        $this->configure();
        $this->execute();
    }

    /**
     * Display status message.
     *
     * Respects calling context:
     * - for CLI output is displayed to STDOUT,
     * - for Web newlines are converted to HTML linebreaks.
     */
    public function msg()
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
    public function fatal()
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
