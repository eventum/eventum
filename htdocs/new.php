<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// | Authors: Elan Ruusamäe <glen@delfi.ee>                               |
// +----------------------------------------------------------------------+

require_once __DIR__ . '/../init.php';

Auth::checkAuthentication();

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

if (!Access::canCreateIssue($usr_id)) {
    Auth::redirect('main.php');
}

$tpl = new Template_Helper();
$tpl->setTemplate('new.tpl.html');
$tpl->assign('new_issue_id', '');

// If the project has changed since the new issue form was requested, then change it back
$issue_prj_id = !empty($_REQUEST['prj_id']) ? (int) $_REQUEST['prj_id'] : 0;
if ($issue_prj_id > 0 && $issue_prj_id != $prj_id) {
    // Switch the project back
    $assigned_projects = Project::getAssocList($usr_id);
    if (isset($assigned_projects[$issue_prj_id])) {
        AuthCookie::setProjectCookie($issue_prj_id);
        $prj_id = $issue_prj_id;
    } else {
        Misc::setMessage(ev_gettext('There was an error creating your issue.'), Misc::MSG_ERROR);
        $tpl->assign('error_msg', '1');
    }
}

if (CRM::hasCustomerIntegration($prj_id)) {
    if (Auth::getCurrentRole() == User::ROLE_CUSTOMER) {
        $crm = CRM::getInstance($prj_id);
        $customer_id = Auth::getCurrentCustomerID();
        $customer = $crm->getCustomer($customer_id);
        $new_issue_message = $customer->getNewIssueMessage();
        if ($new_issue_message) {
            Misc::setMessage($new_issue_message, Misc::MSG_INFO);
        }
    }
}

$cat = isset($_POST['cat']) ? (string) $_POST['cat'] : (isset($_GET['cat']) ? (string) $_GET['cat'] : null);
if ($cat == 'report') {
    $res = Issue::createFromPost();
    if ($res != -1) {
        // redirect to view issue page
        Misc::setMessage(ev_gettext('Your issue was created successfully.'));
        Auth::redirect(APP_BASE_URL . 'view.php?id=' . $res);
    } else {
        // need to show everything again
        Misc::setMessage(ev_gettext('There was an error creating your issue.'), Misc::MSG_ERROR);
        $tpl->assign('error_msg', '1');
    }
}

if ($cat  == 'associate') {
    $item = isset($_GET['item']) ? (array) $_GET['item'] : null;
    if (count($item) > 0) {
        $res = Support::getListDetails($item);
        $tpl->assign('emails', $res);
        $tpl->assign('attached_emails', @implode(',', $item));
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            // also need to guess the contact_id from any attached emails
            try {
                $info = $crm->getCustomerInfoFromEmails($prj_id, $item);
                $tpl->assign(array(
                    'customer_id'   => $info['customer_id'],
                    'customer_name' => $info['customer_name'],
                    'contact_id'    => $info['contact_id'],
                    'contact_name'  => $info['contact_name'],
                    'contacts'      => $info['contacts'],
                ));
            } catch (CRMException $e) {
            }
        }
        // if we are dealing with just one message, use the subject line as the
        // summary for the issue, and the body as the description
        if (count($item) == 1) {
            $email_details = Support::getEmailDetails(Email_Account::getAccountByEmail($item[0]), $item[0]);
            $tpl->assign(array(
                'issue_summary'     => $email_details['sup_subject'],
                'issue_description' => $email_details['seb_body'],
            ));
            // also auto pre-fill the customer contact text fields
            if (CRM::hasCustomerIntegration($prj_id)) {
                $sender_email = Mail_Helper::getEmailAddress($email_details['sup_from']);
                try {
                    $contact = $crm->getContactByEmail($sender_email);
                    $tpl->assign('contact_details', $contact->getDetails());
                } catch (CRMException $e) {
                }
            }
        }
    }
}

$tpl->assign(array(
    'cats'                   => Category::getAssocList($prj_id),
    'priorities'             => Priority::getAssocList($prj_id),
    'severities'             => Severity::getList($prj_id),
    'users'                  => Project::getUserAssocList($prj_id, 'active', User::ROLE_CUSTOMER),
    'releases'               => Release::getAssocList($prj_id),
    'custom_fields'          => Custom_Field::getListByProject($prj_id, 'report_form'),
    'max_attachment_size'    => Attachment::getMaxAttachmentSize(),
    'max_attachment_bytes'   => Attachment::getMaxAttachmentSize(true),
    'field_display_settings' => Project::getFieldDisplaySettings($prj_id),
    'groups'                 => Group::getAssocList($prj_id),
    'products'               => Product::getList(false),
));

$prefs = Prefs::get($usr_id);
$tpl->assign('user_prefs', $prefs);
$tpl->assign('zones', Date_Helper::getTimezoneList());
if (Auth::getCurrentRole() == User::ROLE_CUSTOMER) {
    $crm = CRM::getInstance(Auth::getCurrentProject());
    $customer_contact_id = User::getCustomerContactID($usr_id);
    $contact = $crm->getContact($customer_contact_id);
    $customer_id = Auth::getCurrentCustomerID();
    $customer = $crm->getCustomer($customer_id);
    // TODOCRM: Pull contacts via ajax when user selects contract
    $tpl->assign(array(
        'customer_id' => $customer_id,
        'contact_id'  => $customer_contact_id,
        'customer'    => $customer,
        'contact'     => $contact,
    ));
}

$clone_iss_id = isset($_GET['clone_iss_id']) ? (int) $_GET['clone_iss_id'] : null;
if ($clone_iss_id && Access::canCloneIssue($clone_iss_id, $usr_id)) {
    $tpl->assign(Issue::getCloneIssueTemplateVariables($clone_iss_id));
} else {
    $tpl->assign('defaults', $_REQUEST);
}

$tpl->displayTemplate();
