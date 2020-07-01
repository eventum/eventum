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
use Eventum\Controller\Helper\MessagesHelper;
use Issue;
use Project;
use Status;
use User;

class StatusActionDateController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/status_action_date.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->getInt('prj_id') ?: $request->query->getInt('prj_id');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat == 'new') {
            $this->newAction();
        } elseif ($this->cat == 'update') {
            $this->updateAction();
        } elseif ($this->cat == 'delete') {
            $this->deleteAction();
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function newAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Status::insertCustomization(
            $post->get('project'),
            $post->get('status'),
            $post->get('date_field'),
            $post->get('label')
        );
        $map = [
            1 => [ev_gettext('Thank you, the customization was added successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to add the new customization.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this new customization'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Status::updateCustomization(
            $post->get('id'),
            $post->get('project'),
            $post->get('status'),
            $post->get('date_field'),
            $post->get('label')
        );
        $map = [
            1 => [ev_gettext('Thank you, the customization was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the customization information.'), MessagesHelper::MSG_ERROR],
            -2 => [ev_gettext('Please enter the title for this customization.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Status::removeCustomization($post->get('items'));
        $this->messages->mapMessages(
            $res,
            [
                true => [ev_gettext('Thank you, the customization was deleted successfully.'), MessagesHelper::MSG_INFO],
                false => [ev_gettext('An error occurred while trying to delete the customization information.'), MessagesHelper::MSG_ERROR],
            ]
        );
    }

    private function editAction(): void
    {
        $get = $this->getRequest()->query;

        $details = Status::getCustomizationDetails($get->get('id'));
        $this->tpl->assign(
            [
                'info' => $details,
                'project_id' => $details['psd_prj_id'],
                'status_list' => Status::getAssocStatusList($details['psd_prj_id'], true),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        if ($this->prj_id) {
            $this->tpl->assign(
                [
                    'status_list' => Status::getAssocStatusList($this->prj_id, true),
                    'project_id' => $this->prj_id,
                ]
            );
            $display_customer_fields = CRM::hasCustomerIntegration($this->prj_id);
        } else {
            $display_customer_fields = false;
        }

        $this->tpl->assign(
            [
                'project_list' => Project::getAll(),
                'date_fields' => Issue::getDateFieldsAssocList($display_customer_fields),
                'list' => Status::getCustomizationList(),
            ]
        );
    }
}
