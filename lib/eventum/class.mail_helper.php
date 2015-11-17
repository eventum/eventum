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
 * Class to handle the business logic related to sending email to
 * outside recipients. This class utilizes the PEAR::Mail
 * infrastructure to deliver email in a compatible way across
 * different platforms.
 */

class Mail_Helper
{
    // variable to keep the Mail_mime object
    public $mime;
    // variable to keep the headers to be used in the email
    public $headers = '';
    // text version of this message
    public $text_body = '';

    /**
     * Class constructor. It includes and initializes the required
     * PEAR::Mail related objects
     *
     */
    public function __construct()
    {
        $this->mime = new Mail_mime("\r\n");
    }

    /**
     * Correctly formats the subject line of outgoing emails/notes
     *
     * @param   integer $issue_id The issue ID
     * @param   string $subject The subject to be formatted
     * @return  string The formatted subject
     */
    public static function formatSubject($issue_id, $subject)
    {
        return "[#$issue_id] " . trim(preg_replace("/\[#$issue_id\] {0,1}/", '', $subject));
    }

    /**
     * Believe it or not, this is a method that will remove excess occurrences
     * of 'Re:' that commonly are found in email subject lines.
     * If the second parameter is true, issue #'s will also be stripped.
     *
     * @param   string $subject The subject line
     * @param   boolean $remove_issue_id If the issue ID should be removed
     * @return  string The subject line with the extra occurrences removed from it
     */
    public static function removeExcessRe($subject, $remove_issue_id = false)
    {
        if ($remove_issue_id) {
            $subject = trim(preg_replace("/\[#\d+\] {0,1}/", '', $subject));
        }
        // XXX: this works in most cases,
        // probably the reply prefixes should be configurable per Eventum install
        $re_pattern = "/(\[#\d+\] ){0,1}(([Rr][Ee][Ss]?|Ответ|Antwort|SV|[Aa][Ww]|[Rr][Ii][Ff]\.?)(\[[0-9]+\])?[ \t]*: ){2}(.*)/";
        if (preg_match($re_pattern, $subject, $matches)) {
            // TRANSLATORS: %1 = email subject
            $re_format = '$1'.ev_gettext('Re: %1$s', '$5');
            $subject = preg_replace($re_pattern, $re_format, $subject);

            return self::removeExcessRe($subject);
        } else {
            return $subject;
        }
    }

    /**
     * Returns the canned explanation about why an email message was blocked
     * and saved into an internal note.
     *
     * @return  string The canned explanation
     */
    public static function getCannedBlockedMsgExplanation()
    {
        $msg = ev_gettext('WARNING: This message was blocked because the sender was not allowed to send emails to the associated issue.') . ' ';
        $msg .= ev_gettext('Only staff members listed in the assignment or authorized replier fields can send emails.') . "\n";
        $msg .= str_repeat('-', 70) . "\n\n";

        return $msg;
    }

    /**
     * Checks whether the given headers are from a vacation
     * auto-responder message or not.
     *
     * @param   array $headers The list of headers
     * @return  boolean
     */
    public static function isVacationAutoResponder($headers)
    {
        // loop through the headers and make sure they are all lowercase.
        foreach ($headers as $key => $value) {
            $headers[strtolower($key)] = $value;
        }

        if ((@$headers['x-vacationmessage'] == 'Yes') || ((isset($headers['auto-submitted'])) && (!empty($headers['auto-submitted'])))) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method used to parse a string and return all email addresses contained
     * within it.
     *
     * @param   string $str The string containing email addresses
     * @return  array The list of email addresses
     */
    public static function getEmailAddresses($str)
    {
        $str = self::fixAddressQuoting($str);
        $str = Mime_Helper::encode($str);
        $structs = self::parseAddressList($str);
        $addresses = array();
        foreach ($structs as $structure) {
            if ((!empty($structure->mailbox)) && (!empty($structure->host))) {
                $addresses[] = $structure->mailbox . '@' . $structure->host;
            }
        }

        return $addresses;
    }

    /**
     * Wrapper around Mail_RFC822::parseAddressList to avoid calling it statically
     *
     * @param string  $address         The address(es) to validate.
     * @param string  $default_domain  Default domain/host etc.
     * @param boolean $nest_groups     Whether to return the structure with groups nested for easier viewing.
     * @param boolean $validate        Whether to validate atoms. Turn this off if you need to run addresses through before encoding the personal names, for instance.
     * @return array A structured array of addresses.
     */
    public static function parseAddressList($address, $default_domain = null, $nest_groups = null, $validate = null, $limit = null)
    {
        $obj = new Mail_RFC822($address, $default_domain, $nest_groups, $validate, $limit);

        return $obj->parseAddressList();
    }

    /**
     * Method used to build a properly quoted email address, in the form of
     * "Sender Name" <sender@example.com>.
     *
     * @param   string $address The email address value
     * @return  array The address information
     */
    public static function fixAddressQuoting($address)
    {
        // split multiple addresses if needed
        $addresses = self::splitAddresses($address);

        $return = array();
        foreach ($addresses as $address) {
            // check if we have a <
            if ((strstr($address, '<')) && (!Mime_Helper::isQuotedPrintable($address))) {
                $address = stripslashes(trim($address));
                // is the address in the format 'name' <address> ?
                if ((strstr($address, "'")) || (strstr($address, '.'))) {
                    $bracket_pos = strrpos($address, '<');
                    if ($bracket_pos != 0) {
                        $bracket_pos = $bracket_pos - 1;
                    }
                    $first_part = substr($address, 0, $bracket_pos);
                    if (!empty($first_part)) {
                        $first_part = '"' . str_replace('"', '\"', preg_replace('/(^")|("$)/', '', $first_part)) . '"';
                    }
                    $second_part = substr($address, strrpos($address, '<'));
                    $address = $first_part . ' ' . $second_part;
                    // if the address was already in the format "'name'" <address>, then this code
                    // will end up adding even more double quotes, so let's remove any excess
                    $return[] = str_replace('""', '"', $address);
                } else {
                    $return[] = $address;
                }
            } else {
                $return[] = $address;
            }
        }

        return implode(',', $return);
    }

    /**
     * Method used to break down the email address information and
     * return it for easy manipulation.
     *
     * Expands "Groups" into single addresses.
     *
     * @param   string $address The email address value
     * @param   boolean $multiple If multiple addresses should be returned
     * @return  array The address information
     */
    public static function getAddressInfo($address, $multiple = false)
    {
        $address = self::fixAddressQuoting($address);
        $addresslist = self::parseAddressList($address, null, null, false);
        if (Misc::isError($addresslist)) {
            return $addresslist;
        }

        if (!$multiple) {
            $addresslist = array($addresslist[0]);
        }

        $returns = array();
        foreach ($addresslist as $row) {
            // handle "group" type addresses
            if (isset($row->groupname)) {
                foreach ($row->addresses as $address) {
                    $returns[] = array(
                        'sender_name' => $address->personal,
                        'email' => $address->mailbox . '@' . $address->host,
                        'username' => $address->mailbox,
                        'host' => $address->host,
                    );
                }
                continue;
            }

            $returns[] = array(
                'sender_name' => $row->personal,
                'email' => $row->mailbox . '@' . $row->host,
                'username' => $row->mailbox,
                'host' => $row->host,
            );
        }

        if (!$returns) {
            return $returns;
        }

        if (!$multiple) {
            return $returns[0];
        }

        return $returns;
    }

    /**
     * Method used to get the email address portion of a given
     * recipient information.
     *
     * @param   string $address The email address value
     * @return  string The email address
     */
    public static function getEmailAddress($address)
    {
        $address = Mime_Helper::encodeAddress($address);
        $info = self::getAddressInfo($address);
        if (Misc::isError($info)) {
            return $info;
        }

        return $info['email'];
    }

    /**
     * Method used to get the name portion of a given recipient information.
     *
     * @param   string $address The email address value
     * @param   boolean $multiple If multiple addresses should be returned
     * @return  mixed The name or an array of names if multiple is true
     */
    public static function getName($address, $multiple = false)
    {
        $info = self::getAddressInfo($address, true);
        if (Misc::isError($info)) {
            return $info;
        }
        $returns = array();
        foreach ($info as $row) {
            if (!empty($row['sender_name'])) {
                if ((substr($row['sender_name'], 0, 1) == '"') && (substr($row['sender_name'], -1) == '"')) {
                    $row['sender_name'] = substr($row['sender_name'], 1, -1);
                }
                $returns[] = Mime_Helper::fixEncoding($row['sender_name']);
            } else {
                $returns[] = $row['email'];
            }
        }
        if ($multiple) {
            return $returns;
        } else {
            return $returns[0];
        }
    }

    /**
     * Method used to get the formatted name of the passed address
     * information.
     *
     * @param   string $name The name of the recipient
     * @param   string $email The email of the recipient
     * @return  string
     */
    public static function getFormattedName($name, $email)
    {
        return $name . ' <' . $email . '>';
    }

    /**
     * Method used to get the application specific settings regarding
     * which SMTP server to use, such as login and server information.
     *
     * @return  array
     */
    public static function getSMTPSettings()
    {
        $settings = Setup::get();

        if (file_exists('/etc/mailname')) {
            $settings['smtp']['localhost'] = trim(file_get_contents('/etc/mailname'));
        }

        return $settings['smtp'];
    }

    /**
     * Method used to set the text version of the body of the MIME
     * multipart message that you wish to send.
     *
     * @param   string $text The text-based message
     * @return  void
     */
    public function setTextBody($text)
    {
        $this->text_body = $text;
        $this->mime->setTXTBody($text);
    }

    /**
     * Method used to set the HTML version of the body of the MIME
     * multipart message that you wish to send.
     *
     * @param   string $html The HTML-based message
     * @return  void
     */
    public function setHTMLBody($html)
    {
        $this->mime->setHTMLBody($html);
    }

    /**
     * Method used to add an embedded image to a MIME message.
     *
     * @param   string $filename The full path to the image
     * @return  void
     */
    public function addHTMLImage($filename)
    {
        $this->mime->addHTMLImage($filename);
    }

    /**
     * Method used to set extra headers that you may wish to use when
     * sending the email.
     *
     * @param   mixed $header The header(s) to set
     * @param   mixed $value The value of the header to be set
     * @return  void
     */
    public function setHeaders($header, $value = false)
    {
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                $this->headers[$key] = Mime_Helper::encode($value);
            }
        } else {
            $this->headers[$header] = Mime_Helper::encode($value);
        }
    }

    /**
     * Method used to add an email address in the Cc list.
     *
     * @param   string $email The email address to be added
     * @return  void
     */
    public function addCc($email)
    {
        $this->mime->addCc($email);
    }

    /**
     * Method used to add an attachment to the message.
     *
     * @param   string $name The attachment name
     * @param   string $data The attachment data
     * @param   string $content_type The content type of the attachment
     * @return  void
     */
    public function addAttachment($name, $data, $content_type)
    {
        $this->mime->addAttachment($data, $content_type, $name, false);
    }

    /**
     * Removes the warning message contained in a message, so that certain users
     * don't receive that extra information as it may not be relevant to them.
     *
     * @param   string $str The body of the email
     * @return  string The body of the email, without the warning message
     */
    public static function stripWarningMessage($str)
    {
        $str = str_replace(self::getWarningMessage('allowed'), '', $str);
        $str = str_replace(self::getWarningMessage('blocked'), '', $str);

        return $str;
    }

    /**
     * Returns the warning message that needs to be added to the top of routed
     * issue emails to alert the recipient that he can (or not) send emails to
     * the issue notification list.
     *
     * @param   string $type Whether the warning message is of an allowed recipient or not
     * @return  string The warning message
     */
    public static function getWarningMessage($type)
    {
        if ($type == 'allowed') {
            $str = ev_gettext('ADVISORY: Your reply will be sent to the notification list.');
        } else {
            $str = ev_gettext('WARNING: If replying, add yourself to Authorized Repliers list first.');
        }

        return $str;
    }

    /**
     * Method used to add a customized warning message to the body
     * of outgoing emails.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $to The recipient of the message
     * @param   string $body The body of the message
     * @param   array $headers The headers of the message
     * @return  string The body of the message with the warning message, if appropriate
     */
    public static function addWarningMessage($issue_id, $to, $body, $headers)
    {
        $setup = Setup::get();
        $enabled = $setup['email_routing']['status'] == 'enabled' && $setup['email_routing']['warning']['status'] == 'enabled';
        if (!$enabled) {
            return $body;
        }

        // check if the recipient can send emails to the customer
        $recipient_email = self::getEmailAddress($to);
        $recipient_usr_id = User::getUserIDByEmail($recipient_email);
        // don't add the warning message if the recipient is an unknown email address
        if (empty($recipient_usr_id)) {
            return $body;
        }

        // don't add anything if the recipient is a known customer contact
        $recipient_role_id = User::getRoleByUser($recipient_usr_id, Issue::getProjectID($issue_id));
        if ($recipient_role_id == User::ROLE_CUSTOMER) {
            return $body;
        }

        if (!Support::isAllowedToEmail($issue_id, $recipient_email)) {
            $warning = self::getWarningMessage('blocked');
        } else {
            $warning = self::getWarningMessage('allowed');
        }
        if (@$headers['Content-Transfer-Encoding'] == 'base64') {
            return base64_encode($warning . "\n\n" . trim(base64_decode($body)));
        } else {
            return $warning . "\n\n" . $body;
        }
    }

    /**
     * Strips out email headers that should not be sent over to the recipient
     * of the routed email. The 'Received:' header was sometimes being used to
     * validate the sender of the message, and because of that some emails were
     * not being delivered correctly.
     *
     * @param   array $headers An array of headers for this email
     * @return  array The headers of the email, without the stripped ones
     */
    public static function stripHeaders($headers)
    {
        $ignore_headers = array(
            'to',
            'cc',
            'bcc',
            'return-path',
            'received',
            'Disposition-Notification-To',
        );
        $ignore_pattern = '/^resent.*/';
        foreach ($headers as $name => $value) {
            $lower_name = strtolower($name);
            if ((in_array($lower_name, $ignore_headers)) || (preg_match($ignore_pattern, $lower_name))) {
                unset($headers[$name]);
            }
        }

        return $headers;
    }

    /**
     * Method used to send the SMTP based email message.
     *
     * @param   string $from The originator of the message
     * @param   string $to The recipient of the message
     * @param   string $subject The subject of the message
     * @param   integer $issue_id The ID of the issue. If false, email will not be associated with issue.
     * @param   string $type The type of message this is
     * @param   integer $sender_usr_id The id of the user sending this email.
     * @param   integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @return  string The full body of the message that was sent
     */
    public function send($from, $to, $subject, $save_email_copy = 0, $issue_id = false, $type = '', $sender_usr_id = false, $type_id = false)
    {
        // encode the addresses
        $from = Mime_Helper::encodeAddress($from);
        $to = Mime_Helper::encodeAddress($to);
        $subject = Mime_Helper::encode($subject);

        $body = $this->mime->get(array(
            'text_charset' => APP_CHARSET,
            'html_charset' => APP_CHARSET,
            'head_charset' => APP_CHARSET,
            'text_encoding' => APP_EMAIL_ENCODING,
        ));
        $headers = array(
            'From'    => $from,
            'To'      => self::fixAddressQuoting($to),
            'Subject' => $subject,
        );

        $this->setHeaders($headers);
        $hdrs = $this->mime->headers($this->headers);

        $mail = array(
            'to' => $to,
            'headers' => $hdrs,
            'body' => $body,
        );
        $options = array(
            'save_email_copy' => $save_email_copy,
            'issue_id' => $issue_id,
            'type' => $type,
            'sender_usr_id' => $sender_usr_id,
            'type_id' => $type_id,
        );

        $res = Mail_Queue::addMail($mail, $options);
        if (Misc::isError($res) || $res == false) {
            return $res;
        }

        // RFC 822 formatted date
        $header = 'Date: ' . Date_Helper::getRFC822Date(time()) . "\r\n";
        // return the full dump of the email
        foreach ($hdrs as $name => $value) {
            $header .= "$name: $value\r\n";
        }
        $header .= "\r\n";

        return $header . $body;
    }

    /**
     * Returns the full headers for the email properly encoded.
     *
     * @param   string $from The sender of the email
     * @param   string $to The recipient of the email
     * @param   string $subject The subject of this email
     * @return  string The full header version of the email
     */
    public function getFullHeaders($from, $to, $subject)
    {
        // encode the addresses
        $from = Mime_Helper::encodeAddress($from);
        $to = Mime_Helper::encodeAddress($to);
        $subject = Mime_Helper::encode($subject);

        $body = $this->mime->get(array(
            'text_charset' => APP_CHARSET,
            'html_charset' => APP_CHARSET,
            'head_charset' => APP_CHARSET,
            'text_encoding' => APP_EMAIL_ENCODING,
        ));
        $this->setHeaders(array(
            'From'    => $from,
            'To'      => $to,
            'Subject' => $subject,
        ));
        $hdrs = $this->mime->headers($this->headers);
        // RFC 822 formatted date
        $header = 'Date: ' . Date_Helper::getRFC822Date(time()) . "\r\n";

        // return the full dump of the email
        foreach ($hdrs as $name => $value) {
            $header .= "$name: $value\r\n";
        }
        $header .= "\r\n";

        return $header . $body;
    }

    /**
     * Method used to save a copy of the given email to a configurable address.
     *
     * @param   array $email The email to save.
     * @return bool
     */
    public static function saveOutgoingEmailCopy(&$email)
    {
        // check early: do we really want to save every outgoing email?
        $setup = Setup::get();
        $save_outgoing_email = !empty($setup['smtp']['save_outgoing_email']) && $setup['smtp']['save_outgoing_email'] == 'yes';
        if (!$save_outgoing_email || empty($setup['smtp']['save_address'])) {
            return false;
        }

        static $subjects = array();

        $hdrs = &$email['headers'];
        $body = &$email['body'];
        $issue_id = $email['maq_iss_id'];
        $sender_usr_id = $email['maq_usr_id'];

        // ok, now parse the headers text and build the assoc array
        $full_email = $hdrs . "\n\n" . $body;
        $structure = Mime_Helper::decode($full_email, false, false);
        $_headers = &$structure->headers;
        $header_names = Mime_Helper::getHeaderNames($hdrs);
        $headers = array();
        foreach ($_headers as $lowercase_name => $value) {
            // need to remove the quotes to avoid a parsing problem
            // on senders that have extended characters in the first
            // or last words in their sender name
            if ($lowercase_name == 'from') {
                $value = Mime_Helper::removeQuotes($value);
            }
            $value = Mime_Helper::encode($value);
            // add the quotes back
            if ($lowercase_name == 'from') {
                $value = Mime_Helper::quoteSender($value);
            }
            $headers[$header_names[$lowercase_name]] = $value;
        }
        // remove any Reply-To:/Return-Path: values from outgoing messages
        unset($headers['Reply-To']);
        unset($headers['Return-Path']);

        // prevent duplicate emails from being sent out...
        $subject = @$headers['Subject'];
        if (@in_array($subject, $subjects)) {
            return false;
        }

        // replace the To: header with the requested address
        $address = $setup['smtp']['save_address'];
        $headers['To'] = $address;

        // add specialized headers if they are not already added
        if (empty($headers['X-Eventum-Type'])) {
            $headers += self::getSpecializedHeaders($issue_id, $email['maq_type'], $sender_usr_id);
        }

        $params = self::getSMTPSettings();
        $mail = Mail::factory('smtp', $params);
        $res = $mail->send($address, $headers, $body);
        if (Misc::isError($res)) {
            Logger::app()->error($res->getMessage(), array('debug' => $res->getDebugInfo()));
        }

        $subjects[] = $subject;
    }

    /**
     * Since Mail::prepareHeaders() is not supposed to be called statically, this method
     * instantiates an instance of the mail class and calls prepareHeaders on it.
     *
     * @param array $headers The array of headers to prepare, in an associative
     *              array, where the array key is the header name (ie,
     *              'Subject'), and the array value is the header
     *              value (ie, 'test'). The header produced from those
     *              values would be 'Subject: test'.
     * @return mixed Returns false if it encounters a bad address,
     *               otherwise returns an array containing two
     *               elements: Any From: address found in the headers,
     *               and the plain text version of the headers.
     */
    public static function prepareHeaders($headers)
    {
        $params = self::getSMTPSettings();
        $mail = Mail::factory('smtp', $params);

        return $mail->prepareHeaders($headers);
    }

    /**
     * Generates the specialized headers for an email.
     *
     * @param   integer $issue_id The issue ID
     * @param   string $type The type of message this is
     * @param   integer $sender_usr_id The id of the user sending this email.
     * @return  array An array of specialized headers
     */
    public static function getSpecializedHeaders($issue_id, $type, $sender_usr_id)
    {
        $new_headers = array();
        if (!empty($issue_id)) {
            $prj_id = Issue::getProjectID($issue_id);
            if (count(Group::getAssocList($prj_id)) > 0) {
                // group issue is currently assigned too
                $new_headers['X-Eventum-Group-Issue'] = Group::getName(Issue::getGroupID($issue_id));

                // group of whoever is sending this message.
                if (empty($sender_usr_id)) {
                    $new_headers['X-Eventum-Group-Replier'] = $new_headers['X-Eventum-Group-Issue'];
                } else {
                    $new_headers['X-Eventum-Group-Replier'] = Group::getName(User::getGroupID($sender_usr_id));
                }

                // group of current assignee
                $assignees = Issue::getAssignedUserIDs($issue_id);
                if (empty($assignees[0])) {
                    $new_headers['X-Eventum-Group-Assignee'] = '';
                } else {
                    $new_headers['X-Eventum-Group-Assignee'] = @Group::getName(User::getGroupID($assignees[0]));
                }
            }

            if (CRM::hasCustomerIntegration($prj_id)) {
                $crm = CRM::getInstance($prj_id);
                try {
                    $customer = $crm->getCustomer(Issue::getCustomerID($issue_id));
                    $new_headers['X-Eventum-Customer'] = $customer->getName();
                } catch (CustomerNotFoundException $e) {
                }
                try {
                    $contract = $crm->getContract(Issue::getContractID($issue_id));
                    $support_level = $contract->getSupportLevel();
                    if (is_object($support_level)) {
                        $new_headers['X-Eventum-Level'] = $support_level->getName();
                    }
                } catch (ContractNotFoundException $e) {
                }
            }

            // add assignee header
            $new_headers['X-Eventum-Assignee'] = implode(',', User::getEmail(Issue::getAssignedUserIDs($issue_id)));

            $new_headers['X-Eventum-Category'] = Category::getTitle(Issue::getCategory($issue_id));
            $new_headers['X-Eventum-Project'] = Project::getName($prj_id);

            $new_headers['X-Eventum-Priority'] = Priority::getTitle(Issue::getPriority($issue_id));

            // handle custom fields
            $cf_values = Custom_Field::getValuesByIssue($prj_id, $issue_id);
            $cf_titles = Custom_Field::getFieldsToBeListed($prj_id);
            foreach ($cf_values as $fld_id => $values) {
                // skip empty titles
                // TODO: why they are empty?
                if (!isset($cf_titles[$fld_id])) {
                    continue;
                }
                // skip empty values
                if (empty($values)) {
                    continue;
                }
                $cf_value = implode(', ', (array) $values);

                // value could be empty after multivalued field join
                if (empty($cf_value)) {
                    continue;
                }

                // convert spaces for header fields
                $cf_title = str_replace(' ', '_', $cf_titles[$fld_id]);
                $new_headers['X-Eventum-CustomField-'. $cf_title] = $cf_value;
            }
        }

        $new_headers['X-Eventum-Type'] = $type;

        return $new_headers;
    }

    /**
     * Method used to generate Message-ID header for mail.
     * To be used if message does not include "Message-Id" header.
     *
     * @param string $headers
     * @param string $body
     * @return  string The Message-ID header
     */
    public static function generateMessageID($headers = null, $body = null)
    {
        if ($headers) {
            // calculate hash to make fake message ID
            // NOTE: note the base_convert "10" should be "16" really here
            // but can't fix this because need to generate same message-id for same headers+body.
            // TODO: this can be fixed once we store the generated message-id in database,
            // TODO: i.e work on ZF-MAIL devel branch gets merged
            $first = base_convert(md5($headers), 10, 36);
            $second = base_convert(md5($body), 10, 36);
        } else {
            // generate random one
            // first part is time based
            $first = base_convert(microtime(true), 10, 36);

            // second part is random string
            $second = base_convert(bin2hex(Misc::generateRandom(8)), 16, 36);
        }

        return '<eventum.md5.' . $first . '.' . $second . '@' . APP_HOSTNAME . '>';
    }

    /**
     * Returns the referenced message-id for a given reply.
     *
     * @param   string $text_headers The full headers of the reply
     * @return  string The message-id of the original email
     */
    public static function getReferenceMessageID($text_headers)
    {
        if (preg_match('/^In-Reply-To: (.*)/mi', $text_headers, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/^References: (.+?)(\r?\n\r?\n|\r?\n\r?\S)/smi', $text_headers, $matches)) {
            $references = explode(' ', self::unfold(trim($matches[1])));
            $references = Misc::trim($references);
            // return the first message-id in the list of references
            return $references[0];
        }

        return '';
    }

    /**
     * Returns the message IDs of all emails this message references.
     *
     * @param   string $text_headers The full headers of the message
     * @return  array An array of message-ids
     */
    public static function getAllReferences($text_headers)
    {
        $references = array();
        if (preg_match('/^In-Reply-To: (.*)/mi', $text_headers, $matches)) {
            $references[] = trim($matches[1]);
        }
        if (preg_match('/^References: (.+?)(\r?\n\r?\n|\r?\n\r?\S)/smi', $text_headers, $matches)) {
            $references = array_merge($references, explode(' ', self::unfold(trim($matches[1]))));
            $references = Misc::trim($references);
            $references = array_unique($references);
        }
        foreach ($references as $key => $reference) {
            if (empty($reference)) {
                unset($references[$key]);
            }
        }

        return $references;
    }

    /**
     * Checks to make sure In-Reply-To and References headers are correct.
     *
     */
    public static function rewriteThreadingHeaders($issue_id, $full_email, $headers, $type = 'email')
    {
        list($text_headers, $body) = Mime_Helper::splitHeaderBody($full_email);

        $msg_id = self::getMessageID($text_headers, $body);

        // check if the In-Reply-To header exists and if so, does it relate to a message stored in Eventum
        // if it does not, set new In-Reply-To header
        $reference_msg_id = self::getReferenceMessageID($text_headers);
        $reference_issue_id = false;
        if (!empty($reference_msg_id)) {
            // check if referenced msg id is associated with this issue
            if ($type == 'note') {
                $reference_issue_id = Note::getIssueByMessageID($reference_msg_id);
            } else {
                $reference_issue_id = Support::getIssueByMessageID($reference_msg_id);
            }
        }

        if ((empty($reference_msg_id)) || ($reference_issue_id != $issue_id)) {
            $reference_msg_id = Issue::getRootMessageID($issue_id);
        }
        $references = self::getReferences($issue_id, $reference_msg_id, $type);

        // now the fun part, re-writing the email headers
        if (empty($headers['message-id'])) {
            // add Message-ID since it doesn't exist (curses on Outlook 2003)
            $text_headers .= "\r\nMessage-ID: $msg_id";
            $headers['message-id'] = $msg_id;
        }

        /**
         * Make sure that In-Reply-To and References headers are set and reference a message in this issue.
         * If not, set to be the root message ID of the issue. This is to ensure messages are threaded by
         * issue in mail clients.
         */
        if (preg_match('/^In-Reply-To: (.*)/mi', $text_headers) > 0) {
            // replace existing header
            $text_headers = preg_replace('/^In-Reply-To: (.*)/mi', 'In-Reply-To: ' . $reference_msg_id, $text_headers, 1);
        } else {
            // add new header after message ID
            $text_headers = preg_replace('/^Message-ID: (.*)$/mi', "Message-ID: $1\r\nIn-Reply-To: $reference_msg_id", $text_headers, 1);
        }
        $headers['in-reply-to'] = $reference_msg_id;
        if (preg_match('/^References: (.*)/mi', $text_headers) > 0) {
            // replace existing header
            $text_headers = preg_replace('/^References: (.*)/mi', 'References: ' . self::fold(implode(' ', $references)), $text_headers, 1);
        } else {
            // add new header after In-Reply-To
            $text_headers = preg_replace('/^In-Reply-To: (.*)$/mi', "In-Reply-To: $1\r\nReferences: " . self::fold(implode(' ', $references)), $text_headers, 1);
        }
        $headers['references'] = self::fold(implode(' ', $references));

        return array($text_headers . "\r\n\r\n" . $body, $headers);
    }

    /**
     * Returns a complete list of references for an email/note, including
     * the issue root message ID
     *
     * @param   integer $issue_id The ID of the issue
     * @param   string $msg_id The ID of the message
     * @param   string $type If this is a note or an email
     * @return  array An array of message IDs
     */
    public static function getReferences($issue_id, $msg_id, $type)
    {
        $references = array();
        self::_getReferences($msg_id, $type, $references);
        $references[] = Issue::getRootMessageID($issue_id);
        $references = array_reverse(array_unique($references));

        return $references;
    }

    /**
     * Method to get the list of messages an email/note references
     *
     * @param   string $msg_id The ID of the parent message
     * @param   string $type If this is a note or an email
     * @param   array $references The array the references will be stored in.
     */
    private function _getReferences($msg_id, $type, &$references)
    {
        $references[] = $msg_id;
        if ($type == 'note') {
            $parent_msg_id = Note::getParentMessageIDbyMessageID($msg_id);
        } else {
            $parent_msg_id = Support::getParentMessageIDbyMessageID($msg_id);
        }

        if (!empty($parent_msg_id)) {
            self::_getReferences($parent_msg_id, $type, $references);
        }
    }

    public static function getBaseThreadingHeaders($issue_id)
    {
        $root_msg_id = Issue::getRootMessageID($issue_id);

        return array(
            'Message-ID'    =>  self::generateMessageID(),
            'In-Reply-To'   =>  $root_msg_id,
            'References'    =>  $root_msg_id,
        );
    }

    /**
     * Unfolds message headers
     *
     * @param   string $input The headers to unfold
     * @return  string The unfolded headers
     */
    public static function unfold($input)
    {
        $input = preg_replace("/\r?\n/", "\r\n", $input);
        $input = preg_replace("/\r\n(\t| )+/", ' ', $input);

        return $input;
    }

    /**
     * Folds message headers
     *
     * @param   string $input The headers to fold
     * @return  string The folded headers
     */
    public static function fold($input)
    {
        return wordwrap($input, 70, "\r\n ");
    }

    /**
     * Returns the Message-ID from an email. If no message ID is found (Outlook 2003 doesn't
     * generate them in some cases) a "fake" message-id will be calculated.
     *
     * @param string $headers The message headers
     * @param string $body The message body
     * @return string
     */
    public static function getMessageID($headers, $body)
    {
        $full_email = $headers. "\n\n";
        $structure = Mime_Helper::decode($full_email);

        $has_message_id = isset($structure->headers['message-id']);
        // handle cases when there is duplicate message-id header
        // (presented as Array by PEAR Mail_mimeDecode class)
        if ($has_message_id && is_string($structure->headers['message-id'])) {
            return $structure->headers['message-id'];
        } elseif ($has_message_id && is_array($structure->headers['message-id'])) {
            return current($structure->headers['message-id']);
        }

        return self::generateMessageID($headers, $body);
    }

    public static function splitAddresses($addresses)
    {
        $mail = new Mail_RFC822($addresses);

        $mail->parseAddressList();

        $return = array();
        if (is_array($mail->addresses)) {
            foreach ($mail->addresses as $address) {
                $return[] = $address['address'];
            }
        }

        return $return;
    }
}
