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

namespace Eventum\Controller\Manage;

use CRM;
use FAQ;
use Misc;
use Project;

class FaqController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/faq.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->query->get('prj_id');
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'new') {
            $this->newAction();
        } elseif ($this->cat == 'update') {
            $this->updateAction();
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        } elseif ($this->prj_id) {
            $this->infoAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        } elseif ($this->cat == 'change_rank') {
            $this->changeRankAction();
        }
    }

    private function newAction()
    {
        $res = FAQ::insert();
        $map = array(
            1 => array(ev_gettext('Thank you, the FAQ entry was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the FAQ entry.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this FAQ entry.'), Misc::MSG_ERROR),
            -3 => array(ev_gettext('Please enter the message for this FAQ entry.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $res = FAQ::update();
        $map = array(
            1 => array(ev_gettext('Thank you, the FAQ entry was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the FAQ entry information.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this FAQ entry.'), Misc::MSG_ERROR),
            -3 => array(ev_gettext('Please enter the message for this FAQ entry.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        FAQ::remove();
    }

    private function editAction()
    {
        $info = FAQ::getDetails($_GET['id']);
        if ($this->prj_id) {
            $info['faq_prj_id'] = $this->prj_id;
        }
        if (CRM::hasCustomerIntegration($info['faq_prj_id'])) {
            $crm = CRM::getInstance($info['faq_prj_id']);
            $this->tpl->assign('support_levels', $crm->getSupportLevelAssocList());
        }
        $this->tpl->assign('info', $info);
    }

    private function changeRankAction()
    {
        $get = $this->getRequest()->query;
        FAQ::changeRank($get->get('id'), $get->get('rank'));
    }

    private function infoAction()
    {
        $this->tpl->assign('info', array('faq_prj_id' => $this->prj_id));
        if ($crm = CRM::getInstance($this->prj_id)) {
            $this->tpl->assign('support_levels', $crm->getSupportLevelAssocList());
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'list' => FAQ::getList(),
                'project_list' => Project::getAll(),
            )
        );
    }
}
