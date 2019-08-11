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
use Notification;
use Project;
use Setup;

class NotificationController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'notification.tpl.html';

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
        $this->prj_id = Auth::getCurrentProject();

        return Access::canViewNotificationList($this->issue_id, $this->usr_id);
    }

    /**
     * {@inheritdoc}
     */
    protected function defaultAction(): void
    {
        switch ($this->cat) {
            case 'insert':
                $this->insertAction();
                break;
            case 'update':
                $this->updateAction();
                break;
            case 'edit':
                // does nothing now, but keep to have info such param exists
                break;
            case 'delete':
                $this->deleteAction();
                break;
        }
    }

    private function insertAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Notification::subscribeEmail($this->usr_id, $this->issue_id, $post->get('email'), $post->get('actions'));
        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Thank you, the email has been subscribed to the issue.'));
        }
    }

    private function updateAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Notification::update($this->issue_id, $post->get('id'), $post->get('email'));
        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Thank you, the notification entry was updated successfully.'));
        } elseif ($res == -1) {
            $this->messages->addErrorMessage(ev_gettext('An error occurred while trying to update the notification entry.'));
        } elseif ($res == -2) {
            $this->messages->addErrorMessage(ev_gettext('Error: the given email address is not allowed to be added to the notification list.'));
        }

        $this->redirect(Setup::getRelativeUrl() . 'notification.php', ['iss_id' => $this->issue_id]);
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Notification::remove($post->get('items'));
        if ($res == 1) {
            $this->messages->addInfoMessage(ev_gettext('Thank you, the items have been deleted.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $default_actions = Notification::getDefaultActions();

        if ($this->sub_id) {
            $info = Notification::getDetails($this->sub_id);
        } else {
            $info = [
                'updated' => 0,
                'closed' => 0,
                'files' => 0,
                'emails' => 0,
            ];
            foreach ($default_actions as $action) {
                $info[$action] = 1;
            }
        }

        $users = Project::getAddressBook($this->prj_id, $this->issue_id);
        // add empty value which would be the default value in dropdown
        array_unshift($users, '');

        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'default_actions' => $default_actions,
                'info' => $info,
                'list' => Notification::getSubscriberListing($this->issue_id),
                'users' => $users,
            ]
        );
    }
}
