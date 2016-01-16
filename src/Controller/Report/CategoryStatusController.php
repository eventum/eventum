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

namespace Eventum\Controller\Report;

use Auth;
use Category;
use DB_Helper;
use DbException;
use Status;

class CategoryStatusController extends ReportBaseController
{
    /** @var string */
    protected $tpl_name = 'reports/category_statuses.tpl.html';

    /** @var int */
    protected $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        $this->prj_id = Auth::getCurrentProject();
    }

    private function getReport($categories, $statuses)
    {
        $data = array();
        foreach ($categories as $cat_id => $cat_title) {
            $data[$cat_id] = array(
                'title' => $cat_title,
                'statuses' => array(),
            );
            foreach ($statuses as $sta_id => $sta_title) {
                $sql
                    = 'SELECT
                    count(*)
                FROM
                    {{%issue}}
                WHERE
                    iss_prj_id = ? AND
                    iss_sta_id = ? AND
                    iss_prc_id = ?';
                try {
                    $res = DB_Helper::getInstance()->getOne($sql, array($this->prj_id, $sta_id, $cat_id));
                } catch (DbException $e) {
                    break 2;
                }
                $data[$cat_id]['statuses'][$sta_id] = array(
                    'title' => $sta_title,
                    'count' => $res,
                );
            }
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $categories = Category::getAssocList($this->prj_id);
        $statuses = Status::getAssocStatusList($this->prj_id, true);
        $data = $this->getReport($categories, $statuses);

        $this->tpl->assign(
            array(
                'statuses' => $statuses,
                'categories' => $categories,
                'data' => $data,
            )
        );
    }
}
