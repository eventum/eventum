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

use Eventum\Db\DatabaseException;

/**
 * Class to handle the business logic related to the phone support
 * feature of the application.
 */
class Phone_Support
{
    /**
     * Method used to add a new category to the application.
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function insertCategory()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'INSERT INTO
                    `project_phone_category`
                 (
                    phc_prj_id,
                    phc_title
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['prj_id'], $_POST['title']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to update the values stored in the database.
     * Typically the user would modify the title of the category in
     * the application and this method would be called.
     *
     * @return  int 1 if the update worked properly, any other value otherwise
     */
    public static function updateCategory()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'UPDATE
                    `project_phone_category`
                 SET
                    phc_title=?
                 WHERE
                    phc_prj_id=? AND
                    phc_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$_POST['title'], $_POST['prj_id'], $_POST['id']]);
        } catch (DatabaseException $e) {
            return -1;
        }

        return 1;
    }

    /**
     * Method used to remove user-selected categories from the
     * database.
     *
     * @return  bool Whether the removal worked or not
     */
    public static function removeCategory()
    {
        $items = $_POST['items'];
        $itemlist = DB_Helper::buildList($items);

        $stmt = "DELETE FROM
                    `project_phone_category`
                 WHERE
                    phc_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to get the full details of a category.
     *
     * @param   int $phc_id The category ID
     * @return  array The information about the category provided
     */
    public static function getCategoryDetails($phc_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `project_phone_category`
                 WHERE
                    phc_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$phc_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the full list of categories associated with
     * a specific project.
     *
     * @param   int $prj_id The project ID
     * @return  array The full list of categories
     */
    public static function getCategoryList($prj_id)
    {
        $stmt = 'SELECT
                    phc_id,
                    phc_title
                 FROM
                    `project_phone_category`
                 WHERE
                    phc_prj_id=?
                 ORDER BY
                    phc_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the list of
     * categories associated with a specific project.
     *
     * @param   int $prj_id The project ID
     * @return  array The associative array of categories
     */
    public static function getCategoryAssocList($prj_id)
    {
        $stmt = 'SELECT
                    phc_id,
                    phc_title
                 FROM
                    `project_phone_category`
                 WHERE
                    phc_prj_id=?
                 ORDER BY
                    phc_id ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the details of a given phone support entry.
     *
     * @param   int $phs_id The phone support entry ID
     * @return  array The phone support entry details
     */
    public static function getDetails($phs_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    `phone_support`
                 WHERE
                    phs_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, [$phs_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the full listing of phone support entries
     * associated with a specific issue.
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of notes
     */
    public static function getListing($issue_id)
    {
        $stmt = 'SELECT
                    `phone_support`.*,
                    usr_full_name,
                    phc_title,
                    iss_prj_id
                 FROM
                    `phone_support`,
                    `project_phone_category`,
                    `user`,
                    `issue`
                 WHERE
                    phs_iss_id=iss_id AND
                    iss_prj_id=phc_prj_id AND
                    phs_phc_id=phc_id AND
                    phs_usr_id=usr_id AND
                    phs_iss_id=?
                 ORDER BY
                    phs_created_date ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$issue_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        foreach ($res as &$row) {
            $row['phs_description'] = Misc::activateLinks(nl2br(htmlspecialchars($row['phs_description'])));
            $row['phs_description'] = Link_Filter::processText($row['iss_prj_id'], $row['phs_description']);
        }

        return $res;
    }

    /**
     * Method used to add a phone support entry using the user
     * interface form available in the application.
     *
     * @return  int 1 if the insert worked, -1 or -2 otherwise
     */
    public static function insert()
    {
        $usr_id = Auth::getUserID();
        $iss_id = (int) $_POST['issue_id'];
        $date = $_POST['date'];

        // format the date from the form
        $created_date = sprintf('%04d-%02d-%02d %02d:%02d:%02d',
            $date['Year'], $date['Month'],
            $date['Day'], $date['Hour'],
            $date['Minute'], 0);

        // convert the date to GMT timezone
        $created_date = Date_Helper::convertDateGMT($created_date . ' ' . Date_Helper::getPreferredTimezone());
        $stmt = 'INSERT INTO
                    `phone_support`
                 (
                    phs_iss_id,
                    phs_usr_id,
                    phs_phc_id,
                    phs_created_date,
                    phs_type,
                    phs_phone_number,
                    phs_description,
                    phs_phone_type,
                    phs_call_from_lname,
                    phs_call_from_fname,
                    phs_call_to_lname,
                    phs_call_to_fname
                 ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?
                 )';
        $params = [
            $iss_id,
            $usr_id,
            $_POST['phone_category'],
            $created_date,
            $_POST['type'],
            $_POST['phone_number'],
            $_POST['description'],
            $_POST['phone_type'],
            $_POST['from_lname'],
            $_POST['from_fname'],
            $_POST['to_lname'],
            $_POST['to_fname'],
        ];
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DatabaseException $e) {
            return -1;
        }

        // enter the time tracking entry about this phone support entry
        $phs_id = DB_Helper::get_last_insert_id();
        $prj_id = Auth::getCurrentProject();
        $ttc_id = Time_Tracking::getCategoryId($prj_id, 'Telephone Discussion');
        $time_spent = (int) $_POST['call_length'];
        $summary = ev_gettext('Time entry inserted from phone call.');
        Time_Tracking::addTimeEntry($iss_id, $ttc_id, $time_spent, $date, $summary);
        $stmt = 'SELECT
                    max(ttr_id)
                 FROM
                    `time_tracking`
                 WHERE
                    ttr_iss_id = ? AND
                    ttr_usr_id = ?';
        $ttr_id = DB_Helper::getInstance()->getOne($stmt, [$iss_id, $usr_id]);

        Issue::markAsUpdated($iss_id, 'phone call');
        // need to save a history entry for this
        History::add($iss_id, $usr_id, 'phone_entry_added', 'Phone Support entry submitted by {user}', [
            'user' => User::getFullName($usr_id),
        ]);
        // XXX: send notifications for the issue being updated (new notification type phone_support?)

        // update phone record with time tracking ID.
        if (!empty($phs_id) && !empty($ttr_id)) {
            $stmt = 'UPDATE
                        `phone_support`
                     SET
                        phs_ttr_id = ?
                     WHERE
                        phs_id = ?';
            try {
                DB_Helper::getInstance()->query($stmt, [$ttr_id, $phs_id]);
            } catch (DatabaseException $e) {
                return -1;
            }
        }

        return 1;
    }

    /**
     * Method used to remove a specific phone support entry from the
     * application.
     *
     * @param   int $phone_id The phone support entry ID
     * @return  int 1 if the removal worked, -1 or -2 otherwise
     */
    public static function remove($phone_id)
    {
        $stmt = 'SELECT
                    phs_iss_id,
                    phs_ttr_id,
                    phs_usr_id
                 FROM
                    `phone_support`
                 WHERE
                    phs_id=?';
        $details = DB_Helper::getInstance()->getRow($stmt, [$phone_id]);
        if ($details['phs_usr_id'] != Auth::getUserID()) {
            return -2;
        }

        $stmt = 'DELETE FROM
                    `phone_support`
                 WHERE
                    phs_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, [$phone_id]);
        } catch (DatabaseException $e) {
            return -1;
        }

        Issue::markAsUpdated($details['phs_iss_id']);
        $usr_id = Auth::getUserID();
        History::add($details['phs_iss_id'], $usr_id, 'phone_entry_removed', 'Phone Support entry removed by {user}', [
            'user' => User::getFullName($usr_id),
        ]);

        if (!empty($details['phs_ttr_id'])) {
            $time_result = Time_Tracking::removeTimeEntry($details['phs_ttr_id'], $details['phs_usr_id']);
            if ($time_result == 1) {
                return 2;
            }

            return $time_result;
        }

        return 1;
    }

    /**
     * Returns the number of calls by a user in a time range.
     *
     * @param   string $usr_id The ID of the user
     * @param   int $start The timestamp of the start date
     * @param   int $end The timestamp of the end date
     * @return  int the number of phone calls by the user
     */
    public static function getCountByUser($usr_id, $start, $end)
    {
        $stmt = 'SELECT
                    COUNT(phs_id)
                 FROM
                    `phone_support`,
                    `issue`
                 WHERE
                    phs_iss_id = iss_id AND
                    iss_prj_id = ? AND
                    phs_created_date BETWEEN ? AND ? AND
                    phs_usr_id = ?';
        $params = [Auth::getCurrentProject(), $start, $end, $usr_id];
        try {
            $res = DB_Helper::getInstance()->getOne($stmt, $params);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }
}
