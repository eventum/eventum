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
 * Class to handle the business logic related to the generation of the
 * issue statistics displayed in the main screen of the application.
 */
class Stats
{
    /**
     * Method used to check if the provided array has valid data (e.g. non-zero)
     *
     * @param   array $data The data to check against
     * @return  bool
     */
    public static function hasData($data)
    {
        foreach ($data as $piece) {
            if ($piece) {
                return true;
            }
        }

        return false;
    }

    /**
     * Method used to get an associative array of the list of categories and the
     * total number of issues associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of categories
     */
    public static function getAssocCategory($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Category::getAssocList($prj_id);
        $stats = [];
        foreach ($list as $prc_id => $prc_title) {
            $stmt = 'SELECT
                        COUNT(*) AS total_items
                     FROM
                        `issue`,
                        `status`
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=? AND
                        iss_prc_id=?';
            if ($hide_closed) {
                $stmt .= ' AND
                        sta_is_closed = 0';
            }

            $res = (integer) DB_Helper::getInstance()->getOne($stmt, [$prj_id, $prc_id]);
            if ($res > 0) {
                $stats[$prc_title] = $res;
            }
        }
        arsort($stats);

        return $stats;
    }

    /**
     * Method used to get an associative array of the list of releases and the
     * total number of issues associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of releases
     */
    public static function getAssocRelease($hide_closed = true)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Release::getAssocList($prj_id);
        $stats = [];
        foreach ($list as $pre_id => $pre_title) {
            $stmt = 'SELECT
                        COUNT(*) AS total_items
                     FROM
                        `issue`,
                        `status`
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=? AND
                        iss_pre_id=?';
            if ($hide_closed) {
                $stmt .= ' AND
                        sta_is_closed = 0';
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt, [$prj_id, $pre_id]);
            if ($res > 0) {
                $stats[$pre_title] = $res;
            }
        }
        arsort($stats);

        return $stats;
    }

    /**
     * Method used to get an associative array of the list of statuses and the
     * total number of issues associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of statuses
     */
    public static function getAssocStatus($hide_closed = true)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Status::getAssocStatusList($prj_id);
        $stats = [];
        foreach ($list as $sta_id => $sta_title) {
            $stmt = 'SELECT
                        COUNT(*) AS total_items
                     FROM
                        `issue`,
                        `status`
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=? AND
                        iss_sta_id=?';
            if ($hide_closed) {
                $stmt .= ' AND
                        sta_is_closed = 0';
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt, [$prj_id, $sta_id]);
            if ($res > 0) {
                $stats[$sta_title] = $res;
            }
        }
        arsort($stats);

        return $stats;
    }

    /**
     * Method used to get the list of statuses and the total number of issues
     * associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of statuses
     */
    public static function getStatus($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = 'SELECT
                    DISTINCT iss_sta_id,
                    sta_title,
                    COUNT(*) AS total_items
                 FROM
                    `issue`,
                    `status`
                 WHERE
                    iss_sta_id=sta_id AND
                    iss_prj_id=?';
        if ($hide_closed) {
            $stmt .= ' AND
                        sta_is_closed = 0';
        }
        $stmt .= '
                 GROUP BY
                    iss_sta_id
                 ORDER BY
                    total_items DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of categories and the total number of issues
     * associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of categories
     */
    public static function getCategory($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = 'SELECT
                    DISTINCT iss_prc_id,
                    prc_title,
                    SUM(CASE WHEN sta_is_closed=0 THEN 1 ELSE 0 END) AS total_open_items,
                    SUM(CASE WHEN sta_is_closed=1 THEN 1 ELSE 0 END) AS total_closed_items
                 FROM
                    `issue`,
                    `project_category`,
                    `status`
                 WHERE
                    iss_prj_id=? AND
                    iss_prc_id=prc_id AND
                    iss_sta_id=sta_id';
        if ($hide_closed) {
            $stmt .= ' AND
                    sta_is_closed = 0';
        }
        $stmt .= '
                 GROUP BY
                    iss_prc_id
                 ORDER BY
                    total_open_items';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the list of releases and the total number of issues
     * associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of releases
     */
    public static function getRelease($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = 'SELECT
                    DISTINCT iss_pre_id,
                    pre_title,
                    SUM(CASE WHEN sta_is_closed=0 THEN 1 ELSE 0 END) AS total_open_items,
                    SUM(CASE WHEN sta_is_closed=1 THEN 1 ELSE 0 END) AS total_closed_items
                 FROM
                    `issue`,
                    `project_release`,
                    `status`
                 WHERE
                    iss_prj_id=? AND
                    iss_pre_id=pre_id AND
                    iss_sta_id=sta_id';
        if ($hide_closed) {
            $stmt .= ' AND
                    sta_is_closed = 0';
        }
        $stmt .= '
                 GROUP BY
                    iss_pre_id
                 ORDER BY
                    total_open_items DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the list of priorities and the
     * total number of issues associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of priorities
     */
    public static function getAssocPriority($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Priority::getAssocList($prj_id);
        $stats = [];
        foreach ($list as $pri_id => $pri_title) {
            $stmt = 'SELECT
                        COUNT(*) AS total_items
                     FROM
                        `issue`,
                        `status`
                     WHERE
                        iss_sta_id = sta_id AND
                        iss_prj_id=? AND
                        iss_pri_id=?';
            if ($hide_closed) {
                $stmt .= ' AND
                        sta_is_closed = 0';
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt, [$prj_id, $pri_id]);
            if ($res > 0) {
                $stats[$pri_title] = $res;
            }
        }
        arsort($stats);

        return $stats;
    }

    /**
     * Method used to get the list of priorities and the total number of issues
     * associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of statuses
     */
    public static function getPriority($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = 'SELECT
                    DISTINCT iss_pri_id,
                    pri_title,
                    SUM(CASE WHEN sta_is_closed=0 THEN 1 ELSE 0 END) AS total_open_items,
                    SUM(CASE WHEN sta_is_closed=1 THEN 1 ELSE 0 END) AS total_closed_items
                 FROM
                    `issue`,
                    `project_priority`,
                    `status`
                 WHERE
                    iss_pri_id=pri_id AND
                    iss_sta_id=sta_id AND
                    iss_prj_id=?';
        if ($hide_closed) {
            $stmt .= ' AND
                    sta_is_closed = 0';
        }
        $stmt .= '
                 GROUP BY
                    iss_pri_id
                 ORDER BY
                    total_open_items DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get an associative array of the list of users and the
     * total number of issues associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of users
     */
    public static function getAssocUser($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $list = Project::getUserAssocList($prj_id, 'stats', User::ROLE_CUSTOMER);
        $stats = [];
        foreach ($list as $usr_id => $usr_full_name) {
            $stmt = 'SELECT
                        COUNT(*) AS total_items
                     FROM
                        `issue`,
                        `issue_user`,
                        `status`
                     WHERE
                        iss_sta_id = sta_id AND
                        isu_iss_id=iss_id AND
                        iss_prj_id=? AND
                        isu_usr_id=?';
            if ($hide_closed) {
                $stmt .= ' AND
                        sta_is_closed = 0';
            }
            $res = (integer) DB_Helper::getInstance()->getOne($stmt, [$prj_id, $usr_id]);
            if ($res > 0) {
                $stats[$usr_full_name] = $res;
            }
        }
        arsort($stats);

        return $stats;
    }

    /**
     * Method used to get the list of users and the total number of issues
     * associated with each of them.
     *
     * @param   bool $hide_closed if closed issues should be hidden
     * @return  array List of users
     */
    public static function getUser($hide_closed = false)
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = 'SELECT
                    DISTINCT isu_usr_id,
                    usr_full_name,
                    usr_status,
                    SUM(CASE WHEN sta_is_closed=0 THEN 1 ELSE 0 END) AS total_open_items,
                    SUM(CASE WHEN sta_is_closed=1 THEN 1 ELSE 0 END) AS total_closed_items
                 FROM
                    `issue`,
                    `issue_user`,
                    `user`,
                    `status`
                 WHERE
                    isu_usr_id=usr_id AND
                    isu_iss_id=iss_id AND
                    iss_prj_id=? AND
                    iss_sta_id=sta_id';
        if ($hide_closed) {
            $stmt .= ' AND
                    sta_is_closed = 0';
        }
        $stmt .= '
                 GROUP BY
                    isu_usr_id
                 ORDER BY
                    total_open_items DESC';
        try {
            $res = DB_Helper::getInstance()->getAll($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return $res;
    }

    /**
     * Method used to get the total number of issues associated with each
     * email status.
     *
     * @return  array List of statuses
     */
    public static function getEmailStatus()
    {
        $prj_id = Auth::getCurrentProject();
        $stmt = "SELECT
                    CASE WHEN sup_iss_id > 0 THEN 'associated' ELSE 'unassociated' END AS type,
                    COUNT(*) AS total_items
                 FROM
                    `support_email`,
                    `email_account`
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=? AND
                    sup_removed=0
                 GROUP BY
                    type";
        try {
            $res = DB_Helper::getInstance()->getPair($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        if (empty($res['associated'])) {
            $res['associated'] = 0;
        }
        if (empty($res['unassociated'])) {
            $res['unassociated'] = 0;
        }
        $stmt = 'SELECT
                    COUNT(*) AS total_items
                 FROM
                    `support_email`,
                    `email_account`
                 WHERE
                    sup_ema_id=ema_id AND
                    ema_prj_id=? AND
                    sup_removed=1';
        try {
            $res3 = DB_Helper::getInstance()->getOne($stmt, [$prj_id]);
        } catch (DatabaseException $e) {
            return '';
        }

        return [
            'pending' => $res['unassociated'],
            'associated' => $res['associated'],
            'removed' => $res3,
        ];
    }
}
