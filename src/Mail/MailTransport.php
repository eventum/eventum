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

use Eventum\Config\Paths;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;
use Exception;
use Laminas\Mail\Transport;
use Mail_Helper;

class MailTransport
{
    use LoggerTrait;

    /** @var Transport\TransportInterface|Transport\Smtp */
    private $transport;

    /**
     * Implements Mail::send() function using SMTP.
     *
     * @param mixed $recipient Either a comma-separated list of recipients
     *              (RFC822 compliant), or an array of recipients,
     *              each RFC822 valid. This may contain recipients not
     *              specified in the headers, for Bcc:, resending
     *              messages, etc.
     * @param MailMessage $mail
     * @throws Exception
     */
    public function send($recipient, MailMessage $mail): void
    {
        $transport = $this->getTransport();

        $envelope = new Transport\Envelope();
        // SMTP wants just Address
        $envelope->setTo(Mail_Helper::getEmailAddress($recipient));
        $transport->setEnvelope($envelope);

        try {
            $transport->send($mail->toMessage());
        } catch (Exception $e) {
            $traceFile = $this->getTraceFile();
            if ($traceFile) {
                // this is largely useless, as the exception likely happens in toMessage call above
                file_put_contents($traceFile, json_encode([$recipient, $mail->getHeadersArray(), $mail->getContent()]));
            }
            $this->error($e->getMessage(), ['traceFile' => $traceFile, 'exception' => $e]);

            throw $e;
        } finally {
            // avoid leaking recipient in case of transport reuse
            $transport->setEnvelope(new Transport\Envelope());
        }
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
     */
    private function getTraceFile(): string
    {
        $id = uniqid('zf-mail-', true);

        return Paths::APP_LOG_PATH . "/$id.json";
    }

    /**
     * Get Specification for Mail Transport Factory
     */
    private function getSpec(): array
    {
        $setup = ServiceContainer::getConfig()['smtp'];

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

        if ($setup['ssl']) {
            $ssl = $options['port'] === 587 ? 'tls' : 'ssl';

            $options['connection_config'] = [
                /** @see \Laminas\Mail\Protocol\Smtp */
                // possible values: tls, ssl
                'ssl' => $setup['ssl'] ?: $ssl,
            ];
        }

        if ($setup['auth']) {
            $options['connection_class'] = 'login';
            $options['connection_config']['username'] = $setup['username'];
            $options['connection_config']['password' ] = $setup['password'];
        }

        $spec = [
            /**
             * @see \Laminas\Mail\Transport\Factory::$classMap
             */
            'type' => $setup['type'] ?: 'smtp',
            'options' => $options,
        ];

        return $spec;
    }
}
