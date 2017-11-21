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

use Auth;
use Category;
use Custom_Field;
use Display_Column;
use Filter;
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
    protected $usr_id;

    /** @var int */
    protected $prj_id;

    /** @var int|string */
    private $rows;

    /** @var int */
    private $pagerRow;

    /** @var array */
    private $options_override;

    /** @var bool */
    private $nosave;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->nosave = (bool) $this->getRequest()->get('nosave');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();
        $this->pagerRow = (int) Search::getParam('pagerRow');

        $rows = Search::getParam('rows');
        $this->rows = ($rows == 'ALL' ? $rows : (int) $rows) ?: APP_DEFAULT_PAGER_SIZE;

        $this->options_override = [];
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
                $this->redirect(
                    'list.php', [
                        'users' => $this->usr_id,
                        'hide_closed' => 1,
                        'rows' => $this->rows,
                        'sort_by' => $profile['sort_by'],
                        'sort_order' => $profile['sort_order'],
                    ]
                );
                break;

            case 'customer':
                $customer_id = $request->get('customer_id');
                if (!$customer_id) {
                    return;
                }
                $this->options_override = [
                    'customer_id' => $customer_id,
                    'rows' => $this->rows,
                ];
                if (Search::getParam('hide_closed', true) === '') {
                    $this->options_override['hide_closed'] = 1;
                }
                $this->nosave = true;
                break;

            case 'customer_all':
                $customer_id = $request->get('customer_id');
                if (!$customer_id) {
                    return;
                }

                $this->options_override = [
                    'customer_id' => $customer_id,
                    'rows' => $this->rows,
                ];
                if (Search::getParam('hide_closed', true) === '') {
                    $this->options_override['hide_closed'] = 0;
                }
                $this->nosave = true;
                $profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'issue');
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                $this->redirect(
                    'list.php', [
                        'customer_id' => $customer_id,
                        'hide_closed' => 1,
                        'rows' => $this->rows,
                        'sort_by' => $profile['sort_by'],
                        'sort_order' => $profile['sort_order'],
                        'nosave' => 1,
                    ]
                );
                break;

            case 'reporter':
                $reporter_id = (int) $request->get('reporter_id');
                if (!$reporter_id) {
                    return;
                }

                $profile = Search_Profile::getProfile($this->usr_id, $this->prj_id, 'issue');
                $this->redirect(
                    'list.php', [
                        'reporter' => $reporter_id,
                        'hide_closed' => 1,
                        'rows' => $this->rows,
                        'sort_by' => $profile['sort_by'],
                        'sort_order' => $profile['sort_order'],
                        'nosave' => 1,
                    ]
                );
                break;

            case 'clear':
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                $this->redirect('list.php');
                break;

            case 'clearandfilter':
                Search_Profile::remove($this->usr_id, $this->prj_id, 'issue');
                $params = $request->query->all();
                unset($params['view']);
                $this->redirect('list.php', $params);
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        if ($this->nosave) {
            $options = Search::saveSearchParams(false);
        } else {
            $options = Search::saveSearchParams();
        }

        $options += $this->options_override;
        $options = array_merge($options, $this->options_override);

        $users = Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER);
        $assign_options = $this->assign->getAssignOptions($users);

        // MARIADB-CSTM: Display number of issues from other projects
        $this->tpl->assign('other_project_issues', \MariaDB_Helper::getIssuesFromOtherProjects());

        $prefs = Prefs::get($this->usr_id);
        $list = Search::getListing($this->prj_id, $options, $this->pagerRow, $this->rows);
        $this->tpl->assign(
            [
                'options' => $options,
                'sorting' => $this->getSortingInfo($options),
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
            ]
        );

        // items needed for bulk update tool
        if (Auth::getCurrentRole() > User::ROLE_DEVELOPER) {
            if (Workflow::hasWorkflowIntegration($this->prj_id)) {
                $open_statuses = Workflow::getAllowedStatuses($this->prj_id);
            } else {
                $open_statuses = Status::getAssocStatusList($this->prj_id, false);
            }
            $this->tpl->assign(
                [
                    'users' => $users,
                    'open_status' => $open_statuses,
                    'closed_status' => Status::getClosedAssocList($this->prj_id),
                    'available_releases' => Release::getAssocList($this->prj_id),
                ]
            );
        }
    }

    /**
     * Method used to get the current sorting options used in the grid layout
     * of the issue listing page.
     *
     * @param   array $options The current search parameters
     * @return  array The sorting options
     */
    private function getSortingInfo($options)
    {
        $uri = $this->getRequest()->getBaseUrl();

        $custom_fields = Custom_Field::getFieldsToBeListed(Auth::getCurrentProject());

        // default order for last action date, priority should be descending
        // for textual fields, like summary, ascending is reasonable
        $fields = [
            'pri_rank' => 'desc',
            'sev_rank' => 'asc',
            'iss_id' => 'desc',
            'iss_customer_id' => 'desc',
            'prc_title' => 'asc',
            'sta_rank' => 'asc',
            'iss_created_date' => 'desc',
            'iss_summary' => 'asc',
            'last_action_date' => 'desc',
            'usr_full_name' => 'asc',
            'iss_expected_resolution_date' => 'desc',
            'pre_title' => 'asc',
            'assigned' => 'asc',
            'grp_name' => 'asc',
            'iss_percent_complete' => 'asc',
        ];

        foreach ($custom_fields as $fld_id => $fld_name) {
            $fields['custom_field_' . $fld_id] = 'desc';
        }

        $sortfields = array_combine(array_keys($fields), array_keys($fields));
        $sortfields['pre_title'] = 'pre_scheduled_date';
        $sortfields['assigned'] = 'isu_usr_id';

        $items = [
            'links' => [],
            'order' => [],
        ];
        $current_sort_by = $options['sort_by'];
        $current_sort_order = $options['sort_order'];
        foreach ($sortfields as $field => $sortfield) {
            $sort_order = $fields[$field];
            if ($current_sort_by == $sortfield) {
                if (strtolower($current_sort_order) == 'asc') {
                    $sort_order = 'desc';
                } else {
                    $sort_order = 'asc';
                }
                $items['order'][$field] = strtolower($current_sort_order);
            }
            $options['sort_by'] = $sortfield;
            $options['sort_order'] = $sort_order;
            $items['links'][$field] = $uri . '?' . Filter::buildUrl(Filter::getFiltersInfo(), $options, false, true);
        }

        return $items;
    }
}
