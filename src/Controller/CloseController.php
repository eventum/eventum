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
use Contract;
use CRM;
use Custom_Field;
use Issue;
use Notification;
use Resolution;
use Status;
use Template_Helper;
use Time_Tracking;
use User;

class CloseController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'close.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var string */
    private $cat;

    /** @var array */
    private $details;

    /** @var Contract */
    private $contract;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('id');
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Auth::getCurrentProject();
        $this->role_id = Auth::getCurrentRole();

        // FIXME: ROLE_CUSTOMER check superfluous regarding Issue::canAccess?
        if ($this->role_id == User::ROLE_CUSTOMER || !Issue::canAccess($this->issue_id, $this->usr_id)) {
            // FIXME: use generic 'access denied page'?
            $this->tpl->assign('auth_customer', 'denied');
            $this->tpl->displayTemplate();
            exit;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        $extra_title = ev_gettext('Close Issue #%1$s', $this->issue_id);
        $this->tpl->assign(
            [
                'extra_title' => $extra_title,
            ]
        );

        if (!Issue::exists($this->issue_id, false)) {
            $this->tpl->assign('no_issue', true);
            $this->tpl->displayTemplate();
            exit;
        }

        $this->details = Issue::getDetails($this->issue_id);

        $notification_list = Notification::getSubscribers($this->issue_id, 'closed');
        $this->tpl->assign('notification_list_all', $notification_list['all']);

        $notification_list_internal = Notification::getSubscribers($this->issue_id, 'closed', User::ROLE_USER);
        $this->tpl->assign('notification_list_internal', $notification_list_internal['all']);

        if ($this->cat == 'close') {
            $this->closeAction();
        }
    }

    private function closeAction()
    {
        $request = $this->getRequest();
        $post = $request->request;

        Custom_Field::updateFromPost();
        $res = Issue::close(
            Auth::getUserID(), $this->issue_id, $request->get('send_notification'), $request->get('resolution'),
            $request->get('status'), $request->get('reason'), $request->get('notification_list')
        );

        if ($post->get('time_spent')) {
            $this->addTimeEntry();
        }

        // FIXME: is CRM::hasCustomerIntegration check necessary?
        if (isset($this->details['contract']) && (CRM::hasCustomerIntegration($this->prj_id))) {
            $this->contract = $this->details['contract'];
            if ($this->contract->hasPerIncident()) {
                $redeem = $request->get('redeem');
                $this->contract->updateRedeemedIncidents($this->issue_id, $redeem);
            }
        }

        $this->tpl->assign('close_result', $res);
        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Thank you, the issue was closed successfully'));
            $this->displayNotifiedUsers(Notification::getLastNotifiedAddresses($this->issue_id));
            $this->redirect(APP_RELATIVE_URL . 'view.php?id=' . $this->issue_id);
        }
    }

    private function displayNotifiedUsers($notify_list)
    {
        if (!$notify_list) {
            return;
        }

        $update_tpl = new Template_Helper();
        $update_tpl->setTemplate('include/notified_list.tpl.html');
        $update_tpl->assign('notify_list', $notify_list);
        $this->messages->addHtmlBoxMessage($update_tpl->getTemplateContents(false));
    }

    private function addTimeEntry()
    {
        $post = $this->getRequest()->request;

        $date = (array) $post->get('date');
        $ttc_id = $post->getInt('category');
        $time_spent = $post->getInt('time_spent');
        $summary = ev_gettext('Time entry inserted when closing issue.');
        Time_Tracking::addTimeEntry($this->issue_id, $ttc_id, $time_spent, $date, $summary);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $custom_fields = Custom_Field::getListByIssue($this->prj_id, $this->issue_id, $this->usr_id, 'close_form', true);

        $this->tpl->assign(
            [
                'statuses' => Status::getClosedAssocList($this->prj_id),
                'resolutions' => Resolution::getAssocList(),
                'time_categories' => Time_Tracking::getAssocCategories($this->prj_id),
                'notify_list' => Notification::getLastNotifiedAddresses($this->issue_id),
                'custom_fields' => $custom_fields,
                'issue_id' => $this->issue_id,
            ]
        );

        if ($this->contract && $this->contract->hasPerIncident()) {
            $this->tpl->assign(
                [
                    'redeemed' => $this->contract->getRedeemedIncidentDetails($this->issue_id),
                    'incident_details' => $this->details['customer']['incident_details'],
                ]
            );
        }
    }
}
