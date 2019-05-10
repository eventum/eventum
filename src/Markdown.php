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

use Eventum\CommonMark\MentionExtension;
use Eventum\EventDispatcher\EventManager;
use HTMLPurifier;
use HTMLPurifier_HTML5Config;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;
use League\CommonMark\Environment;
use League\CommonMark\Ext\Autolink\AutolinkExtension;
use League\CommonMark\Ext\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\Extension\CommonMarkCoreExtension;
use Lossendae\CommonMark\TaskLists\TaskListsCheckbox;
use Lossendae\CommonMark\TaskLists\TaskListsCheckboxRenderer;
use Lossendae\CommonMark\TaskLists\TaskListsParser;
use Misc;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webuni\CommonMark\TableExtension\TableExtension;

class Markdown
{
    private const PURIFIER_CACHE_DIR = APP_CACHE_PATH . '/purifier';
    /**
     * Use moderately sane value
     *
     * @see https://commonmark.thephpleague.com/security/#nesting-level
     */
    private const MAX_NESTING_LEVEL = 500;

    /** @var HTMLPurifier */
    private $purifier;
    /** @var ConverterInterface[] */
    private $converter = [];

    public function __construct()
    {
        $this->purifier = $this->createPurifier();
    }

    public function render(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->getConverter(false)->convertToHtml($text);
        $html = $this->getPurifier()->purify($html);

        return $html;
    }

    public function renderInline(string $text): string
    {
        if (!$text) {
            return $text;
        }

        $html = $this->getConverter(true)->convertToHtml($text);
        $html = $this->getPurifier()->purify($html);

        return $html;
    }

    private function getConverter(bool $inline): ConverterInterface
    {
        return $this->converter[(int)$inline] ?? $this->converter[(int)$inline] = $this->createConverter($inline);
    }

    private function getPurifier(): HTMLPurifier
    {
        return $this->purifier ?? $this->purifier = $this->createPurifier();
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

    private function createPurifier(): HTMLPurifier
    {
        $config = HTMLPurifier_HTML5Config::createDefault();

        $config->set('AutoFormat.AutoParagraph', true);
        // remove empty tag pairs
        $config->set('AutoFormat.RemoveEmpty', true);
        // remove empty, even if it contains an &nbsp;
        $config->set('AutoFormat.RemoveEmpty.RemoveNbsp', true);
        // preserve html comments
        $config->set('HTML.AllowedCommentsRegexp', '/.+/');

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

    private function applyExtensions(Environment $environment): void
    {
        $environment->addExtension(new AutolinkExtension());
        $environment->addExtension(new TableExtension());
        $environment->addExtension(new MentionExtension());

        $environment->addInlineRenderer(TaskListsCheckbox::class, new TaskListsCheckboxRenderer());
        $environment->addInlineParser(new TaskListsParser());

        // allow extensions to apply behaviour
        $event = new GenericEvent($environment);
        EventManager::dispatch(Event\SystemEvents::MARKDOWN_ENVIRONMENT_CONFIGURE, $event);
    }
}
