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

use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventManager
{
    /**
     * Singleton for Event Dispatcher
     * @deprecated since 3.10.2, use ServiceContainer::getEventDispatcher();
     */
    public static function getEventDispatcher($notifyDeprecated = true): EventDispatcherInterface
    {
        static $dispatcher;
        if (!$dispatcher) {
            if ($notifyDeprecated) {
                trigger_deprecation('eventum/eventum', '3.10.2', 'Method "%s::%s" is deprecated', __CLASS__, __METHOD__);
            }
            $dispatcher = ServiceContainer::get(EventDispatcherInterface::class);
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
     * @deprecated since 3.10.2, use ServiceContainer::dispatch();
     */
    public static function dispatch($eventName, $event = null)
    {
        trigger_deprecation('eventum/eventum', '3.10.2', 'Method "%s::%s" is deprecated, use ServiceContainer::dispatch', __CLASS__, __METHOD__);

        return self::getEventDispatcher(false)->dispatch($event ?? new Event(), $eventName);
    }
}
