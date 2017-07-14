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

use Access;
use Auth;
use AuthCookie;
use Category;
use CRM;
use Custom_Field;
use Date_Helper;
use Group;
use Issue;
use Issue_Lock;
use Notification;
use Prefs;
use Priority;
use Product;
use Project;
use Release;
use Resolution;
use Severity;
use Status;
use Template_Helper;
use User;
use Workflow;

class UpdateController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'update.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var array */
    private $details;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('id');
        $this->cat = $request->request->getAlpha('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();
        $this->role_id = Auth::getCurrentRole();

        $this->details = $details = Issue::getDetails($this->issue_id);
        if (!$details) {
            $this->error(ev_gettext('Error: The issue #%1$s could not be found.', $this->issue_id));
        }

        $associated_projects = array_keys(Project::getAssocList($this->usr_id));

        // check if the requested issue is a part of the 'current' project. If it doesn't
        // check if issue exists in another project and if it does, switch projects
        $iss_prj_id = Issue::getProjectID($this->issue_id);
        if ($iss_prj_id && $iss_prj_id != $this->prj_id && in_array($iss_prj_id, $associated_projects)) {
            AuthCookie::setProjectCookie($iss_prj_id);
            $this->messages->addInfoMessage(ev_gettext('Note: Project automatically switched to "%1$s" from "%2$s".', Project::getName($this->prj_id), Project::getName($iss_prj_id)));
            $this->prj_id = $iss_prj_id;
        }

        // in the case of a customer user, also need to check if that customer has access to this issue
        if (($this->role_id == User::ROLE_CUSTOMER) && (!$details || (User::getCustomerID($this->usr_id) != $details['iss_customer_id']))
            || !Issue::canAccess($this->issue_id, $this->usr_id)
            || !($this->role_id > User::ROLE_REPORTER)
            || !Issue::canUpdate($this->issue_id, $this->usr_id)
        ) {
            $this->error(ev_gettext('Sorry, you do not have the required privileges to update this issue.'));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        Workflow::prePage($this->prj_id, 'update');

        if (Issue_Lock::acquire($this->issue_id, $this->usr_id)) {
            $issue_lock = false;
        } else {
            $issue_lock = Issue_Lock::getInfo($this->issue_id);
            $issue_lock['locker'] = User::getDetails($issue_lock['usr_id']);
            $issue_lock['expires_formatted_time'] = Date_Helper::getFormattedDate($issue_lock['expires']);
        }
        $this->tpl->assign('issue_lock', $issue_lock);

        $cancel_update = isset($_POST['cancel']);

        if ($cancel_update) {
            // be sure not to unlock somebody else's lock
            if (!$issue_lock) {
                Issue_Lock::release($this->issue_id);
                $this->messages->addInfoMessage(ev_gettext('Cancelled Issue #%1$s update.', $this->issue_id));
            }

            $this->redirect(APP_RELATIVE_URL . 'view.php?id=' . $this->issue_id);
        }

        if ($this->cat == 'update') {
            if ($issue_lock) {
                $this->error(ev_gettext("Sorry, you can't update issue if it's locked by another user"));
            }

            $this->updateAction();
        }
    }

    private function updateAction()
    {
        $res = Issue::update($this->issue_id);
        Issue_Lock::release($this->issue_id);

        if ($res == -1) {
            $this->error(ev_gettext('Sorry, an error happened while trying to update this issue.'));
        }

        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Thank you, issue #%1$s was updated successfully.', $this->issue_id));
        }

        $notify_list = Notification::getLastNotifiedAddresses($this->issue_id);
        $has_duplicates = Issue::hasDuplicates($this->issue_id);

        /**
         *  FIXME: refactor this global $errors
         * it's filled by Issue::updateAssociatedIssuesRelations via Issue::update
         * https://github.com/eventum/eventum/blob/v3.0.6/lib/eventum/class.issue.php#L1474
         * @see Issue::update()
         * @see Issue::updateAssociatedIssuesRelations()
         */
        global $errors;
        if ($has_duplicates || count($errors) > 0 || count($notify_list) > 0) {
            $update_tpl = new Template_Helper();
            $update_tpl->setTemplate('include/update_msg.tpl.html');
            $update_tpl->assign('update_result', $res);
            $update_tpl->assign('errors', $errors);
            $update_tpl->assign('notify_list', $notify_list);
            if ($has_duplicates) {
                $update_tpl->assign('has_duplicates', 'yes');
            }
            $this->messages->addHtmlBoxMessage($update_tpl->getTemplateContents(false));
        }

        $this->redirect(APP_RELATIVE_URL . 'view.php?id=' . $this->issue_id);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        // if currently selected release is in the past, manually add it to list
        $releases = Release::getAssocList($this->prj_id);
        if ($this->details['iss_pre_id'] != 0 && empty($releases[$this->details['iss_pre_id']])) {
            $releases = [$this->details['iss_pre_id'] => $this->details['pre_title']] + $releases;
        }

        if (Workflow::hasWorkflowIntegration($this->prj_id)) {
            $statuses = Workflow::getAllowedStatuses($this->prj_id, $this->issue_id);
            // if currently selected release is not on list, go ahead and add it.
        } else {
            $statuses = Status::getAssocStatusList($this->prj_id, false);
        }

        if (!empty($this->details['iss_sta_id']) && empty($statuses[$this->details['iss_sta_id']])) {
            $statuses[$this->details['iss_sta_id']] = Status::getStatusTitle($this->details['iss_sta_id']);
        }

        $priorities = Priority::getAssocList($this->prj_id);
        $categories = Category::getAssocList($this->prj_id);
        $severities = Severity::getList($this->prj_id);
        $this->tpl->assign(
            [
                'user_prefs' => Prefs::get($this->usr_id),
                'issue_id' => $this->issue_id,
                'issue' => $this->details,
                'extra_title' => ev_gettext('Update Issue #%1$s', $this->issue_id),
                'subscribers' => Notification::getSubscribers($this->issue_id),
                'categories' => $categories,
                'priorities' => $priorities,
                'severities' => $severities,
                'status' => $statuses,
                'releases' => $releases,
                'resolutions' => Resolution::getAssocList(),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'one_week_ts' => time() + (7 * Date_Helper::DAY),
                'groups' => Group::getAssocList($this->prj_id),
                'current_year' => date('Y'),
                'products' => Product::getList(false),
                'grid' => $this->getColumnsForDisplay(
                    $this->details, $this->prj_id, $this->role_id, $categories, $priorities, $severities
                ),
                'custom_fields' => Custom_Field::getListByIssue($this->prj_id, $this->issue_id, $this->usr_id, false, true),
                'usr_role_id' => User::getRoleByUser($this->usr_id, $this->prj_id),
            ]
        );
    }

    /**
     * @param int $prj_id
     * @param int $role_id
     */
    private function getColumnsForDisplay($details, $prj_id, $role_id, $categories, $priorities, $severities)
    {
        $columns = [0 => [], 1 => []];
        if (CRM::hasCustomerIntegration($prj_id) and !empty($details['iss_customer_id'])) {
            $columns[0][] = [
                'title' => 'Customer',
                'field' => 'customer_0',
            ];
            $columns[1][] = [
                'title' => 'Customer Contract',
                'field' => 'customer_1',
            ];
        }

        if ($categories) {
            $columns[0][] = [
                'title' => ev_gettext('Category'),
                'data' => $details['prc_title'],
                'field' => 'category',
            ];
        }
        $columns[0][] = [
            'title' => ev_gettext('Status'),
            'data' => $details['sta_title'],
            'data_bgcolor' => $details['status_color'],
            'field' => 'status',
        ];

        if ($severities) {
            $columns[0][] = [
                'title' => ev_gettext('Severity'),
                'data' => $details['sev_title'],
                'field' => 'severity',
            ];
        }

        if ($priorities
            && ((!isset($issue_fields_display['priority']))
                || ($issue_fields_display['priority'] != false))
        ) {
            if ((isset($issue_fields_display['priority']['min_role']))
                && ($issue_fields_display['priority']['min_role'] > User::ROLE_CUSTOMER)
            ) {
                $bgcolor = APP_INTERNAL_COLOR;
            } else {
                $bgcolor = '';
            }
            $columns[0][] = [
                'title' => ev_gettext('Priority'),
                'data' => $details['pri_title'],
                'title_bgcolor' => $bgcolor,
                'field' => 'priority',
            ];
        }

        if (Release::getAssocList($prj_id) && ($role_id != User::ROLE_CUSTOMER)) {
            $columns[0][] = [
                'title' => ev_gettext('Scheduled Release'),
                'title_bgcolor' => APP_INTERNAL_COLOR,
                'field' => 'scheduled_release',
            ];
        }
        if ($role_id > User::ROLE_CUSTOMER) {
            $columns[0][] = [
                'title' => ev_gettext('Resolution'),
                'data' => $details['iss_resolution'],
                'field' => 'resolution',
            ];
        }

        if ((!isset($issue_fields_display['percent_complete']))
            || ($issue_fields_display['percent_complete'] != false)
        ) {
            $columns[0][] = [
                'title' => ev_gettext('Percentage Complete'),
                'data' => (empty($details['iss_percent_complete']) ? 0 : $details['iss_percent_complete']) . '%',
                'field' => 'percentage_complete',
            ];
        }
        $columns[0][] = [
            'title' => ev_gettext('Reporter'),
            'field' => 'reporter',
        ];

        if (Product::getAssocList(false)) {
            $columns[0][] = [
                'title' => ev_gettext('Product'),
                'field' => 'product',
            ];
            $columns[0][] = [
                'title' => ev_gettext('Product Version'),
                'field' => 'product_version',
            ];
        }
        if (Access::canChangeAssignee($this->issue_id, $this->usr_id)) {
            $columns[0][] = [
                'title' => ev_gettext('Assignment'),
                'data' => $details['assignments'],
                'field' => 'assignment',
            ];
        }

        $columns[1][] = [
            'title' => ev_gettext('Notification List'),
            'field' => 'notification_list',
        ];
        $columns[1][] = [
            'title' => ev_gettext('Submitted Date'),
            'data' => $details['iss_created_date'],
            'field' => 'iss_created_date',
        ];
        $columns[1][] = [
            'title' => ev_gettext('Last Updated Date'),
            'data' => $details['iss_updated_date'],
            'field' => 'iss_updated_date',
        ];
        $columns[1][] = [
            'title' => ev_gettext('Associated Issues'),
            'field' => 'associated_issues',
        ];

        if ((!isset($issue_fields_display['expected_resolution']))
            || ($issue_fields_display['expected_resolution'] != false)
        ) {
            $columns[1][] = [
                'title' => ev_gettext('Expected Resolution Date'),
                'field' => 'expected_resolution',
            ];
        }

        if ((!isset($issue_fields_display['estimated_dev_time']))
            || ($issue_fields_display['estimated_dev_time'] != false)
        ) {
            $columns[1][] = [
                'title' => ev_gettext('Estimated Dev. Time'),
                'data' => $details['iss_dev_time'] . empty($details['iss_dev_time']) ? '' : ' hours',
                'field' => 'estimated_dev_time',
            ];
        }

        if ($role_id > User::ROLE_CUSTOMER) {
            $columns[1][] = [
                'title' => ev_gettext('Duplicates'),
                'field' => 'duplicates',
                'title_bgcolor' => APP_INTERNAL_COLOR,
            ];
            $columns[1][] = [
                'title' => ev_gettext('Authorized Repliers'),
                'field' => 'authorized_repliers',
                'title_bgcolor' => APP_INTERNAL_COLOR,
            ];
        }

        if ($role_id > User::ROLE_CUSTOMER && Group::getAssocList($prj_id)) {
            $columns[1][] = [
                'title' => ev_gettext('Group'),
                'data' => isset($details['group']) ? $details['group']['grp_name'] : '',
                'title_bgcolor' => APP_INTERNAL_COLOR,
                'field' => 'group',
            ];
        }

        return $columns;
    }
}
