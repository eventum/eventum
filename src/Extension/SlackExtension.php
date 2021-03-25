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

use Eventum\Config\Config;
use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider\SubscriberProvider;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SlackExtension implements SubscriberProvider, EventSubscriberInterface
{
    use LoggerTrait;

    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = ServiceContainer::getConfig()['slack'];
    }

    public function getSubscribers(): array
    {
        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            /** @see configUpgrade */
            SystemEvents::CONFIG_CRYPTO_UPGRADE => 'configUpgrade',
            /** @see configDowngrade */
            SystemEvents::CONFIG_CRYPTO_DOWNGRADE => 'configDowngrade',
            /** @see configSave */
            SystemEvents::CONFIG_SAVE => 'configSave',
        ];
    }

    public function configUpgrade(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->encrypt($config['slack']['webhook_url']);
    }

    public function configDowngrade(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->decrypt($config['slack']['webhook_url']);
    }

    public function configSave(ConfigUpdateEvent $event): void
    {
        if ($event->hasEncryption()) {
            $this->configUpgrade($event);
        } else {
            $this->configDowngrade($event);
        }
    }
}
