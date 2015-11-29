<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                     |
// |                                                                      |
// | This program is free software; you can redistribute it and/or modify |
// | it under the terms of the GNU General Public License as published by |
// | the Free Software Foundation; either version 2 of the License, or    |
// | (at your option) any later version.                                  |
// |                                                                      |
// | This program is distributed in the hope that it will be useful,      |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of       |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        |
// | GNU General Public License for more details.                         |
// |                                                                      |
// | You should have received a copy of the GNU General Public License    |
// | along with this program; if not, write to:                           |
// |                                                                      |
// | Free Software Foundation, Inc.                                       |
// | 51 Franklin Street, Suite 330                                        |
// | Boston, MA 02110-1301, USA.                                          |
// +----------------------------------------------------------------------+

namespace Eventum\Controller;

use Auth;
use Draft;
use Issue;
use Link_Filter;
use Mail_Queue;
use Misc;
use Note;
use Phone_Support;
use Support;
use User;

/*
 * This page is used to return a single content to the expandable table using
 * httpClient library or jQuery.
 */

class RemoteDataController extends BaseController
{
    /** @var int */
    private $usr_id;

    /** @var string */
    private $action;

    /** @var string */
    private $callback;

    /** @var string */
    private $list_id;

    /** @var string */
    private $ec_id;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $request = $this->getRequest();

        $this->action = (string)$request->get('action');
        $this->callback = (string)$request->query->get('callback');
        $this->list_id = (string)$request->get('list_id');
        $this->ec_id = (string)$request->get('ec_id');
    }

    /**
     * @inheritdoc
     */
    protected function canAccess()
    {
        Auth::checkAuthentication();

        $this->usr_id = Auth::getUserID();

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defaultAction()
    {
        $valid_functions = array(
            'email' => 'getEmail',
            'note' => 'getNote',
            'draft' => 'getDraft',
            'phone' => 'getPhoneSupport',
            'mailqueue' => 'getMailQueue',
            'description' => 'getIssueDescription',
        );

        if (in_array($this->action, array_keys($valid_functions))) {
            $method = $valid_functions[$this->action];
            $res = $this->$method($this->list_id);
        } else {
            $res = 'ERROR: Unable to call function ' . htmlspecialchars($this->action);
        }

        // convert to wanted format
        $res = array(
            'ec_id' => $this->ec_id,
            'list_id' => $this->list_id,
            'message' => $res,
        );

        if ($this->callback) {
            echo $this->callback, '(', json_encode($res), ')';
        } else {
            echo $res['message'];
        }
        exit;
    }

    /**
     * @inheritdoc
     */
    protected function prepareTemplate()
    {
    }


    public function getIssueDescription($issue_id)
    {
        if (Issue::canAccess($issue_id, $this->usr_id)) {
            $details = Issue::getDetails($issue_id);

            return Link_Filter::processText(Auth::getCurrentProject(), $details['iss_description']);
        }

        return null;
    }

    /**
     * Selects the email from the table and returns the contents.
     *
     * @param   string $id The sup_ema_id and sup_id seperated by a -.
     * @return  string A string containing the body of the email,
     */
    public function getEmail($id)
    {
        $split = explode('-', $id);
        $info = Support::getEmailDetails($split[0], $split[1]);

        if (!Issue::canAccess($info['sup_iss_id'], $this->usr_id)) {
            return '';
        }

        if (!$this->ec_id) {
            return $info['seb_body'];
        }

        return Link_Filter::processText(Auth::getCurrentProject(), nl2br(Misc::highlightQuotedReply($info['seb_body'])));
    }

    /**
     * Selects a note from the table and returns the contents.
     *
     * @param   string $id The ID of this note.
     * @return  string A string containing the note.
     */
    public function getNote($id)
    {
        $note = Note::getDetails($id);
        if (!Issue::canAccess($note['not_iss_id'], $this->usr_id)) {
            return '';
        }
        if (!$this->ec_id) {
            return $note['not_note'];
        }

        return Link_Filter::processText(Auth::getCurrentProject(), nl2br(Misc::highlightQuotedReply($note['not_note'])));
    }

    /**
     * Selects a draft from the table and returns the contents.
     *
     * @param   string $id The ID of this draft.
     * @return  string A string containing the note.
     */
    public function getDraft($id)
    {
        $info = Draft::getDetails($id);
        if (!Issue::canAccess($info['emd_iss_id'], $this->usr_id)) {
            return '';
        }
        if (!$this->ec_id) {
            return $info['emd_body'];
        }

        return Link_Filter::processText(Auth::getCurrentProject(), nl2br(htmlspecialchars($info['emd_body'])));
    }

    /**
     * Selects a phone support entry from the table and returns the contents.
     *
     * @param   string $id The phone support entry ID.
     * @return  string A string containing the description.
     */
    public function getPhoneSupport($id)
    {
        $res = Phone_Support::getDetails($id);
        if (!Issue::canAccess($res['phs_iss_id'], $this->usr_id)) {
            return '';
        }
        if (!$this->ec_id) {
            return $res['phs_description'];
        }

        return Link_Filter::processText(Auth::getCurrentProject(), nl2br(htmlspecialchars($res['phs_description'])));
    }

    /**
     * Selects a mail queue entry from the table and returns the contents.
     *
     * @param   string $id The mail queue entry ID.
     * @return  string A string containing the body.
     */
    public function getMailQueue($id)
    {
        if (Auth::getCurrentRole() < User::ROLE_DEVELOPER) {
            return null;
        }

        $res = Mail_Queue::getEntry($id);
        if (!Issue::canAccess($res['maq_iss_id'], $this->usr_id)) {
            return '';
        }
        if (!$this->ec_id) {
            return $res['maq_body'];
        }

        return Link_Filter::processText(Auth::getCurrentProject(), nl2br(htmlspecialchars($res['maq_headers'] . "\n" . $res['maq_body'])));
    }
}
