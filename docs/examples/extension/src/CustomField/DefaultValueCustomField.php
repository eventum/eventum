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

namespace Example\CustomField;

use Eventum\CustomField\Fields\DefaultValueInterface;

/**
 * Custom field backend showing example default value
 */
class DefaultValueCustomField implements DefaultValueInterface
{
    public function getDefaultValue(int $fld_id): string
    {
        // your logic here
        return '123';
    }
}
