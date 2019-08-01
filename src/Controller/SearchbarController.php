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

use Eventum\Controller\Traits\RedirectResponseTrait;
use Filter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchbarController
{
    use RedirectResponseTrait;

    public function defaultAction(Request $request): Response
    {
        $custom_id = $request->query->getInt('custom_id');

        if ($custom_id) {
            return $this->filterAction($request, $custom_id);
        }

        return $this->redirect('index.php');
    }

    private function filterAction(Request $request, int $custom_id): ?Response
    {
        $filter = $this->getFilterById($custom_id);
        if (!$filter) {
            return null;
        }

        $params = [];

        // merge with GET and POST
        $params += $request->request->all();
        $params += $request->query->all();
        $params += $filter;

        unset($params['custom_id']);
        $params['cat'] = 'search';

        return $this->redirect('list.php', $params);
    }

    private function getFilterById(int $cst_id): ?array
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
}
