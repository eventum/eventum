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
use Mail_Helper;
use Setup;
use Zend\Mail\Transport;

class MailTransport
{
    /** @var Transport\TransportInterface|Transport\Smtp */
    private $transport;

    /**
     * Implements Mail::send() function using SMTP.
     *
     * @param mixed $recipient Either a comma-seperated list of recipients
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
        $transport = $this->getTransport();

        $envelope = new Transport\Envelope();
        // SMTP wants just Address
        $envelope->setTo(Mail_Helper::getEmailAddress($recipient));
        $transport->setEnvelope($envelope);

        try {
            $message = MailMessage::createFromHeaderBody($headers, $body);

            $transport->send($message->toMessage());
            $res = true;
        } catch (\Exception $e) {
            $traceFile = $this->getTraceFile();
            if ($traceFile) {
                file_put_contents($traceFile, json_encode([$recipient, $headers, $body]));
            }
            Logger::app()->error($e->getMessage(), ['traceFile' => $traceFile, 'exception' => $e]);
            $res = $e;
        } finally {
            // avoid leaking recipient in case of transport reuse
            $transport->setEnvelope(new Transport\Envelope());
        }

        return $res;
    }

    /**
     * Return Transport instance.
     *
     * @return Transport\Smtp|Transport\TransportInterface
     */
    public function getTransport()
    {
        if (!$this->transport) {
            $this->transport = Transport\Factory::create($this->getSpec());
        }

        return $this->transport;
    }

    /**
     * Get path where to dump trace of errors.
     * Can be made configurable in the future.
     *
     * @return string
     */
    private function getTraceFile()
    {
        $id = uniqid('zf-mail-');
        $traceFile = APP_LOG_PATH . "/$id.json";

        return $traceFile;
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
            $options['port'] = (int)$setup['port'];
        }

        if (file_exists('/etc/mailname')) {
            $options['name'] = trim(file_get_contents('/etc/mailname'));
        }

        if ($setup['auth']) {
            $ssl = $options['port'] === 587 ? 'tls' : 'ssl';
            $options['connection_class'] = 'login';
            $options['connection_config'] = [
                'username' => $setup['username'],
                'password' => $setup['password'],
                /** @see \Zend\Mail\Protocol\Smtp */
                // possible values: tls, ssl
                'ssl' => $setup['ssl'] ?: $ssl,
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
