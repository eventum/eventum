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

include_once(APP_INC_PATH . "class.misc.php");
include_once(APP_INC_PATH . "class.mail.php");
include_once(APP_INC_PATH . "class.support.php");
include_once(APP_INC_PATH . "class.issue.php");
include_once(APP_INC_PATH . "class.mime_helper.php");
include_once(APP_INC_PATH . "class.date.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.notification.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.note.php");
include_once(APP_INC_PATH . "class.project.php");
include_once(APP_INC_PATH . "class.status.php");
include_once(APP_INC_PATH . "class.history.php");

/**
 * Class to handle all routing functionality
 *
 * @author  Bryan Alsdorf <bryan@mysql.com>
 * @version 1.0
 */
class Routing
{
    /**
     * Routes an email to the correct issue.
     *
     * @param   string $full_message The full email message, including headers
     * @param   integer $email_account_id The ID of the email account this email should be routed too. If empty this method will try to figure it out
     */
    function route_emails($full_message, $email_account_id = 0)
    {
        GLOBAL $HTTP_POST_VARS;

        // save the full message for logging purposes
        Support::saveRoutedEmail($full_message);

        if (preg_match("/^(boundary=).*/m", $full_message)) {
            $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
            $replacement = '$1$2; $3$4';
            $full_message = preg_replace($pattern, $replacement, $full_message);
        }
        // associate routed emails to the internal system account
        $sys_account = User::getNameEmail(APP_SYSTEM_USER_ID);
        $associated_user = $sys_account['usr_email'];

        // need some validation here
        if (empty($full_message)) {
            return array(66, "Error: The email message was empty.\n");
        }
        if (empty($associated_user)) {
            return array(78, "Error: The associated user for the email routing interface needs to be set.\n");
        }


        //
        // DON'T EDIT ANYTHING BELOW THIS LINE
        //

        // remove the reply-to: header
        if (preg_match("/^(reply-to:).*/im", $full_message)) {
            $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
        }

        // check for magic cookie
        if (Mail_API::hasMagicCookie($full_message)) {
            // strip the magic cookie
            $full_message = Mail_API::stripMagicCookie($full_message);
            $has_magic_cookie = true;
        } else {
            $has_magic_cookie = false;
        }

        Auth::createFakeCookie(APP_SYSTEM_USER_ID);

        // check if the email routing interface is even supposed to be enabled
        $setup = Setup::load();
        if ($setup['email_routing']['status'] != 'enabled') {
            return array(78, "Error: The email routing interface is disabled.\n");
        }
        $prefix = $setup['email_routing']['address_prefix'];
        // escape plus signs so 'issue+1@example.com' becomes a valid routing address
        $prefix = str_replace('+', '\+', $prefix);
        $mail_domain = quotemeta($setup['email_routing']['address_host']);
        $mail_domain_alias = quotemeta(@$setup['email_routing']['host_alias']);
        if (!empty($mail_domain_alias)) {
            $mail_domain = "(?:" . $mail_domain . "|" . $mail_domain_alias . ")";
        }
        if (empty($prefix)) {
            return array(78, "Error: Please configure the email address prefix.\n");
        }
        if (empty($mail_domain)) {
            return array(78, "Error: Please configure the email address domain.\n");
        }
        $structure = Mime_Helper::decode($full_message, true, true);

        // find which issue ID this email refers to
        @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['to'], $matches);
        @$issue_id = $matches[1];
        // validation is always a good idea
        if (empty($issue_id)) {
            // we need to try the Cc header as well
            @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['cc'], $matches);
            if (!empty($matches[1])) {
                $issue_id = $matches[1];
            } else {
                return array(65, "Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.\n");
            }
        }
        if (empty($email_account_id)) {
            $issue_prj_id = Issue::getProjectID($issue_id);
            if (empty($issue_prj_id)) {
                return array(65, "Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.\n");
            }
            $email_account_id = Email_Account::getEmailAccount($issue_prj_id);
        }

        if (empty($email_account_id)) {
            return array(78, "Error: Please provide the email account ID.\n");
        }

        $body = Mime_Helper::getMessageBody($structure);

        // hack for clients that set more then one from header
        if (is_array($structure->headers['from'])) {
            $structure->headers['from'] = $structure->headers['from'][0];
        }

        // associate the email to the issue
        $parts = array();
        Mime_Helper::parse_output($structure, $parts);

        // get the sender's email address
        $sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));

        // strip out the warning message sent to staff users
        if (($setup['email_routing']['status'] == 'enabled') &&
                ($setup['email_routing']['warning']['status'] == 'enabled')) {
            $full_message = Mail_API::stripWarningMessage($full_message);
            $body = Mail_API::stripWarningMessage($body);
        }

        $prj_id = Issue::getProjectID($issue_id);
        Auth::createFakeCookie(APP_SYSTEM_USER_ID, $prj_id);
        $staff_emails = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Customer'));
        $staff_emails = array_map('strtolower', $staff_emails);
        // only allow staff users to use the magic cookie
        if (!in_array($sender_email, array_values($staff_emails))) {
            $has_magic_cookie = false;
        }

        if (Mime_Helper::hasAttachments($full_message)) {
            $has_attachments = 1;
        } else {
            $has_attachments = 0;
        }

        // remove certain CC addresses
        if ((!empty($structure->headers['cc'])) && (@$setup['smtp']['save_outgoing_email'] == 'yes')) {
            $ccs = explode(",", @$structure->headers['cc']);
            for ($i = 0; $i < count($ccs); $i++) {
                if (Mail_API::getEmailAddress($ccs[$i]) == $setup['smtp']['save_address']) {
                    unset($ccs[$i]);
                }
            }
            @$structure->headers['cc'] = join(', ', $ccs);
        }

        // Remove excess Re's
        @$structure->headers['subject'] = Mail_API::removeExcessRe(@$structure->headers['subject'], true);

        $t = array(
            'issue_id'       => $issue_id,
            'ema_id'         => $email_account_id,
            'message_id'     => @$structure->headers['message-id'],
            'date'           => Date_API::getCurrentDateGMT(),
            'from'           => @$structure->headers['from'],
            'to'             => @$structure->headers['to'],
            'cc'             => @$structure->headers['cc'],
            'subject'        => @$structure->headers['subject'],
            'body'           => @$body,
            'full_email'     => @$full_message,
            'has_attachment' => $has_attachments,
            'headers'        => @$structure->headers
        );
        // automatically associate this incoming email with a customer
        if (Customer::hasCustomerIntegration($prj_id)) {
            if (!empty($structure->headers['from'])) {
                list($customer_id,) = Customer::getCustomerIDByEmails($prj_id, array($sender_email));
                if (!empty($customer_id)) {
                    $t['customer_id'] = $customer_id;
                }
            }
        }
        if (empty($t['customer_id'])) {
            $t['customer_id'] = "NULL";
        }

        if ((!$has_magic_cookie) && (Support::blockEmailIfNeeded($t))) {
            return true;
        }

        // re-write Threading headers if needed
        list($t['full_email'], $t['headers']) = Mail_API::rewriteThreadingHeaders($t['issue_id'], $t['full_email'], $t['headers'], "email");
        $res = Support::insertEmail($t, $structure, $sup_id);
        if ($res != -1) {
            Support::extractAttachments($issue_id, $full_message);

            // notifications about new emails are always external
            $internal_only = false;
            $assignee_only = false;
            // special case when emails are bounced back, so we don't want a notification to customers about those
            if (Notification::isBounceMessage($sender_email)) {
                // broadcast this email only to the assignees for this issue
                $internal_only = true;
                $assignee_only = true;
            }
            Notification::notifyNewEmail(Auth::getUserID(), $issue_id, $t, $internal_only, $assignee_only, '', $sup_id);
            // try to get usr_id of sender, if not, use system account
            $usr_id = User::getUserIDByEmail(Mail_API::getEmailAddress($structure->headers['from']));
            if (!$usr_id) {
                $usr_id = APP_SYSTEM_USER_ID;
            }
            // mark this issue as updated
            if ((!empty($t['customer_id'])) && ($t['customer_id'] != 'NULL')) {
                Issue::markAsUpdated($issue_id, 'customer action');
            } else {
                if ((!empty($usr_id)) && (User::getRoleByUser($usr_id, $prj_id) > User::getRoleID('Customer'))) {
                    Issue::markAsUpdated($issue_id, 'staff response');
                } else {
                    Issue::markAsUpdated($issue_id, 'user response');
                }
            }
            // log routed email
            History::add($issue_id, $usr_id, History::getTypeID('email_routed'), "Email routed from " . $structure->headers['from']);
        }

        return true;
    }


    /**
     * Routes a note to the correct issue
     *
     * @param   string $full_message The full note
     */
    function route_notes($full_message)
    {
        GLOBAL $HTTP_POST_VARS;

        // save the full message for logging purposes
        Note::saveRoutedNote($full_message);

        if (preg_match("/^(boundary=).*/m", $full_message)) {
            $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
            $replacement = '$1$2; $3$4';
            $full_message = preg_replace($pattern, $replacement, $full_message);
        }

        list($headers,) = Mime_Helper::splitHeaderBody($full_message);

        // need some validation here
        if (empty($full_message)) {
            return array(66, "Error: The email message was empty.\n");
        }


        //
        // DON'T EDIT ANYTHING BELOW THIS LINE
        //

        // remove the reply-to: header
        if (preg_match("/^(reply-to:).*/im", $full_message)) {
            $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
        }

        // check if the email routing interface is even supposed to be enabled
        $setup = Setup::load();
        if (@$setup['note_routing']['status'] != 'enabled') {
            return array(78, "Error: The internal note routing interface is disabled.\n");
        }
        $prefix = $setup['note_routing']['address_prefix'];
        // escape plus signs so 'note+1@example.com' becomes a valid routing address
        $prefix = str_replace('+', '\+', $prefix);
        $mail_domain = quotemeta($setup['note_routing']['address_host']);
        if (empty($prefix)) {
            return array(78, "Error: Please configure the email address prefix.\n");
        }
        if (empty($mail_domain)) {
            return array(78, "Error: Please configure the email address domain.\n");
        }
        $structure = Mime_Helper::decode($full_message, true, true);

        // find which issue ID this email refers to
        @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['to'], $matches);
        @$issue_id = $matches[1];
        // validation is always a good idea
        if (empty($issue_id)) {
            // we need to try the Cc header as well
            @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['cc'], $matches);
            if (!empty($matches[1])) {
                $issue_id = $matches[1];
            } else {
                return array(65, "Error: The routed note had no associated Eventum issue ID or had an invalid recipient address.\n");
            }
        }

        $prj_id = Issue::getProjectID($issue_id);
        // check if the sender is allowed in this issue' project and if it is an internal user
        $users = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Customer'));
        $sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));
        $user_emails = array_map('strtolower', array_values($users));
        if (!in_array($sender_email, $user_emails)) {
            return array(77, "Error: The sender of this email is not allowed in the project associated with issue #$issue_id.\n");
        }

        Auth::createFakeCookie(User::getUserIDByEmail($sender_email), $prj_id);

        // parse the Cc: list, if any, and add these internal users to the issue notification list
        $users = array_flip($users);
        $addresses = array();
        $to_addresses = Mail_API::getEmailAddresses(@$structure->headers['to']);
        if (count($to_addresses)) {
            $addresses = $to_addresses;
        }
        $cc_addresses = Mail_API::getEmailAddresses(@$structure->headers['cc']);
        if (count($cc_addresses)) {
            $addresses = array_merge($addresses, $cc_addresses);
        }
        $cc_users = array();
        foreach ($addresses as $email) {
            if (in_array(strtolower($email), $user_emails)) {
                $cc_users[] = $users[$email];
            }
        }

        $body = Mime_Helper::getMessageBody($structure);

        $reference_msg_id = Mail_API::getReferenceMessageID($headers);
        if (!empty($reference_msg_id)) {
            $parent_id = Note::getIDByMessageID($reference_msg_id);
        } else {
            $parent_id = false;
        }

        // insert the new note and send notification about it
        $HTTP_POST_VARS = array(
            'title'                => @$structure->headers['subject'],
            'note'                 => $body,
            'note_cc'              => $cc_users,
            'add_extra_recipients' => 'yes',
            'message_id'           => @$structure->headers['message-id'],
            'parent_id'            => $parent_id,
        );

        // add the full email to the note if there are any attachments
        // this is needed because the front end code will display attachment links
        if (Mime_Helper::hasAttachments($full_message)) {
            $HTTP_POST_VARS['blocked_msg'] = $full_message;
        }
        $res = Note::insert(Auth::getUserID(), $issue_id, false, false);
        // need to handle attachments coming from notes as well
        if ($res != -1) {
            Support::extractAttachments($issue_id, $full_message, true, $res);
        }
        History::add($issue_id, Auth::getUserID(), History::getTypeID('note_routed'), "Note routed from " . $structure->headers['from']);

        return true;
    }


    /**
     * Routes a draft to the correct issue.
     *
     * @param   string $full_message The complete draft.
     */
    function route_drafts($full_message)
    {
        GLOBAL $HTTP_POST_VARS;

        // save the full message for logging purposes
        Draft::saveRoutedMessage($full_message);

        if (preg_match("/^(boundary=).*/m", $full_message)) {
            $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
            $replacement = '$1$2; $3$4';
            $full_message = preg_replace($pattern, $replacement, $full_message);
        }

        // need some validation here
        if (empty($full_message)) {
            return array(66, "Error: The email message was empty.\n");
        }


        //
        // DON'T EDIT ANYTHING BELOW THIS LINE
        //

        // remove the reply-to: header
        if (preg_match("/^(reply-to:).*/im", $full_message)) {
            $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
        }

        // check if the draft interface is even supposed to be enabled
        $setup = Setup::load();
        if (@$setup['draft_routing']['status'] != 'enabled') {
            return array(78, "Error: The email draft interface is disabled.\n");
        }
        $prefix = $setup['draft_routing']['address_prefix'];
        // escape plus signs so 'draft+1@example.com' becomes a valid address
        $prefix = str_replace('+', '\+', $prefix);
        $mail_domain = quotemeta($setup['draft_routing']['address_host']);
        if (empty($prefix)) {
            return array(78, "Error: Please configure the email address prefix.\n");
        }
        if (empty($mail_domain)) {
            return array(78, "Error: Please configure the email address domain.\n");
        }
        $structure = Mime_Helper::decode($full_message, true, false);

        // find which issue ID this email refers to
        @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['to'], $matches);
        @$issue_id = $matches[1];
        // validation is always a good idea
        if (empty($issue_id)) {
            // we need to try the Cc header as well
            @preg_match("/$prefix(\d*)@$mail_domain/i", $structure->headers['cc'], $matches);
            if (!empty($matches[1])) {
                $issue_id = $matches[1];
            } else {
                return array(65, "Error: The routed draft had no associated Eventum issue ID or had an invalid recipient address.\n");
            }
        }

        $prj_id = Issue::getProjectID($issue_id);
        // check if the sender is allowed in this issue' project and if it is an internal user
        $users = Project::getUserEmailAssocList($prj_id, 'active', User::getRoleID('Customer'));
        $sender_email = strtolower(Mail_API::getEmailAddress($structure->headers['from']));
        $user_emails = array_map('strtolower', array_values($users));
        if (!in_array($sender_email, $user_emails)) {
            return array(77, "Error: The sender of this email is not allowed in the project associated with issue #$issue_id.\n");
        }

        Auth::createFakeCookie(User::getUserIDByEmail($sender_email), $prj_id);

        $body = Mime_Helper::getMessageBody($structure);

        Draft::saveEmail($issue_id, @$structure->headers['to'], @$structure->headers['cc'], @$structure->headers['subject'], $body, false, false, false);
        // XXX: need to handle attachments coming from drafts as well?
        History::add($issue_id, Auth::getUserID(), History::getTypeID('draft_routed'), "Draft routed from " . $structure->headers['from']);
        return true;
    }
}
?>
