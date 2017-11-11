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

namespace Eventum\Event;

use Eventum\Crypto\CryptoManager;
use Eventum\Crypto\EncryptedValue;
use Setup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CryptoSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            WorkflowEvents::CONFIG_CRYPTO_UPGRADE => 'upgradeConfig',
            WorkflowEvents::CONFIG_CRYPTO_DOWNGRADE => 'downgradeConfig',
        ];
    }

    /**
     * Upgrade config so that values contain EncryptedValue where some secrecy is wanted
     *
     * @see \Eventum\Crypto\CryptoUpgradeManager::upgradeConfig
     */
    public function upgradeConfig()
    {
        $config = $this->getConfig();

        if (count($config['ftp']) && !$config['ftp']['password'] instanceof EncryptedValue) {
            $config['ftp']['password'] = new EncryptedValue(
                CryptoManager::encrypt($config['ftp']['password'])
            );
        }
    }

    /**
     * Downgrade config: remove all EncryptedValue elements
     *
     * @see \Eventum\Crypto\CryptoUpgradeManager::downgradeConfig
     */
    public function downgradeConfig()
    {
        $config = $this->getConfig();

        if (count($config['ftp']) && $config['ftp']['password'] instanceof EncryptedValue) {
            /** @var EncryptedValue $value */
            $value = $config['ftp']['password'];
            $config['ftp']['password'] = $value->getValue();
        }
    }

    /**
     * @return \Zend\Config\Config
     */
    private function getConfig()
    {
        return Setup::get();
    }
}
