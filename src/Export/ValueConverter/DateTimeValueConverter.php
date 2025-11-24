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

namespace Eventum\Export\ValueConverter;

use DateTime;
use Port\ValueConverter\DateTimeToStringValueConverter;

class DateTimeValueConverter
{
    // "2021-05-19T14:16:35.842+03:00"
    private const DATE_FORMAT = DATE_RFC3339;
    /** @var DateTimeToStringValueConverter */
    private $converter;

    public function __construct()
    {
        $this->converter = new DateTimeToStringValueConverter(self::DATE_FORMAT);
    }

    public function convert(DateTime $date)
    {
        return $this->converter->__invoke($date);
    }
}
