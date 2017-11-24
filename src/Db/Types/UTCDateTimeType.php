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

namespace Eventum\Db\Types;

use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateTimeType;

/**
 * Eventum stores values as UTC in database,
 * regardless what is timezone in MySQL Server.
 *
 * @see http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/cookbook/working-with-datetime.html
 * @see https://github.com/braincrafted/doctrine-bundle/blob/master/DBAL/Type/UTCDateTimeType.php
 */
class UTCDateTimeType extends DateTimeType
{
    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof DateTime) {
            return null;
        }

        $value->setTimezone(self::getUTCTimeZone());

        return $value->format($platform->getDateTimeFormatString());
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        $val = DateTime::createFromFormat(
            $platform->getDateTimeFormatString(),
            $value,
            self::getUTCTimeZone()
        );

        if (!$val) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }

    private static function getUTCTimeZone()
    {
        /** @var DateTimeZone */
        static $timeZone;

        return $timeZone ?: ($timeZone = new DateTimeZone('UTC'));
    }
}
