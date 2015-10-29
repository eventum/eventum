<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
//

class MySQL_Fulltext_Search extends Abstract_Fulltext_Search
{
    public function getIssueIDs($options)
    {
        // no pre-existing list, generate them
        $stmt = '(SELECT
                    iss_id
                 FROM
                     {{%issue}}
                 WHERE
                     MATCH(iss_summary, iss_description) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    not_iss_id
                 FROM
                     {{%note}}
                 WHERE
                     not_removed = 0 AND
                     MATCH(not_note) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    ttr_iss_id
                 FROM
                     {{%time_tracking}}
                 WHERE
                     MATCH(ttr_summary) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                    phs_iss_id
                 FROM
                     {{%phone_support}}
                 WHERE
                     MATCH(phs_description) AGAINST (? IN BOOLEAN MODE)
                 ) UNION (
                 SELECT
                     sup_iss_id
                 FROM
                     {{%support_email}},
                     {{%support_email_body}}
                 WHERE
                     sup_id = seb_sup_id AND
                     sup_removed = 0 AND
                     MATCH(seb_body) AGAINST (? IN BOOLEAN MODE)
                 )';
        $params = array(
            $options['keywords'],
            $options['keywords'],
            $options['keywords'],
            $options['keywords'],
            $options['keywords'],
        );
        try {
            $res = DB_Helper::getInstance()->getColumn($stmt, $params);
        } catch (DbException $e) {
            return array(-1);
        }

        $stmt = 'SELECT
                    DISTINCT(icf_iss_id)
                FROM
                    {{%issue_custom_field}}
                WHERE
                    (MATCH (icf_value) AGAINST (? IN BOOLEAN MODE) OR
                     MATCH (icf_value_integer) AGAINST (? IN BOOLEAN MODE))';
        $params1 = array(
            $options['keywords'],
            $options['keywords'],
        );
        try {
            $custom_res = DB_Helper::getInstance()->getColumn($stmt, $params1);
        } catch (DbException $e) {
            return array(-1);
        }

        $issues = array_merge($res, $custom_res);

        // we kill the query results on purpose to flag that no
        // issues could be found with fulltext search
        if (count($issues) < 1) {
            return array(-1);
        }

        return $issues;
    }

    public function getMatchModes()
    {
        return false;
    }

    public function getExcerpts()
    {
        return array();
    }
}
