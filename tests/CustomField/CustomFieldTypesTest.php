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

use Eventum\Model\Entity\CustomField;

/**
 * @group db
 */
class CustomFieldTypesTest extends TestCase
{
    public function testTextField(): void
    {
        $cf = $this->repo->findOrCreate(self::ID_TEXT);
        $cf->setTitle('Text Custom Field');
        $cf->setType(CustomField::TYPE_TEXT);
        $cf->setRank($this->repo->getNextRank());

        $this->setEntityId($cf, self::ID_TEXT);
        $this->persistAndFlush($cf);

        $this->repo->setProjectAssociation($cf, [self::PROJECT_ID]);
    }
}
