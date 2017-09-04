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

class Mail_Helper
{
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
        $re_pattern = "/(\[#\d+\] ){0,1}(([Rr][Ee][Ss]?|Ответ|Antwort|SV|[Aa][Ww]|[Rr][Ii][Ff]\.?)(\[[0-9]+\])?[ \t]*: ){2}(.*)/u";
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
     * Method used to save a copy of the given email to a configurable address.
     *
     * @param MailMessage $mail the email to save
     * @param int $issue_id
     * @param string $maq_type
     */
    public static function saveOutgoingEmailCopy(MailMessage $mail, $issue_id, $maq_type)
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
            self::addSpecializedHeaders($mail, $issue_id, $maq_type);
        }

        $transport = new MailTransport();
        $transport->send($recipient, $mail);
    }

    /**
     * Adds specialized headers for an email.
     *
     * @param MailMessage $mail
     * @param int $issue_id The issue ID
     * @param string $type The type of message this is
     */
    public static function addSpecializedHeaders(MailMessage $mail, $issue_id, $type)
    {
        $new_headers = [];
        $new_headers['X-Eventum-Type'] = $type;

        if (!$issue_id) {
            // nothing else to do if no issue id
            $mail->addHeaders($new_headers);

            return;
        }

        $prj_id = Issue::getProjectID($issue_id);
        if (count(Group::getAssocList($prj_id)) > 0) {
            // group issue is currently assigned too
            $groupName = Group::getName(Issue::getGroupID($issue_id));
            $new_headers['X-Eventum-Group-Issue'] = $groupName;
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

        $assignees = User::getEmail(Issue::getAssignedUserIDs($issue_id));
        $new_headers['X-Eventum-Assignee'] = implode(',', $assignees);
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

        $mail->addHeaders($new_headers);
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
     * Make sure that In-Reply-To and References headers are set and reference a message in this issue.
     * If not, set to be the root message ID of the issue. This is to ensure messages are threaded by
     * issue in mail clients.
     *
     * @param MailMessage $mail
     * @param int $issue_id
     * @param string $type
     */
    public static function rewriteThreadingHeaders(MailMessage $mail, $issue_id, $type = 'email')
    {
        // check if the In-Reply-To header exists and if so,
        // does it relate to a message stored in Eventum
        // if it does not, set new In-Reply-To header

        $reference_msg_id = $mail->getReferenceMessageId();
        $reference_issue_id = null;
        if ($reference_msg_id) {
            // check if referenced msg id is associated with this issue
            if ($type == 'note') {
                $reference_issue_id = Note::getIssueByMessageID($reference_msg_id);
            } else {
                $reference_issue_id = Support::getIssueByMessageID($reference_msg_id);
            }
        }

        if (!$reference_msg_id || $reference_issue_id != $issue_id) {
            $reference_msg_id = Issue::getRootMessageID($issue_id);
        }
        $references = self::getReferences($issue_id, $reference_msg_id, $type);

        $mail->setInReplyTo($reference_msg_id);
        $mail->setReferences($references);
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
     * @return array
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
