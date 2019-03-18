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

use Eventum\EventDispatcher\EventManager;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment;
use League\CommonMark\Ext\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use League\CommonMark\Extras\CommonMarkExtrasExtension;
use Lossendae\CommonMark\TaskLists\TaskListsCheckbox;
use Lossendae\CommonMark\TaskLists\TaskListsCheckboxRenderer;
use Lossendae\CommonMark\TaskLists\TaskListsParser;
use Symfony\Component\EventDispatcher\GenericEvent;
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
        if (!$text) {
            return $text;
        }

        return $this->getConverter(false)->convertToHtml($text);
    }

    public function renderInline(string $text): string
    {
        if (!$text) {
            return $text;
        }

        return $this->getConverter(true)->convertToHtml($text);
    }

    private function getConverter(bool $inline): ConverterInterface
    {
        return $this->converter[(int)$inline] ?? $this->createConverter($inline);
    }

    private function createConverter(bool $inline): ConverterInterface
    {
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

        $environment = new Environment($config);
        if ($inline) {
            $environment->addExtension(new InlinesOnlyExtension());
        } else {
            $environment->addExtension(new CommonMarkCoreExtension());
        }

        $this->applyExtensions($environment);

        return new CommonMarkConverter([], $environment);
    }

    private function applyExtensions(Environment $environment): void
    {
        $environment->addExtension(new CommonMarkExtrasExtension());
        $environment->addExtension(new TableExtension());

        $environment->addInlineRenderer(TaskListsCheckbox::class, new TaskListsCheckboxRenderer());
        $environment->addInlineParser(new TaskListsParser());

        // allow extensions to apply behaviour
        $event = new GenericEvent($environment);
        EventManager::dispatch(Event\SystemEvents::MARKDOWN_ENVIRONMENT_CONFIGURE, $event);
    }
}
