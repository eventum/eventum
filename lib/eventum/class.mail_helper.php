<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 encoding=utf-8: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003 - 2008 MySQL AB                                   |
// | Copyright (c) 2008 - 2010 Sun Microsystem Inc.                       |
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
// | Authors: João Prado Maia <jpm@mysql.com>                             |
// +----------------------------------------------------------------------+
//

require_once 'Mail/RFC822.php';

/**
 * Class to handle the business logic related to sending email to
 * outside recipients. This class utilizes the PEAR::Mail
 * infrastructure to deliver email in a compatible way across
 * different platforms.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

class Mail_Helper
{
    // variable to keep the Mail_mime object
    var $mime;
    // variable to keep the headers to be used in the email
    var $headers = '';
    // text version of this message
    var $text_body = '';


    /**
     * Class constructor. It includes and initializes the required
     * PEAR::Mail related objects
     *
     * @access  public
     */
    function Mail_Helper()
    {
        require_once 'Mail.php';
        require_once 'Mail/mime.php';
        $this->mime = new Mail_mime("\r\n");
    }


    /**
     * Correctly formats the subject line of outgoing emails/notes
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $subject The subject to be formatted
     * @return  string The formatted subject
     */
    function formatSubject($issue_id, $subject)
    {
        return "[#$issue_id] " . trim(preg_replace("/\[#$issue_id\] {0,1}/", '', $subject));
    }


    /**
     * Believe it or not, this is a method that will remove excess occurrences
     * of 'Re:' that commonly are found in email subject lines.
     * If the second parameter is true, issue #'s will also be stripped.
     *
     * @access  public
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
        // probably the reply prefixes should be configrable per Eventum install
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
     * @access  public
     * @return  string The canned explanation
     */
    function getCannedBlockedMsgExplanation()
    {
        $msg = ev_gettext("WARNING: This message was blocked because the sender was not allowed to send emails to the associated issue.") . " ";
        $msg .= ev_gettext("Only staff members listed in the assignment or authorized replier fields can send emails.") . "\n";
        $msg .= str_repeat('-', 70) . "\n\n";
        return $msg;
    }


    /**
     * Checks whether the given headers are from a vacation
     * auto-responder message or not.
     *
     * @access  public
     * @param   array $headers The list of headers
     * @return  boolean
     */
    function isVacationAutoResponder($headers)
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
     * @access  public
     * @param   string $str The string containing email addresses
     * @return  array The list of email addresses
     */
    function getEmailAddresses($str)
    {
        $str = self::fixAddressQuoting($str);
        $str = Mime_Helper::encode($str);
        $structs = Mail_RFC822::parseAddressList($str);
        $addresses = array();
        foreach ($structs as $structure) {
            if ((!empty($structure->mailbox)) && (!empty($structure->host))) {
                $addresses[] = $structure->mailbox . '@' . $structure->host;
            }
        }
        return $addresses;
    }


    /**
     * Method used to build a properly quoted email address, in the form of
     * "Sender Name" <sender@example.com>.
     *
     * @access  public
     * @param   string $address The email address value
     * @return  array The address information
     */
    function fixAddressQuoting($address)
    {
        // split multiple addresses if needed
        $addresses = self::splitAddresses($address);

        $return = array();
        foreach ($addresses as $address) {
            // check if we have a <
            if ((strstr($address, '<')) && (!Mime_Helper::isQuotedPrintable($address))) {
                $address = stripslashes(trim($address));
                // is the address in the format 'name' <address> ?
                if ((strstr($address, "'")) || (strstr($address, "."))) {
                    $bracket_pos = strpos($address, '<');
                    if ($bracket_pos != 0) {
                        $bracket_pos = $bracket_pos - 1;
                    }
                    $first_part = substr($address, 0, $bracket_pos);
                    if (!empty($first_part)) {
                        $first_part = '"' . str_replace('"', '\"', preg_replace("/(^\")|(\"$)/", '', $first_part)) . '"';
                    }
                    $second_part = substr($address, strpos($address, '<'));
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

        return join(',', $return);
    }


    /**
     * Method used to break down the email address information and
     * return it for easy manipulation.
     *
     * @access  public
     * @param   string $address The email address value
     * @param   boolean $multiple If multiple addresses should be returned
     * @return  array The address information
     */
    function getAddressInfo($address, $multiple = false)
    {
        $address = self::fixAddressQuoting($address);
        $t = Mail_RFC822::parseAddressList($address, null, null, false);
        if (PEAR::isError($t)) {
            return $t;
        }
        if ($multiple) {
            $returns = array();
            for ($i = 0; $i < count($t); $i++) {
                $returns[] = array(
                    'sender_name' => $t[$i]->personal,
                    'email'       => $t[$i]->mailbox . '@' . $t[$i]->host,
                    'username'    => $t[$i]->mailbox,
                    'host'        => $t[$i]->host
                );
            }
            return $returns;
        } else {
            return array(
                'sender_name' => $t[0]->personal,
                'email'       => $t[0]->mailbox . '@' . $t[0]->host,
                'username'    => $t[0]->mailbox,
                'host'        => $t[0]->host
            );
        }
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
        if (PEAR::isError($info)) {
            return $info;
        }
        return $info['email'];
    }


    /**
     * Method used to get the name portion of a given recipient information.
     *
     * @access  public
     * @param   string $address The email address value
     * @param   boolean $multiple If multiple addresses should be returned
     * @return  mixed The name or an array of names if multiple is true
     */
    function getName($address, $multiple = false)
    {
        $info = self::getAddressInfo($address, true);
        if (PEAR::isError($info)) {
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
     * @access  public
     * @param   string $name The name of the recipient
     * @param   string $email The email of the recipient
     * @return  string
     */
    function getFormattedName($name, $email)
    {
        return $name . " <" . $email . ">";
    }


    /**
     * Method used to get the application specific settings regarding
     * which SMTP server to use, such as login and server information.
     *
     * @access  public
     * @return  array
     */
    function getSMTPSettings()
    {
        $settings = Setup::load();
        settype($settings['smtp']['auth'], 'boolean');
        if (file_exists('/etc/mailname') ) {
            $settings['smtp']['localhost'] = trim( file_get_contents('/etc/mailname') );
        }
        return $settings["smtp"];
    }


    /**
     * Method used to set the text version of the body of the MIME
     * multipart message that you wish to send.
     *
     * @access  public
     * @param   string $text The text-based message
     * @return  void
     */
    function setTextBody($text)
    {
        $this->text_body = $text;
        $this->mime->setTXTBody($text);
    }


    /**
     * Method used to set the HTML version of the body of the MIME
     * multipart message that you wish to send.
     *
     * @access  public
     * @param   string $html The HTML-based message
     * @return  void
     */
    function setHTMLBody($html)
    {
        $this->mime->setHTMLBody($html);
    }


    /**
     * Method used to add an embedded image to a MIME message.
     *
     * @access  public
     * @param   string $filename The full path to the image
     * @return  void
     */
    function addHTMLImage($filename)
    {
        $this->mime->addHTMLImage($filename);
    }


    /**
     * Method used to set extra headers that you may wish to use when
     * sending the email.
     *
     * @access  public
     * @param   mixed $header The header(s) to set
     * @param   mixed $value The value of the header to be set
     * @return  void
     */
    function setHeaders($header, $value = FALSE)
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
     * @access  public
     * @param   string $email The email address to be added
     * @return  void
     */
    function addCc($email)
    {
        $this->mime->addCc($email);
    }


    /**
     * Method used to add an attachment to the message.
     *
     * @access  public
     * @param   string $name The attachment name
     * @param   string $data The attachment data
     * @param   string $content_type The content type of the attachment
     * @return  void
     */
    function addAttachment($name, $data, $content_type)
    {
        $this->mime->addAttachment($data, $content_type, $name, false);
    }


    /**
     * Method used to add a message/rfc822 attachment to the message.
     *
     * @access  public
     * @param   string $message_body The attachment data
     * @return  void
     */
    function addMessageRfc822($message_body)
    {
        $this->mime->addMessageRfc822($message_body, '8bit');
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
            $str = ev_gettext("ADVISORY: Your reply will be sent to the notification list.");
        } else {
            $str = ev_gettext("WARNING: If replying, add yourself to Authorized Repliers list first.");
        }
        return $str;
    }


    /**
     * Method used to add a customized warning message to the body
     * of outgoing emails.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $to The recipient of the message
     * @param   string $body The body of the message
     * @param   headers $headers The headers of the message
     * @return  string The body of the message with the warning message, if appropriate
     */
    function addWarningMessage($issue_id, $to, $body, $headers)
    {
        $setup = Setup::load();
        if ((@$setup['email_routing']['status'] == 'enabled') &&
                ($setup['email_routing']['warning']['status'] == 'enabled')) {
            // check if the recipient can send emails to the customer
            $recipient_email = self::getEmailAddress($to);
            $recipient_usr_id = User::getUserIDByEmail($recipient_email);
            // don't add the warning message if the recipient is an unknown email address
            if (empty($recipient_usr_id)) {
                return $body;
            } else {
                // don't add anything if the recipient is a known customer contact
                $recipient_role_id = User::getRoleByUser($recipient_usr_id, Issue::getProjectID($issue_id));
                if ($recipient_role_id == User::getRoleID('Customer')) {
                    return $body;
                } else {
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
            }
        } else {
            return $body;
        }
    }


    /**
     * Strips out email headers that should not be sent over to the recipient
     * of the routed email. The 'Received:' header was sometimes being used to
     * validate the sender of the message, and because of that some emails were
     * not being delivered correctly.
     *
     * @access  public
     * @param   array $headers An array of headers for this email
     * @return  array The headers of the email, without the stripped ones
     */
    function stripHeaders($headers)
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
     * @access  public
     * @param   string $from The originator of the message
     * @param   string $to The recipient of the message
     * @param   string $subject The subject of the message
     * @param   integer $issue_id The ID of the issue. If false, email will not be associated with issue.
     * @param   string $type The type of message this is
     * @param   integer $sender_usr_id The id of the user sending this email.
     * @param   integer $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     * @return  string The full body of the message that was sent
     */
    function send($from, $to, $subject, $save_email_copy = 0, $issue_id = false, $type = '', $sender_usr_id = false, $type_id = false)
    {
        static $support_levels;

        // encode the addresses
        $from = MIME_Helper::encodeAddress($from);
        $to = MIME_Helper::encodeAddress($to);
        $subject = MIME_Helper::encode($subject);

        $body = $this->mime->get(array('text_charset' => APP_CHARSET, 'head_charset' => APP_CHARSET, 'text_encoding' => APP_EMAIL_ENCODING));
        $headers = array(
            'From'    => $from,
            'To'      => self::fixAddressQuoting($to),
            'Subject' => $subject
        );

        $this->setHeaders($headers);
        $hdrs = $this->mime->headers($this->headers);
        $res = Mail_Queue::add($to, $hdrs, $body, $save_email_copy, $issue_id, $type, $sender_usr_id, $type_id);
        if ((PEAR::isError($res)) || ($res == false)) {
            return $res;
        } else {
            // RFC 822 formatted date
            $header = 'Date: ' . date('D, j M Y H:i:s O') . "\r\n";
            // return the full dump of the email
            foreach ($hdrs as $name => $value) {
                $header .= "$name: $value\r\n";
            }
            $header .= "\r\n";
            return $header . $body;
        }
    }


    /**
     * Returns the full headers for the email properly encoded.
     *
     * @access  public
     * @param   string $from The sender of the email
     * @param   string $to The recipient of the email
     * @param   string $subject The subject of this email
     * @return  string The full header version of the email
     */
    function getFullHeaders($from, $to, $subject)
    {
        // encode the addresses
        $from = MIME_Helper::encodeAddress($from);
        $to = MIME_Helper::encodeAddress($to);
        $subject = MIME_Helper::encode($subject);

        $body = $this->mime->get(array('text_charset' => APP_CHARSET, 'head_charset' => APP_CHARSET, 'text_encoding' => APP_EMAIL_ENCODING));
        $this->setHeaders(array(
            'From'    => $from,
            'To'      => $to,
            'Subject' => $subject
        ));
        $hdrs = $this->mime->headers($this->headers);
        // RFC 822 formatted date
        $header = 'Date: ' . gmdate('D, j M Y H:i:s O') . "\r\n";
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
     * @access  public
     * @param   array $email The email to save.
     */
    function saveOutgoingEmailCopy(&$email)
    {
        // check early: do we really want to save every outgoing email?
        $setup = Setup::load();
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
        $structure = Mime_Helper::decode($full_email, FALSE, FALSE);
        $_headers =& $structure->headers;
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
            $headers += self::getSpecializedHeaders($issue_id, $email['maq_type'], $headers, $sender_usr_id);
        }

        $params = self::getSMTPSettings($address);
        $mail =& Mail::factory('smtp', $params);
        $res = $mail->send($address, $headers, $body);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
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
    function prepareHeaders($headers)
    {
        $params = self::getSMTPSettings();
        $mail =& Mail::factory('smtp', $params);
        return $mail->prepareHeaders($headers);
    }


    /**
     * Generates the specialized headers for an email.
     *
     * @access  public
     * @param   integer $issue_id The issue ID
     * @param   string $type The type of message this is
     * @param   string $headers The existing headers of this message.
     * @param   integer $sender_usr_id The id of the user sending this email.
     * @return  array An array of specialized headers
     */
    function getSpecializedHeaders($issue_id, $type, $headers, $sender_usr_id)
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
            if (Customer::hasCustomerIntegration($prj_id)) {
                if (empty($support_levels)) {
                    $support_levels = Customer::getSupportLevelAssocList($prj_id);
                }
                $customer_id = Issue::getCustomerID($issue_id);
                $contract_id = Issue::getContractID($issue_id);
                if (!empty($customer_id)) {
                    $customer_details = Customer::getDetails($prj_id, $customer_id, false, $contract_id);
                    $new_headers['X-Eventum-Customer'] = $customer_details['customer_name'];
                }
                if (count($support_levels) > 0) {
                    $new_headers['X-Eventum-Level'] = $support_levels[Customer::getSupportLevelID($prj_id, $customer_id, $contract_id)];
                }
            }
            // add assignee header
            $new_headers['X-Eventum-Assignee'] = join(',', User::getEmail(Issue::getAssignedUserIDs($issue_id)));

            $new_headers['X-Eventum-Category'] = Category::getTitle(Issue::getCategory($issue_id));
            $new_headers['X-Eventum-Project'] = Project::getName($prj_id);
        }
        $new_headers['X-Eventum-Type'] = $type;
        return $new_headers;
    }


    /**
     * Method used to get the appropriate Message-ID header for a
     * given issue.
     *
     * @access  public
     * @return  string The Message-ID header
     */
    function generateMessageID()
    {
        list($usec, $sec) = explode(" ", microtime());
        $time = ((float)$usec + (float)$sec);
        $first = base_convert($time, 10, 36);
        mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);
        $rand = mt_rand();
        $second = base_convert($rand, 10, 36);
        return "<eventum." . $first . "." . $second . "@" . APP_HOSTNAME . ">";
    }


    /**
     * Returns the referenced message-id for a given reply.
     *
     * @access  public
     * @param   string $text_headers The full headers of the reply
     * @return  string The message-id of the original email
     */
    function getReferenceMessageID($text_headers)
    {
        $references = array();
        if (preg_match('/^In-Reply-To: (.*)/mi', $text_headers, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/^References: (.+?)(\r?\n\r?\n|\r?\n\r?\S)/smi', $text_headers, $matches)) {
            $references = explode(" ", self::unfold(trim($matches[1])));
            $references = array_map('trim', $references);
            // return the first message-id in the list of references
            return $references[0];
        }
        return '';
    }


    /**
     * Returns the message IDs of all emails this message references.
     *
     * @access  public
     * @param   string $text_headers The full headers of the message
     * @return  array An array of message-ids
     */
    function getAllReferences($text_headers)
    {
        $references = array();
        if (preg_match('/^In-Reply-To: (.*)/mi', $text_headers, $matches)) {
            $references[] = trim($matches[1]);
        }
        if (preg_match('/^References: (.+?)(\r?\n\r?\n|\r?\n\r?\S)/smi', $text_headers, $matches)) {
            $references = array_merge($references, explode(" ", self::unfold(trim($matches[1]))));
            $references = array_map('trim', $references);
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
    function rewriteThreadingHeaders($issue_id, $full_email, $headers, $type = 'email')
    {
        list($text_headers, $body) = Mime_Helper::splitHeaderBody($full_email);

        if ($type == 'note') {
            $class = 'Note';
        } else {
            $class = 'Support';
        }

        $msg_id = self::getMessageID($text_headers, $body);

        // check if the In-Reply-To header exists and if so, does it relate to a message stored in Eventum
        // if it does not, set new In-Reply-To header
        $reference_msg_id = self::getReferenceMessageID($text_headers);
        $reference_issue_id = false;
        if (!empty($reference_msg_id)) {
            // check if referenced msg id is associated with this issue
            $reference_issue_id = call_user_func(array($class, 'getIssueByMessageID'), $reference_msg_id);
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
            $text_headers = preg_replace('/^References: (.*)/mi', 'References: ' . self::fold(join(' ', $references)), $text_headers, 1);
        } else {
            // add new header after In-Reply-To
            $text_headers = preg_replace('/^In-Reply-To: (.*)$/mi', "In-Reply-To: $1\r\nReferences: " . self::fold(join(' ', $references)), $text_headers, 1);
        }
        $headers['references'] = self::fold(join(' ', $references));
        return array($text_headers . "\r\n\r\n" . $body, $headers);
    }


    /**
     * Returns a complete list of references for an email/note, including
     * the issue root message ID
     *
     * @access  private
     * @param   integer $issue_id The ID of the issue
     * @param   string $msg_id The ID of the message
     * @param   string $type If this is a note or an email
     * @return  array An array of message IDs
     */
    function getReferences($issue_id, $msg_id, $type)
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
     * @access  private
     * @param   string $msg_id The ID of the parent message
     * @param   string $type If this is a note or an email
     * @param   array $references The array the references will be stored in.
     */
    function _getReferences($msg_id, $type, &$references)
    {
        $references[] = $msg_id;
        if ($type == 'note') {
            $class = 'Note';
        } else {
            $class = 'Support';
        }
        $parent_msg_id = call_user_func(array($class, 'getParentMessageIDbyMessageID'), $msg_id);
        if (!empty($parent_msg_id)) {
            self::_getReferences($parent_msg_id, $type, $references);
        }
    }



    function getBaseThreadingHeaders($issue_id)
    {
        $root_msg_id = Issue::getRootMessageID($issue_id);
        return array(
            "Message-ID"    =>  self::generateMessageID(),
            "In-Reply-To"   =>  $root_msg_id,
            "References"    =>  $root_msg_id
        );
    }

    /**
     * Unfolds message headers
     *
     * @access  public
     * @param   string $input The headers to unfold
     * @return  string The unfolded headers
     */
    function unfold($input)
    {
        $input = preg_replace("/\r?\n/", "\r\n", $input);
        $input = preg_replace("/\r\n(\t| )+/", ' ', $input);
        return $input;
    }

    /**
     * Folds message headers
     *
     * @access  public
     * @param   string $input The headers to fold
     * @return  string The folded headers
     */
    function fold($input)
    {
        return wordwrap($input, 70, "\r\n ");
    }


    /**
     * Returns the Message-ID from an email. If no message ID is found (Outlook 2003 doesn't
     * generate them in some cases) a "fake" message-id will be calculated.
     *
     * @access  public
     * @param   string $headers The message headers
     * @param   string $body The message body
     */
    public static function getMessageID($headers, $body)
    {
        $full_email = $headers. "\n\n";
        $structure = Mime_Helper::decode($full_email);

        if (!empty($structure->headers['message-id'])) {
            return $structure->headers['message-id'];
        }

        // no match, calculate hash to make fake message ID
        $first = base_convert(md5($headers), 10, 36);
        $second = base_convert(md5($body), 10, 36);
        return '<eventum.md5.' . $first . '.' . $second . '@' . APP_HOSTNAME . '>';
    }


    function splitAddresses($addresses)
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
