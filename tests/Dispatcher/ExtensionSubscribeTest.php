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

namespace Eventum\Test\Dispatcher;

use Eventum\Extension\Provider;
use Eventum\Test\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ExtensionSubscribeTest extends TestCase
{
    public const RESPONSE = 'eventum_event_response';
    public const NAME = 'eventum_event_name';

    public function testExtensionEvents(): void
    {
        $config = [
            Extension1::class => __FILE__,
            Extension2::class => __FILE__,
        ];

        $manager = $this->getExtensionManager($config);
        $dispatcher = new EventDispatcher();
        $subscribers = $manager->getSubscribers();
        foreach ($subscribers as $subscriber) {
            $dispatcher->addSubscriber($subscriber);
        }

        $event = new Event();
        $dispatcher->dispatch($event, self::RESPONSE);
        $dispatcher->dispatch($event, self::NAME);

        $id = StoreSubscriber::class;
        $res = $event->{$id};
        $exp = [
            'onKernelResponsePre',
            'onKernelResponsePost',
            'onStoreOrder',
        ];

        $this->assertEquals($exp, $res);
    }
}

class Extension1 implements Provider\SubscriberProvider
{
    public function getSubscribers(): array
    {
        return [];
    }
}

class Extension2 implements Provider\SubscriberProvider
{
    public function getSubscribers(): array
    {
        return [
            StoreSubscriber::class,
        ];
    }
}

class StoreSubscriber implements EventSubscriberInterface
{
    private $id;

    public function __construct()
    {
        $this->id = __CLASS__;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ExtensionSubscribeTest::RESPONSE => [
                ['onKernelResponsePre', 10],
                ['onKernelResponsePost', -10],
            ],
            ExtensionSubscribeTest::NAME => 'onStoreOrder',
        ];
    }

    public function onKernelResponsePre(Event $event): void
    {
        $event->{$this->id}[] = __FUNCTION__;
    }

    public function onKernelResponsePost(Event $event): void
    {
        $event->{$this->id}[] = __FUNCTION__;
    }

    public function onStoreOrder(Event $event): void
    {
        $event->{$this->id}[] = __FUNCTION__;
    }
}
