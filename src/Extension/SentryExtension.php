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
use Eventum\Event\SystemEvents;
use Eventum\Extension\Provider\SubscriberProvider;
use Eventum\Logger\LoggerTrait;
use Eventum\ServiceContainer;
use Smarty;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

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
        if ($this->config['status'] !== 'enabled') {
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
        ];
    }

    public function smartyProcess(GenericEvent $event): void
    {
        /** @var Smarty $smarty */
        $smarty = $event->getSubject();

        // dsn consists of: 'https://<key>@<organization>.ingest.sentry.io/<project>'
        $config = [
            'dsn' => sprintf('https://%s@%s/%s',
                $this->config['key'] ?: 'anonymous',
                $this->config['domain'] ?: 'ingest.sentry.io',
                $this->config['project'] ?: 0
            ),
        ];

        $smarty->assign('sentry', $config);
    }
}
