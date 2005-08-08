<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004, 2005 MySQL AB                              |
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
// | Authors: Bryan Alsdorf <bryan@mysql.com>                             |
// +----------------------------------------------------------------------+
//

include_once(APP_INC_PATH . "workflow/class.abstract_workflow_backend.php");

/**
 * Workflow class used in intranet installation of Eventum by Eventum developers.
 * Included as an example of how the Workflow API might be used in real life.
 * 
 * @author  Bryan Alsdorf <bryan@mysql.com>
 */
class Intranet_Workflow_Backend extends Abstract_Workflow_Backend
{

    /**
     * Called when a new issue is created. If the issue was created from an email,
     * this will add the sender of the email (if an eventum user) to the
     * authorized repliers list.
     *
     * @param   integer $prj_id The projectID
     * @param   integer $issue_id The ID of the issue.
     * @param   boolean $has_TAM If this issue has a technical account manager.
     * @param   boolean $has_RR If Round Robin was used to assign this issue.
     */
    function handleNewIssue($prj_id, $issue_id, $has_TAM, $has_RR)
    {
        // Add the senders of any emails to authorized replies list
        $emails = Support::getEmailsByIssue($issue_id);

        if ((!empty($emails)) && (count($emails) > 0)) {
            foreach ($emails as $email) {
                $usr_id = User::getUserIDByEmail(Mail_API::getEmailAddress($email['sup_from']));
                if (!empty($usr_id)) {
                    Authorized_Replier::addUser($issue_id, $usr_id);
                }
            }
        }
    }


    /**
     * Called when a new message is recieved. 
     *
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   array $message An array containing the new email
     * @param   array $row The array of data that was inserted into the database.
     */
    function handleNewEmail($prj_id, $issue_id, $message, $row)
    {
        if (empty($row["issue_id"])) {
            // email not associated with issue
            $irc_message = "New Pending Email ("; 
            $irc_message .= "Subject: " . $message->headers['subject'] . ")";
            Notification::notifyIRC($prj_id, $irc_message, 0);
        }
    }
    
    
    /**
     * Called when an attempt is made to add a user or email address to the
     * notification list. 
     * 
     * @param   integer $prj_id The project ID
     * @param   integer $issue_id The ID of the issue.
     * @param   integer $subscriber_usr_id The ID of the user to subscribe if this is a real user (false otherwise).
     * @param   string $email The email address to subscribe to subscribe (if this is not a real user).
     * @param   array $types The action types.
     * @return  mixed An array of information or true to continue unchanged or false to prevent the user from being added.
     */
    function handleSubscription($prj_id, $issue_id, &$subscriber_usr_id, &$email, &$actions)
    {
        return true;
    }
    
    
    /**
     * Determines if the address should should be emailed.
     * 
     * @param   integer $prj_id The project ID
     * @param   string $address The email address to check
     * @return  boolean
     */
    function shouldEmailAddress($prj_id, $address)
    {
        if (strtolower($address) == "addnewemployee@mysql.com") {
            return false;
        } else {
            return true;
        }
    }
}
?>