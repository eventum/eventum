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
use CRMException;
use Custom_Field;
use Date_Helper;
use Eventum\Attachment\AttachmentManager;
use Group;
use Issue;
use Mail_Helper;
use Priority;
use Product;
use Project;
use Release;
use Setup;
use Severity;
use Support;
use User;

class NewController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'new.tpl.html';

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var string */
    private $cat;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->getAlpha('cat') ?: $request->query->getAlpha('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();

        if (!Access::canCreateIssue($this->usr_id)) {
            $this->redirect('main.php');
        }

        // If the project has changed since the new issue form was requested, then change it back
        $issue_prj_id = (int) $this->getRequest()->get('prj_id');
        if ($issue_prj_id > 0 && $issue_prj_id != $this->prj_id) {
            // Switch the project back
            $assigned_projects = Project::getAssocList($this->usr_id);
            if (!isset($assigned_projects[$issue_prj_id])) {
                $this->error(ev_gettext('There was an error creating your issue.'));
//                $tpl->assign('error_msg', '1');
            }

            $this->prj_id = $issue_prj_id;
            AuthCookie::setProjectCookie($this->prj_id);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $this->tpl->assign('new_issue_id', '');

        if (Auth::getCurrentRole() == User::ROLE_CUSTOMER && ($crm = CRM::getInstance($this->prj_id))) {
            $customer_id = Auth::getCurrentCustomerID();
            $customer = $crm->getCustomer($customer_id);
            $new_issue_message = $customer->getNewIssueMessage();
            if ($new_issue_message) {
                $this->messages->addInfoMessage($new_issue_message);
            }
        }

        if ($this->cat === 'report') {
            $this->reportAction();
        } elseif ($this->cat === 'associate') {
            $this->associateAction();
        }
    }

    private function reportAction(): void
    {
        $res = Issue::createFromPost();
        if ($res != -1) {
            // redirect to view issue page
            $this->messages->addInfoMessage(ev_gettext('Your issue was created successfully.'));
            $this->redirect(Setup::getRelativeUrl() . 'view.php?id=' . $res);
        }

        // need to show everything again
        $this->messages->addErrorMessage(ev_gettext('There was an error creating your issue.'));
        $this->tpl->assign('error_msg', '1');
    }

    private function associateAction(): void
    {
        $request = $this->getRequest();
        $item = $request->query->get('item');
        if (!$item) {
            return;
        }

        $res = Support::getListDetails($item);
        $this->tpl->assign('emails', $res);
        $this->tpl->assign('attached_emails', implode(',', $item));

        $crm = CRM::getInstance($this->prj_id);

        if ($crm) {
            // also need to guess the contact_id from any attached emails
            try {
                $info = $crm->getCustomerInfoFromEmails($item);
                $this->tpl->assign(
                    [
                        'customer_id' => $info['customer_id'],
                        'customer_name' => $info['customer_name'],
                        'contact_id' => $info['contact_id'],
                        'contact_name' => $info['contact_name'],
                        'contacts' => $info['contacts'],
                    ]
                );
            } catch (CRMException $e) {
            }
        }

        // if we are dealing with just one message, use the subject line as the
        // summary for the issue, and the body as the description
        if (count($item) == 1) {
            $email_details = Support::getEmailDetails($item[0]);
            $this->tpl->assign(
                [
                    'issue_summary' => $email_details['sup_subject'],
                    'issue_description' => $email_details['seb_body'],
                ]
            );

            // also auto pre-fill the customer contact text fields
            if ($crm) {
                $sender_email = Mail_Helper::getEmailAddress($email_details['sup_from']);
                try {
                    $contact = $crm->getContactByEmail($sender_email);
                    $this->tpl->assign('contact_details', $contact->getDetails());
                } catch (CRMException $e) {
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $request = $this->getRequest();

        $this->tpl->assign(
            [
                'cats' => Category::getAssocList($this->prj_id),
                'priorities' => Priority::getAssocList($this->prj_id),
                'severities' => Severity::getList($this->prj_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'releases' => Release::getAssocList($this->prj_id),
                'custom_fields' => Custom_Field::getListByProject($this->prj_id, 'report_form', false, true),
                'max_attachment_size' => AttachmentManager::getMaxAttachmentSize(),
                'max_attachment_bytes' => AttachmentManager::getMaxAttachmentSize(true),
                'field_display_settings' => Project::getFieldDisplaySettings($this->prj_id),
                'groups' => Group::getAssocList($this->prj_id),
                'products' => Product::getList(false),
                'access_levels' => Access::getAccessLevels(),
            ]
        );

        $this->tpl->assign('zones', Date_Helper::getTimezoneList());
        if (Auth::getCurrentRole() == User::ROLE_CUSTOMER && ($crm = CRM::getInstance($this->prj_id))) {
            $customer_contact_id = User::getCustomerContactID($this->usr_id);
            $contact = $crm->getContact($customer_contact_id);
            $customer_id = Auth::getCurrentCustomerID();
            $customer = $crm->getCustomer($customer_id);
            // TODOCRM: Pull contacts via ajax when user selects contract
            $this->tpl->assign(
                [
                    'customer_id' => $customer_id,
                    'contact_id' => $customer_contact_id,
                    'customer' => $customer,
                    'contact' => $contact,
                ]
            );
        }

        $clone_iss_id = $request->query->getInt('clone_iss_id');
        if ($clone_iss_id && Access::canCloneIssue($clone_iss_id, $this->usr_id)) {
            $this->tpl->assign($this->getCloneIssueTemplateVariables($clone_iss_id));
        } else {
            // take POST and GET data, so that POST data overrides
            $data = $request->request->all() + $request->query->all();
            $this->tpl->assign('defaults', $data);
        }
    }

    /**
     * Returns an array of variables to be set on the new issue page when cloning an issue.
     *
     * @param int $issue_id The ID of the issue to clone
     * @return array
     */
    private function getCloneIssueTemplateVariables($issue_id)
    {
        $prj_id = Issue::getProjectID($issue_id);
        $details = Issue::getDetails($issue_id);

        $defaults = [
            'clone_iss_id' => $issue_id,
            'category' => $details['iss_prc_id'],
            'group' => $details['iss_grp_id'],
            'severity' => $details['iss_sev_id'],
            'priority' => $details['iss_pri_id'],
            'users' => $details['assigned_users'],
            'summary' => $details['iss_summary'],
            'description' => $details['iss_original_description'],
            'expected_resolution_date' => $details['iss_expected_resolution_date'],
            'estimated_dev_time' => $details['iss_dev_time'],
        ];

        if (count($details['products']) > 0) {
            $defaults['product'] = $details['products'][0]['pro_id'];
            $defaults['product_version'] = $details['products'][0]['version'];
        }

        $defaults['custom_fields'] = [];
        foreach (Custom_Field::getListByIssue($prj_id, $issue_id, null, false, true) as $field) {
            if (isset($field['selected_cfo_id'])) {
                $defaults['custom_fields'][$field['fld_id']] = $field['selected_cfo_id'];
            } else {
                $defaults['custom_fields'][$field['fld_id']] = $field['value'];
            }
        }

        $vars = [
            'defaults' => $defaults,
        ];

        if (isset($details['customer'], $details['contact'])) {
            $vars += [
                'customer_id' => $details['iss_customer_id'],
                'contact_id' => $details['iss_customer_contact_id'],
                'customer' => $details['customer'],
                'contact' => $details['contact'],
            ];
        }

        return $vars;
    }
}
