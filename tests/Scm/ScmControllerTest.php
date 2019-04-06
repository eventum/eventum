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

namespace Eventum\Test\Scm;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ScmControllerTest extends WebTestCase
{
    public function testShowPost(): void
    {
        $client = static::createClient();
        $client->request('POST', '/scm_ping');
        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"code":0,"message":""}', $response->getContent());
    }
}
