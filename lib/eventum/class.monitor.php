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

use Eventum\Db;
use Eventum\Db\DatabaseException;

class Monitor
{
    /**
     * Checks the mail queue logs for any email that wasn't delivered.
     *
     * @return  int number of errors encountered
     */
    public static function checkMailQueue()
    {
        $stmt = "SELECT
                    maq_id
                 FROM
                    {{%mail_queue}},
                    {{%mail_queue_log}}
                 WHERE
                    maq_status='error' AND
                    maq_id=mql_maq_id
                 GROUP BY
                    maq_id";
        try {
            $queue_ids = DB_Helper::getInstance()->getColumn($stmt);
        } catch (DatabaseException $e) {
            echo ev_gettext('ERROR: There was a DB error checking the mail queue status'), "\n";

            return;
        }
        $errors = count($queue_ids);
        if ($errors) {
            echo ev_gettext('ERROR: There is a total of %d queued emails with errors.', $errors), "\n";
        }

        return $errors;
    }

    /**
     * Checks the associated emails page (emails.php) that there aren't any unassociated mails
     *
     * @see class.support.php getEmailListing()
     * @return  int number of mails not associated
     */
    public static function checkMailAssociation()
    {
        // TODO: optimize this
        // TODO: should we check it per project?
        $stmt = 'SELECT
                    COUNT(*)
                 FROM
                    (
                    {{%support_email}},
                    {{%email_account}}
                    )
                    LEFT JOIN
                        {{%issue}}
                    ON
                        sup_iss_id = iss_id
                    WHERE sup_removed=0 AND sup_ema_id=ema_id AND sup_iss_id = 0
        ';
        try {
            $res = DB_Helper::getInstance()->getOne($stmt);
        } catch (DatabaseException $e) {
            return false;
        }

        if ($res > 0) {
            echo ev_gettext('ERROR: There is a total of %d emails not associated.', $res), "\n";
        }

        return $res;
    }

    /**
     * Checks the free disk space status on the server.
     *
     * @return  int number of errors encountered
     */
    public static function checkDiskspace($partition, $low_limit = 5, $high_limit = 15)
    {
        $total_space = disk_total_space($partition);
        $free_space = disk_free_space($partition);
        $free_percentage = ($free_space * 100) / $total_space;
        if ($free_percentage < $low_limit) {
            echo ev_gettext('ERROR: Almost no free disk space left (percentage left: %.2f%%)', $free_percentage), "\n";

            return 1;
        }
        if ($free_percentage < $high_limit) {
            echo ev_gettext('ERROR: Free disk space left is getting very low (percentage left: %.2f%%)', $free_percentage), "\n";

            return 1;
        }

        return 0;
    }

    /**
     * Checks on the status of the required configuration and auxiliary files
     * and directories.
     *
     * @param   array $required_files an array of files that should be checked on
     * @return  int number of errors encountered
     */
    public static function checkRequiredFiles($required_files)
    {
        $errors = 0;
        foreach ($required_files as $file_path => $options) {
            // check if file exists
            if (!file_exists($file_path)) {
                echo ev_gettext('ERROR: File could not be found (path: %s)', $file_path), "\n";
                $errors++;
                continue;
            }
            // check the owner and group for these files
            list($owner, $group) = self::getOwnerAndGroup($file_path);
            if (!empty($options['check_owner']) && $options['owner'] != $owner) {
                echo ev_gettext('ERROR: File owner mismatch (path: %1$s; current owner: %2$s; correct owner: %3$s)', $file_path, $owner, $options['owner']), "\n";
                $errors++;
            }
            if (!empty($options['check_group']) && $options['group'] != $group) {
                echo ev_gettext('ERROR: File group mismatch (path: %1$s; current group: %2$s; correct group: %3$s)', $file_path, $group, $options['group']), "\n";
                $errors++;
            }
            // check permission bits
            $perm = self::getOctalPerms($file_path);
            if (!empty($options['check_permission']) && $options['permission'] != $perm) {
                echo ev_gettext('ERROR: File permission mismatch (path: %1$s; current perm: %2$s; correct perm: %3$s)', $file_path, $perm, $options['permission']), "\n";
                $errors++;
            }
            // check filesize
            if (!empty($options['check_filesize']) && filesize($file_path) < $options['filesize']) {
                echo ev_gettext('ERROR: File size mismatch (path: %1$s; current filesize: %2$s)', $file_path, filesize($file_path)), "\n";
                $errors++;
            }
        }

        return $errors;
    }

    /**
     * Checks on the status of the required directories.
     *
     * @param   array $required_directories an array of files that should be checked on
     * @return  int number of errors encountered
     */
    public static function checkRequiredDirs($required_directories)
    {
        $errors = 0;
        foreach ($required_directories as $dir_path => $options) {
            // check if directory exists
            if (!file_exists($dir_path)) {
                echo ev_gettext('ERROR: Directory could not be found (path: %1$s)', $dir_path), "\n";
                $errors++;
                continue;
            }
            // check permission bits
            $perm = self::getOctalPerms($dir_path);
            if ((@$options['check_permission']) && ($options['permission'] != $perm)) {
                echo ev_gettext('ERROR: Directory permission mismatch (path: %1$s; current perm: %2$s; correct perm: %3$s)', $dir_path, $perm, $options['permission']), "\n";
                $errors++;
            }
        }

        return $errors;
    }

    /**
     * Checks on the status of the MySQL database.
     *
     * @return  int number of errors encountered
     */
    public static function checkDatabase()
    {
        $required_tables = Db\Table::getTableList();

        // add the table prefix to all of the required tables
        $dbc = DB_Helper::getConfig();
        $required_tables = array_map(
            function ($table) use ($dbc) {
                return "{$dbc['table_prefix']}$table";
            }, $required_tables
        );

        // check if all of the required tables are really there
        $table_list = DB_Helper::getInstance()->getColumn('SHOW TABLES');
        $errors = 0;
        foreach ($required_tables as $table) {
            if (!in_array($table, $table_list)) {
                echo ev_gettext('ERROR: Could not find required table "%s"', $table), "\n";
                $errors++;
            }
        }

        return $errors;
    }

    /**
     * Returns the owner and group name for the given file.
     *
     * @static
     * @param   string $file The full path to the file
     * @return  array The owner and group name associated with that file
     */
    private static function getOwnerAndGroup($file)
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
     * @static
     * @param   string $file The full path to the file
     * @return  string The octal permission string
     */
    private static function getOctalPerms($file)
    {
        return substr(sprintf('%o', fileperms($file)), -3);
    }
}
