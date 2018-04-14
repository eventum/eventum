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

use Eventum\Db\Doctrine;
use Eventum\Extension\ExtensionManager;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Create ExtensionManager with given config
     *
     * @return ExtensionManager
     */
    protected function getExtensionManager($config)
    {
        /** @var ExtensionManager $stub */
        $stub = $this->getMockBuilder(ExtensionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionFiles'])
            ->getMock();

        $stub->method('getExtensionFiles')
            ->willReturn($config);

        // as ->getMock() calls original constructor before method mocks is setup
        // we disabled original constructor and call it out now.
        $stub->__construct();

        return $stub;
    }

    protected function getDataFile($fileName)
    {
        $dataFile = dirname(__DIR__) . '/data/' . $fileName;
        $this->assertFileExists($dataFile);

        return $dataFile;
    }

    /**
     * Read file from tests/data directory.
     *
     * @param string $filename
     * @return string
     */
    protected function readDataFile($filename)
    {
        return $this->readFile($this->getDataFile($filename));
    }

    /**
     * @param string $filename
     * @return string
     */
    protected function readFile($filename)
    {
        $this->assertFileExists($filename);
        $content = file_get_contents($filename);
        $this->assertNotEmpty($content);

        return $content;
    }

    protected function getEntityManager()
    {
        return Doctrine::getEntityManager();
    }
}
