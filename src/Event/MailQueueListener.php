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

namespace Eventum\Event;

use Date_Helper;
use DB_Helper;
use Eventum\Mail\MailMessage;
use League\Flysystem\Exception;
use Mail_Helper;
use Mail_Queue;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class MailQueueListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::MAIL_QUEUE_SEND => 'send',
            SystemEvents::MAIL_QUEUE_SENT => 'sent',
            SystemEvents::MAIL_QUEUE_ERROR => 'error',
        ];
    }

    public function send(GenericEvent $event)
    {
        /** @var MailMessage $mail */
        $mail = $event->getSubject();

        // remove any Reply-To:/Return-Path: values from outgoing messages
        $headers = $mail->getHeaders();
        $headers->removeHeader('Reply-To');
        $headers->removeHeader('Return-Path');
    }

    public function sent(GenericEvent $event)
    {
        /** @var MailMessage $mail */
        $mail = $event->getSubject();
        $this->addStatusLog($event['id'], Mail_Queue::STATUS_SENT);
        $this->setStatus($event['id'], Mail_Queue::STATUS_SENT);

        if ($event['save_copy']) {
            Mail_Helper::saveOutgoingEmailCopy($mail, $event['maq_iss_id'], $event['maq_type']);
        }
    }

    public function error(GenericEvent $event)
    {
        /** @var Exception $e */
        $e = $event->getSubject();

        $errorMessage = $e->getMessage();
        $errorCount = $this->getQueueErrorCount($event['id']);

        if ($event['maq_iss_id']) {
            $errorMessage = "issue #{$event['maq_iss_id']}: $errorMessage";
        }
        echo "Mail_Queue: Can't send mail {$event['id']} ($errorCount tries): $errorMessage\n";

        $status = Mail_Queue::STATUS_ERROR;
        $this->addStatusLog($event['id'], $status, $errorMessage);

        if ($errorCount >= Mail_Queue::MAX_RETRIES) {
            $status = Mail_Queue::STATUS_FAILED;
        }
        $this->setStatus($event['id'], $status);
    }

    /**
     * Saves a log entry about the attempt, successful or otherwise, to send the
     * queued email message. Also updates maq_status of $maq_id to $status.
     *
     * @param   int $maq_id The queued email message ID
     * @param   string $status The status of the attempt ('sent' or 'error')
     * @param   string $server_message The full message from the SMTP server, in case of an error
     */
    private function addStatusLog($maq_id, $status, $server_message = '')
    {
        $stmt = 'INSERT INTO
                    `mail_queue_log`
                 (
                    mql_maq_id,
                    mql_created_date,
                    mql_status,
                    mql_server_message
                 ) VALUES (
                    ?, ?, ?, ?
                 )';
        $params = [
            $maq_id,
            Date_Helper::getCurrentDateGMT(),
            $status,
            $server_message,
        ];
        DB_Helper::getInstance()->query($stmt, $params);
    }

    private function setStatus($maq_id, $status)
    {
        $stmt = 'UPDATE
                    `mail_queue`
                 SET
                    maq_status=?
                 WHERE
                    maq_id=?';

        DB_Helper::getInstance()->query($stmt, [$status, $maq_id]);
    }

    /**
     * Return number of errors for this queue item
     *
     * @param int $maq_id
     * @return int
     */
    private function getQueueErrorCount($maq_id)
    {
        $sql = 'select count(*) from `mail_queue_log` where mql_maq_id=? and mql_status=?';
        $res = DB_Helper::getInstance()->getOne($sql, [$maq_id, Mail_Queue::STATUS_ERROR]);

        return (int)$res;
    }
}
