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

/**
 * DateHelper tests involving user (using database)
 */
class DateHelperUserTest extends TestCase
{
    /**
     * timezone used for preferred user timezone tests
     */
    const USER_TIMEZONE = 'Europe/Tallinn';
    const ADMIN_TIMEZONE = 'UTC';

    public static function setUpBeforeClass()
    {
        self::assertDatabase();
        self::setTimezone(APP_ADMIN_USER_ID, self::USER_TIMEZONE);
        self::setTimezone(APP_SYSTEM_USER_ID, self::ADMIN_TIMEZONE);
    }

    private function setTimezone($usr_id, $timezone)
    {
        $prefs = Prefs::get($usr_id);
        $prefs['timezone'] = $timezone;
        Prefs::set($usr_id, $prefs);
        // this will force db refetch
        Prefs::get($usr_id, true);
    }

    /**
     * @covers  Date_Helper::getTimezoneShortNameByUser
     */
    public function testGetTimezoneShortNameByUser()
    {
        $res = Date_Helper::getTimezoneShortNameByUser(APP_SYSTEM_USER_ID);
        $this->assertEquals('UTC', $res);

        $res = Date_Helper::getTimezoneShortNameByUser(APP_ADMIN_USER_ID);
        $this->assertRegExp('/EET|EEST/', $res);
    }

    /**
     * @covers  Date_Helper::getPreferredTimezone
     */
    public function testGetPreferredTimezone()
    {
        $res = Date_Helper::getPreferredTimezone();
        $this->assertEquals('UTC', $res);

        $res = Date_Helper::getPreferredTimezone(APP_SYSTEM_USER_ID);
        $this->assertEquals('UTC', $res);

        $res = Date_Helper::getPreferredTimezone(APP_ADMIN_USER_ID);
        $this->assertEquals(self::USER_TIMEZONE, $res);
    }
}
