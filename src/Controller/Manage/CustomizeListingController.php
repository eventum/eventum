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
use Issue;
use Misc;
use Project;
use Status;
use User;

class CustomizeListingController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/customize_listing.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /** @var int */
    private $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->prj_id = $request->request->get('prj_id') ?: $request->query->get('prj_id');
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
        }

        if ($this->cat == 'edit') {
            $this->editAction();
        }
    }

    private function newAction()
    {
        $post = $this->getRequest()->request;

        $res = Status::insertCustomization(
            $post->get('project'), $post->get('status'), $post->get('date_field'), $post->get('label')
        );
        $map = array(
            1 => array(ev_gettext('Thank you, the customization was added successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to add the new customization.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this new customization'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;

        $res = Status::updateCustomization(
            $post->get('id'), $post->get('project'), $post->get('status'), $post->get('date_field'), $post->get('label')
        );
        $map = array(
            1 => array(ev_gettext('Thank you, the customization was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the customization information.'), Misc::MSG_ERROR),
            -2 => array(ev_gettext('Please enter the title for this customization.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);
    }

    private function deleteAction()
    {
        $post = $this->getRequest()->request;

        $res = Status::removeCustomization($post->get('items'));
        Misc::mapMessages(
            $res, array(
                true => array(ev_gettext('Thank you, the customization was deleted successfully.'), Misc::MSG_INFO),
                false => array(ev_gettext('An error occurred while trying to delete the customization information.'), Misc::MSG_ERROR),
            )
        );
    }

    private function editAction()
    {
        $get = $this->getRequest()->query;

        $details = Status::getCustomizationDetails($get->get('id'));
        $this->tpl->assign(
            array(
                'info' => $details,
                'project_id' => $details['psd_prj_id'],
                'status_list' => Status::getAssocStatusList($details['psd_prj_id'], true),
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        if ($this->prj_id) {
            $this->tpl->assign(
                array(
                    'status_list' => Status::getAssocStatusList($this->prj_id, true),
                    'project_id' => $this->prj_id,
                )
            );
            $display_customer_fields = CRM::hasCustomerIntegration($this->prj_id);
        } else {
            $display_customer_fields = false;
        }

        $this->tpl->assign(
            array(
                'project_list' => Project::getAll(),
                'date_fields' => Issue::getDateFieldsAssocList($display_customer_fields),
                'list' => Status::getCustomizationList(),
            )
        );
    }
}
