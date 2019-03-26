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
use Project;
use RuntimeException;
use Throwable;

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
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $request = $this->getRequest();

        $this->issue_id = $request->request->getInt('issue_id') ?: $request->query->getInt('iss_id');
        $this->cat = $request->request->get('cat');
    }

    /**
     * {@inheritdoc}
     */
    protected function canAccess(): bool
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
     * {@inheritdoc}
     */
    protected function defaultAction(): void
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

    private function insertAction(): void
    {
        $post = $this->getRequest()->request;

        $res = Authorized_Replier::manualInsert($this->issue_id, $post->get('email'));

        if ($res == 1) {
            $message = ev_gettext('Thank you, the authorized replier was inserted successfully.');
            $this->messages->addInfoMessage($message);
        } elseif ($res == -1) {
            $message = ev_gettext('An error occurred while trying to insert the authorized replier.');
            $this->messages->addErrorMessage($message);
        } elseif ($res == -2) {
            $message = ev_gettext(
                "Users with a role of 'customer' or below are not allowed to be added to the authorized repliers list."
            );
            $this->messages->addErrorMessage($message);
        }
    }

    private function deleteAction(): void
    {
        $post = $this->getRequest()->request;

        try {
            $iur_ids = $post->get('items') ?: [];
            if (!$iur_ids) {
                throw new RuntimeException('No users provided for removal.');
            }

            Authorized_Replier::removeRepliers($this->issue_id, $iur_ids);
            $message = ev_gettext('Thank you, the authorized replier was deleted successfully.');
            $this->messages->addInfoMessage($message);
        } catch (Throwable $e) {
            $this->logger->error($e);
            $message = ev_gettext('An error occurred while trying to delete the authorized replier.');
            $this->messages->addErrorMessage($message);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(): void
    {
        $repliers = Authorized_Replier::getAuthorizedRepliers($this->issue_id)[1];

        $users = Project::getAddressBook($this->prj_id, $this->issue_id);
        // add empty value which would be the default value in dropdown
        array_unshift($users, '');

        $this->tpl->assign(
            [
                'issue_id' => $this->issue_id,
                'list' => $repliers,
                'users' => $users,
            ]
        );
    }
}
