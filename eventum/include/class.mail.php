<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
// +----------------------------------------------------------------------+
// | Eventum - Issue Tracking System                                      |
// +----------------------------------------------------------------------+
// | Copyright (c) 2003, 2004 MySQL AB                                    |
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
// @(#) $Id: s.class.mail.php 1.26 04/01/23 00:25:55-00:00 jpradomaia $
//


/**
 * Class to handle the business logic related to sending email to 
 * outside recipients. This class utilizes the PEAR::Mail 
 * infrastructure to deliver email in a compatible way across 
 * different platforms.
 *
 * @version 1.0
 * @author João Prado Maia <jpm@mysql.com>
 */

include_once(APP_INC_PATH . "class.error_handler.php");
include_once(APP_INC_PATH . "class.setup.php");
include_once(APP_INC_PATH . "class.user.php");
include_once(APP_INC_PATH . "class.mime_helper.php");

class Mail_API
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
    function Mail_API()
    {
        @include_once(APP_PEAR_PATH . 'Mail.php');
        @include_once(APP_PEAR_PATH . 'Mail/mime.php');
        $this->mime = new Mail_mime("\r\n");
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
        $str = Mail_API::fixAddressQuoting($str);
        $str = Mime_Helper::encodeValue($str);
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
     * "Sender Name" <sender@domain.com>.
     *
     * @access  public
     * @param   string $address The email address value
     * @return  array The address information
     */
    function fixAddressQuoting($address)
    {
        // check if we have a <
        if (strstr($address, '<')) {
            $address = stripslashes($address);
            if (strstr($address, "'")) {
                $first_part = substr($address, 0, strpos($address, '<') - 1);
                $first_part = '"' . $first_part . '"';
                $second_part = substr($address, strpos($address, '<'));
                return $first_part . ' ' . $second_part;
            } else {
                return $address;
            }
        } else {
            return $address;
        }
    }


    /**
     * Method used to break down the email address information and
     * return it for easy manipulation.
     *
     * @access  public
     * @param   string $address The email address value
     * @return  array The address information
     */
    function getAddressInfo($address)
    {
        $address = Mail_API::fixAddressQuoting($address);
        $address = Mime_Helper::encodeValue($address);
        include_once(APP_PEAR_PATH . "Mail/RFC822.php");
        $t = Mail_RFC822::parseAddressList($address);
        return array(
            'sender_name' => $t[0]->personal,
            'email'       => $t[0]->mailbox . '@' . $t[0]->host,
            'username'    => $t[0]->mailbox,
            'host'        => $t[0]->host
        );
    }


    /**
     * Method used to get the email address portion of a given
     * recipient information.
     *
     * @access  public
     * @param   string $address The email address value
     * @return  string The email address
     */
    function getEmailAddress($address)
    {
        $address = Mail_API::fixAddressQuoting($address);
        $address = Mime_Helper::encodeValue($address);
        include_once(APP_PEAR_PATH . "Mail/RFC822.php");
        $t = Mail_RFC822::parseAddressList($address);
        return $t[0]->mailbox . '@' . $t[0]->host;
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
                $this->headers[$key] = Mime_Helper::encodeValue($value);
            }
        } else {
            $this->headers[$header] = Mime_Helper::encodeValue($value);
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
     * Method used to add a customized warning message to the body
     * of outgoing emails.
     *
     * @access  public
     * @param   string $from The originator of the message
     * @param   string $to The recipient of the message
     * @param   string $body The body of the message
     * @return  string The body of the message with the warning message, if appropriate
     */
    function addWarningMessage($from, $to, $body)
    {
        $setup = Setup::load();
        if (($setup['email_routing']['status'] == 'enabled') && ($setup['email_routing']['warning']['status'] == 'enabled')) {
            $sender_email = Mail_API::getEmailAddress($from);
            $recipient_email = Mail_API::getEmailAddress($to);
            $recipient_usr_id = User::getUserIDByEmail($recipient_email);
            // don't add the warning message if the recipient is an unknown email address
            if (empty($recipient_usr_id)) {
                return $body;
            }
            $recipient_role_id = User::getRoleByUser($recipient_usr_id);
            if (($recipient_role_id <= $setup['email_routing']['warning']['permission_level']) &&
                    (!empty($setup['email_routing']['warning']['message'])) && 
                    (substr($sender_email, 0, strlen($setup['email_routing']['address_prefix'])) == $setup['email_routing']['address_prefix']) && 
                    (substr($sender_email, strpos($sender_email, '@')+1) == $setup['email_routing']['address_host'])) {
                $issue_id = substr($sender_email, strlen($setup['email_routing']['address_prefix']), strpos($sender_email, '@')-strlen($setup['email_routing']['address_prefix']));
                $warning_msg = $setup['email_routing']['warning']['message'];
                $warning_msg = str_replace('%APP_URL%', APP_BASE_URL, $warning_msg);
                $warning_msg = str_replace('%ISSUE_ID%', $issue_id, $warning_msg);
                return $warning_msg . "\n\n" . $body;
            } else {
                return $body;
            }
        } else {
            return $body;
        }
    }


    /**
     * Method used to send the SMTP based email message.
     *
     * @access  public
     * @param   string $from The originator of the message
     * @param   string $to The recipient of the message
     * @param   string $subject The subject of the message
     * @return  string The full body of the message that was sent
     */
    function send($from, $to, $subject, $save_email_copy = FALSE)
    {
        // encode the addresses
        $from = MIME_Helper::encodeAddress($from);
        $to = MIME_Helper::encodeAddress($to);
        $subject = MIME_Helper::encode($subject);

        // add a warning if we are sending an issue related email to
        // an user that has its permission level lower than a configurable setting
        $this->setTextBody($this->addWarningMessage($from, $to, $this->text_body));

        $setup = Setup::load();
        $body = $this->mime->get();
        $params = $this->getSMTPSettings();
        $this->setHeaders(array(
            'From'    => $from,
            'To'      => Mail_API::fixAddressQuoting($to),
            'Subject' => $subject
        ));
        $hdrs = $this->mime->headers($this->headers);
        $mail =& Mail::factory('smtp', $params);
        $res = $mail->send($to, $hdrs, $body);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
            return $res;
        } else {
            if ($save_email_copy) {
                // send a copy of this email to eventum_sent@
                $this->saveEmailInformation($from, $hdrs, $body);
            }
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
     * Method used to get the list of issues to be displayed in the grid layout.
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
        $from = addslashes(MIME_Helper::encodeAddress($from));
        $to = addslashes(MIME_Helper::encodeAddress($to));
        $subject = MIME_Helper::encode($subject);

        $body = $this->mime->get();
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
     * @param   string $from The sender of the email
     * @param   array $hdrs The list of headers to be used in this email
     * @param   string $body The body of the email
     */
    function saveEmailInformation($from, $hdrs, $body)
    {
        static $subjects;

        // do we really want to save every outgoing email?
        $setup = Setup::load();
        if ((@$setup['smtp']['save_outgoing_email'] != 'yes') || (empty($setup['smtp']['save_address']))) {
            return false;
        }

        // prevent duplicate emails from being sent out...
        $subject = @$hdrs['Subject'];
        if (@in_array($subject, $subjects)) {
            return false;
        }

        // replace the To: header with the requested address
        $address = $setup['smtp']['save_address'];
        $body = preg_replace("/To: (.*)/i", "To: $address", $body, 1);
        $hdrs['To'] = $address;

        $params = Mail_API::getSMTPSettings();
        $mail =& Mail::factory('smtp', $params);
        $res = $mail->send($address, $hdrs, $body);
        if (PEAR::isError($res)) {
            Error_Handler::logError(array($res->getMessage(), $res->getDebugInfo()), __FILE__, __LINE__);
        }

        $subjects[] = $subject;
    }
}

// benchmarking the included file (aka setup time)
if (APP_BENCHMARK) {
    $GLOBALS['bench']->setMarker('Included Mail_API Class');
}
?>