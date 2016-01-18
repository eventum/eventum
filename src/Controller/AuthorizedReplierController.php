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
use Authorized_Replier;
use Misc;
use Project;

class AuthorizedReplierController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'authorized_replier.tpl.html';

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
        if (!Access::canViewAuthorizedRepliers($this->issue_id, $this->usr_id)) {
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
        switch ($this->cat) {
            case 'insert':
                $this->insertAction();
                break;

            case 'delete':
                $this->deleteAction();
                break;
        }
    }

    private function insertAction()
    {
        $post = $this->getRequest()->request;

        $res = Authorized_Replier::manualInsert($this->issue_id, $post->get('email'));

        if ($res == 1) {
            $message = ev_gettext('Thank you, the authorized replier was inserted successfully.');
            Misc::setMessage($message);
        } elseif ($res == -1) {
            $message = ev_gettext('An error occurred while trying to insert the authorized replier.');
            Misc::setMessage($message, Misc::MSG_ERROR);
        } elseif ($res == -2) {
            $message = ev_gettext(
                "Users with a role of 'customer' or below are not allowed to be added to the authorized repliers list."
            );
            Misc::setMessage($message, Misc::MSG_ERROR);
        }
    }

    private function deleteAction()
    {
        $post = $this->getRequest()->request;

        $res = Authorized_Replier::removeRepliers($post->get('items'));
        if ($res == 1) {
            $message = ev_gettext('Thank you, the authorized replier was deleted successfully.');
            Misc::setMessage($message);
        } elseif ($res == -1) {
            $message = ev_gettext('An error occurred while trying to delete the authorized replier.');
            Misc::setMessage($message, Misc::MSG_ERROR);
        }
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
        list(, $repliers) = Authorized_Replier::getAuthorizedRepliers($this->issue_id);

        $this->tpl->assign(
            array(
                'issue_id' => $this->issue_id,
                'list' => $repliers,
                'assoc_users' => Project::getAddressBook($this->prj_id, $this->issue_id),
            )
        );
    }
}
