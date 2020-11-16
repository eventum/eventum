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

namespace Eventum\ServiceProvider;

use Eventum\Event\Subscriber;
use Eventum\ServiceContainer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventDispatcherService implements ServiceProviderInterface
{
    public function register(Container $app): void
    {
        $app[EventDispatcherInterface::class] = static function () {
            $dispatcher = new EventDispatcher();

            // register subscribers from extensions
            $em = ServiceContainer::getExtensionManager();
            $em->boot();
            $subscribers = $em->getSubscribers();
            foreach ($subscribers as $subscriber) {
                $dispatcher->addSubscriber($subscriber);
            }

            // load builtin event subscribers
            $dispatcher->addSubscriber(new Subscriber\MailQueueListener());
            $dispatcher->addSubscriber(new Subscriber\CryptoSubscriber());

            return $dispatcher;
        };
    }
}
