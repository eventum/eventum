<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

class Monitor
{
    /**
     * Checks the mail queue logs for any email that wasn't delivered.
     *
     * @access  public
     * @return  integer Number of errors encountered.
     */
    function checkMailQueue()
    {
        $stmt = "SELECT
                    maq_id,
                    COUNT(mql_id) total_tries
                 FROM
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "mail_queue_log
                 WHERE
                    maq_status='error' AND
                    maq_id=mql_maq_id
                 GROUP BY
                    maq_id";
        $queue_ids = DB_Helper::getInstance()->getCol($stmt);
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
     * @access  public
     * @return  integer Number of mails not associated.
     */
    function checkMailAssociation()
    {
        // TODO: optimize this
        // TODO: should we check it per project?
        $stmt = "SELECT
                    COUNT(*)
                 FROM
                    (
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "support_email,
                    " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "email_account
                    )
                    LEFT JOIN
                        " . APP_DEFAULT_DB . "." . APP_TABLE_PREFIX . "issue
                    ON
                        sup_iss_id = iss_id
                    WHERE sup_removed=0 AND sup_ema_id=ema_id AND sup_iss_id = 0
        ";
        $res = DB_Helper::getInstance()->getOne($stmt);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
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
     * @access  public
     * @return  integer Number of errors encountered.
     */
    function checkDiskspace($partition, $low_limit = 5, $high_limit = 15)
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
     * @access  public
     * @param   array $required_files An array of files that should be checked on.
     * @return  integer Number of errors encountered.
     */
    function checkRequiredFiles($required_files)
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
     * @access  public
     * @param   array $required_directories An array of files that should be checked on.
     * @return  integer Number of errors encountered.
     */
    function checkRequiredDirs($required_directories)
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
     * @access  public
     * @return  integer Number of errors encountered.
     */
    function checkDatabase()
    {
        $required_tables = array(
            "custom_field",
            "custom_field_option",
            "custom_filter",
            "customer_account_manager",
            "customer_note",
            "email_account",
            "email_draft",
            "email_draft_recipient",
            "email_response",
            "faq",
            "faq_support_level",
            "group",
            "history_type",
            "irc_notice",
            "issue",
            "issue_association",
            "issue_attachment",
            "issue_attachment_file",
            "issue_checkin",
            "issue_custom_field",
            "issue_history",
            "issue_quarantine",
            "issue_requirement",
            "issue_user",
            "issue_user_replier",
            "mail_queue",
            "mail_queue_log",
            "news",
            "note",
            "phone_support",
            "project",
            "project_category",
            "project_custom_field",
            "project_email_response",
            "project_group",
            "project_news",
            "project_phone_category",
            "project_priority",
            "project_release",
            "project_round_robin",
            "project_status",
            "project_status_date",
            "project_user",
            "reminder_action",
            "reminder_action_list",
            "reminder_action_type",
            "reminder_field",
            "reminder_history",
            "reminder_level",
            "reminder_level_condition",
            "reminder_operator",
            "reminder_priority",
            "reminder_requirement",
            "reminder_triggered_action",
            "resolution",
            "round_robin_user",
            "search_profile",
            "status",
            "subscription",
            "subscription_type",
            "support_email",
            "support_email_body",
            "time_tracking",
            "time_tracking_category",
            "user"
        );

        // add the table prefix to all of the required tables
        $required_tables = Misc::array_map_deep($required_tables, array(__CLASS__, 'add_table_prefix'));

        // check if all of the required tables are really there
        $stmt = "SHOW TABLES";
        $table_list = DB_Helper::getInstance()->getCol($stmt);
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
     * Checks on the status of the IRC bot.
     *
     * @access  public
     * @return  integer Number of errors encountered.
     */
    function checkIRCBot()
    {
        // check if any bot.php process is still running (lame, but oh well)
        ob_start();
        passthru("ps -ef | grep [e]ventum-irc-bot");
        $contents = ob_get_contents();
        ob_end_clean();
        $lines = explode("\n", $contents);
        if (count($lines) <= 1) {
            echo ev_gettext('ERROR: Could not find IRC bot pid from process list.'), "\n";
            return 1;
        }
        return 0;
    }


    /**
     * Method used by the code that checks if the required tables
     * do exist in the appropriate database. It returns the given
     * table name prepended with the appropriate table prefix.
     *
     * @static
     * @param   string $table_name The table name
     * @return  string The table name with the prefix added to it
     */
    public static function add_table_prefix($table_name)
    {
        return APP_TABLE_PREFIX . $table_name;
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
        return array(
            $owner_info['name'],
            $group_info['name']
        );
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
        return substr(sprintf("%o", fileperms($file)), -3);
    }
}
