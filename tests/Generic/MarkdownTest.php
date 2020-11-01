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

namespace Eventum\Test\Generic;

use Eventum\Markdown\MarkdownRendererInterface;
use Eventum\ServiceProvider\MarkdownServiceProvider;
use Eventum\Test\TestCase;
use Generator;
use Pimple\Container;

/**
 * @group db
 */
class MarkdownTest extends TestCase
{
    /** @var MarkdownRendererInterface */
    private $renderer;

    public function setUp(): void
    {
        $this->renderer = $this->getRenderer();
    }

    /**
     * @dataProvider dataProvider
     * @group flaky
     */
    public function testMarkdown(string $input, string $expected): void
    {
        $rendered = $this->renderer->render($input);
        $this->assertEquals($expected, $rendered);
    }

    public function dataProvider(): Generator
    {
        $testNames = [
            'autolink',
            'h5-details',
            'headers',
            'inline',
            'linkrefs',
            'script',
            'table',
            'tasklist',
            'userhandle',
        ];

        foreach ($testNames as $testName) {
            yield $testName => [
                $this->readDataFile("markdown/$testName.md"),
                $this->readDataFile("markdown/$testName.html"),
            ];
        }
    }

    private function getRenderer(): MarkdownRendererInterface
    {
        $container = new Container();
        $container->register(new MarkdownServiceProvider());

        return $container[MarkdownRendererInterface::RENDER_BLOCK];
    }
}
