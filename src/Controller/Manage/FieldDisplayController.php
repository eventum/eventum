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
use Project;
use User;

class FieldDisplayController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/field_display.tpl.html';

    /** @var array */
    private $fields;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->prj_id = $request->query->getInt('prj_id');
        $this->fields = $request->request->get('fields');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->fields) {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        $res = Project::updateFieldDisplaySettings($this->prj_id, $this->fields);
        $this->tpl->assign('result', $res);
        $map = [
            1 => [ev_gettext('Thank you, the information was updated successfully.'), MessagesHelper::MSG_INFO],
            -1 => [ev_gettext('An error occurred while trying to update the information.'), MessagesHelper::MSG_ERROR],
        ];
        $this->messages->mapMessages($res, $map);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $fields = Project::getDisplayFields();

        $excluded_roles = ['viewer'];
        if (!CRM::hasCustomerIntegration($this->prj_id)) {
            $excluded_roles[] = User::ROLE_CUSTOMER;
        }
        $user_roles = User::getRoles($excluded_roles);
        $user_roles[9] = ev_gettext('Never Display');

        $this->tpl->assign(
            [
                'type' => 'field_display',
                'prj_id' => $this->prj_id,
                'fields' => $fields,
                'user_roles' => $user_roles,
                'display_settings' => Project::getFieldDisplaySettings($this->prj_id),
            ]
        );
    }
}
