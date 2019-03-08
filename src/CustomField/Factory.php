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

namespace Eventum\CustomField;

use Custom_Field;
use Eventum\CustomField\Fields\CustomFieldInterface;

abstract class Factory
{
    public static function create(string $className): Proxy
    {
        /** @var CustomFieldInterface $field */
        $field = Custom_Field::getExtensionLoader()->createInstance($className);

        // proxy it via helper, to support custom fields without interfaces
        return new Proxy($field);
    }
}
