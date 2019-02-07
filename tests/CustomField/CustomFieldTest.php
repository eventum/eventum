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

use Eventum\Db\Doctrine;
use Eventum\Test\TestCase;

class CustomFieldTest extends TestCase
{
    public function test1(): void
    {
        $repo = Doctrine::getCustomFieldRepository();
        $cf = $repo->findById(1);
    }
}
