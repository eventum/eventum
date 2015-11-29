<?php

namespace Eventum\Controller;

use Access;
use Attachment;
use Auth;
use AuthCookie;
use CRM;
use CRMException;
use Category;
use Custom_Field;
use Date_Helper;
use Email_Account;
use Group;
use Issue;
use Mail_Helper;
use Misc;
use Prefs;
use Priority;
use Product;
use Project;
use Release;
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
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->getAlpha('cat') ?: $request->query->getAlpha('cat');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();

        if (!Access::canCreateIssue($this->usr_id)) {
            $this->redirect('main.php');
        }

        // If the project has changed since the new issue form was requested, then change it back
        $issue_prj_id = (int)$this->getRequest()->get('prj_id');
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
     * @inheritdoc
     */
    protected function defaultAction()
    {
        $this->tpl->assign('new_issue_id', '');

        if (CRM::hasCustomerIntegration($this->prj_id)) {
            if (Auth::getCurrentRole() == User::ROLE_CUSTOMER) {
                $crm = CRM::getInstance($this->prj_id);
                $customer_id = Auth::getCurrentCustomerID();
                $customer = $crm->getCustomer($customer_id);
                $new_issue_message = $customer->getNewIssueMessage();
                if ($new_issue_message) {
                    Misc::setMessage($new_issue_message, Misc::MSG_INFO);
                }
            }
        }

        if ($this->cat == 'report') {
            $this->reportAction();
        } elseif ($this->cat == 'associate') {
            $this->associateAction();
        }
    }

    private function reportAction()
    {
        $res = Issue::createFromPost();
        if ($res != -1) {
            // redirect to view issue page
            Misc::setMessage(ev_gettext('Your issue was created successfully.'));
            $this->redirect(APP_BASE_URL . 'view.php?id=' . $res);
        }

        // need to show everything again
        Misc::setMessage(ev_gettext('There was an error creating your issue.'), Misc::MSG_ERROR);
        $this->tpl->assign('error_msg', '1');
    }

    private function associateAction()
    {
        $request = $this->getRequest();
        $item = $request->query->get('item');
        if (!$item) {
            return;
        }

        $res = Support::getListDetails($item);
        $this->tpl->assign('emails', $res);
        $this->tpl->assign('attached_emails', implode(',', $item));

        if (CRM::hasCustomerIntegration($this->prj_id)) {
            $crm = CRM::getInstance($this->prj_id);
            // also need to guess the contact_id from any attached emails
            try {
                $info = $crm->getCustomerInfoFromEmails($this->prj_id, $item);
                $this->tpl->assign(
                    array(
                        'customer_id' => $info['customer_id'],
                        'customer_name' => $info['customer_name'],
                        'contact_id' => $info['contact_id'],
                        'contact_name' => $info['contact_name'],
                        'contacts' => $info['contacts'],
                    )
                );
            } catch (CRMException $e) {
            }
        }

        // if we are dealing with just one message, use the subject line as the
        // summary for the issue, and the body as the description
        if (count($item) == 1) {
            $email_details = Support::getEmailDetails(Email_Account::getAccountByEmail($item[0]), $item[0]);
            $this->tpl->assign(
                array(
                    'issue_summary' => $email_details['sup_subject'],
                    'issue_description' => $email_details['seb_body'],
                )
            );

            // also auto pre-fill the customer contact text fields
            if (CRM::hasCustomerIntegration($this->prj_id)) {
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
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $request = $this->getRequest();

        $this->tpl->assign(
            array(
                'cats' => Category::getAssocList($this->prj_id),
                'priorities' => Priority::getAssocList($this->prj_id),
                'severities' => Severity::getList($this->prj_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'releases' => Release::getAssocList($this->prj_id),
                'custom_fields' => Custom_Field::getListByProject($this->prj_id, 'report_form'),
                'max_attachment_size' => Attachment::getMaxAttachmentSize(),
                'max_attachment_bytes' => Attachment::getMaxAttachmentSize(true),
                'field_display_settings' => Project::getFieldDisplaySettings($this->prj_id),
                'groups' => Group::getAssocList($this->prj_id),
                'products' => Product::getList(false),
            )
        );

        $prefs = Prefs::get($this->usr_id);
        $this->tpl->assign('user_prefs', $prefs);
        $this->tpl->assign('zones', Date_Helper::getTimezoneList());
        if (Auth::getCurrentRole() == User::ROLE_CUSTOMER) {
            $crm = CRM::getInstance(Auth::getCurrentProject());
            $customer_contact_id = User::getCustomerContactID($this->usr_id);
            $contact = $crm->getContact($customer_contact_id);
            $customer_id = Auth::getCurrentCustomerID();
            $customer = $crm->getCustomer($customer_id);
            // TODOCRM: Pull contacts via ajax when user selects contract
            $this->tpl->assign(
                array(
                    'customer_id' => $customer_id,
                    'contact_id' => $customer_contact_id,
                    'customer' => $customer,
                    'contact' => $contact,
                )
            );
        }

        $clone_iss_id = $request->query->getInt('clone_iss_id');
        if ($clone_iss_id && Access::canCloneIssue($clone_iss_id, $this->usr_id)) {
            $this->tpl->assign(Issue::getCloneIssueTemplateVariables($clone_iss_id));
        } else {
            // take POST and GET data, so that POST data overrides
            $data = $request->request->all() + $request->query->all();
            $this->tpl->assign('defaults', $data);
        }
    }
}
