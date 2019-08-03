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
use Eventum\Db\Doctrine;
use Setup;
use Throwable;
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
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->fld_id = $request->request->get('fld_id') ?: $request->query->get('fld_id');
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        if ($this->cat === 'update') {
            $this->updateAction();
        }
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;
        $options = $post->get('existing_options', []);
        $new_options = $post->get('new_options', []);

        try {
            $repo = Doctrine::getCustomFieldRepository();
            $repo->updateCustomFieldOptions($this->fld_id, $options, $new_options);

            $message = ev_gettext('Thank you, the custom field options were updated successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to update the custom field options.');
            $this->messages->addErrorMessage($message);
        }

        $this->redirect(Setup::getRelativeUrl() . 'manage/custom_field_options.php?fld_id=' . $this->fld_id);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
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
