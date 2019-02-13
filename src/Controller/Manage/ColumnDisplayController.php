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

namespace Eventum\Controller\Manage;

use CRM;
use Display_Column;
use Eventum\Controller\Helper\MessagesHelper;
use Project;
use User;

class ColumnDisplayController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/column_display.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'save') {
            $this->saveColumnAction();
        }
    }

    private function saveColumnAction(): void
    {
        $res = Display_Column::save();
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, columns to display was saved successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to save columns to display.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $page = 'list_issues';
        $available = Display_Column::getAllColumns($page);
        $selected = Display_Column::getSelectedColumns($this->prj_id, $page);

        // re-order available array to match rank
        $available_ordered = [];
        foreach ($selected as $field_name => $field_info) {
            $available_ordered[$field_name] = $available[$field_name];
            unset($available[$field_name]);
        }
        if (count($available) > 0) {
            $available_ordered += $available;
        }

        $excluded_roles = [];
        if (!CRM::hasCustomerIntegration($this->prj_id)) {
            $excluded_roles[] = User::ROLE_CUSTOMER;
        }

        $user_roles = User::getRoles($excluded_roles);
        $user_roles[9] = ev_gettext('Never Display');

        // generate ranks
        $ranks = [];
        $navailable_ordered = count($available_ordered);
        for ($i = 1; $i <= $navailable_ordered; $i++) {
            $ranks[$i] = $i;
        }

        $this->tpl->assign(
            [
                'available' => $available_ordered,
                'selected' => $selected,
                'user_roles' => $user_roles,
                'page' => $page,
                'ranks' => $ranks,
                'prj_id' => $this->prj_id,
                'project_name' => Project::getName($this->prj_id),
            ]
        );
    }
}
