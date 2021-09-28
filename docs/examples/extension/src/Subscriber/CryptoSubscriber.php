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

use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CryptoSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::CONFIG_CRYPTO_UPGRADE => 'upgradeConfig',
            SystemEvents::CONFIG_CRYPTO_DOWNGRADE => 'downgradeConfig',
        ];
    }

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     */
    public function upgradeConfig(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->encrypt($config['ftp']['password']);
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     */
    public function downgradeConfig(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->decrypt($config['ftp']['password']);
    }
}
