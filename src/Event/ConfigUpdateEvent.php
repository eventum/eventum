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

use Eventum\Crypto\CryptoException;
use Eventum\Crypto\EncryptedValue;
use Symfony\Component\EventDispatcher\GenericEvent;
use Zend\Config\Config;

class ConfigUpdateEvent extends GenericEvent
{
    /**
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->getSubject();
    }

    /**
     * @param string $plaintext
     * @throws CryptoException
     * @return EncryptedValue
     */
    public function encrypt(string $plaintext): EncryptedValue
    {
        return (new EncryptedValue())->setValue($plaintext);
    }

    /**
     * @param EncryptedValue $encrypted
     * @throws CryptoException
     * @return string
     */
    public function decrypt(EncryptedValue $encrypted): string
    {
        return $encrypted->getValue();
    }
}
