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
use Edit_Reporter;
use Misc;
use Project;

class EditReporterController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'edit_reporter.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $prj_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();

        if (!Access::canChangeReporter($this->issue_id, $this->usr_id)) {
            return false;
        }

        $this->prj_id = Auth::getCurrentProject();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        if ($this->cat == 'update') {
            $this->updateReporterAction();
        }
    }

    private function updateReporterAction()
    {
        $post = $this->getRequest()->request;
        $email = trim($post->get('email'));

        $res = Edit_Reporter::update($this->issue_id, $email);
        $map = array(
            1 => array(ev_gettext('Thank you, the Reporter was updated successfully.'), Misc::MSG_INFO),
            -1 => array(ev_gettext('An error occurred while trying to update the Reporter.'), Misc::MSG_ERROR),
        );

        Misc::mapMessages($res, $map);
        $this->redirect(APP_RELATIVE_URL . 'view.php', array('id' => $this->issue_id));
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        $this->tpl->assign(
            array(
                'issue_id' => $this->issue_id,
            )
        );
    }
}
