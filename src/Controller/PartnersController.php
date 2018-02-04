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
use Issue;
use Partner;
use User;

class PartnersController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'select_partners.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_DEVELOPER;

    /** @var bool */
    protected $is_popup = true;

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        $this->usr_id = Auth::getUserID();

        if (!Access::canViewIssuePartners($this->issue_id, $this->usr_id)) {
            return false;
        }

        $this->prj_id = Issue::getProjectID($this->issue_id);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'update') {
            $this->updatePartnerAction();
        }
    }

    private function updatePartnerAction()
    {
        $post = $this->getRequest()->request;

        $res = Partner::selectPartnersForIssue($this->issue_id, $post->get('partners'));
        $this->tpl->assign('update_result', $res);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'enabled_partners' => Partner::getPartnersByProject($this->prj_id),
                'partners' => Partner::getPartnersByIssue($this->issue_id),
            ]
        );
    }
}
