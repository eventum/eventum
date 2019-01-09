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

namespace Example\Event\Subscriber;

use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShouldEmailAddressSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            SystemEvents::NOTIFICATION_NOTIFY_ADDRESS => 'shouldEmailAddress',
        ];
    }

    public function shouldEmailAddress(ResultableEvent $event)
    {
        $address = $event['address'];
        if ($address === 'support@example.net') {
            $event->setResult(false);
        }
    }
}
