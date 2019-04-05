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
    public const TEXTAREA = 2;
    public const COMBO_BOX = 3;
    public const MULTIPLE_COMBO_BOX = 4;
    public const DATE = 5;
    public const INTEGER = 6;
    public const CHECKBOX = 7;

    public function run(): void
    {
        $fields = [
            self::TEXT_INPUT => [
                CustomField::TYPE_TEXT,
                'Text Input',
            ],
            self::TEXTAREA => [
                CustomField::TYPE_TEXTAREA,
                'Textarea',
            ],
            self::COMBO_BOX => [
                CustomField::TYPE_COMBO,
                'Combo box',
            ],
            self::MULTIPLE_COMBO_BOX => [
                CustomField::TYPE_MULTIPLE_COMBO,
                'Multiple combo box',
            ],
            self::DATE => [
                CustomField::TYPE_MULTIPLE_COMBO,
                'Date field',
            ],
            self::INTEGER => [
                CustomField::TYPE_INTEGER,
                'Integer field',
            ],
            self::CHECKBOX => [
                CustomField::TYPE_INTEGER,
                'Integer field',
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
