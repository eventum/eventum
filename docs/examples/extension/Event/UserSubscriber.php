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

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::USER_CREATE => 'userCreated',
            SystemEvents::USER_UPDATE => 'userUpdated',
        ];
    }

    /**
     * @param UnstructuredEvent $event
     */
    public function userCreated(UnstructuredEvent $event)
    {
        error_log("user created: #{$event->id}");
    }

    /**
     * @param UnstructuredEvent $event
     */
    public function userUpdated(UnstructuredEvent $event)
    {
        error_log("user updated: #{$event->id}");
    }
}
