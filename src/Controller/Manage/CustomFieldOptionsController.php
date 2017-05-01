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

use Custom_Field;
use Eventum\Controller\Helper\MessagesHelper;
use User;

class CustomFieldOptionsController extends ManageBaseController
{
    /** @var string */
    protected $tpl_name = 'manage/custom_field_options.tpl.html';

    /** @var int */
    protected $min_role = User::ROLE_ADMINISTRATOR;

    /** @var string */
    private $cat;

    /** @var int */
    private $fld_id;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->fld_id = $request->request->get('fld_id') ?: $request->query->get('fld_id');

        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'update') {
            $this->updateAction();
        }
    }

    private function updateAction()
    {
        $post = $this->getRequest()->request;
        $res = Custom_Field::updateOptions($this->fld_id, $post->get('existing_options'), $post->get('new_options'));
        $this->messages->mapMessages(
            $res, [
                1 => [ev_gettext('Thank you, the custom field options were updated successfully.'), MessagesHelper::MSG_INFO],
                -1 => [ev_gettext('An error occurred while trying to update the custom field options.'), MessagesHelper::MSG_ERROR],
            ]
        );
        $this->redirect(APP_RELATIVE_URL . 'manage/custom_field_options.php?fld_id=' . $this->fld_id);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $field_info = Custom_Field::getDetails($this->fld_id);
        if (empty($field_info)) {
            $this->error(ev_gettext('Invalid custom field ID'));
        }

        $this->tpl->assign([
                'info' => $field_info,
                'options' => Custom_Field::getOptions($this->fld_id),
        ]);
    }
}
