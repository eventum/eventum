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

use Eventum\Event\Subscriber;
use Eventum\Extension\ExtensionManager;
use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventManager
{
    /**
     * Singleton for Event Dispatcher
     */
    public static function getEventDispatcher(): EventDispatcherInterface
    {
        static $dispatcher;
        if (!$dispatcher) {
            $dispatcher = new EventDispatcher();

            // register subscribers from extensions
            /** @var ExtensionManager $em */
            $em = ServiceContainer::get(ExtensionManager::class);
            $subscribers = $em->getSubscribers();
            foreach ($subscribers as $subscriber) {
                $dispatcher->addSubscriber($subscriber);
            }

            // load builtin event subscribers
            $dispatcher->addSubscriber(new Subscriber\MailQueueListener());
            $dispatcher->addSubscriber(new Subscriber\CryptoSubscriber());
        }

        return $dispatcher;
    }

    /**
     * Helper to dispatch events
     *
     * @param string $eventName
     * @param Event|\Symfony\Component\EventDispatcher\Event $event
     * @return Event|object
     * @see EventDispatcherInterface::dispatch()
     */
    public static function dispatch($eventName, $event = null)
    {
        return self::getEventDispatcher()->dispatch($event ?? new Event(), $eventName);
    }
}
