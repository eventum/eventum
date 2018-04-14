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

namespace Eventum\Mail\Helper;

use InvalidArgumentException;
use Mime_Helper;
use Zend\Mail\Address;
use Zend\Mail\AddressList;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\HeaderInterface;
use Zend\Mail\Header\To;

/**
 * Helper to parse any address list type header (to, from, cc, bcc, reply-to) into Header object
 */
class AddressHeader
{
    // if address can not be parsed, this value is returned instead
    const INVALID_ADDRESS = 'INVALID ADDRESS:;';

    /** @var To */
    private $header;

    public function __construct(AbstractAddressList $addresses)
    {
        $this->header = $addresses;
    }

    /**
     * @param string $addresses
     * @throws \Zend\Mail\Header\Exception\InvalidArgumentException
     * @return AddressHeader
     */
    public static function fromString($addresses)
    {
        // avoid exceptions if NULL or empty string passed as input
        if (!$addresses) {
            return new static(new To());
        }

        // fromString expects 7bit input
        $addresses = Mime_Helper::encodeValue($addresses);

        // use To header to utilize AddressList functionality
        return new self(To::fromString('To:' . $addresses));
    }

    /**
     * Collect email addresses from AddressList
     *
     * @return string[]
     */
    public function getEmails()
    {
        $res = [];
        foreach ($this->header->getAddressList() as $address) {
            $res[] = $address->getEmail();
        }

        return $res;
    }

    /**
     * Collect display names from AddressList.
     * If display name is missing email is used.
     *
     * @return string[]
     */
    public function getNames()
    {
        $res = [];
        foreach ($this->header->getAddressList() as $address) {
            $name = $address->getName();
            $res[] = $name ? $name : $address->getEmail();
        }

        return $res;
    }

    /**
     * @return AddressList
     */
    public function getAddressList()
    {
        return $this->header->getAddressList();
    }

    /**
     * @param bool $format Whether to Mime-Encode header or not
     *
     * @return string
     */
    public function toString($format = HeaderInterface::FORMAT_ENCODED)
    {
        return $this->header->getFieldValue($format);
    }

    /**
     * Get Address object of the AddressList.
     * The input may contain only one address.
     *
     * @throws InvalidArgumentException
     * @return Address
     */
    public function getAddress()
    {
        $addressList = $this->getAddressList();
        $count = $addressList->count();
        if ($count !== 1) {
            throw new InvalidArgumentException("Expected 1 address, got $count");
        }

        return $addressList->current();
    }
}
