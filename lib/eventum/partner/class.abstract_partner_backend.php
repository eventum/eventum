<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2011 Eventum Team              .                       |
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
// | 59 Temple Place - Suite 330                                          |
// | Boston, MA 02111-1307, USA.                                          |
// +----------------------------------------------------------------------+
// | Authors: Bryan Alsdorf <balsdorf@gmail.com>                          |
// +----------------------------------------------------------------------+


/**
 * Abstract parent class for partner business logic.
 */
abstract class Abstract_Partner_Backend
{
    public function __construct()
    {
        // setup the backend
    }

    public function getCode()
    {
        // return the code grabbed from this class name
    }

    abstract public function getName();

    public function issueAdded($iss_id) {}

    public function issueRemoved($iss_id) {}

    public function canViewIssue($iss_id, $usr_id) {}

    public function handleNewEmail($iss_id, $sup_id) {}

    public function handleNewNote($iss_id, $not_id) {}

    public function handleIssueChange($iss_id, $usr_id, $old_details, $changes) {}

    public function getIssueMessage($iss_id) {}
}