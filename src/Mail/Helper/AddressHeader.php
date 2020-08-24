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
use Laminas\Mail\Address;
use Laminas\Mail\AddressList;
use Laminas\Mail\Header\AbstractAddressList;
use Laminas\Mail\Header\HeaderInterface;
use Laminas\Mail\Header\To;
use Mime_Helper;

/**
 * Helper to parse any address list type header (to, from, cc, bcc, reply-to) into Header object
 */
final class AddressHeader
{
    /** @var To */
    private $header;

    public function __construct(AbstractAddressList $addresses)
    {
        $this->header = $addresses;
    }

    /**
     * @param string $addresses
     * @throws \Laminas\Mail\Header\Exception\InvalidArgumentException
     * @return AddressHeader
     */
    public static function fromString($addresses): self
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
    public function getEmails(): array
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
    public function getNames(): array
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
    public function getAddressList(): AddressList
    {
        return $this->header->getAddressList();
    }

    /**
     * @param bool $format Whether to Mime-Encode header or not
     * @return string
     */
    public function toString($format = HeaderInterface::FORMAT_ENCODED): string
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
    public function getAddress(): Address
    {
        $addressList = $this->getAddressList();
        $count = $addressList->count();
        if ($count !== 1) {
            throw new InvalidArgumentException("Expected 1 address, got $count");
        }

        return $addressList->current();
    }
}
