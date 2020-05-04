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
use Eventum\Logger\LoggerTrait;
use Setup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Throwable;
use Xhgui\Profiler\Profiler;
use Xhgui\Profiler\ProfilingFlags;

class XhguiProfilerExtension implements SubscriberProvider, EventSubscriberInterface
{
    use LoggerTrait;

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
        } catch (Throwable $e) {
            $this->debug($e->getMessage(), ['exception' => $e]);

            return;
        }

        $profiler->enable();
        $profiler->registerShutdownHandler();
    }

    private function getConfig(): array
    {
        $defaultConfig = [
            'profiler.enable' => static function () {
                return true;
            },
            'profiler.flags' => [
                ProfilingFlags::CPU,
                ProfilingFlags::MEMORY,
                ProfilingFlags::NO_BUILTINS,
                ProfilingFlags::NO_SPANS,
            ],
            'profiler.options' => [
            ],
        ];
        $config = Setup::get()['xhgui_profiler']->toArray();

        return array_merge($defaultConfig, $config);
    }
}
