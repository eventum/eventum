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

use Eventum\Model\Entity\CustomField;
use Phinx\Seed\AbstractSeed;

class CustomFieldSeeder extends AbstractSeed
{
    public const TEXT_INPUT = 1;

    public function run(): void
    {
        $fields = [
            self::TEXT_INPUT => [
                CustomField::TYPE_TEXT,
                'Text Input',
            ],
        ];

        $table = $this->table('custom_field');
        foreach ($fields as $fld_id => [$type, $title]) {
            $row = [
                'fld_id' => $fld_id,
                'fld_title' => $title,
                'fld_description' => $title,
                'fld_type' => $type,
            ];
            $table->insert($row);
        }

        $table->saveData();
    }
}
