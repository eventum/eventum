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

use Monitor;
use Setup;
use Symfony\Component\Console\Output\OutputInterface;

class MonitorCommand
{
    // Nagios compatible exit codes
    const STATE_OK = 0;
    const STATE_WARNING = 1;
    const STATE_CRITICAL = 2;
    const STATE_UNKNOWN = 3;
    const STATE_DEPENDENT = 4;

    public function execute($quiet, OutputInterface $output)
    {
        // the owner, group and filesize settings should be changed to match the correct permissions on your server.
        $required_files = [
            APP_CONFIG_PATH . '/config.php' => [
                'check_owner' => true,
                'owner' => 'apache',
                'check_group' => true,
                'group' => 'apache',
                'check_permission' => true,
                'permission' => 640,
            ],
            APP_CONFIG_PATH . '/setup.php' => [
                'check_owner' => true,
                'owner' => 'apache',
                'check_group' => true,
                'group' => 'apache',
                'check_permission' => true,
                'permission' => 660,
                'check_filesize' => true,
                'filesize' => 1024,
            ],
        ];

        $required_directories = [
            APP_PATH . '/misc/routed_emails' => [
                'check_permission' => true,
                'permission' => 770,
            ],
            APP_PATH . '/misc/routed_notes' => [
                'check_permission' => true,
                'permission' => 770,
            ],
        ];

        $errors = 0;
        // load prefs
        $setup = Setup::get();
        $prefs = $setup['monitor'];

        $errors += Monitor::checkDatabase();
        $errors += Monitor::checkMailQueue();
        $errors += Monitor::checkMailAssociation();

        if ($prefs['diskcheck']['status'] == 'enabled') {
            $errors += Monitor::checkDiskspace($prefs['diskcheck']['partition']);
        }
        if ($prefs['paths']['status'] == 'enabled') {
            $errors += Monitor::checkRequiredFiles($required_files);
            $errors += Monitor::checkRequiredDirs($required_directories);
        }

        if ($errors) {
            // propagate status code to shell
            return self::STATE_CRITICAL;
        }

        if (!$quiet) {
            $output->writeln(ev_gettext('OK: No errors found'));
        }

        return self::STATE_OK;
    }
}
