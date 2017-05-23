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

use Eventum\Extension\AbstractExtension;
use Eventum\Test\TestCase;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExtensionSubscribeTest extends TestCase
{
    const RESPONSE = 'response';
    const NAME = 'response';

    public function testExtensionEvents()
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
        $dispatcher->dispatch('response', $event);
        $dispatcher->dispatch('name', $event);

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

class Extension1 extends AbstractExtension
{
}

class Extension2 extends AbstractExtension
{
    public function getSubscribers()
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

    public static function getSubscribedEvents()
    {
        return [
            'response' => [
                ['onKernelResponsePre', 10],
                ['onKernelResponsePost', -10],
            ],
            'name' => 'onStoreOrder',
        ];
    }

    public function onKernelResponsePre(Event $event)
    {
        $event->{$this->id}[] = __FUNCTION__;
    }

    public function onKernelResponsePost(Event $event)
    {
        $event->{$this->id}[] = __FUNCTION__;
    }

    public function onStoreOrder(Event $event)
    {
        $event->{$this->id}[] = __FUNCTION__;
    }
}
