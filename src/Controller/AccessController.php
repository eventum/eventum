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
use Issue;
use Project;
use User;

class AccessController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'access.tpl.html';

    /** @var int */
    private $issue_id;

    /** @var string */
    private $cat;

    /** @var int */
    private $usr_id;

    /** @var int */
    private $sub_id;

    /** @var int */
    private $prj_id;

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat') ?: $request->query->get('cat');
        $this->sub_id = $request->query->getInt('id');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
    {
        Auth::checkAuthentication(null, true);

        $this->usr_id = Auth::getUserID();
        $this->prj_id = Issue::getProjectID($this->issue_id);

        return Access::canChangeAccessLevel($this->issue_id, $this->usr_id);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'set_level':
                $this->setLevelAction();
                break;
            case 'update_users':
                $this->updateUsersAction();
                break;
        }
    }

    private function setLevelAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Issue::setAccessLevel($this->issue_id, $post->get('level'));
        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Thank you, the access level has been updated.'));
        } else {
            $this->messages->addErrorMessage(ev_gettext('Sorry, there was an error setting the access level'));
        }
    }

    private function updateUsersAction(): void
    {
        $post = $this->getRequest()->request;

        $new_access_list = $post->get('access_list');
        $old_access_list = Access::getAccessList($this->issue_id);

        foreach (array_diff($old_access_list, $new_access_list) as $usr_id) {
            Access::removeUserFromIssue($this->issue_id, $usr_id);
        }

        foreach (array_diff($new_access_list, $old_access_list) as $usr_id) {
            Access::addUserToIssue($this->issue_id, $usr_id);
        }

        $this->messages->addInfoMessage(ev_gettext('Thank you, the access list has been updated.'));
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'levels' => Access::getAccessLevels(),
                'level' => Issue::getAccessLevel($this->issue_id),
                'users' => Project::getUserAssocList($this->prj_id, 'active', User::ROLE_CUSTOMER),
                'access_list' => Access::getAccessList($this->issue_id),
            ]
        );
    }
}
