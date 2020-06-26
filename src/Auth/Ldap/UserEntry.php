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

namespace Eventum\Auth\Ldap;

use Eventum\Config\Config;
use InvalidArgumentException;
use Misc;
use Symfony\Component\Ldap\Entry;

class UserEntry
{
    /** @var string */
    private $dn;
    /** @var string */
    private $uid;
    /** @var string */
    private $full_name;
    /** @var string[] */
    private $emails;
    /** @var int */
    private $customer_id;
    /** @var int */
    private $contact_id;

    /**
     * @param Entry $entry
     * @param Config $config
     */
    public function __construct(Entry $entry, Config $config)
    {
        $customer_id = $this->getAttributeValue($entry, $config['customer_id_attribute']);
        $contact_id = $this->getAttributeValue($entry, $config['contact_id_attribute']);
        $emails = $entry->getAttribute('mail');

        $this->dn = $entry->getDn();
        $this->uid = $this->getAttributeValue($entry, $config['user_id_attribute'] ?: 'uid');
        $this->full_name = $this->getAttributeValue($entry, 'cn');
        $this->emails = Misc::trim(Misc::lowercase($emails));
        $this->customer_id = Misc::trim($customer_id) ?: null;
        $this->contact_id = Misc::trim($contact_id) ?: null;
    }

    /**
     * @return string
     */
    public function getDn()
    {
        return $this->dn;
    }

    /**
     * @param string $dn
     * @return UserEntry
     */
    public function setDn($dn)
    {
        $this->dn = $dn;

        return $this;
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * @param string $uid
     * @return UserEntry
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->full_name;
    }

    /**
     * @param string $full_name
     * @return UserEntry
     */
    public function setFullName($full_name)
    {
        $this->full_name = $full_name;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getEmails()
    {
        return $this->emails;
    }

    /**
     * @param string[] $emails
     * @return UserEntry
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;

        return $this;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * @param int $customer_id
     * @return UserEntry
     */
    public function setCustomerId($customer_id)
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    /**
     * @return int
     */
    public function getContactId()
    {
        return $this->contact_id;
    }

    /**
     * @param int $contact_id
     * @return UserEntry
     */
    public function setContactId($contact_id)
    {
        $this->contact_id = $contact_id;

        return $this;
    }

    /**
     * Fetches a attribute value from an LDAP entry.
     *
     * @param Entry $entry
     * @param string $attribute
     * @return string|null
     */
    private function getAttributeValue(Entry $entry, $attribute)
    {
        if (!$entry->hasAttribute($attribute)) {
            return null;
        }

        $values = $entry->getAttribute($attribute);

        if (1 !== count($values)) {
            throw new InvalidArgumentException(sprintf('Attribute "%s" has multiple values.', $attribute));
        }

        return $values[0];
    }

    public function __toString()
    {
        return sprintf('%s(%s)', __CLASS__, $this->getDn());
    }
}
