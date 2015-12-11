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

use Auth;
use Impact_Analysis;

class RequirementController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'requirement.tpl.html';

    /** @var int */
    private $isr_id;

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->isr_id = $request->request->getInt('isr_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'set_analysis') {
            $res = Impact_Analysis::update($this->isr_id);
            $this->tpl->assign('set_analysis_result', $res);
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
