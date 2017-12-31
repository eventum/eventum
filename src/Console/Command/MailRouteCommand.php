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

use Eventum\Mail\Exception\RoutingException;
use Eventum\Mail\MailMessage;
use Routing;
use Symfony\Component\Console\Output\OutputInterface;

class MailRouteCommand
{
    const DEFAULT_COMMAND = 'mail:route';
    const USAGE = self::DEFAULT_COMMAND . '  [filename]';

    /**
     * @param OutputInterface $output
     * @param string $filename optional filename to load
     * @return int Program exit code
     */
    public function execute(OutputInterface $output, $filename)
    {
        // take input from first argument if specified
        // otherwise read from STDIN
        if ($filename) {
            $full_message = file_get_contents($filename);
        } else {
            $full_message = stream_get_contents(STDIN);
        }

        Routing::removeMboxHeader($full_message);

        $mail = MailMessage::createFromString($full_message);

        try {
            $return = Routing::route($mail);
        } catch (RoutingException $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));

            return $e->getCode();
        }

        if ($return === false) {
            // message was not able to be routed
            $output->writeln('No route');

            return RoutingException::EX_NOUSER;
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
        return 0;
    }
}
