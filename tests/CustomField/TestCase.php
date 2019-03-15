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
use Eventum\Model\Repository\CustomFieldRepository;
use Eventum\Test\DoctrineTraits;
use Eventum\Test\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DoctrineTraits;

    protected const ID_TEXT = 0x6001;

    /** @var CustomFieldRepository */
    protected $repo;

    public function setUp(): void
    {
        $this->repo = $this->repo ?: Doctrine::getCustomFieldRepository();
    }
}
