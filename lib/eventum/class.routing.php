<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
// | Copyright (c) 2011 - 2015 Eventum Team.                              |
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
//

/**
 * Class to handle all routing functionality
 */
class Routing
{
    /** data format error */
    const EX_DATAERR = 65;

    /** cannot open input */
    const EX_NOINPUT = 66;

    /** addressee unknown */
    const EX_NOUSER = 67;

    /** permission denied */
    const EX_NOPERM = 77;

    /** configuration error */
    const EX_CONFIG = 78;

    /**
     * Route all mail kinds: emails, notes, drafts (in that order) processing "To:" and "Cc:" headers
     *
     * @param string &$full_message
     * @return array|bool
     */
    public static function route(&$full_message)
    {
        $structure = Mime_Helper::decode($full_message, false, true);

        $addresses = array();
        if (isset($structure->headers['to'])) {
            $addresses[] = $structure->headers['to'];
        }
        if (isset($structure->headers['cc'])) {
            $addresses[] = $structure->headers['cc'];
        }
        // free memory
        unset($structure);

        $types = array('email', 'note', 'draft');
        foreach ($addresses as $address) {
            foreach ($types as $type) {
                if (self::getMatchingIssueIDs($address, $type) === false) {
                    continue;
                }
                $method = "route_{$type}s";
                $return = self::$method($full_message);
                if ($return === true || is_array($return)) {
                    return $return;
                }
            }
        }

        // did not handle anything
        return false;
    }

    /**
     * Routes an email to the correct issue.
     *
     * @param   string $full_message The full email message, including headers
     * @return  mixed   true or array(ERROR_CODE, ERROR_STRING) in case of failure
     */
    public static function route_emails($full_message)
    {
        // need some validation here
        if (empty($full_message)) {
            return array(self::EX_NOINPUT, ev_gettext('Error: The email message was empty') . ".\n");
        }

        // save the full message for logging purposes
        Support::saveRoutedEmail($full_message);

        // check if the email routing interface is even supposed to be enabled
        $setup = Setup::get();
        if ($setup['email_routing']['status'] != 'enabled') {
            return array(self::EX_CONFIG, ev_gettext('Error: The email routing interface is disabled.') . "\n");
        }
        if (empty($setup['email_routing']['address_prefix'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please configure the email address prefix.') . "\n");
        }
        if (empty($setup['email_routing']['address_host'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please configure the email address domain.') . "\n");
        }

        // associate routed emails to the internal system account
        $sys_account = User::getNameEmail(APP_SYSTEM_USER_ID);
        if (empty($sys_account['usr_email'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: The associated user for the email routing interface needs to be set.') . "\n");
        }
        unset($sys_account);

        // join the Content-Type line (for easier parsing?)
        if (preg_match('/^boundary=/m', $full_message)) {
            $pattern = "#(Content-Type: multipart/.+); ?\r?\n(boundary=.*)$#im";
            $replacement = '$1; $2';
            $full_message = preg_replace($pattern, $replacement, $full_message);
        }

        // remove the reply-to: header
        if (preg_match('/^reply-to:.*/im', $full_message)) {
            $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
        }

        AuthCookie::setAuthCookie(APP_SYSTEM_USER_ID);

        $structure = Mime_Helper::decode($full_message, true, true);

        // find which issue ID this email refers to
        if (isset($structure->headers['to'])) {
            $issue_id = self::getMatchingIssueIDs($structure->headers['to'], 'email');
        }
        // we need to try the Cc header as well
        if (empty($issue_id) and isset($structure->headers['cc'])) {
            $issue_id = self::getMatchingIssueIDs($structure->headers['cc'], 'email');
        }

        if (empty($issue_id)) {
            return array(self::EX_DATAERR, ev_gettext('Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.') . "\n");
        }

        $issue_prj_id = Issue::getProjectID($issue_id);
        if (empty($issue_prj_id)) {
            return array(self::EX_DATAERR, ev_gettext('Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.') . "\n");
        }

        $email_account_id = Email_Account::getEmailAccount($issue_prj_id);
        if (empty($email_account_id)) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please provide the email account ID.') . "\n");
        }

        $body = $structure->body;

        // hack for clients that set more then one from header
        if (is_array($structure->headers['from'])) {
            $structure->headers['from'] = $structure->headers['from'][0];
        }

        // associate the email to the issue
        $parts = array();
        Mime_Helper::parse_output($structure, $parts);

        // get the sender's email address
        $sender_email = strtolower(Mail_Helper::getEmailAddress($structure->headers['from']));

        // strip out the warning message sent to staff users
        if (($setup['email_routing']['status'] == 'enabled') &&
                ($setup['email_routing']['warning']['status'] == 'enabled')) {
            $full_message = Mail_Helper::stripWarningMessage($full_message);
            $body = Mail_Helper::stripWarningMessage($body);
        }

        $prj_id = Issue::getProjectID($issue_id);
        AuthCookie::setAuthCookie(APP_SYSTEM_USER_ID);
        AuthCookie::setProjectCookie($prj_id);

        if (Mime_Helper::hasAttachments($structure)) {
            $has_attachments = 1;
        } else {
            $has_attachments = 0;
        }

        // remove certain CC addresses
        if ((!empty($structure->headers['cc'])) && ($setup['smtp']['save_outgoing_email'] == 'yes')) {
            $ccs = explode(',', @$structure->headers['cc']);
            foreach ($ccs as $i => $address) {
                if (Mail_Helper::getEmailAddress($address) == $setup['smtp']['save_address']) {
                    unset($ccs[$i]);
                }
            }
            $structure->headers['cc'] = implode(', ', $ccs);
        }

        // Remove excess Re's
        $structure->headers['subject'] = Mail_Helper::removeExcessRe(@$structure->headers['subject'], true);

        $t = array(
            'issue_id'       => $issue_id,
            'ema_id'         => $email_account_id,
            'message_id'     => @$structure->headers['message-id'],
            'date'           => Date_Helper::getCurrentDateGMT(),
            'from'           => @$structure->headers['from'],
            'to'             => @$structure->headers['to'],
            'cc'             => @$structure->headers['cc'],
            'subject'        => @$structure->headers['subject'],
            'body'           => @$body,
            'full_email'     => @$full_message,
            'has_attachment' => $has_attachments,
            'headers'        => @$structure->headers,
        );
        // automatically associate this incoming email with a customer
        if (CRM::hasCustomerIntegration($prj_id)) {
            $crm = CRM::getInstance($prj_id);
            if (!empty($structure->headers['from'])) {
                try {
                    $contact = $crm->getContactByEmail($sender_email);
                    $issue_contract = $crm->getContract(Issue::getContractID($issue_id));
                    if ($contact->canAccessContract($issue_contract)) {
                        $t['customer_id'] = $issue_contract->getCustomerID();
                    }
                } catch (CRMException $e) {
                }
            }
        }
        if (empty($t['customer_id'])) {
            $t['customer_id'] = null;
        }

        if (Support::blockEmailIfNeeded($t)) {
            return true;
        }

        // re-write Threading headers if needed
        list($t['full_email'], $t['headers']) = Mail_Helper::rewriteThreadingHeaders($t['issue_id'], $t['full_email'], $t['headers'], 'email');
        $res = Support::insertEmail($t, $structure, $sup_id);
        if ($res != -1) {
            Support::extractAttachments($issue_id, $structure);

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
            $usr_id = User::getUserIDByEmail(Mail_Helper::getEmailAddress($structure->headers['from']));
            if (!$usr_id) {
                $usr_id = APP_SYSTEM_USER_ID;
            }
            // mark this issue as updated
            if ((!empty($t['customer_id'])) && ($t['customer_id'] != null)) {
                Issue::markAsUpdated($issue_id, 'customer action');
            } else {
                if ((!empty($usr_id)) && ($usr_id != APP_SYSTEM_USER_ID) &&
                        (User::getRoleByUser($usr_id, $prj_id) > User::ROLE_CUSTOMER)) {
                    Issue::markAsUpdated($issue_id, 'staff response');
                } else {
                    Issue::markAsUpdated($issue_id, 'user response');
                }
            }

            // log routed email
            History::add($issue_id, $usr_id, 'email_routed', 'Email routed from {from}', array(
                'from' => $structure->headers['from'],
            ));
        }

        return true;
    }

    /**
     * Routes a note to the correct issue
     *
     * @param   string $full_message The full note
     * @return  mixed   true or array(ERROR_CODE, ERROR_STRING) in case of failure
     */
    public static function route_notes($full_message)
    {
        // save the full message for logging purposes
        Note::saveRoutedNote($full_message);

        // join the Content-Type line (for easier parsing?)
        if (preg_match('/^boundary=/m', $full_message)) {
            $pattern = "#(Content-Type: multipart/.+); ?\r?\n(boundary=.*)$#im";
            $replacement = '$1; $2';
            $full_message = preg_replace($pattern, $replacement, $full_message);
        }

        list($headers) = Mime_Helper::splitHeaderBody($full_message);

        // need some validation here
        if (empty($full_message)) {
            return array(self::EX_NOINPUT, ev_gettext('Error: The email message was empty.') . "\n");
        }

        // remove the reply-to: header
        if (preg_match('/^reply-to:.*/im', $full_message)) {
            $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
        }

        // check if the email routing interface is even supposed to be enabled
        $setup = Setup::get();
        if ($setup['note_routing']['status'] != 'enabled') {
            return array(self::EX_CONFIG, ev_gettext('Error: The internal note routing interface is disabled.') . "\n");
        }
        if (empty($setup['note_routing']['address_prefix'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please configure the email address prefix.') . "\n");
        }
        if (empty($setup['note_routing']['address_host'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please configure the email address domain.') . "\n");
        }
        $structure = Mime_Helper::decode($full_message, true, true);

        // find which issue ID this email refers to
        if (isset($structure->headers['to'])) {
            $issue_id = self::getMatchingIssueIDs($structure->headers['to'], 'note');
        }
        // validation is always a good idea
        if (empty($issue_id) and isset($structure->headers['cc'])) {
            // we need to try the Cc header as well
            $issue_id = self::getMatchingIssueIDs($structure->headers['cc'], 'note');
        }

        if (empty($issue_id)) {
            return array(self::EX_DATAERR, ev_gettext('Error: The routed note had no associated Eventum issue ID or had an invalid recipient address.') . "\n");
        }

        $prj_id = Issue::getProjectID($issue_id);
        // check if the sender is allowed in this issue' project and if it is an internal user
        $sender_email = strtolower(Mail_Helper::getEmailAddress($structure->headers['from']));
        $sender_usr_id = User::getUserIDByEmail($sender_email, true);
        if (((empty($sender_usr_id)) || (User::getRoleByUser($sender_usr_id, $prj_id) < User::ROLE_USER) ||
                (User::isPartner($sender_usr_id) && !Access::canViewInternalNotes($issue_id, $sender_usr_id))) &&
                ((!Workflow::canSendNote($prj_id, $issue_id, $sender_email, $structure)))) {
            return array(self::EX_NOPERM, ev_gettext("Error: The sender of this email is not allowed in the project associated with issue #$issue_id.") . "\n");
        }

        if (empty($sender_usr_id)) {
            $sender_usr_id = APP_SYSTEM_USER_ID;
            $unknown_user = $structure->headers['from'];
        } else {
            $unknown_user = false;
        }
        AuthCookie::setAuthCookie($sender_usr_id);
        AuthCookie::setProjectCookie($prj_id);

        // parse the Cc: list, if any, and add these internal users to the issue notification list
        $addresses = array();
        $to_addresses = Mail_Helper::getEmailAddresses(@$structure->headers['to']);
        if (count($to_addresses)) {
            $addresses = $to_addresses;
        }
        $cc_addresses = Mail_Helper::getEmailAddresses(@$structure->headers['cc']);
        if (count($cc_addresses)) {
            $addresses = array_merge($addresses, $cc_addresses);
        }
        $cc_users = array();
        foreach ($addresses as $email) {
            $cc_usr_id = User::getUserIDByEmail(strtolower($email), true);
            if ((!empty($cc_usr_id)) && (User::getRoleByUser($cc_usr_id, $prj_id) >= User::ROLE_USER)) {
                $cc_users[] = $cc_usr_id;
            }
        }

        $body = $structure->body;
        $reference_msg_id = Mail_Helper::getReferenceMessageID($headers);
        if (!empty($reference_msg_id)) {
            $parent_id = Note::getIDByMessageID($reference_msg_id);
        } else {
            $parent_id = false;
        }

        // insert the new note and send notification about it
        $_POST = array(
            'title'                => @$structure->headers['subject'],
            'note'                 => $body,
            'note_cc'              => $cc_users,
            'add_extra_recipients' => 'yes',
            'message_id'           => @$structure->headers['message-id'],
            'parent_id'            => $parent_id,
        );

        // add the full email to the note if there are any attachments
        // this is needed because the front end code will display attachment links
        if (Mime_Helper::hasAttachments($structure)) {
            $_POST['full_message'] = $full_message;
        }

        $usr_id = Auth::getUserID();
        $res = Note::insertFromPost($usr_id, $issue_id, $unknown_user, false);
        // need to handle attachments coming from notes as well
        if ($res != -1) {
            Support::extractAttachments($issue_id, $structure, true, $res);
        }

        // FIXME! $res == -2 is not handled
        History::add($issue_id, $usr_id, 'note_routed', 'Note routed from {user}', array(
            'user' => $structure->headers['from'],
        ));

        return true;
    }

    /**
     * Routes a draft to the correct issue.
     *
     * @param   string $full_message The complete draft.
     * @return  mixed   true or array(ERROR_CODE, ERROR_STRING) in case of failure
     */
    public static function route_drafts($full_message)
    {
        // save the full message for logging purposes
        Draft::saveRoutedMessage($full_message);

        if (preg_match('/^(boundary=).*/m', $full_message)) {
            $pattern = "/(Content-Type: multipart\/)(.+); ?\r?\n(boundary=)(.*)$/im";
            $replacement = '$1$2; $3$4';
            $full_message = preg_replace($pattern, $replacement, $full_message);
        }

        // need some validation here
        if (empty($full_message)) {
            return array(self::EX_NOINPUT, ev_gettext('Error: The email message was empty.') . "\n");
        }

        // remove the reply-to: header
        if (preg_match('/^(reply-to:).*/im', $full_message)) {
            $full_message = preg_replace("/^(reply-to:).*\n/im", '', $full_message, 1);
        }

        // check if the draft interface is even supposed to be enabled
        $setup = Setup::get();
        if ($setup['draft_routing']['status'] != 'enabled') {
            return array(self::EX_CONFIG, ev_gettext('Error: The email draft interface is disabled.') . "\n");
        }
        if (empty($setup['draft_routing']['address_prefix'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please configure the email address prefix.') . "\n");
        }
        if (empty($setup['draft_routing']['address_host'])) {
            return array(self::EX_CONFIG, ev_gettext('Error: Please configure the email address domain.') . "\n");
        }

        $structure = Mime_Helper::decode($full_message, true, false);

        // find which issue ID this email refers to
        if (isset($structure->headers['to'])) {
            $issue_id = self::getMatchingIssueIDs($structure->headers['to'], 'draft');
        }
        // validation is always a good idea
        if (empty($issue_id) and isset($structure->headers['cc'])) {
            // we need to try the Cc header as well
            $issue_id = self::getMatchingIssueIDs($structure->headers['cc'], 'draft');
        }

        if (empty($issue_id)) {
            return array(self::EX_DATAERR, ev_gettext('Error: The routed email had no associated Eventum issue ID or had an invalid recipient address.') . "\n");
        }

        $prj_id = Issue::getProjectID($issue_id);
        // check if the sender is allowed in this issue' project and if it is an internal user
        $sender_email = strtolower(Mail_Helper::getEmailAddress($structure->headers['from']));
        $sender_usr_id = User::getUserIDByEmail($sender_email, true);
        if (!empty($sender_usr_id)) {
            $sender_role = User::getRoleByUser($sender_usr_id, $prj_id);
            if ($sender_role < User::ROLE_USER) {
                return array(self::EX_NOPERM, ev_gettext("Error: The sender of this email is not allowed in the project associated with issue #$issue_id.") . "\n");
            }
        }

        AuthCookie::setAuthCookie(User::getUserIDByEmail($sender_email));
        AuthCookie::setProjectCookie($prj_id);

        $body = $structure->body;

        Draft::saveEmail($issue_id, @$structure->headers['to'], @$structure->headers['cc'], @$structure->headers['subject'], $body, false, false, false);
        // XXX: need to handle attachments coming from drafts as well?
        $usr_id = Auth::getUserID();
        History::add($issue_id, $usr_id, 'draft_routed', 'Draft routed from {from}', array('from' => $structure->headers['from']));

        return true;
    }

    /**
     * Check for $adresses for matches
     *
     * @param   mixed   $addresses to check
     * @param   string  $type Type of address match to find (email, note, draft)
     * @return  int|bool $issue_id in case of match otherwise false
     */
    public static function getMatchingIssueIDs($addresses, $type)
    {
        $setup = Setup::get();
        $settings = $setup["${type}_routing"];
        if (!$settings) {
            return false;
        }

        if (empty($settings['address_prefix'])) {
            return false;
        }
        // escape plus signs so 'issue+1@example.com' becomes a valid routing address
        $prefix = quotemeta($settings['address_prefix']);

        if (empty($settings['address_host'])) {
            return false;
        }

        $mail_domain = quotemeta($settings['address_host']);

        if (!empty($settings['host_alias'])) {
            // XXX: legacy split by '|' as well
            if (strstr($settings['host_alias'], '|')) {
                $host_aliases = explode('|', $settings['host_alias']);
            } else {
                $host_aliases = explode(' ', $settings['host_alias']);
            }
            $host_aliases = implode('|', array_map(function ($s) { return quotemeta($s); }, $host_aliases));

            $mail_domain = '(?:' . $mail_domain . '|' . $host_aliases . ')';
        }

        // if there are multiple CC or To headers Mail_Mime creates array.
        // handle both cases (strings and arrays).
        if (!is_array($addresses)) {
            $addresses = array($addresses);
        }

        // everything safely escaped and checked, try matching address
        foreach ($addresses as $address) {
            if (preg_match("/$prefix(\d*)@$mail_domain/i", $address, $matches)) {
                return (int) $matches[1];
            }
        }

        return false;
    }
}
