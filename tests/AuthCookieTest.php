<?php

class AuthCookieTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (getenv('TRAVIS')) {
            $this->markTestSkipped("Missing Travis setup");
        }
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
