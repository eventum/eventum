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

class MySQL_Fulltext_Search extends Abstract_Fulltext_Search
{
    public function getIssueIDs($options)
    {
        // no pre-existing list, generate them
        $stmt = '(SELECT
                    iss_id
                 FROM
                     `issue`
                 WHERE
                     MATCH(iss_summary, iss_description) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    not_iss_id
                 FROM
                     `note`
                 WHERE
                     not_removed = 0 AND
                     MATCH(not_note) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    ttr_iss_id
                 FROM
                     `time_tracking`
                 WHERE
                     MATCH(ttr_summary) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    phs_iss_id
                 FROM
                     `phone_support`
                 WHERE
                     MATCH(phs_description) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                     sup_iss_id
                 FROM
                     `support_email`,
                     `support_email_body`
                 WHERE
                     sup_id = seb_sup_id AND
                     sup_removed = 0 AND
                     MATCH(seb_body) AGAINST (? IN BOOLEAN MODE)
                 )';
        $params = [
            $options['keywords'],
            $options['keywords'],
            $options['keywords'],
            $options['keywords'],
            $options['keywords'],
        ];
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params);
        } catch (DatabaseException $e) {
            return [-1];
        }

        $stmt = 'SELECT
                    DISTINCT(icf_iss_id)
                FROM
                    `issue_custom_field`
                WHERE
                    (MATCH (icf_value) AGAINST (? IN BOOLEAN MODE) OR
                     MATCH (icf_value_integer) AGAINST (? IN BOOLEAN MODE))';
        $params1 = [
            $options['keywords'],
            $options['keywords'],
        ];
        try {
            $custom_res = DB_Helper::getInstance()->getColumn($stmt, $params1);
        } catch (DatabaseException $e) {
            return [-1];
        }

        $issues = array_merge($res, $custom_res);

        // we kill the query results on purpose to flag that no
        // issues could be found with fulltext search
        if (count($issues) < 1) {
            return [-1];
        }

        return $issues;
    }

    public function getMatchModes()
    {
        return false;
    }

    public function getExcerpts()
    {
        return [];
    }
}
