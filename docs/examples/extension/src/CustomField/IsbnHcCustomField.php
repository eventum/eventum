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

use Custom_Field;
use Dynamic_Custom_Field_Backend;

class IsbnHcCustomField extends Dynamic_Custom_Field_Backend
{
    public function getStructuredData(): array
    {
        $fld_id = $this->getControllingCustomFieldId();
        // should pull from a dynamic data source but will hard code for now
        $data = [
            [
                'keys' => [Custom_Field::getOptionKey($fld_id, 'present')],
                'options' => [],
            ],
        ];

        return $data;
    }

    public function getControllingCustomFieldId(): int
    {
        return Custom_Field::getIdByTitle($this->getControllingCustomFieldName()) ?: 0;
    }

    public function getControllingCustomFieldName(): string
    {
        return 'isbn';
    }

    public function hideWhenNoOptions(): bool
    {
        return true;
    }
}
