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
use Authorized_Replier;
use Category;
use CRM;
use Custom_Field;
use Date_Helper;
use Draft;
use Email_Account;
use Eventum\Attachment\AttachmentManager;
use Eventum\Db\Doctrine;
use Group;
use Issue;
use Issue_Field;
use Link_Filter;
use Note;
use Notification;
use Partner;
use Phone_Support;
use Product;
use Project;
use Release;
use Search;
use Severity;
use Status;
use Support;
use Time_Tracking;
use User;
use Workflow;

class ViewController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'view.tpl.html';

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var int */
    private $issue_id;

    /** @var array */
    private $details;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        return true;
    }

    private function checkProject()
    {
        $associated_projects = array_keys(Project::getAssocList($this->usr_id));

        // check if the requested issue is a part of the 'current' project. If it doesn't
        // check if issue exists in another project and if it does, switch projects
        $iss_prj_id = Issue::getProjectID($this->issue_id);
        $auto_switched_from = false;
        if (!empty($iss_prj_id) && $iss_prj_id != $this->prj_id && in_array($iss_prj_id, $associated_projects)) {
            AuthCookie::setProjectCookie($iss_prj_id);
            $auto_switched_from = $this->prj_id;
            $this->prj_id = $iss_prj_id;
        }

        // check if the requested issue is a part of one of the projects
        // associated with this user
        if (!in_array($iss_prj_id, $associated_projects)) {
            $this->error(ev_gettext('Sorry, you do not have the required privileges to view this issue.'));
        }

        if ($auto_switched_from) {
            $this->tpl->assign(
                [
                    'project_auto_switched' => 1,
                    'old_project' => Project::getName($auto_switched_from),
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();
        $this->role_id = Auth::getCurrentRole();

        $this->checkProject();

        $this->details = $details = Issue::getDetails($this->issue_id);
        if (!$details) {
            $this->error(ev_gettext('Error: The issue #%1$s could not be found.', $this->issue_id));
        }

        // in the case of a customer user, also need to check if that customer has access to this issue
        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            $this->error(ev_gettext('Sorry, you do not have the required privileges to view this issue.'));
        }

        // if the issue has a different customer then the currently selected one, switch customers
        if (Auth::getCurrentRole() == User::ROLE_CUSTOMER
            && Auth::getCurrentCustomerID() != $details['iss_customer_id']
        ) {
            Auth::setCurrentCustomerID($details['iss_customer_id']);
            // TRANSLATORS: %1 - customer name
            $message = ev_gettext("Active customer changed to '%s'", $details['customer']->getName());
            $this->messages->addInfoMessage($message);
            $this->redirect(APP_RELATIVE_URL . 'view.php', ['id' => $this->issue_id]);
        }

        if ($details['iss_prj_id'] != $this->prj_id) {
            $this->error(ev_gettext('Error: The issue #%1$s could not be found.', $this->issue_id));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $options = Search::saveSearchParams();
        $sides = Issue::getSides($this->issue_id, $options);

        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'issue' => $this->details,

                // TRANSLATORS: Page HTML title: %1 = issue id, %2 = issue summary
                'extra_title' => ev_gettext('#%1$s - %2$s', $this->issue_id, $this->details['iss_summary']),

                'next_issue' => $sides['next'],
                'previous_issue' => $sides['previous'],
                'subscribers' => Notification::getSubscribers($this->issue_id),
                'custom_fields' => Custom_Field::getListByIssue($this->prj_id, $this->issue_id),
                'files' => AttachmentManager::getList($this->issue_id),
                'emails' => Support::getEmailsByIssue($this->issue_id),
                'zones' => Date_Helper::getTimezoneList(),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'ema_id' => Email_Account::getEmailAccount(),
                'max_attachment_size' => AttachmentManager::getMaxAttachmentSize(),
                'quarantine' => Issue::getQuarantineInfo($this->issue_id),
                'grid' => $this->getColumnsForDisplay(),
                'can_update' => Access::canUpdateIssue($this->issue_id, $this->usr_id),
                'enabled_partners' => Partner::getPartnersByProject($this->prj_id),
                'partners' => Partner::getPartnersByIssue($this->issue_id),
                'issue_access' => Access::getIssueAccessArray($this->issue_id, $this->usr_id),
                'is_user_notified' => Notification::isUserNotified($this->issue_id, $this->usr_id),
                'access_level_name' => Access::getAccessLevelName($this->details['iss_access_level']),
            ]
        );

        if ($this->role_id != User::ROLE_CUSTOMER) {
            $this->setTemplateNonCustomer();
        }
    }

    /**
     * Set template variables for non-customers
     */
    private function setTemplateNonCustomer()
    {
        $cookie = $this->getRequest()->cookies;
        $show_all_drafts = $cookie->get('show_all_drafts') == 1;

        if (Workflow::hasWorkflowIntegration($this->prj_id)) {
            $statuses = Workflow::getAllowedStatuses($this->prj_id, $this->issue_id);
            // if currently selected release is not on list, go ahead and add it.
        } else {
            $statuses = Status::getAssocStatusList($this->prj_id, false);
        }

        if (!empty($details['iss_sta_id']) && empty($statuses[$details['iss_sta_id']])) {
            $statuses[$details['iss_sta_id']] = Status::getStatusTitle($details['iss_sta_id']);
        }

        $time_entries = Time_Tracking::getTimeEntryListing($this->issue_id);

        $this->tpl->assign(
            [
                'notes' => Note::getListing($this->issue_id),
                'is_user_assigned' => Issue::isAssignedToUser($this->issue_id, $this->usr_id),
                'is_user_authorized' => Authorized_Replier::isUserAuthorizedReplier($this->issue_id, $this->usr_id),
                'phone_entries' => Phone_Support::getListing($this->issue_id),
                'phone_categories' => Phone_Support::getCategoryAssocList($this->prj_id),
                'checkins' => $this->getIssueCommits($this->issue_id),
                'time_categories' => Time_Tracking::getAssocCategories($this->prj_id),
                'time_entries' => $time_entries['list'],
                'total_time_by_user' => $time_entries['total_time_by_user'],
                'total_time_spent' => $time_entries['total_time_spent'],
                'statuses' => $statuses,
                'drafts' => Draft::getList($this->issue_id, $show_all_drafts),
                'groups' => Group::getAssocList($this->prj_id),
            ]
        );
    }

    private function getColumnsForDisplay()
    {
        $details = $this->details;
        $display = Issue_Field::getFieldsToDisplay($this->issue_id, 'view_issue');

        // figure out what data to show in each column
        $columns = [0 => [], 1 => []];

        if (CRM::hasCustomerIntegration($this->prj_id) and !empty($details['iss_customer_id'])) {
            $columns[0][] = [
                'title' => 'Customer',
                'field' => 'customer_0',
            ];
            $columns[1][] = [
                'title' => 'Customer Contract',
                'field' => 'customer_1',
            ];
        }

        if (Category::getList($this->prj_id)) {
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

        if (Severity::getList($this->prj_id)) {
            $columns[0][] = [
                'title' => ev_gettext('Severity'),
                'data' => $details['sev_title'],
                'field' => 'severity',
            ];
        }

        if (!isset($display['priority']) || $display['priority'] != false) {
            if (isset($display['priority']['min_role']) && $display['priority']['min_role'] > User::ROLE_CUSTOMER) {
                $bgcolor = APP_INTERNAL_COLOR;
            } else {
                $bgcolor = '';
            }
            $columns[0][] = [
                'title' => ev_gettext('Priority'),
                'pri_title' => $details['pri_title'],
                'pri_icon' => $details['pri_icon'],
                'title_bgcolor' => $bgcolor,
                'field' => 'priority',
            ];
        }

        if ($this->role_id != User::ROLE_CUSTOMER && Release::getAssocList($this->prj_id)) {
            $columns[0][] = [
                'title' => ev_gettext('Scheduled Release'),
                'data' => $details['pre_title'],
                'title_bgcolor' => APP_INTERNAL_COLOR,
            ];
        }

        $columns[0][] = [
            'title' => ev_gettext('Resolution'),
            'data' => $details['iss_resolution'],
            'field' => 'resolution',
        ];

        if (!isset($display['percent_complete']) || $display['percent_complete'] != false) {
            $percent = empty($details['iss_percent_complete']) ? 0 : $details['iss_percent_complete'];
            $columns[0][] = [
                'title' => ev_gettext('Percentage Complete'),
                'percent' => $percent,
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
        }

        $columns[0][] = [
            'title' => ev_gettext('Assignment'),
            'data' => $details['assignments'],
            'field' => 'assignment',
        ];

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

        if (!isset($display['expected_resolution']) || $display['expected_resolution'] != false) {
            $columns[1][] = [
                'title' => ev_gettext('Expected Resolution Date'),
                'field' => 'expected_resolution',
            ];
        }

        if (!isset($display['estimated_dev_time']) || $display['estimated_dev_time'] != false) {
            $columns[1][] = [
                'title' => ev_gettext('Estimated Dev. Time'),
                'data' => empty($details['iss_dev_time']) ? '' : $details['iss_dev_time'] . ' hours',
                'field' => 'estimated_dev_time',
            ];
        }

        if ($this->role_id > User::ROLE_CUSTOMER) {
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

        if ($this->role_id > User::ROLE_CUSTOMER && Group::getAssocList($this->prj_id)) {
            $columns[1][] = [
                'title' => ev_gettext('Group'),
                'data' => isset($details['group']) ? $details['group']['grp_name'] : '',
                'title_bgcolor' => APP_INTERNAL_COLOR,
            ];
        }

        return $columns;
    }

    /**
     * Get commits related to issue formatted to array for templating
     *
     * @param   int $issue_id The issue ID
     * @return  array The list of checkins
     */
    private function getIssueCommits($issue_id)
    {
        $commit = Doctrine::getIssueRepository()->getCommits($issue_id);

        $checkins = [];
        foreach ($commit as $c) {
            $scm = $c->getCommitRepo();

            $checkin = $c->toArray();
            $checkin['isc_commit_date'] = Date_Helper::convertDateGMT($checkin['com_commit_date']);
            $checkin['isc_commit_msg'] = Link_Filter::processText(
                Issue::getProjectID($issue_id), nl2br(htmlspecialchars($checkin['com_message']))
            );
            $checkin['author'] = $c->getAuthor();
            $checkin['project_name'] = $c->getProjectName();
            $checkin['branch'] = $c->getBranch();
            $checkin['commit_short'] = $c->getChangeset(true);
            $checkin['changeset_url'] = $scm->getChangesetUrl($c);
            $checkin['branch_url'] = $scm->getBranchUrl($c);
            $checkin['project_url'] = $scm->getProjectUrl($c);
            $checkin['files'] = [];

            foreach ($c->getFiles() as $cf) {
                $f = $cf->toArray();

                $f['added'] = $cf->isAdded();
                $f['removed'] = $cf->isRemoved();
                $f['modified'] = $cf->isModified();

                // flag indicating whether file has versions
                $f['versions'] = $cf->hasVersions();

                // fill for url builder
                $f['project_name'] = $c->getProjectName();

                // fill urls
                $f['checkout_url'] = $scm->getCheckoutUrl($c, $cf);
                $f['diff_url'] = $scm->getDiffUrl($c, $cf);
                $f['scm_log_url'] = $scm->getLogUrl($c, $cf);

                $checkin['files'][] = $f;
            }

            $checkins[] = $checkin;
        }

        return $checkins;
    }
}
