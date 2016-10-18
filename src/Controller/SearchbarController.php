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

namespace Eventum\Controller;

use Filter;

class SearchbarController extends BaseController
{
    /** @var int */
    private $custom_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->custom_id = $request->query->getInt('custom_id');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->custom_id) {
            $this->filterAction();
        }
    }

    private function filterAction()
    {
        $filter = $this->getFilterById($this->custom_id);
        if (!$filter) {
            return;
        }

        $request = $this->getRequest();
        $params = [];

        // merge with GET and POST
        $params += $request->request->all();
        $params += $request->query->all();
        $params += $filter;

        unset($params['custom_id']);
        $params['cat'] = 'search';

        $this->redirect('list.php', $params);
    }

    private function getFilterById($cst_id)
    {
        $filters = Filter::getListing(true);
        foreach ($filters as $filter) {
            if ($filter['cst_id'] == $cst_id) {
                parse_str($filter['url'], $params);

                return $params;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
