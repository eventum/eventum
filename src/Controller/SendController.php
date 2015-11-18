<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2015 Eventum Team.                                |
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
use Issue;

/**
 * Class handling send.php
 */
class SendController extends BaseController
{
    /** @var string */
    protected $tpl_name = 'send.tpl.html';

    /** @var string */
    private $cat;

    /** @var int */
    private $issue_id;

    /** @var int */
    private $usr_id;

    /**
     * create variables from request, etc
     */
    public function configure()
    {
        $this->usr_id = Auth::getUserID();

        $request = $this->getRequest();
        $this->issue_id = $request->get('issue_id');
        $this->cat = $request->get('cat');
    }

    public function canAccess()
    {
        return Issue::canAccess($this->issue_id, $this->usr_id);
    }

    /**
     * run the controller
     */
    public function defaultAction()
    {
        switch ($this->cat) {
            case 'send_email':
                return $this->sendEmailAction();
            case 'save_draft':
                return $this->saveDraftAction();
            case 'update_draft':
                return $this->updateDraftAction();
            case 'view_draft':
                return $this->viewDraftAction();
            case 'create_draft':
                return $this->createDraftAction();
            case 'reply':
                return $this->replyAction();
        }
        return null;
    }

    public function sendEmailAction()
    {
        return true;
    }
}
