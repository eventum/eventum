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
use Eventum\Db\Doctrine;
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
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
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
    protected function defaultAction(): void
    {
        if ($this->cat === 'update') {
            $this->updatePartnerAction();
        }
    }

    private function updatePartnerAction(): void
    {
        $post = $this->getRequest()->request;

        $repo = Doctrine::getIssuePartnerRepository();
        $repo->setIssueAssociation($this->issue_id, $post->get('partners', []));
        $this->tpl->assign('update_result', 1);
    }

    protected function prepareTemplate(): void
    {
        $this->tpl->assign([
            'issue_id' => $this->issue_id,
            'partners' => $this->getPartnersOptions(),
        ]);
    }

    private function getPartnersOptions(): array
    {
        $selected = Partner::getPartnersByIssue($this->issue_id);
        $options = [];
        foreach (Partner::getPartnersByProject($this->prj_id) as $option => $value) {
            $options[$option] = $value['name'];
        }

        return $this->html->checkboxes($options, array_keys($selected));
    }
}
