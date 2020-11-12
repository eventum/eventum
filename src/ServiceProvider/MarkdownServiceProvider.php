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

namespace Eventum\ServiceProvider;

use Eventum\Config\Paths;
use Eventum\Event;
use Eventum\EventDispatcher\EventManager;
use Eventum\Markdown;
use Eventum\Markdown\CommonMark\UserMentionGenerator;
use HTMLPurifier;
use HTMLPurifier_HTML5Config;
use League\CommonMark\Block\Element\FencedCode;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use Misc;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Setup;
use Symfony\Component\EventDispatcher\GenericEvent;

class MarkdownServiceProvider implements ServiceProviderInterface
{
    private const PURIFIER_CACHE_DIR = Paths::APP_CACHE_PATH . '/purifier';

    /**
     * Use moderately sane value
     *
     * @see https://commonmark.thephpleague.com/security/#nesting-level
     */
    private const MAX_NESTING_LEVEL = 500;

    public function register(Container $app): void
    {
        $app[Markdown\MarkdownRendererInterface::RENDER_BLOCK] = function ($app) {
            return new Markdown\MarkdownRenderer($this->createConverter(false), $app[HTMLPurifier::class]);
        };
        $app[Markdown\MarkdownRendererInterface::RENDER_INLINE] = function ($app) {
            return new Markdown\MarkdownRenderer($this->createConverter(true), $app[HTMLPurifier::class]);
        };

        $app[HTMLPurifier::class] = static function () {
            return self::createPurifier();
        };
    }

    private function createConverter(bool $inline): CommonMarkConverter
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

            // Add mermaid integration
            $environment->addEventListener(DocumentParsedEvent::class, static function (DocumentParsedEvent $e) {
                $walker = $e->getDocument()->walker();

                while ($event = $walker->next()) {
                    $node = $event->getNode();
                    if (!$event->isEntering() || !($node instanceof FencedCode) || $node->getInfo() !== 'mermaid') {
                        continue;
                    }

                    $node->data['attributes']['class'] = 'mermaid';
                    $node->data['attributes']['title'] = 'mermaid diagram';
                }
            });
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

    private static function createPurifier(): HTMLPurifier
    {
        $config = HTMLPurifier_HTML5Config::createDefault();

        $config->set('AutoFormat.AutoParagraph', true);
        // remove empty tag pairs
        $config->set('AutoFormat.RemoveEmpty', true);
        // remove empty, even if it contains an &nbsp;
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        // preserve html comments
        $config->set('HTML.AllowedCommentsRegexp', '/.+/');
        // Add target="_blank" to all outgoing links
        $config->set('HTML.TargetBlank', true);

        // disable tidy processing, even if extension present
        $config->set('Output.TidyFormat', false);

        // disable useless normalizer we do not need
        $config->set('Core.NormalizeNewlines', false);

        // allow tasklist <input> checkboxes
        // https://github.com/ezyang/htmlpurifier/issues/213#issuecomment-487206892
        $config->set('HTML.Trusted', true);
        $config->set('HTML.ForbiddenElements', ['script', 'noscript']);

        // Absolute path with no trailing slash to store serialized definitions in.
        $config->set('Cache.SerializerPath', Misc::ensureDir(self::PURIFIER_CACHE_DIR));

        return new HTMLPurifier($config);
    }
}
