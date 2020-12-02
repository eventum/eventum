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

namespace Eventum\Test\Date;

use Date_Helper;
use Eventum\Db\Doctrine;
use Eventum\ServiceContainer;
use Eventum\Test\TestCase;
use Setup;

/**
 * DateHelper tests involving user (using database)
 *
 * @group date
 * @group db
 */
class DateHelperUserTest extends TestCase
{
    /**
     * timezone used for preferred user timezone tests
     */
    private const USER_TIMEZONE = 'Europe/Tallinn';
    private const ADMIN_TIMEZONE = 'UTC';

    /** @var int */
    private $admin_user_id;
    /** @var int */
    private $system_user_id;

    public function setUp(): void
    {
        $config = ServiceContainer::getConfig();
        self::setTimezone($this->admin_user_id = $config['admin_user'], self::USER_TIMEZONE);
        self::setTimezone($this->system_user_id = Setup::getSystemUserId(), self::ADMIN_TIMEZONE);
    }

    private static function setTimezone(int $usr_id, string $timezone): void
    {
        $repo = Doctrine::getUserPreferenceRepository();
        $prefs = $repo->findOrCreate($usr_id);
        $prefs->setTimezone($timezone);
        $em = ServiceContainer::getEntityManager();
        $em->persist($prefs);
        $em->flush();
    }

    /**
     * @covers Date_Helper::getTimezoneShortNameByUser
     */
    public function testGetTimezoneShortNameByUser(): void
    {
        $res = Date_Helper::getTimezoneShortNameByUser($this->system_user_id);
        $this->assertEquals('UTC', $res);

        $res = Date_Helper::getTimezoneShortNameByUser($this->admin_user_id);
        $this->assertRegExp('/EET|EEST/', $res);
    }

    /**
     * @covers Date_Helper::getPreferredTimezone
     */
    public function testGetPreferredTimezone(): void
    {
        $res = Date_Helper::getPreferredTimezone();
        $this->assertEquals('UTC', $res);

        $res = Date_Helper::getPreferredTimezone($this->system_user_id);
        $this->assertEquals('UTC', $res);

        $res = Date_Helper::getPreferredTimezone($this->admin_user_id);
        $this->assertEquals(self::USER_TIMEZONE, $res);
    }
}
