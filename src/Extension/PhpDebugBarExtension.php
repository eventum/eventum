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

use DebugBar\StandardDebugBar;
use Eventum\DebugBarManager;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider\SubscriberProvider;
use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PhpDebugBarExtension implements SubscriberProvider, EventSubscriberInterface
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
        DebugBarManager::getDebugBarManager($this->isEnabled());
    }

    private function isEnabled(): bool
    {
        // disable debugBar in CLI
        if (PHP_SAPI === 'cli') {
            return false;
        }

        // in production, class not present
        if (!class_exists(StandardDebugBar::class)) {
            return false;
        }

        $config = ServiceContainer::getConfig()['debugbar'];

        return $config['status'] === 'enabled';
    }
}
