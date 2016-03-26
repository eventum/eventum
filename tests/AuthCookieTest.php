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

class AuthCookieTest extends TestCase
{
    public static function setupBeforeClass()
    {
        if (file_exists(APP_CONFIG_PATH . '/private_key.php')) {
            return;
        }
        Auth::generatePrivateKey();
    }

    public function setUp()
    {
        $this->skipCi('Missing Travis/Jenkins setup');
    }

    public function testAuthCookie()
    {
        $usr_id = APP_ADMIN_USER_ID;
        AuthCookie::setAuthCookie($usr_id);
        $this->assertNotEmpty(Auth::getUserID());
    }

    public function testProjectCookie()
    {
        $prj_id = 1;
        AuthCookie::setProjectCookie($prj_id);
        $this->assertNotNull(Auth::getCurrentProject());
    }
}
