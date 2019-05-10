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

use Email_Account;
use Eventum\Crypto\EncryptedValue;
use Eventum\Event\ConfigUpdateEvent;
use Eventum\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CryptoSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SystemEvents::CONFIG_CRYPTO_UPGRADE => 'upgrade',
            SystemEvents::CONFIG_CRYPTO_DOWNGRADE => 'downgrade',
        ];
    }

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     */
    public function upgrade(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->encrypt($config['database']['password']);
        $event->encrypt($config['ldap']['bindpw']);

        // encrypt email account passwords
        $accounts = Email_Account::getList();
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id'], true);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            // the raw value contains the original plaintext
            Email_Account::updatePassword($account['ema_id'], $password->getEncrypted());
        }
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     */
    public function downgrade(ConfigUpdateEvent $event): void
    {
        $config = $event->getConfig();

        $event->decrypt($config['database']['password']);
        $event->decrypt($config['ldap']['bindpw']);

        $accounts = Email_Account::getList();

        $state = $config['encryption'];

        // collect passwords when encryption enabled
        $passwords = [];
        $config['encryption'] = 'enabled';
        foreach ($accounts as $account) {
            $account = Email_Account::getDetails($account['ema_id'], true);
            /** @var EncryptedValue $password */
            $password = $account['ema_password'];
            $passwords[$account['ema_id']] = $password->getValue();
        }

        // save passwords when encryption disabled
        $config['encryption'] = 'disabled';
        foreach ($passwords as $ema_id => $password) {
            Email_Account::updatePassword($ema_id, $password);
        }

        // this needs to be restored, other events may rely on the value
        $config['encryption'] = $state;
    }
}
