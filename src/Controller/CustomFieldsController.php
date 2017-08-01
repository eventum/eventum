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
use Custom_Field;
use Issue;

class CustomFieldsController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'custom_fields_form.tpl.html';

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

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('issue_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();

        if (!Access::canUpdateIssue($this->issue_id, $this->usr_id)) {
            return false;
        }

        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction()
    {
        if ($this->cat == 'update_values') {
            $this->updateValuesAction();
        }
    }

    private function updateValuesAction()
    {
        $res = Custom_Field::updateFromPost(true);
        if (is_array($res)) {
            $res = 1;
        }
        $this->tpl->assign('update_result', $res);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate()
    {
        $custom_fields = Custom_Field::getListByIssue($this->prj_id, $this->issue_id, null, false, true);

        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'custom_fields' => $custom_fields,
            ]
        );
    }
}
