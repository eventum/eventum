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

/*
 * PHPUnit_Framework_TestCase is dropped in phpunit 6.0.0
 * https://github.com/sebastianbergmann/phpunit/wiki/Release-Announcement-for-PHPUnit-6.0.0
 *
 * Load PHPUnit_Framework_TestCase wrapper if using older PHPUnit.
 */

if (!class_exists('\PHPUnit\Framework\TestCase')) {
    require_once __DIR__ . '/phpunit-compat.php';
}

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $filename
     * @return string
     */
    protected function readfile($filename)
    {
        $content = file_get_contents($filename);
        $this->assertNotEmpty($content);

        return $content;
    }
}
