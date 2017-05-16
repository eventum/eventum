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

namespace Eventum\Test\Extension;

use Eventum\Extension\AbstractExtension;
use Eventum\Extension\ExtensionInterface;
use Eventum\Extension\ExtensionManager;
use Eventum\Test\TestCase;

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
    public function registerAutoloader($loader)
    {
        $baseDir = __DIR__ . '/../../docs/examples/workflow';
        $classMap = ['Example_Workflow_Backend' => $baseDir . '/class.example.php'];
        $loader->addClassMap($classMap);
    }

    public function getAvailableWorkflows()
    {
        return [
            'Example_Workflow_Backend',
        ];
    }
}

/**
 * Class ExtensionManagerTest
 *
 * @group extension
 */
class ExtensionManagerTest extends TestCase
{
    public function testWorkflowList()
    {
        $config = [
            TestExtension1::class => __FILE__,
            TestExtension2::class => __FILE__,
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
