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

class TestCase extends PHPUnit_Framework_TestCase
{
    public static function skipTravis($message = 'Disabled in Travis')
    {
        if (getenv('TRAVIS')) {
            self::markTestSkipped($message);
        }
    }

    public static function skipJenkins($message = 'Disabled Jenkins')
    {
        if (getenv('JENKINS_HOME')) {
            self::markTestSkipped($message);
        }
    }

    public static function skipCi($message = 'Disabled in Travis/Jenkins')
    {
        if (getenv('TRAVIS') || getenv('JENKINS_HOME')) {
            self::markTestSkipped($message);
        }
    }

    /**
     * skip test if database is not available
     */
    public static function assertDatabase()
    {
        self::skipTravis();
    }
}
