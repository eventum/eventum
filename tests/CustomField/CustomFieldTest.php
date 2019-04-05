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

namespace Eventum\Test\CustomField;

use CustomFieldSeeder;
use Eventum\CustomField\Converter;
use Eventum\EventDispatcher\EventManager;
use Eventum\Model\Entity\CustomField;
use ProjectSeeder;
use User;

/**
 * @group db
 */
class CustomFieldTest extends TestCase
{
    public function testGetCustomField(): void
    {
        $cf = $this->repo->findById(CustomFieldSeeder::TEXT_INPUT);
        $this->assertEquals(CustomField::TYPE_TEXT, $cf->getType());
    }

    /**
     * @see Custom_Field::getListByIssue
     */
    public function testGetListByIssue(): void
    {
        $prj_id = ProjectSeeder::DEFAULT_PROJECT_ID;
        $iss_id = 24;
        $min_role = User::ROLE_VIEWER;
        $forEdit = false;
        $formType = 'edit_form';
        $customFields = $this->repo->getListByIssue($prj_id, $iss_id, $min_role, $formType, $forEdit);

        dump(count($customFields));

        // trigger setup of extensions
        EventManager::getEventDispatcher();

        $converter = new Converter();
        $fields = $converter->convertIssueCustomFields($customFields, $iss_id, $formType);
        dump(count($fields));
    }

    /**
     * @see Custom_Field::getListByProject
     */
    public function testGetListByProject(): void
    {
        $prj_id = ProjectSeeder::DEFAULT_PROJECT_ID;
        $iss_id = 20;
        $min_role = User::ROLE_VIEWER;
        $forEdit = false;
        $formType = 'edit_form';
        $fieldType = 'multiple';
        $customFields = $this->repo->getListByProject($prj_id, $min_role, $formType, $fieldType, $forEdit);

        dump(count($customFields));

        $converter = new Converter();
        $fields = $converter->convertCustomFields($customFields, $iss_id, $formType);
        dump(count($fields));
    }

    public function testUpdateCustomFieldOptions(): void
    {
        $fld_id = 2;
        $new_options = [
            0 => '',
            1 => '',
            2 => 'option3',
        ];
        $options = [
            1 => 'option1',
            2 => 'option2',
        ];

        $this->repo->updateCustomFieldOptions($fld_id, $options, $new_options);
    }
}
