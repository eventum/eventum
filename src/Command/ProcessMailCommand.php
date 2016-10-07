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

use Eventum\Mail\Exception\RoutingException;
use Routing;

class ProcessMailCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function execute()
    {
        global $argv;

        // take input from first argument if specified
        // otherwise read from STDIN
        if (isset($argv[1])) {
            $full_message = file_get_contents($argv[1]);
        } else {
            $full_message = stream_get_contents(STDIN);
        }

        try {
            $return = Routing::route($full_message);
        } catch (RoutingException $e) {
            echo $e->getMessage();
            exit($e->getCode());
        }

        if ($return === false) {
            // message was not able to be routed
            echo 'no route';
            exit(RoutingException::EX_NOUSER);
        }

        /*
         * TODO: Save other emails
        // save this message in a special directory
        $path = "/home/eventum/bounced_emails/";
        list($usec,) = explode(" ", microtime());
        $filename = date('d-m-Y.H-i-s.') . $usec . '.email.txt';
        $fp = fopen($path . $filename, 'a+');
        fwrite($fp, $full_message);
        fclose($fp);
        chmod($path . $filename, 0777);
        */

        // this indicates the script ran successfully to postfix
        exit(0);
    }
}
