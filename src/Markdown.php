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

namespace Eventum;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment;
use League\CommonMark\Ext\Autolink\AutolinkExtension;
use League\CommonMark\Ext\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use Lossendae\CommonMark\TaskLists\TaskListsCheckbox;
use Lossendae\CommonMark\TaskLists\TaskListsCheckboxRenderer;
use Lossendae\CommonMark\TaskLists\TaskListsParser;
use Webuni\CommonMark\TableExtension\TableExtension;

class Markdown
{
    /**
     * Use moderately sane value
     *
     * @see https://commonmark.thephpleague.com/security/#nesting-level
     */
    private const MAX_NESTING_LEVEL = 500;

    /** @var ConverterInterface[] */
    private $converter = [];

    public function render(string $text): string
    {
        return $this->getConverter(false)->convertToHtml($text);
    }

    public function renderInline(string $text): string
    {
        return $this->getConverter(true)->convertToHtml($text);
    }

    private function getConverter(bool $inline): ConverterInterface
    {
        return $this->converter[(int)$inline] ?? $this->createConverter($inline);
    }

    private function createConverter(bool $inline): ConverterInterface
    {
        $environment = new Environment();
        if ($inline) {
            $environment->addExtension(new InlinesOnlyExtension());
        } else {
            $environment->addExtension(new CommonMarkCoreExtension());
        }

        $this->applyExtensions($environment);

        $config = [
            'renderer' => [
                'block_separator' => "\n",
                'inner_separator' => "\n",
                'soft_break' => "<br />\n",
            ],
            'html_input' => Environment::HTML_INPUT_ALLOW,
            'allow_unsafe_links' => false,
            'max_nesting_level' => self::MAX_NESTING_LEVEL,
        ];

        return new CommonMarkConverter($config, $environment);
    }

    private function applyExtensions(Environment $environment): void
    {
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new AutolinkExtension());

        $environment->addInlineRenderer(TaskListsCheckbox::class, new TaskListsCheckboxRenderer());
        $environment->addInlineParser(new TaskListsParser());
    }
}
