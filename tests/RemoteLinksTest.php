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

namespace Eventum\Test;

use Eventum\Db\Doctrine;
use Eventum\Model\Repository\RemoteLinksRepository;

/**
 * @group db
 */
class RemoteLinksTest extends TestCase
{
    /** @var \Doctrine\ORM\EntityRepository|RemoteLinksRepository */
    private $repo;

    public function setUp()
    {
        $this->repo = Doctrine::getRemoteLinksRepository();
    }

    public function testRemoteLink()
    {
        $url = 'http://example.org';
        $title = 'example';

        $link = $this->repo->addRemoteLink($url, $title);

        $this->assertEquals($url, $link->getUrl());
        $this->assertEquals($title, $link->getTitle());
    }
}
