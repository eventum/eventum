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
use Misc;

class SpellCheckController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'spell_check.tpl.html';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        $request = $this->getRequest();
        $form_name = $request->query->get('form_name');

        if ($form_name) {
            // show temporary form
            $this->tpl->assign('show_temp_form', 'yes');
        } else {
            $textarea = $request->query->get('textarea');
            $this->tpl->assign('spell_check', Misc::checkSpelling($textarea));
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
