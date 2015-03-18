<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2013 Eventum Team.                              |
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
// | 51 Franklin Street, Suite 330                                          |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+

require_once dirname(__FILE__) . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate("new.tpl.html");

$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

Auth::checkAuthentication(APP_COOKIE);
if (!Access::canCreateIssue($usr_id)) {
    Auth::redirect("main.php");
}

$tpl->assign("new_issue_id", '');

// If the project has changed since the new issue form was requested, then change it back
$issue_prj_id = !empty($_REQUEST['prj_id']) ? (int) $_REQUEST['prj_id'] : 0;
if (($issue_prj_id > 0) && ($issue_prj_id != $prj_id)) {
    // Switch the project back
    $assigned_projects = Project::getAssocList($usr_id);
    if (isset($assigned_projects[$issue_prj_id])) {
        $cookie = Auth::getCookieInfo(APP_PROJECT_COOKIE);
        Auth::setCurrentProject($issue_prj_id, $cookie["remember"]);
        $prj_id = $issue_prj_id;
    } else {
        Misc::setMessage(ev_gettext('There was an error creating your issue.'), Misc::MSG_ERROR);
        $tpl->assign("error_msg", "1");
    }
}

if (CRM::hasCustomerIntegration($prj_id)) {
    if (Auth::getCurrentRole() == User::getRoleID('Customer')) {
        $crm = CRM::getInstance($prj_id);
        $customer_id = Auth::getCurrentCustomerID();
        $customer = $crm->getCustomer($customer_id);
        $new_issue_message = $customer->getNewIssueMessage();
        if (!empty($new_issue_message)) {
            Misc::setMessage($new_issue_message, Misc::MSG_INFO);
        }
    }
}

if (@$_POST["cat"] == "report") {
    $res = Issue::createFromPost();
    if ($res != -1) {
        // redirect to view issue page
        Misc::setMessage(ev_gettext('Your issue was created successfully.'));
        Auth::redirect(APP_BASE_URL . "view.php?id=" . $res);
    } else {
        // need to show everything again
        Misc::setMessage(ev_gettext('There was an error creating your issue.'), Misc::MSG_ERROR);
        $tpl->assign("error_msg", "1");
    }
}

if (@$_GET["cat"] == "associate") {
    if (@count($_GET["item"]) > 0) {
        $res = Support::getListDetails($_GET["item"]);
        $tpl->assign("emails", $res);
        $tpl->assign("attached_emails", @implode(",", $_GET["item"]));
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            // also need to guess the contact_id from any attached emails
            try {
                $info = $crm->getCustomerInfoFromEmails($prj_id, $_GET["item"]);
                $tpl->assign(array(
                    "customer_id"   => $info['customer_id'],
                    'customer_name' => $info['customer_name'],
                    "contact_id"    => $info['contact_id'],
                    'contact_name'  => $info['contact_name'],
                    'contacts'      => $info['contacts']
                ));
            } catch (CRMException $e) {}
        }
        // if we are dealing with just one message, use the subject line as the
        // summary for the issue, and the body as the description
        if (count($_GET["item"]) == 1) {
            $email_details = Support::getEmailDetails(Email_Account::getAccountByEmail($_GET["item"][0]), $_GET["item"][0]);
            $tpl->assign(array(
                'issue_summary'     => $email_details['sup_subject'],
                'issue_description' => $email_details['seb_body']
            ));
            // also auto pre-fill the customer contact text fields
            if (CRM::hasCustomerIntegration($prj_id)) {
                $sender_email = Mail_Helper::getEmailAddress($email_details['sup_from']);
                try {
                    $contact = $crm->getContactByEmail($sender_email);
                    $tpl->assign("contact_details", $contact->getDetails());
                } catch (CRMException $e) {}
            }
        }
    }
}

$tpl->assign(array(
    "cats"                   => Category::getAssocList($prj_id),
    "priorities"             => Priority::getAssocList($prj_id),
    "severities"             => Severity::getList($prj_id),
    "users"                  => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    "releases"               => Release::getAssocList($prj_id),
    "custom_fields"          => Custom_Field::getListByProject($prj_id, 'report_form'),
    "max_attachment_size"    => Attachment::getMaxAttachmentSize(),
    "field_display_settings" => Project::getFieldDisplaySettings($prj_id),
    "groups"                 => Group::getAssocList($prj_id),
    "products"               => Product::getList(false),
));

$setup = Setup::load();
$tpl->assign("allow_unassigned_issues", @$setup["allow_unassigned_issues"]);

$prefs = Prefs::get($usr_id);
$tpl->assign("user_prefs", $prefs);
$tpl->assign("zones", Date_Helper::getTimezoneList());
if (Auth::getCurrentRole() == User::getRoleID('Customer')) {
    $crm = CRM::getInstance(Auth::getCurrentProject());
    $customer_contact_id = User::getCustomerContactID($usr_id);
    $contact = $crm->getContact($customer_contact_id);
    $customer_id = Auth::getCurrentCustomerID();
    $customer = $crm->getCustomer($customer_id);
    // TODOCRM: Pull contacts via ajax when user selects contract
    $tpl->assign(array(
        "customer_id" => $customer_id,
        "contact_id"  => $customer_contact_id,
        "customer"    => $customer,
        "contact"     => $contact,
    ));
}

if (isset($_GET['clone_iss_id']) && Access::canCloneIssue($_REQUEST['clone_iss_id'], $usr_id)) {
    $clone_details = Issue::getDetails($_REQUEST['clone_iss_id']);
    $defaults = array(
        'clone_iss_id'  =>  $_GET['clone_iss_id'],
        'category'  =>  $clone_details['iss_prc_id'],
        'group'  =>  $clone_details['iss_grp_id'],
        'severity'  =>  $clone_details['iss_sev_id'],
        'priority'  =>  $clone_details['iss_pri_id'],
        'users' =>  $clone_details['assigned_users'],
        'summary'   =>  $clone_details['iss_summary'],
        'description'   =>  $clone_details['iss_original_description'],
        'expected_resolution_date'   =>  $clone_details['iss_expected_resolution_date'],
        'estimated_dev_time'   =>  $clone_details['iss_dev_time'],
        'private'   =>  $clone_details['iss_private'],
    );
    if (count($clone_details['products']) > 0) {
        $defaults['product'] = $clone_details['products'][0]['pro_id'];
        $defaults['product_version'] = $clone_details['products'][0]['version'];
    }
    $defaults['custom_fields'] = array();
    foreach (Custom_Field::getListByIssue($prj_id, $_REQUEST['clone_iss_id']) as $field) {
        if (isset($field['selected_cfo_id'])) {
            $defaults['custom_fields'][$field['fld_id']] = $field['selected_cfo_id'];
        } else {
            $defaults['custom_fields'][$field['fld_id']] = $field['value'];
        }
    }
    $tpl->assign('defaults', $defaults);
    if (isset($clone_details['customer']) && isset($clone_details['contact'])) {
        $tpl->assign(array(
                'customer_id' => $clone_details['iss_customer_id'],
                'contact_id'  => $clone_details['iss_customer_contact_id'],
                "customer"    => $clone_details['customer'],
                "contact"     => $clone_details['contact'],
        ));
    }
} else {
    $tpl->assign('defaults', $_REQUEST);
}

$tpl->displayTemplate();
