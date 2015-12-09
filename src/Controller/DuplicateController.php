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
use Issue;
use Misc;

class DuplicateController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'duplicate.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id');
        $this->cat = $request->request->get('cat');
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
        if ($this->cat == 'mark') {
            $this->markAsDuplicateAction();
        }
    }

    private function markAsDuplicateAction()
    {
        $res = Issue::markAsDuplicate($this->issue_id);
        $map = array(
            1 => array(ev_gettext('Thank you, the issue was marked as a duplicate successfully'), Misc::MSG_INFO),
            -1 => array(ev_gettext('Sorry, an error happened while trying to run your query.'), Misc::MSG_ERROR),
        );
        Misc::mapMessages($res, $map);

        $this->redirect(APP_RELATIVE_URL . 'view.php', array('id' => $this->issue_id));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }
}
