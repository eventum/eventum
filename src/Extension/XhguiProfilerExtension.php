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

namespace Eventum\Extension;

use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider\SubscriberProvider;
use RuntimeException;
use Setup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Xhgui\Profiler\Profiler;

class XhguiProfilerExtension implements SubscriberProvider, EventSubscriberInterface
{
    public function getSubscribers(): array
    {
        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::BOOT => 'boot',
        ];
    }

    public function boot(): void
    {
        $config = $this->getConfig();

        if ($config['status'] !== 'enabled') {
            return;
        }

        try {
            $profiler = new Profiler($config);
        } catch (RuntimeException $e) {
            return;
        }

        $profiler->enable();
        $profiler->registerShutdownHandler();
    }

    private function getConfig(): array
    {
        $defaultConfig = [
            'profiler.enable' => function () {
                return true;
            },
        ];
        $config = Setup::get()['xhgui_profiler']->toArray();

        return array_merge($defaultConfig, $config);
    }
}
