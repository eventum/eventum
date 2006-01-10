<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005, 2006 MySQL AB                        |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//
// @(#) $Id: s.new.php 1.14 03/07/11 05:04:05-00:00 jpm $
//
include_once("config.inc.php");
include_once(APP_INC_PATH . "class.template.php");
include_once(APP_INC_PATH . "class.auth.php");
include_once(APP_INC_PATH . "class.category.php");
include_once(APP_INC_PATH . "class.priority.php");
include_once(APP_INC_PATH . "class.release.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.group.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.custom_field.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "db_access.php");

$tpl = new Template_API();
$tpl->setTemplate("new.tpl.html");

Auth::checkAuthentication(APP_COOKIE);
if (Auth::getCurrentRole() < User::getRoleID("Reporter")) {
    Auth::redirect("main.php");
}
$usr_id = Auth::getUserID();
$prj_id = Auth::getCurrentProject();

if (Customer::hasCustomerIntegration($prj_id)) {
    if (Auth::getCurrentRole() == User::getRoleID('Customer')) {
        $customer_id = User::getCustomerID($usr_id);
        // check if the current customer has already redeemed all available per-incident tickets
        if ((empty($HTTP_POST_VARS['cat'])) && (Customer::hasPerIncidentContract($prj_id, $customer_id)) &&
                (!Customer::hasIncidentsLeft($prj_id, $customer_id))) {
            // show warning about per-incident limitation
            $tpl->setTemplate("customer/" . Customer::getBackendImplementationName($prj_id) . "/incident_limit_reached.tpl.html");
            $tpl->assign('customer', Customer::getDetails($prj_id, $customer_id));
            $tpl->displayTemplate();
            exit;
        }
        $tpl->assign("message", Customer::getNewIssueMessage($prj_id, $customer_id));
    }
}

if (@$HTTP_POST_VARS["cat"] == "report") {
    $res = Issue::insert();
    if ($res != -1) {
        // show direct links to the issue page, issue listing page and
        // email listing page
        $tpl->assign("new_issue_id", $res);
        $tpl->assign("quarantine", Issue::getQuarantineInfo($res));
        $tpl->assign("errors", $insert_errors);
        $tpl->assign("ticket", Issue::getDetails($res));
    } else {
        // need to show everything again
        $tpl->assign("error_msg", "1");
    }
}

if (@$HTTP_GET_VARS["cat"] == "associate") {
    if (@count($HTTP_GET_VARS["item"]) > 0) {
        $res = Support::getListDetails($HTTP_GET_VARS["item"]);
        $tpl->assign("emails", $res);
        $tpl->assign("attached_emails", @implode(",", $HTTP_GET_VARS["item"]));
        if (Customer::hasCustomerIntegration($prj_id)) {
            // also need to guess the contact_id from any attached emails
            $info = Customer::getCustomerInfoFromEmails($prj_id, $HTTP_GET_VARS["item"]);
            $tpl->assign(array(
                "customer_id"   => $info['customer_id'],
                'customer_name' => $info['customer_name'],
                "contact_id"    => $info['contact_id'],
                'contact_name'  => $info['contact_name'],
                'contacts'      => $info['contacts']
            ));
        }
        // if we are dealing with just one message, use the subject line as the
        // summary for the issue, and the body as the description
        if (count($HTTP_GET_VARS["item"]) == 1) {
            $email_details = Support::getEmailDetails(Email_Account::getAccountByEmail($HTTP_GET_VARS["item"][0]), $HTTP_GET_VARS["item"][0]);
            $tpl->assign(array(
                'issue_summary'     => $email_details['sup_subject'],
                'issue_description' => $email_details['message']
            ));
            // also auto pre-fill the customer contact text fields
            if (Customer::hasCustomerIntegration($prj_id)) {
                $sender_email = Mail_API::getEmailAddress($email_details['sup_from']);
                list(,$contact_id) = Customer::getCustomerIDByEmails($prj_id, array($sender_email));
                if (!empty($contact_id)) {
                    $tpl->assign("contact_details", Customer::getContactDetails($prj_id, $contact_id));
                }
            }
        }
    }
}

$tpl->assign(array(
    "cats"                   => Category::getAssocList($prj_id),
    "priorities"             => Priority::getAssocList($prj_id),
    "users"                  => Project::getUserAssocList($prj_id, 'active', User::getRoleID('Customer')),
    "releases"               => Release::getAssocList($prj_id),
    "custom_fields"          => Custom_Field::getListByProject($prj_id, 'report_form'),
    "max_attachment_size"    => Attachment::getMaxAttachmentSize(),
    "field_display_settings" => Project::getFieldDisplaySettings($prj_id),
    "groups"                 => Group::getAssocList($prj_id)
));

$setup = Setup::load();
$tpl->assign("allow_unassigned_issues", @$setup["allow_unassigned_issues"]);

$prefs = Prefs::get($usr_id);
$tpl->assign("user_prefs", $prefs);
$tpl->assign("zones", Date_API::getTimezoneList());
if (User::getRole(Auth::getCurrentRole()) == "Customer") {
    $customer_contact_id = User::getCustomerContactID($usr_id);
    $tpl->assign("contact_details", Customer::getContactDetails($prj_id, $customer_contact_id));
    $customer_id = User::getCustomerID($usr_id);
    $tpl->assign("contacts", Customer::getContactEmailAssocList($prj_id, $customer_id));
    $tpl->assign(array(
        "customer_id" => User::getCustomerID($usr_id),
        "contact_id"  => User::getCustomerContactID($usr_id)
    ));
}

$tpl->displayTemplate();
?>