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

namespace Eventum\Test\Generic;

use Auth;
use AuthCookie;
use Eventum\Test\TestCase;
use ProjectSeeder;
use Setup;
use UserSeeder;

/**
 * @group db
 */
class AuthCookieTest extends TestCase
{
    public static function setupBeforeClass(): void
    {
        if (file_exists(Setup::getPrivateKeyPath())) {
            return;
        }
        Auth::generatePrivateKey();
    }

    public function testAuthCookie(): void
    {
        $usr_id = UserSeeder::ACTIVE_ACCOUNT;
        AuthCookie::setAuthCookie($usr_id);
        $this->assertEquals($usr_id, Auth::getUserID());
    }

    public function testProjectCookie(): void
    {
        $prj_id = ProjectSeeder::DEFAULT_PROJECT_ID;
        AuthCookie::setProjectCookie($prj_id);
        $this->assertEquals($prj_id, Auth::getCurrentProject());
    }
}
