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

require_once __DIR__ . '/../init.php';

$tpl = new Template_Helper();
$tpl->setTemplate('associate.tpl.html');

Auth::checkAuthentication('index.php?err=5', true);

if (@$_POST['cat'] == 'associate') {
    if ($_POST['target'] == 'email') {
        $res = Support::associate(Auth::getUserID(), $_POST['issue_id'], $_POST['item']);
        if ($res == 1) {
            Workflow::handleManualEmailAssociation(Issue::getProjectID($_POST['issue_id']), $_POST['issue_id']);
        }
        $tpl->assign('associate_result', $res);
    } elseif ($_POST['target'] == 'reference') {
        $res = Support::associateEmail(Auth::getUserID(), $_POST['issue_id'], $_POST['item']);
        if ($res == 1) {
            Workflow::handleManualEmailAssociation(Issue::getProjectID($_POST['issue_id']), $_POST['issue_id']);
        }
        $tpl->assign('associate_result', $res);
    } else {
        foreach ($_POST['item'] as $item) {
            $email = Support::getEmailDetails(Email_Account::getAccountByEmail($item), $item);
            // add the message body as a note
            $_POST['full_message'] = $email['seb_full_email'];
            $_POST['title'] = $email['sup_subject'];
            $_POST['note'] = $email['seb_body'];
            // XXX: probably broken to use the current logged in user as the 'owner' of
            // XXX: this new note, but that's how it was already
            $res = Note::insertFromPost(Auth::getUserID(), $_POST['issue_id'], false, true, false, true, true);
            // remove the associated email
            if ($res) {
                list($_POST['from']) = Support::getSender(array($item));
                Workflow::handleBlockedEmail(Issue::getProjectID($_POST['issue_id']), $_POST['issue_id'], $_POST, 'associated');
                Support::removeEmail($item);
            }
        }
        $tpl->assign('associate_result', $res);
    }
    @$tpl->assign('total_emails', count($_POST['item']));
} else {
    @$tpl->assign('emails', $_GET['item']);
    @$tpl->assign('total_emails', count($_GET['item']));
    $prj_id = Issue::getProjectID($_GET['issue_id']);
    if (CRM::hasCustomerIntegration($prj_id)) {
        // check if the selected emails all have sender email addresses that are associated with the issue' customer
        $crm = CRM::getInstance($prj_id);
        $senders = Support::getSender($_GET['item']);
        $sender_emails = array();
        foreach ($senders as $sender) {
            $email = Mail_Helper::getEmailAddress($sender);
            $sender_emails[$email] = $sender;
        }
        $contract_id = Issue::getContractID($_GET['issue_id']);
        if (!empty($contract_id)) {
            try {
                $contract = $crm->getContract($contract_id);
                // TODOCRM: Active contacts only
                $contact_emails = array_keys($contract->getContactEmailAssocList());
            } catch (CRMException $e) {
                $contact_emails = array();
            }
            $unknown_contacts = array();
            foreach ($sender_emails as $email => $address) {
                if (!@in_array($email, $contact_emails)) {
                    $usr_id = User::getUserIDByEmail($email);
                    if (empty($usr_id)) {
                        $unknown_contacts[] = $address;
                    } else {
                        // if we got a real user ID, check if the customer user is the correct one
                        // (e.g. a contact from the customer associated with the selected issue)
                        if (User::getRoleByUser($usr_id, $prj_id) == User::ROLE_CUSTOMER) {
                            if (!Issue::canAccess($_GET['issue_id'], $usr_id)) {
                                $unknown_contacts[] = $address;
                            }
                        }
                    }
                }
            }
            if (count($unknown_contacts) > 0) {
                $tpl->assign('unknown_contacts', $unknown_contacts);
            }
        }
    }
}

$tpl->assign('current_user_prefs', Prefs::get(Auth::getUserID()));

$tpl->displayTemplate();
