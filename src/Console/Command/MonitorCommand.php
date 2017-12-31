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

use DB_Helper;
use Eventum\Db;
use Eventum\Db\DatabaseException;
use Exception;
use Setup;
use Symfony\Component\Console\Output\OutputInterface;

class MonitorCommand
{
    const DEFAULT_COMMAND = 'system:monitor';
    const USAGE = self::DEFAULT_COMMAND . ' [-q|--quiet]';

    // Nagios compatible exit codes
    const STATE_OK = 0;
    const STATE_WARNING = 1;
    const STATE_CRITICAL = 2;
    const STATE_UNKNOWN = 3;
    const STATE_DEPENDENT = 4;

    /** @var OutputInterface */
    private $output;

    /** @var int */
    private $errors = 0;

    public function execute(OutputInterface $output, $quiet)
    {
        $this->output = $output;

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

        // load prefs
        $setup = Setup::get();
        $prefs = $setup['monitor'];

        $this->checkDatabase();
        $this->checkMailQueue();
        $this->checkMailAssociation();

        if ($prefs['diskcheck']['status'] === 'enabled') {
            $this->checkDiskspace($prefs['diskcheck']['partition']);
        }
        if ($prefs['paths']['status'] === 'enabled') {
            $this->checkRequiredFiles($required_files);
            $this->checkRequiredDirs($required_directories);
        }

        if ($this->errors) {
            // propagate status code to shell
            return self::STATE_CRITICAL;
        }

        if (!$quiet) {
            $output->writeln(ev_gettext('OK: No errors found'));
        }

        return self::STATE_OK;
    }

    /**
     * Checks on the status of the MySQL database.
     *
     * @throws DatabaseException
     * @throws Exception
     */
    protected function checkDatabase()
    {
        $required_tables = Db\Table::getTableList();

        // check if all of the required tables are really there
        $table_list = DB_Helper::getInstance()->getColumn('SHOW TABLES');
        foreach ($required_tables as $table) {
            if (!in_array($table, $table_list, true)) {
                $this->error(ev_gettext('ERROR: Could not find required table "%s"', $table));
            }
        }
    }

    /**
     * Checks the mail queue logs for any email that wasn't delivered.
     */
    protected function checkMailQueue()
    {
        $stmt
            = "SELECT
                    maq_id
                 FROM
                    `mail_queue`,
                    `mail_queue_log`
                 WHERE
                    maq_status='error' AND
                    maq_id=mql_maq_id
                 GROUP BY
                    maq_id";
        try {
            $queue_ids = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DatabaseException $e) {
            $this->error(ev_gettext('ERROR: There was a DB error checking the mail queue status'));

            return;
        }

        $errors = count($queue_ids);
        if ($errors) {
            $this->error(ev_gettext('ERROR: There is a total of %d queued emails with errors.', $errors));
        }
    }

    /**
     * Checks the associated emails page (emails.php) that there aren't any unassociated mails
     *
     * @see \Support::getEmailListing()
     */
    protected function checkMailAssociation()
    {
        // TODO: optimize this
        // TODO: should we check it per project?
        $stmt
            = 'SELECT
                    COUNT(*)
                 FROM
                    (
                    `support_email`,
                    `email_account`
                    )
                    LEFT JOIN
                        `issue`
                    ON
                        sup_iss_id = iss_id
                    WHERE sup_removed=0 AND sup_ema_id=ema_id AND sup_iss_id = 0
        ';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt);
        } catch (DatabaseException $e) {
            $this->error(ev_gettext('ERROR: There was a DB error checking the mail association status'));

            return;
        }

        if ($res > 0) {
            $this->error(ev_gettext('ERROR: There is a total of %d emails not associated.', $res));
        }
    }

    /**
     * Checks the free disk space status on the server.
     */
    protected function checkDiskspace($partition, $low_limit = 5, $high_limit = 15)
    {
        $total_space = disk_total_space($partition);
        $free_space = disk_free_space($partition);
        $free_percentage = ($free_space * 100) / $total_space;
        if ($free_percentage < $low_limit) {
            $this->error(
                ev_gettext('ERROR: Almost no free disk space left (percentage left: %.2f%%)', $free_percentage)
            );

            return;
        }
        if ($free_percentage < $high_limit) {
            $this->error(
                ev_gettext(
                    'ERROR: Free disk space left is getting very low (percentage left: %.2f%%)', $free_percentage
                )
            );

            return;
        }
    }

    /**
     * Checks on the status of the required configuration and auxiliary files
     * and directories.
     *
     * @param   array $required_files an array of files that should be checked on
     */
    protected function checkRequiredFiles($required_files)
    {
        foreach ($required_files as $file_path => $options) {
            // check if file exists
            if (!file_exists($file_path)) {
                $this->error(ev_gettext('ERROR: File could not be found (path: %s)', $file_path));
                continue;
            }
            // check the owner and group for these files
            list($owner, $group) = self::getOwnerAndGroup($file_path);
            if (!empty($options['check_owner']) && $options['owner'] != $owner) {
                $message = ev_gettext(
                    'ERROR: File owner mismatch (path: %1$s; current owner: %2$s; correct owner: %3$s)', $file_path,
                    $owner, $options['owner']
                );
                $this->error($message);
            }
            if (!empty($options['check_group']) && $options['group'] != $group) {
                $message = ev_gettext(
                    'ERROR: File group mismatch (path: %1$s; current group: %2$s; correct group: %3$s)', $file_path,
                    $group, $options['group']
                );
                $this->error($message);
            }
            // check permission bits
            $perm = self::getOctalPerms($file_path);
            if (!empty($options['check_permission']) && $options['permission'] != $perm) {
                $message = ev_gettext(
                    'ERROR: File permission mismatch (path: %1$s; current perm: %2$s; correct perm: %3$s)', $file_path,
                    $perm, $options['permission']
                );
                $this->error($message);
            }

            // check filesize
            if (!empty($options['check_filesize']) && filesize($file_path) < $options['filesize']) {
                $message = ev_gettext(
                    'ERROR: File size mismatch (path: %1$s; current filesize: %2$s)', $file_path, filesize($file_path)
                );
                $this->error($message);
            }
        }
    }

    /**
     * Checks on the status of the required directories.
     *
     * @param   array $required_directories an array of files that should be checked on
     */
    protected function checkRequiredDirs($required_directories)
    {
        foreach ($required_directories as $dir_path => $options) {
            // check if directory exists
            if (!file_exists($dir_path)) {
                $this->error(ev_gettext('ERROR: Directory could not be found (path: %1$s)', $dir_path));
                continue;
            }
            // check permission bits
            $perm = self::getOctalPerms($dir_path);
            if ((@$options['check_permission']) && ($options['permission'] != $perm)) {
                $this->error(
                    ev_gettext(
                        'ERROR: Directory permission mismatch (path: %1$s; current perm: %2$s; correct perm: %3$s)',
                        $dir_path, $perm, $options['permission']
                    )
                );
            }
        }
    }

    /**
     * Print out $message and increase error count.
     *
     * @param string $message
     */
    private function error($message)
    {
        $this->output->writeln("<error>$message</error>");
        $this->errors++;
    }

    /**
     * Returns the owner and group name for the given file.
     *
     * @param   string $file The full path to the file
     * @return  array The owner and group name associated with that file
     */
    private function getOwnerAndGroup($file)
    {
        $owner_info = posix_getpwuid(fileowner($file));
        $group_info = posix_getgrgid(filegroup($file));

        return [
            $owner_info['name'],
            $group_info['name'],
        ];
    }

    /**
     * Returns the octal permission string for a given file.
     *
     * @param   string $file The full path to the file
     * @return  string The octal permission string
     */
    private function getOctalPerms($file)
    {
        return substr(sprintf('%o', fileperms($file)), -3);
    }
}
