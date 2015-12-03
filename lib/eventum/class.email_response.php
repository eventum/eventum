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

/**
 * Class to handle the business logic related to the administration
 * of canned email responses in the system.
 */
class Email_Response
{
    /**
     * Method used to add a project association to a email
     * response entry.
     *
     * @param   integer $ere_id The email response ID
     * @param   integer $prj_id The project ID
     * @return  void
     */
    public function addProjectAssociation($ere_id, $prj_id)
    {
        $stmt = 'INSERT INTO
                    {{%project_email_response}}
                 (
                    per_ere_id,
                    per_prj_id
                 ) VALUES (
                    ?, ?
                 )';
        DB_Helper::getInstance()->query($stmt, array($ere_id, $prj_id));
    }

    /**
     * Method used to add a new canned email response to the system.
     *
     * @return  integer 1 if the insert worked, -1 otherwise
     */
    public static function insert()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'INSERT INTO
                    {{%email_response}}
                 (
                    ere_title,
                    ere_response_body
                 ) VALUES (
                    ?, ?
                 )';
        try {
            DB_Helper::getInstance()->query($stmt, array($_POST['title'], $_POST['response_body']));
        } catch (DbException $e) {
            return -1;
        }

        $new_response_id = DB_Helper::get_last_insert_id();
        // now populate the project-news mapping table
        foreach ($_POST['projects'] as $prj_id) {
            self::addProjectAssociation($new_response_id, $prj_id);
        }

        return 1;
    }

    /**
     * Method used to remove a canned email response from the system.
     *
     * @return  boolean
     */
    public static function remove()
    {
        $items = $_POST['items'];
        $stmt = 'DELETE FROM
                    {{%email_response}}
                 WHERE
                    ere_id IN (' . DB_Helper::buildList($items) . ')';
        try {
            DB_Helper::getInstance()->query($stmt, $items);
        } catch (DbException $e) {
            return false;
        }

        self::removeProjectAssociations($items);

        return true;
    }

    /**
     * Method used to remove the project associations for a given
     * email response entry.
     *
     * @param   integer $ere_id The email response ID
     * @param   integer $prj_id The project ID
     * @return  boolean
     */
    public function removeProjectAssociations($ere_id, $prj_id = null)
    {
        if (!is_array($ere_id)) {
            $ere_id = array($ere_id);
        }

        $stmt = 'DELETE FROM
                    {{%project_email_response}}
                 WHERE
                    per_ere_id IN (' . DB_Helper::buildList($ere_id) . ')';
        $params = $ere_id;
        if ($prj_id) {
            $stmt .= ' AND per_prj_id=?';
            $params[] = $prj_id;
        }
        try {
            DB_Helper::getInstance()->query($stmt, $params);
        } catch (DbException $e) {
            return false;
        }

        return true;
    }

    /**
     * Method used to update a canned email response in the system.
     *
     * @return  integer 1 if the update worked, -1 otherwise
     */
    public static function update()
    {
        if (Validation::isWhitespace($_POST['title'])) {
            return -2;
        }
        $stmt = 'UPDATE
                    {{%email_response}}
                 SET
                    ere_title=?,
                    ere_response_body=?
                 WHERE
                    ere_id=?';
        try {
            DB_Helper::getInstance()->query($stmt, array($_POST['title'], $_POST['response_body'], $_POST['id']));
        } catch (DbException $e) {
            return -1;
        }

        // remove all of the associations with projects, then add them all again
        self::removeProjectAssociations($_POST['id']);
        foreach ($_POST['projects'] as $prj_id) {
            self::addProjectAssociation($_POST['id'], $prj_id);
        }

        return 1;
    }

    /**
     * Method used to get the details of a canned email response for a given
     * response ID.
     *
     * @param   integer $ere_id The email response ID
     * @return  array The canned email response details
     */
    public static function getDetails($ere_id)
    {
        $stmt = 'SELECT
                    *
                 FROM
                    {{%email_response}}
                 WHERE
                    ere_id=?';
        try {
            $res = DB_Helper::getInstance()->getRow($stmt, array($ere_id));
        } catch (DbException $e) {
            return '';
        }

        // get all of the project associations here as well
        $res['projects'] = array_keys(self::getAssociatedProjects($res['ere_id']));

        return $res;
    }

    /**
     * Method used to get the list of associated projects for a given
     * email response entry.
     *
     * @param   integer $ere_id The email response ID
     * @return  array The list of projects
     */
    public function getAssociatedProjects($ere_id)
    {
        $stmt = 'SELECT
                    prj_id,
                    prj_title
                 FROM
                    {{%project}},
                    {{%project_email_response}}
                 WHERE
                    prj_id=per_prj_id AND
                    per_ere_id=?';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($ere_id));
        } catch (DbException $e) {
            return array();
        }

        return $res;
    }

    /**
     * Method used to get the list of canned email responses available in the
     * system.
     *
     * @return  array The list of canned email responses
     */
    public static function getList()
    {
        $stmt = 'SELECT
                    ere_id,
                    ere_title
                 FROM
                    {{%email_response}}
                 ORDER BY
                    ere_title ASC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt);
        } catch (DbException $e) {
            return '';
        }

        // get the list of associated projects
        foreach ($res as &$row) {
            $row['projects'] = implode(', ', array_values(self::getAssociatedProjects($row['ere_id'])));
        }

        return $res;
    }

    /**
     * Method used to get an associate array of all canned email responses
     * available in the system.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of canned email responses
     */
    public static function getAssocList($prj_id)
    {
        $stmt = 'SELECT
                    ere_id,
                    ere_title
                 FROM
                    {{%email_response}},
                    {{%project_email_response}}
                 WHERE
                    per_ere_id=ere_id AND
                    per_prj_id=?
                 ORDER BY
                    ere_title ASC';
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of all of the canned email
     * responses' bodies.
     *
     * @param   integer $prj_id The project ID
     * @return  array The list of canned email responses' bodies.
     */
    public static function getAssocListBodies($prj_id)
    {
        $stmt = 'SELECT
                    ere_id,
                    ere_response_body
                 FROM
                    {{%email_response}},
                    {{%project_email_response}}
                 WHERE
                    per_ere_id=ere_id AND
                    per_prj_id=?';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, array($prj_id));
        } catch (DbException $e) {
            return '';
        }

        // fix the newlines in the response bodies so javascript doesn't die
        // FIXME: this is very wrong, escaping should be done in template where ere_response_body is output, not here
        foreach ($res as &$row) {
            $row['ere_response_body'] = Misc::escapeWhitespace($row['ere_response_body']);
            $row['ere_response_body'] = str_replace('"', '\"', $row['ere_response_body']);
        }

        return $res;
    }
}
