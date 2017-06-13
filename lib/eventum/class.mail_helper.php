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

use Eventum\Mail\Helper\AddressHeader;
use Eventum\Mail\MailMessage;
use Eventum\Mail\MailTransport;
use Zend\Mail\Address;

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
     */
    public function __construct()
    {
        $this->mime = new Mail_mime("\r\n");
    }

    /**
     * Correctly formats the subject line of outgoing emails/notes
     *
     * @param   int $issue_id The issue ID
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
     * @param   bool $remove_issue_id If the issue ID should be removed
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
            $re_format = '$1' . ev_gettext('Re: %1$s', '$5');
            $subject = preg_replace($re_pattern, $re_format, $subject);

            return self::removeExcessRe($subject);
        }

        return $subject;
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
     * @return  bool
     */
    public static function isVacationAutoResponder($headers)
    {
        // loop through the headers and make sure they are all lowercase.
        foreach ($headers as $key => $value) {
            $headers[strtolower($key)] = $value;
        }

        if ((@$headers['x-vacationmessage'] == 'Yes') || ((isset($headers['auto-submitted'])) && (!empty($headers['auto-submitted'])))) {
            return true;
        }

        return false;
    }

    /**
     * Method used to build a properly quoted email address, in the form of
     * "Sender Name" <sender@example.com>.
     *
     * @param   string $address The email address value
     * @return  string The address information
     * @deprecated use AddressHeader directly
     */
    public static function fixAddressQuoting($address)
    {
        if (!$address instanceof Address) {
            $address = AddressHeader::fromString($address);
        }

        return $address->toString();
    }

    /**
     * Method used to get the email address portion of a given
     * recipient information. Normalizes the address by lowercasing it.
     *
     * @param Address|string $address The email address value
     * @return string The email address
     */
    public static function getEmailAddress($address)
    {
        if (!$address instanceof Address) {
            $address = AddressHeader::fromString($address)->getAddress();
        }

        return strtolower($address->getEmail());
    }

    /**
     * Method used to get the display name of a given recipient information.
     *
     * Method should be used when displaying header values to user.
     *
     * @param Address|string $address The email address(es) value
     * @param   bool $multiple If multiple addresses should be returned
     * @throws \Zend\Mail\Header\Exception\InvalidArgumentException
     * @return string[]|string The name or an array of names if multiple is true
     */
    public static function getName($address, $multiple = false)
    {
        if (!$address instanceof Address) {
            $address = AddressHeader::fromString($address);
        }

        $names = $address->getNames();

        return $multiple ? $names : current($names);
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
        if (empty($name)) {
            return $email;
        }

        return $name . ' <' . $email . '>';
    }

    /**
     * Method used to set the text version of the body of the MIME
     * multipart message that you wish to send.
     *
     * @param   string $text The text-based message
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
     */
    public function setHTMLBody($html)
    {
        $this->mime->setHTMLBody($html);
    }

    /**
     * Method used to add an embedded image to a MIME message.
     *
     * @param   string $filename The full path to the image
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
     * @param   int $issue_id The issue ID
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
        }

        return $warning . "\n\n" . $body;
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
        $ignore_headers = [
            'to',
            'cc',
            'bcc',
            'return-path',
            'received',
            'disposition-notification-to',
        ];
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
     * Build message and add it to mail queue.
     *
     * @param   string $from The originator of the message
     * @param   string $to The recipient of the message
     * @param   string $subject The subject of the message
     * @param   int $issue_id The ID of the issue. If false, email will not be associated with issue.
     * @param   string $type The type of message this is
     * @param   int $sender_usr_id the id of the user sending this email
     * @param   int $type_id The ID of the event that triggered this notification (issue_id, sup_id, not_id, etc)
     */
    public function send($from, $to, $subject, $save_email_copy = 0, $issue_id = false, $type = '', $sender_usr_id = false, $type_id = false)
    {
        if ($from === null) {
            $from = Setup::get()->smtp->from;
        }
        // encode the addresses
        $from = Mime_Helper::encodeAddress($from);
        $to = Mime_Helper::encodeAddress($to);
        $subject = Mime_Helper::encode($subject);

        $body = $this->mime->get([
            'text_charset' => APP_CHARSET,
            'html_charset' => APP_CHARSET,
            'head_charset' => APP_CHARSET,
            'text_encoding' => APP_EMAIL_ENCODING,
        ]);
        $headers = [
            'From' => $from,
            'To' => self::fixAddressQuoting($to),
            'Subject' => $subject,
        ];

        $this->setHeaders($headers);
        $hdrs = $this->mime->headers($this->headers);

        $mail = MailMessage::createFromHeaderBody($hdrs, $body);
        $options = [
            'save_email_copy' => $save_email_copy,
            'issue_id' => $issue_id,
            'type' => $type,
            'sender_usr_id' => $sender_usr_id,
            'type_id' => $type_id,
        ];

        Mail_Queue::addMail($mail, $to, $options);
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

        $body = $this->mime->get([
            'text_charset' => APP_CHARSET,
            'html_charset' => APP_CHARSET,
            'head_charset' => APP_CHARSET,
            'text_encoding' => APP_EMAIL_ENCODING,
        ]);
        $this->setHeaders([
            'From' => $from,
            'To' => $to,
            'Subject' => $subject,
        ]);
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
     * @param int $issue_id
     * @param string $maq_type
     * @param MailMessage $mail the email to save
     */
    public static function saveOutgoingEmailCopy($issue_id, $maq_type, MailMessage $mail)
    {
        // check early: do we really want to save every outgoing email?
        $setup = Setup::get();
        $save_outgoing_email = $setup['smtp']['save_outgoing_email'] == 'yes';
        if (!$save_outgoing_email || !$setup['smtp']['save_address']) {
            return;
        }

        $headers = $mail->getHeaders();

        // remove any Reply-To:/Return-Path: values from outgoing messages
        $headers->removeHeader('Reply-To');
        $headers->removeHeader('Return-Path');

        $recipient = $setup['smtp']['save_address'];

        // replace the To: header with the requested address
        $mail->setTo($recipient);

        // add specialized headers if they are not already added
        if (!$headers->has('X-Eventum-Type')) {
            $headers->addHeaders(self::getSpecializedHeaders($issue_id, $maq_type));
        }

        $transport = new MailTransport();
        $transport->send($recipient, $mail);
    }

    /**
     * Generates the specialized headers for an email.
     *
     * @param   int $issue_id The issue ID
     * @param   string $type The type of message this is
     * @return  array An array of specialized headers
     */
    public static function getSpecializedHeaders($issue_id, $type)
    {
        $new_headers = [];

        $new_headers['X-Eventum-Type'] = $type;

        if (!$issue_id) {
            return $new_headers;
        }

        $prj_id = Issue::getProjectID($issue_id);
        if (count(Group::getAssocList($prj_id)) > 0) {
            // group issue is currently assigned too
            $new_headers['X-Eventum-Group-Issue'] = Group::getName(Issue::getGroupID($issue_id));
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
            $new_headers['X-Eventum-CustomField-' . $cf_title] = $cf_value;
        }

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
        $references = [];

        // if X-Forwarded-Message-Id is present, assume this is forwarded email and this root email
        if (preg_match('/^X-Forwarded-Message-Id: .*/mi', $text_headers)) {
            return $references;
        }

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
     * Make sure that In-Reply-To and References headers are set and reference a message in this issue.
     * If not, set to be the root message ID of the issue. This is to ensure messages are threaded by
     * issue in mail clients.
     */
    public static function rewriteThreadingHeaders($issue_id, $full_email, $headers, $type = 'email')
    {
        list($text_headers, $body) = Mime_Helper::splitHeaderBody($full_email);

        $msg_id = MailMessage::createFromString($full_email)->messageId;

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

        return [$text_headers . "\r\n\r\n" . $body, $headers];
    }

    /**
     * Returns a complete list of references for an email/note, including
     * the issue root message ID
     *
     * @param   int $issue_id The ID of the issue
     * @param   string $msg_id The ID of the message
     * @param   string $type If this is a note or an email
     * @return  string[] An array of message IDs
     */
    public static function getReferences($issue_id, $msg_id, $type)
    {
        $references = [];
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
     * @param   array $references the array the references will be stored in
     */
    private static function _getReferences($msg_id, $type, &$references)
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

    /**
     * @param int $issue_id
     */
    public static function getBaseThreadingHeaders($issue_id)
    {
        $root_msg_id = Issue::getRootMessageID($issue_id);

        return [
            'Message-ID' => self::generateMessageID(),
            'In-Reply-To' => $root_msg_id,
            'References' => $root_msg_id,
        ];
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
     * Removes newlines and tabs from subject
     *
     * @param $subject string The subject to clean
     * @return mixed string
     */
    public static function cleanSubject($subject)
    {
        return str_replace(["\t", "\n"], '', $subject);
    }
}
