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
use Setup;
use Zend\Mail\Transport;

class MailTransport
{
    /** @var Transport\TransportInterface|Transport\Smtp|Transport\File */
    private $transport;

    public function __construct()
    {
        $this->transport = Transport\Factory::create($this->getSpec());
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
     * @return mixed Returns true on success, or a exception class
     *               containing a descriptive error message on
     *               failure
     */
    public function send($recipient, $headers, $body)
    {
        if ($this->transport instanceof Transport\Smtp) {
            $envelope = new Transport\Envelope();
            $envelope->setTo($recipient);
            $this->transport->setEnvelope($envelope);
        }

        $message = MailMessage::createFromHeaderBody($headers, $body);

        try {
            $this->transport->send($message->toMessage());
            $res = true;
        } catch (\Exception $e) {
            Logger::app()->error($e->getMessage());
            $res = $e;
        } finally {
            // avoid leaking recipient in case of transport reuse
            if ($this->transport instanceof Transport\Smtp) {
                $this->transport->setEnvelope(new Transport\Envelope());
            }
        }

        return $res;
    }

    /**
     * Get Specification for Mail Transport Factory
     *
     * @return array
     */
    private function getSpec()
    {
        $setup = Setup::get()['smtp'];

        $options = [];
        if ($setup['host']) {
            $options['host'] = $setup['host'];
        }
        if ($setup['port']) {
            $options['port'] = $setup['port'];
        }

        if (file_exists('/etc/mailname')) {
            $options['name'] = trim(file_get_contents('/etc/mailname'));
        }

        if ($setup['auth']) {
            $options['connection_class'] = 'login';
            $options['connection_config'] = [
                'username' => $setup['username'],
                'password' => $setup['password'],
            ];
        }

        $spec = [
            /**
             * @see \Zend\Mail\Transport\Factory::$classMap
             */
            'type' => $setup['type'] ?: 'smtp',
            'options' => $options,
        ];

        return $spec;
    }
}
