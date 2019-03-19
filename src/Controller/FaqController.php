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
use FAQ;
use User;

class FaqController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'faq.tpl.html';

    /** @var int */
    private $faq_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->faq_id = $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
    }

    private function getSupportLevelIds()
    {
        if (Auth::getCurrentRole() != User::ROLE_CUSTOMER) {
            // show all FAQ entries
            return [];
        }

        if (!CRM::hasCustomerIntegration($this->prj_id)) {
            // show all FAQ entries
            return [];
        }

        $contact = Auth::getCurrentContact();
        $support_level_ids = [];
        // TODOCRM: only active contracts?
        foreach ($contact->getContracts() as $contract) {
            $support_level_ids[] = $contract->getSupportLevel()->getLevelID();
        }

        return $support_level_ids;
    }

    private function getFaqDetails($support_level_ids)
    {
        if (!$this->faq_id) {
            return null;
        }

        $faq = FAQ::getDetails($this->faq_id);
        if (!$faq) {
            return null;
        }

        // check if this customer should have access to this FAQ entry or not
        if ($support_level_ids) {
            $intersect = array_intersect($support_level_ids, $faq['support_levels']);
            if (!$intersect) {
                return -1;
            }
        }

        return $faq;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $support_level_ids = $this->getSupportLevelIds();
        $this->tpl->assign(
            [
                'faqs' => FAQ::getListBySupportLevel($support_level_ids),
                'faq' => $this->getFaqDetails($support_level_ids),
            ]
        );
    }
}
