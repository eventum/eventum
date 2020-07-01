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
use Template_Helper;

class SentryExtension implements SubscriberProvider, EventSubscriberInterface
{
    use LoggerTrait;

    /** @var Config */
    private $config;

    public function __construct()
    {
        $this->config = ServiceContainer::getConfig()['sentry'];
    }

    public function getSubscribers(): array
    {
        if ($this->config['status'] !== 'enabled' || !$this->config['project']) {
            return [];
        }

        return [
            self::class,
        ];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::SMARTY_PROCESS => 'smartyProcess',
            SystemEvents::CONFIG_CRYPTO_UPGRADE => 'configUpgrade',
            SystemEvents::CONFIG_CRYPTO_DOWNGRADE => 'configDowngrade',
            SystemEvents::CONFIG_SAVE => 'configSave',
        ];
    }

    public function smartyProcess(Template_Helper $smarty): void
    {
        // dsn consists of: 'https://<key>@<organization>.ingest.sentry.io/<project>'
        $config = [
            'dsn' => sprintf('https://%s@%s/%s',
                $this->config['key'] ?: 'anonymous',
                $this->config['domain'] ?: 'ingest.sentry.io',
                $this->config['project'] ?: 0
            ),
        ];

        $smarty->assign('sentry', $config);
        $smarty->addHeaderTemplate('sentry');
    }

    public function configUpgrade(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->encrypt($config['sentry']['key']);
    }

    public function configDowngrade(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->decrypt($config['sentry']['key']);
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
