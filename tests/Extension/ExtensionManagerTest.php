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

use Eventum\Extension\Provider;
use Eventum\Test\TestCase;

/**
 * Class ExtensionManagerTest
 *
 * @group extension
 */
class ExtensionManagerTest extends TestCase
{
    public function testWorkflowList(): void
    {
        $config = [
            TestExtension1::class => __FILE__,
            TestExtension2::class => __FILE__,
        ];

        $manager = $this->getExtensionManager($config);
        $classes = $manager->getWorkflowClasses();
        $this->assertCount(2, $classes);
    }
}

class TestExtension1 implements
    Provider\WorkflowProvider
{
    public function getAvailableWorkflows(): array
    {
        return [
            __CLASS__,
        ];
    }
}

class TestExtension2 implements
    Provider\AutoloadProvider,
    Provider\WorkflowProvider
{
    public function registerAutoloader($loader): void
    {
        $baseDir = __DIR__ . '/../../docs/examples/workflow';
        $classMap = ['Example_Workflow_Backend' => $baseDir . '/class.example.php'];
        $loader->addClassMap($classMap);
    }

    public function getAvailableWorkflows(): array
    {
        return [
            'Example_Workflow_Backend',
        ];
    }
}
