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

namespace Eventum\Event\Subscriber;

use Eventum\Crypto\EncryptedValue;
use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CryptoSubscriber implements EventSubscriberInterface
{
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

        if (!$config['database']['password'] instanceof EncryptedValue) {
            $config['database']['password'] = $event->encrypt($config['database']['password']);
        }

        if (count($config['ldap']) && !$config['ldap']['bindpw'] instanceof EncryptedValue) {
            $config['ldap']['bindpw'] = $event->encrypt($config['ldap']['bindpw']);
        }
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     */
    public function downgradeConfig(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        if ($config['database']['password'] instanceof EncryptedValue) {
            $config['database']['password'] = $event->decrypt($config['database']['password']);
        }

        if (count($config['ldap']) && $config['ldap']['bindpw'] instanceof EncryptedValue) {
            $config['ldap']['bindpw'] = $event->decrypt($config['ldap']['bindpw']);
        }
    }
}
