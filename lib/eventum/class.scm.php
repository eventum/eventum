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
        $items = DB_Helper::buildList($ids);
        $stmt = "DELETE FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_iss_id IN ($items)";
        try {
            DB_Helper::getInstance()->query($stmt, $ids);
        } catch (DatabaseException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to remove a specific list of checkins
     *
     * @param   int[] $items list to remove
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function remove($items)
    {
        $itemlist = DB_Helper::buildList($items);

        $stmt = "SELECT
                    isc_iss_id
                 FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_id IN ($itemlist)";
        $issue_id = DB_Helper::getInstance()->getOne($stmt, $items);

        $stmt = "DELETE FROM
                    {{%issue_checkin}}
                 WHERE
                    isc_id IN ($itemlist)";
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DatabaseException $e) {
            return -1;
        }

        // need to mark this issue as updated
        Issue::markAsUpdated($issue_id);
        $usr_id = Auth::getUserID();
        History::add($issue_id, $usr_id, 'scm_checkin_removed', 'SCM Checkins removed by {user}', array(
            'user' => User::getFullName($usr_id)
        ));

        return 1;
    }
}
