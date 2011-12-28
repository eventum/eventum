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
 * Example class for partner business logic.
 */
class Example_Partner_Backend extends Abstract_Partner_Backend
{
    public function __construct()
    {
        // setup the backend
    }

    public function getName()
    {
        return "Example";
    }

    public function issueAdded($iss_id)
    {
        echo "partner: issue $iss_id added for " . $this->getName();
    }

    public function issueRemoved($iss_id)
    {
        echo "partner: issue $iss_id removed for " . $this->getName();
    }

    public function canViewIssue($iss_id, $usr_id)
    {

    }

    public function handleNewEmail($iss_id, $sup_id)
    {
        echo "partner: new email $sup_id on issue $iss_id";
    }

    public function handleNewNote($iss_id, $not_id)
    {
        echo "partner: new note $not_id on $iss_id";
    }

    public function handleIssueChange($iss_id, $usr_id, $old_details, $changes)
    {
        echo "partner: issue $iss_id changed";
    }

    public function getIssueMessage($iss_id)
    {
        return "foo blah blah";
    }

    public static function canUserAccessFeature($usr_id, $feature)
    {
        switch($feature) {
            case 'create_issue':
                return false;
            case 'associate_emails':
                return false;
            case 'reports':
                return false;
        }
    }

    public static function canUserAccessIssueSection($usr_id, $section)
    {
        switch($section) {
            case 'partners':
                return false;
            case 'drafts':
                return false;
            case 'time':
                return false;
            case 'notes':
                return false;
            case 'phone':
                return false;
        }
    }
}