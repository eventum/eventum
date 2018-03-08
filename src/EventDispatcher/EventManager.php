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

namespace Eventum\EventDispatcher;

use Eventum\Event\MailQueueListener;
use Eventum\Extension\ExtensionManager;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventManager
{
    /**
     * Singleton for Event Dispatcher
     *
     * @return EventDispatcher
     */
    public static function getEventDispatcher()
    {
        static $dispatcher;
        if (!$dispatcher) {
            $dispatcher = new EventDispatcher();

            // register subscribers from extensions
            $em = ExtensionManager::getManager();
            $subscribers = $em->getSubscribers();
            foreach ($subscribers as $subscriber) {
                $dispatcher->addSubscriber($subscriber);
            }

            // TODO: figure out how to add builtins
            $dispatcher->addSubscriber(new MailQueueListener());
        }

        return $dispatcher;
    }

    /**
     * Helper to dispatch events
     *
     * @param string $eventName
     * @param Event $event
     * @return Event
     * @see EventDispatcherInterface::dispatch()
     */
    public static function dispatch($eventName, Event $event = null)
    {
        return self::getEventDispatcher()->dispatch($eventName, $event);
    }
}
