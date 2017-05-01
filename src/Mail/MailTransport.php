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

namespace Eventum\Mail;

use Eventum\Monolog\Logger;
use Mail;
use Mail_smtp;
use Misc;
use PEAR_Error;
use Setup;

class MailTransport
{
    /** @var Mail_smtp */
    private $smtp;

    public function __construct()
    {
        $this->smtp = new Mail_smtp($this->getSMTPSettings());
    }

    /**
     * Implements Mail::send() function using SMTP.
     *
     * @param mixed $recipients Either a comma-seperated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     *
     * @param array $headers The array of headers to send with the mail, in an
     *              associative array, where the array key is the
     *              header name (e.g., 'Subject'), and the array value
     *              is the header value (e.g., 'test'). The header
     *              produced from those values would be 'Subject:
     *              test'.
     *
     * @param string $body the full text of the message body, including any
     *               MIME parts, etc
     *
     * @return mixed returns true on success, or a PEAR_Error
     *               containing a descriptive error message on
     *               failure
     */
    public function send($recipient, $headers, $body)
    {
        $res = $this->smtp->send($recipient, $headers, $body);
        if (Misc::isError($res)) {
            /** @var PEAR_Error $res */
            Logger::app()->error($res->getMessage(), ['debug' => $res->getDebugInfo()]);

            return $res;
        }

        return true;
    }

    /**
     * Method used to get the application specific settings regarding
     * which SMTP server to use, such as login and server information.
     *
     * @return  array
     */
    private function getSMTPSettings()
    {
        $settings = Setup::get();

        if (file_exists('/etc/mailname')) {
            $settings['smtp']['localhost'] = trim(file_get_contents('/etc/mailname'));
        }

        return $settings['smtp']->toArray();
    }
}
