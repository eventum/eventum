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

namespace Eventum\Controller\Ajax;

use Auth;
use Custom_Field;
use Eventum\Controller\BaseController;
use Eventum\CustomField\Fields\DynamicCustomFieldInterface;

class DynamicCustomFieldController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'js/dynamic_custom_field.tpl.js';
    /** @var int */
    private $prj_id;
    /** @var int */
    private $issue_id;
    /** @var string */
    private $form_type;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('iss_id') ?: $request->query->getInt('iss_id');
        $this->form_type = $request->request->get('form_type') ?: $request->query->get('form_type');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication();

        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        header('Content-Type: text/javascript; charset=UTF-8');
    }

    private function getData(): array
    {
        if ($this->issue_id) {
            $fields = Custom_Field::getListByIssue($this->prj_id, $this->issue_id, null, false, true);
        } else {
            $fields = Custom_Field::getListByProject($this->prj_id, $this->form_type, false, true);
        }

        $data = [];
        foreach ($fields as $field) {
            $backend = Custom_Field::getBackend($field['fld_id']);
            if ($backend && $backend->hasInterface(DynamicCustomFieldInterface::class)) {
                $field['structured_data'] = $backend->getStructuredData();
                $data[] = $field;
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign('fields', $this->getData());
    }
}
