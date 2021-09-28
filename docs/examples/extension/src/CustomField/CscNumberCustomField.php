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

use Eventum\CustomField\Fields\FormatValueInterface;

class CscNumberCustomField implements FormatValueInterface
{
    public function formatValue(?string $value, int $fld_id, int $iss_id): ?string
    {
        if ($value) {
            $numbers = explode(',', $value);
            $links = [];
            foreach ($numbers as $number) {
                $links[] = "<a href=\"https://example.com/view.php?id=$number\" target=\"csc_$number\" class=\"link\">$number</a>";
            }

            return implode(', ', $links);
        }

        return $value;
    }
}
