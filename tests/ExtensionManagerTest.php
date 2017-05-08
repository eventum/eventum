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

use Eventum\Extension\AbstractExtension;
use Eventum\Extension\ExtensionInterface;
use Eventum\Extension\ExtensionManager;

class TestExtension1 extends AbstractExtension implements ExtensionInterface
{
    public function getAvailableWorkflows()
    {
        return [
            __CLASS__,
        ];
    }
}

class TestExtension2 extends AbstractExtension implements ExtensionInterface
{
    public function getAvailableWorkflows()
    {
        return [
            'Example_Workflow_Backend',
        ];
    }
}

class ExtensionManagerTest extends TestCase
{
    public function testWorkflowList()
    {
        $config = [
            __NAMESPACE__ . '\\TestExtension1' => __FILE__,
            __NAMESPACE__ . '\\TestExtension2' => __FILE__,
        ];

        $manager = $this->getExtensionManager($config);
        $classes = $manager->getWorkflowClasses();
        $this->assertCount(2, $classes);
    }

    /**
     * Create ExtensionManager with given config
     *
     * @return ExtensionManager
     */
    private function getExtensionManager($config)
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
}
