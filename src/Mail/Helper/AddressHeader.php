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

use Mime_Helper;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\To;

/**
 * Helper to parse any address list type header (to, from, cc, bcc, reply-to) into Header object
 */
class AddressHeader
{
    /** @var To */
    private $header;

    public function __construct(AbstractAddressList $addresses)
    {
        $this->header = $addresses;
    }

    public static function fromString($addresses)
    {
        // avoid exceptions if NULL or empty string passed as input
        if (!$addresses) {
            return new static(new To());
        }

        // fromString expects 7bit input
        $addresses = Mime_Helper::encode($addresses);

        // use To header to utilize AddressList functionality
        return new static(To::fromString('To:' . $addresses));
    }

    public function getEmails()
    {
        $res = [];
        foreach ($this->header->getAddressList() as $address) {
            $res[] = $address->getEmail();
        }

        return $res;
    }
}
