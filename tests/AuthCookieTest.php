<?php

class AuthCookieTest extends PHPUnit_Framework_TestCase
{
    public function testAuthCookie()
    {
        $usr_id = APP_ADMIN_USER_ID;
        $ac = new AuthCookie($usr_id);
        $this->assertNotNull($ac->generateCookie());
    }

    public function testProjectCookie()
    {
        $prj_id = 1;
        $this->assertNotNull(AuthCookie::generateProjectCookie($prj_id));
    }
}
