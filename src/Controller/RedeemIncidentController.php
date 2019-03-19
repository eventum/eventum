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
use Eventum\Controller\Helper\MessagesHelper;
use Issue;
use User;

/**
 * This page handles marking an issue as 'redeeming' an incident.
 */
class RedeemIncidentController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'redeem_incident.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /** @var bool */
    private $submit;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('iss_id') ?: $request->query->getInt('iss_id');
        $this->submit = $request->get('submit');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        if (Auth::getCurrentRole() <= User::ROLE_CUSTOMER) {
            return false;
        }

        $this->prj_id = Auth::getCurrentProject();
        $this->usr_id = Auth::getUserID();

        if (!Issue::canAccess($this->issue_id, $this->usr_id)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        $crm = CRM::getInstance($this->prj_id);
        $contract = $crm->getContract(Issue::getContractID($this->issue_id));

        if ($this->submit) {
            $this->updateRedeemedIncidents($contract);
        }
        $details = $contract->getDetails();

        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'redeemed' => $contract->getRedeemedIncidentDetails($this->issue_id),
                'incident_details' => $details['incident_details'],
            ]
        );
    }

    /**
     * update counts
     *
     * @param Contract $contract
     */
    private function updateRedeemedIncidents(Contract $contract): void
    {
        $request = $this->getRequest();
        $redeem = $request->get('redeem');

        $res = $contract->updateRedeemedIncidents($this->issue_id, $redeem);
        $this->tpl->assign('res', $res);
        // FIXME: translate
        $map = [
            1 => ['Thank you, the issue was successfully marked.', MessagesHelper::MSG_INFO],
            -1 => ['There was an error marking this issue as redeemed', MessagesHelper::MSG_ERROR],
            -2 => ['This issue already has been marked as redeemed', MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
    }
}
