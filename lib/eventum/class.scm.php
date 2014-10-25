<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2014 Eventum Team.                              |
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

/**
 * Class to handle the business logic related to the source control management
 * integration features of the application.
 */
class SCM
{
    /**
     * Method used to remove all checkins associated with a list of issues.
     *
     * @param   array $ids The list of issues
     * @return  boolean
     */
    public static function removeByIssues($ids)
    {
        $items = implode(", ", Misc::escapeInteger($ids));
        $stmt = "DELETE FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_iss_id IN ($items)";
        try {
            $res = DB_Helper::getInstance()->query($stmt);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove a specific list of checkins
     *
     * @param   int[] $isc_id list to remove
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function remove($items)
    {
        $items = implode(", ", Misc::escapeInteger($items));
        $stmt = "SELECT
                    isc_iss_id
                 FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_id IN ($items)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt);

        $stmt = "DELETE FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt);
        } catch (DbException $e) {
            return -1;
        }

        // need to mark this issue as updated
        Issue::markAsUpdated($issue_id);
        // need to save a history entry for this
        $summary = ev_gettext('SCM Checkins removed by %1$s', User::getFullName(Auth::getUserID()));
        History::add($issue_id, Auth::getUserID(), History::getTypeID('scm_checkin_removed'), $summary);

        return 1;
    }

    /**
     * Method used to parse an user provided URL and substitute a known set of
     * placeholders for the appropriate information.
     *
     * @param   string $url The user provided URL
     * @return  string The parsed URL
     */
    public function parseURL($url, $info)
    {
        $url = str_replace('{MODULE}', $info["isc_module"], $url);
        $url = str_replace('{FILE}', $info["isc_filename"], $url);
        $url = str_replace('{OLD_VERSION}', $info["isc_old_version"], $url);
        $url = str_replace('{NEW_VERSION}', $info["isc_new_version"], $url);

        // the current version to look log from
        if ($info['added']) {
            $url = str_replace('{VERSION}', $info["isc_new_version"], $url);
        } elseif ($info['removed']) {
            $url = str_replace('{VERSION}', $info["isc_old_version"], $url);
        } else {
            $url = str_replace('{VERSION}', $info["isc_new_version"], $url);
        }

        return $url;
    }

    /**
     * Method used to get the full list of checkins associated with an issue.
     *
     * @param   integer $issue_id The issue ID
     * @return  array The list of checkins
     */
    public static function getCheckinList($issue_id)
    {
        $setup = Setup::load();
        $stmt = "SELECT
                    *
                 FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_iss_id=?
                 ORDER BY
                    isc_created_date ASC";
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($issue_id));
        } catch (DbException $e) {
            return array();
        }

        if (empty($res)) {
            return array();
        }

        foreach ($res as $i => $row) {
            // add ADDED and REMOVED fields
            $res[$i]['added'] = $res[$i]['isc_old_version'] == 'NONE';
            $res[$i]['removed'] = $res[$i]['isc_new_version'] == 'NONE';

            $res[$i]["isc_commit_msg"] = Link_Filter::processText(Issue::getProjectID($issue_id), nl2br(htmlspecialchars($res[$i]["isc_commit_msg"])));
            $res[$i]["checkout_url"] = self::parseURL($setup["checkout_url"], $res[$i]);
            $res[$i]["diff_url"] = self::parseURL($setup["diff_url"], $res[$i]);
            $res[$i]["scm_log_url"] = self::parseURL($setup["scm_log_url"], $res[$i]);
            $res[$i]["isc_created_date"] = Date_Helper::getFormattedDate($res[$i]["isc_created_date"]);
        }

        return $res;
    }

    /**
     * Method used to associate a new checkin with an existing issue
     *
     * @param   integer $issue_id The ID of the issue.
     * @param   string $module The SCM module commit was made.
     * @param   array $file File info with their version numbers changes made on.
     * @param   string $username SCM user doing the checkin.
     * @param   string $commit_msg Message associated with the SCM commit.
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function logCheckin($issue_id, $module, $file, $username, $commit_msg)
    {
        $stmt = "INSERT INTO
                    {{%issue_checkin}}
                 (
                    isc_iss_id,
                    isc_module,
                    isc_filename,
                    isc_old_version,
                    isc_new_version,
                    isc_created_date,
                    isc_username,
                    isc_commit_msg
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?
                 )";
        $params = array(
            $issue_id,
            $module,
            $file['file'],
            $file['old_version'],
            $file['new_version'],
            Date_Helper::getCurrentDateGMT(),
            $username,
            $commit_msg,
        );
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return -1;
        }

        // need to mark this issue as updated
        Issue::markAsUpdated($issue_id, 'scm checkin');
        // need to save a history entry for this

        // TRANSLATORS: %1: username
        $summary = ev_gettext('SCM Checkins associated by SCM user "%1$s"', $username);
        History::add($issue_id, APP_SYSTEM_USER_ID, History::getTypeID('scm_checkin_associated'), $summary);

        return 1;
    }
}
