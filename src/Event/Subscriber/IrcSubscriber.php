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

namespace Eventum\Event\Subscriber;

use Date_Helper;
use DB_Helper;
use Eventum\Event\SystemEvents;
use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class IrcSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::IRC_NOTIFY => 'notifyIrc',
        ];
    }

    /**
     * Save event details to irc_notice table.
     *
     * @param GenericEvent $event
     * @param string $eventName
     * @param EventDispatcherInterface $dispatcher
     */
    public function notifyIrc(GenericEvent $event, $eventName, EventDispatcherInterface $dispatcher): void
    {
        if (!$this->notificationEnabled()) {
            return;
        }

        $dispatcher->dispatch($event, SystemEvents::IRC_FORMAT_MESSAGE);

        // if notice is empty, skip insert
        // this can be used in event handler to skip event handling.
        if (!$event['notice']) {
            return;
        }

        $params = [
            'ino_prj_id' => $event['prj_id'],
            'ino_created_date' => Date_Helper::getCurrentDateGMT(),
            'ino_status' => 'pending',
            'ino_message' => $event['notice'],
            'ino_category' => $event['category'],
        ];

        if ($event['issue_id']) {
            $params['ino_iss_id'] = $event['issue_id'];
        }
        if ($event['usr_id']) {
            $params['ino_target_usr_id'] = $event['usr_id'];
        }

        $stmt = 'INSERT INTO `irc_notice` SET ' . DB_Helper::buildSet($params);
        DB_Helper::getInstance()->query($stmt, $params);
    }

    private function notificationEnabled(): bool
    {
        $setup = ServiceContainer::getConfig();

        return $setup['irc_notification'] === 'enabled';
    }
}
