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

namespace Example\Subscriber;

use Eventum\Event\ResultableEvent;
use Eventum\Event\SystemEvents;
use Laminas\Mail\Address;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShouldEmailAddressSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::NOTIFICATION_NOTIFY_ADDRESS => 'shouldEmailAddress',
        ];
    }

    public function shouldEmailAddress(ResultableEvent $event): void
    {
        /** @var Address $address */
        $address = $event['address'];
        $email = $address;

        if ($email === 'support@example.net') {
            $event->setResult(false);
        }
    }
}
