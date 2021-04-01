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
use Eventum\CustomField\Fields\DynamicCustomFieldInterface;
use Eventum\Db\Doctrine;
use Eventum\Model\Repository\CustomFieldRepository;
use LazyProperty\LazyPropertiesTrait;

class DynamicCustomFieldController extends AjaxBaseController
{
    use LazyPropertiesTrait;

    /** @var string */
    protected $tpl_name = 'js/dynamic_custom_field.tpl.js';
    /** @var int */
    private $prj_id;
    /** @var int */
    private $issue_id;
    /** @var string */
    private $form_type;
    /** @var CustomFieldRepository */
    private $customFieldRepository;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->initLazyProperties([
            /** @see getCustomFieldRepository */
            'customFieldRepository',
        ]);
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
        $this->role_id = Auth::getCurrentRole();

        return true;
    }

    protected function ajaxAction(): void
    {
    }

    private function getData(): array
    {
        if ($this->issue_id) {
            $customFields = $this->customFieldRepository->getListByIssue($this->prj_id, $this->issue_id, $this->role_id, null, true);
        } else {
            $customFields = $this->customFieldRepository->getListByProject($this->prj_id, $this->role_id, $this->form_type, null, true);
        }

        $data = [];
        foreach ($customFields as $cf) {
            $backend = $cf->getProxy();
            if (!$backend || !$backend->hasInterface(DynamicCustomFieldInterface::class)) {
                continue;
            }

            $field = $cf->toArray();
            $field['structured_data'] = $backend->getStructuredData();
            $field['controlling_field_id'] = $backend->getControllingCustomFieldId();
            $field['controlling_field_name'] = $backend->getControllingCustomFieldName();
            $field['hide_when_no_options'] = $backend->hideWhenNoOptions();
            $field['lookup_method'] = $backend->lookupMethod();

            $data[] = $field;
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

    private function getCustomFieldRepository(): CustomFieldRepository
    {
        return Doctrine::getCustomFieldRepository();
    }
}
