<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
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

namespace Eventum\Controller;

use Auth;
use Category;
use Display_Column;
use Filter;
use Group;
use Misc;
use Prefs;
use Priority;
use Product;
use Project;
use Release;
use Search;
use Search_Profile;
use Severity;
use Status;
use User;
use Workflow;

class ListController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'list.tpl.html';

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var int|string */
    private $rows;

    /** @var int */
    private $pagerRow;

    /** @var array */
    private $options_override;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();
        $this->pagerRow = (int)Search::getParam('pagerRow');

        $rows = Search::getParam('rows');
        $this->rows = ($rows == 'ALL' ? $rows : (int)$rows) ?: APP_DEFAULT_PAGER_SIZE;

        $this->options_override = array();
        $this->viewAction();
    }

    /**
     * handle $view parameter actions
     */
    private function viewAction()
    {
        $request = $this->getRequest();

        switch ($request->get('view')) {
            case 'my_assignments':
                $profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'issue');
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                Auth::redirect(
                    "list.php?users={$this->usr_id}&hide_closed=1&rows={$this->rows}&sort_by=" .
                    $profile['sort_by'] . '&sort_order=' . $profile['sort_order']
                );
                break;

            case 'customer':
                $customer_id = (int)$request->get('customer_id');
                if (!$customer_id) {
                    return;
                }
                $this->options_override = array(
                    'customer_id' => $customer_id,
                    'rows' => $this->rows,
                );
                if (Search::getParam('hide_closed', true) === '') {
                    $options_override['hide_closed'] = 1;
                }
                $_REQUEST['nosave'] = 1;
                break;

            case 'customer_all':
                $customer_id = (int)$request->get('customer_id');
                if (!$customer_id) {
                    return;
                }

                $this->options_override = array(
                    'customer_id' => $customer_id,
                    'rows' => $this->rows,
                );
                if (Search::getParam('hide_closed', true) === '') {
                    $this->options_override['hide_closed'] = 0;
                }
                $_REQUEST['nosave'] = 1;
                $profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'issue');
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                Auth::redirect(
                    "list.php?customer_id={$customer_id}" .
                    "&hide_closed=1&rows={$this->rows}&sort_by={$profile['sort_by']}" .
                    "&sort_order={$profile['sort_order']}&nosave=1"
                );
                break;

            case 'reporter':
                $reporter_id = (int)$request->get('reporter_id');
                if (!$reporter_id) {
                    return;
                }

                $profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'issue');
                Auth::redirect(
                    "list.php?reporter={$reporter_id}" .
                    "&hide_closed=1&rows={$this->rows}&sort_by={$profile['sort_by']}" .
                    "&sort_order={$profile['sort_order']}&nosave=1"
                );
                break;

            case 'clear':
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                Auth::redirect('list.php');
                break;

            case 'clearandfilter':
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                Auth::redirect('list.php?' . str_replace('view=clearandfilter&', '', $_SERVER['QUERY_STRING']));
                break;
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        if (!empty($_REQUEST['nosave'])) {
            $options = Search::saveSearchParams(false);
        } else {
            $options = Search::saveSearchParams();
        }

        $options += $this->options_override;
        $options = array_merge($options, $this->options_override);

        $this->tpl->assign(
            array(
                'options' => $options,
                'sorting' => Search::getSortingInfo($options),
            )
        );

        // generate options for assign list. If there are groups and user is above a customer, include groups
        $groups = Group::getAssocList($this->prj_id);
        $users = Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER);
        $assign_options = array(
            '' => ev_gettext('Any'),
            '-1' => ev_gettext('un-assigned'),
            '-2' => ev_gettext('myself and un-assigned'),
        );
        if (Auth::isAnonUser()) {
            unset($assign_options['-2']);
        } elseif (User::getGroupID($this->usr_id)) {
            $assign_options['-3'] = ev_gettext('myself and my group');
            $assign_options['-4'] = ev_gettext('myself, un-assigned and my group');
        }
        if ((count($groups) > 0) && (Auth::getCurrentRole() > User::ROLE_CUSTOMER)) {
            foreach ($groups as $grp_id => $grp_name) {
                $assign_options["grp:$grp_id"] = ev_gettext('Group') . ': ' . $grp_name;
            }
        }
        $assign_options += $users;

        $prefs = Prefs::get($this->usr_id);
        $list = Search::getListing($this->prj_id, $options, $this->pagerRow, $this->rows);
        $this->tpl->assign(
            array(
                'list' => $list['list'],
                'list_info' => $list['info'],
                'csv_data' => base64_encode($list['csv']),
                'match_modes' => Search::getMatchModes(),
                'supports_excerpts' => Search::doesBackendSupportExcerpts(),
                'columns' => Display_Column::getColumnsToDisplay($this->prj_id, 'list_issues'),
                'priorities' => Priority::getAssocList($this->prj_id),
                'severities' => Severity::getAssocList($this->prj_id),
                'status' => Status::getAssocStatusList($this->prj_id),
                'assign_options' => $assign_options,
                'custom' => Filter::getAssocList(),
                'csts' => Filter::getListing(true),
                'active_filters' => Filter::getActiveFilters($options),
                'categories' => Category::getAssocList($this->prj_id),
                'releases' => Release::getAssocList($this->prj_id, true),
                'reporters' => Project::getReporters($this->prj_id),
                'products' => Product::getAssocList(false),
                'refresh_rate' => $prefs['list_refresh_rate'] * 60,
                'refresh_page' => 'list.php',
            )
        );

        // items needed for bulk update tool
        if (Auth::getCurrentRole() > User::ROLE_DEVELOPER) {

            if (Workflow::hasWorkflowIntegration($this->prj_id)) {
                $open_statuses = Workflow::getAllowedStatuses($this->prj_id);
            } else {
                $open_statuses = Status::getAssocStatusList($this->prj_id, false);
            }
            $this->tpl->assign(
                array(
                    'users' => $users,
                    'open_status' => $open_statuses,
                    'closed_status' => Status::getClosedAssocList($this->prj_id),
                    'available_releases' => Release::getAssocList($this->prj_id),
                )
            );
        }
    }
}
