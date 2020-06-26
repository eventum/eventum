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

namespace Eventum\Markdown;

use Eventum\Event;
use Eventum\EventDispatcher\EventManager;
use Eventum\Markdown\CommonMark\UserMentionGenerator;
use HTMLPurifier;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverterInterface;
use Setup;
use Symfony\Component\EventDispatcher\GenericEvent;

final class MarkdownRenderer implements MarkdownRendererInterface
{
    /**
     * Use moderately sane value
     *
     * @see https://commonmark.thephpleague.com/security/#nesting-level
     */
    private const MAX_NESTING_LEVEL = 500;

    /** @var HTMLPurifier */
    private $purifier;
    /** @var MarkdownConverterInterface[] */
    private $converter = [];

    public function __construct(HTMLPurifier $purifier)
    {
        $this->purifier = $purifier;
    }

    public function render(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->getConverter(false)->convertToHtml($text);
        $html = $this->purifier->purify($html);

        return $html;
    }

    public function renderInline(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->getConverter(true)->convertToHtml($text);
        $html = $this->purifier->purify($html);

        return $html;
    }

    private function getConverter(bool $inline): MarkdownConverterInterface
    {
        return $this->converter[(int)$inline] ?? $this->converter[(int)$inline] = $this->createConverter($inline);
    }

    private function createConverter(bool $inline): MarkdownConverterInterface
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
            'heading_permalink' => [
                'inner_contents' => '¶',
                'insert' => 'after',
            ],

            // https://commonmark.thephpleague.com/1.5/extensions/mentions/
            'mentions' => [
                'eventum_handle' => [
                    'symbol' => '@',
                    'regex' => '/^[A-Za-z0-9_]{1,255}(?!\w)/',
                    'generator' => new UserMentionGenerator(Setup::getBaseUrl()),
                ],
            ],
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
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new TaskListExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new HeadingPermalinkExtension());
        $environment->addExtension(new AttributesExtension());
        $environment->addExtension(new FootnoteExtension());
        $environment->addExtension(new MentionExtension());

        // allow extensions to apply behaviour
        $event = new GenericEvent($environment);
        EventManager::dispatch(Event\SystemEvents::MARKDOWN_ENVIRONMENT_CONFIGURE, $event);
    }
}
