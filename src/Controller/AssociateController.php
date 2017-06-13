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
use CRM;
use CRMException;
use Issue;
use Mail_Helper;
use Note;
use Support;
use User;
use Workflow;

class AssociateController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'associate.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var string */
    private $cat;

    /** @var string */
    private $target;

    /** @var array */
    private $items;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('issue_id');
        $this->cat = $request->request->get('cat');
        $this->target = $request->request->get('target');
        $this->items = $request->request->get('item');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Issue::getProjectID($this->issue_id);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'associate') {
            $this->associateAction();
        } else {
            $this->listAction();
        }
    }

    // TODO: this method could use cleanup/rewrite
    // FIXME: get rid of $_POST
    public function associateAction()
    {
        if ($this->target == 'email') {
            $res = Support::associate($this->usr_id, $this->issue_id, $this->items);
            if ($res == 1) {
                Workflow::handleManualEmailAssociation($this->prj_id, $this->issue_id);
            }
            $this->tpl->assign('associate_result', $res);
        } elseif ($this->target == 'reference') {
            $res = Support::associateEmail($this->usr_id, $this->issue_id, $this->items);
            if ($res == 1) {
                Workflow::handleManualEmailAssociation($this->prj_id, $this->issue_id);
            }
            $this->tpl->assign('associate_result', $res);
        } else {
            foreach ($this->items as $item) {
                $email = Support::getEmailDetails($item);
                // add the message body as a note
                $_POST['full_message'] = $email['seb_full_email'];
                $_POST['title'] = $email['sup_subject'];
                $_POST['note'] = $email['seb_body'];
                // XXX: probably broken to use the current logged in user as the 'owner' of
                // XXX: this new note, but that's how it was already
                $res = Note::insertFromPost($this->usr_id, $this->issue_id, false, true, false, true, true);
                // remove the associated email
                if ($res) {
                    list($_POST['from']) = Support::getSender([$item]);
                    Workflow::handleBlockedEmail($this->prj_id, $this->issue_id, $_POST, 'associated');
                    Support::removeEmail($item);
                }
            }
            $this->tpl->assign('associate_result', $res);
        }

        $this->tpl->assign('total_emails', count($this->items));
    }

    // TODO: this method could use cleanup/rewrite
    private function listAction()
    {
        $this->tpl->assign([
            'emails' => $this->items,
            'total_emails' => count($this->items),
        ]);

        if (CRM::hasCustomerIntegration($this->prj_id)) {
            // check if the selected emails all have sender email addresses that are associated with the issue' customer
            $crm = CRM::getInstance($this->prj_id);
            $senders = Support::getSender($this->items);
            $sender_emails = [];
            foreach ($senders as $sender) {
                $email = Mail_Helper::getEmailAddress($sender);
                $sender_emails[$email] = $sender;
            }
            $contract_id = Issue::getContractID($this->issue_id);
            if (!empty($contract_id)) {
                try {
                    $contract = $crm->getContract($contract_id);
                    // TODOCRM: Active contacts only
                    $contact_emails = array_keys($contract->getContactEmailAssocList());
                } catch (CRMException $e) {
                    $contact_emails = [];
                }
                $unknown_contacts = [];
                foreach ($sender_emails as $email => $address) {
                    if (!in_array($email, $contact_emails)) {
                        $usr_id = User::getUserIDByEmail($email);
                        if (empty($usr_id)) {
                            $unknown_contacts[] = $address;
                        } else {
                            // if we got a real user ID, check if the customer user is the correct one
                            // (e.g. a contact from the customer associated with the selected issue)
                            if (User::getRoleByUser($usr_id, $this->prj_id) == User::ROLE_CUSTOMER) {
                                if (!Issue::canAccess($this->issue_id, $usr_id)) {
                                    $unknown_contacts[] = $address;
                                }
                            }
                        }
                    }
                }

                if (count($unknown_contacts) > 0) {
                    $this->tpl->assign('unknown_contacts', $unknown_contacts);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
    }
}
